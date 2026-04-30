<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncMfHistoryCommand extends Command
{
    protected $signature = 'sync:mf-history
                            {months=12 : Months of history to fetch per scheme}
                            {--scheme= : Single scheme_code to sync (debug/backfill)}
                            {--force : Re-insert even if records already exist}';

    protected $description = 'Fetch historical NAVs + compute period returns from mfapi.in';

    private const MFAPI_BASE    = 'https://api.mfapi.in/mf';
    private const NAV_CHUNK     = 500;
    private const SLEEP_MS      = 300000; // 0.3 s between each scheme (avoid rate limiting)
    private const HTTP_RETRIES  = 5;
    private const HTTP_BACKOFF  = [1, 2, 4, 8, 16]; // seconds per retry attempt

    // [carbon-method, value]
    private const PERIODS = [
        '1d' => ['subDays',   1],
        '3d' => ['subDays',   3],
        '7d' => ['subDays',   7],
        '1m' => ['subMonth',  1],
        '3m' => ['subMonths', 3],
        '6m' => ['subMonths', 6],
        '9m' => ['subMonths', 9],
        '1y' => ['subYear',   1],
        '3y' => ['subYears',  3],
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $months     = (int) $this->argument('months');
        $cutoff     = now()->subMonths($months)->startOfMonth()->format('Y-m-d');

        $singleCode = $this->option('scheme');

        $query = DB::table('mutual_funds')->where('is_active', 1);
        if ($singleCode) $query->where('scheme_code', $singleCode);
        $schemes = $query->get();

        if ($schemes->isEmpty()) {
            $this->warn('No active schemes found — auto-running sync:mf-daily --force to populate master...');
            Artisan::call('sync:mf-daily', ['--force' => true], $this->output);
            $schemes = $query->get();
        }

        if ($schemes->isEmpty()) {
            $this->error('Still no schemes after sync:mf-daily. Check AMFI connectivity.');
            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Syncing %d months of history + returns for %d schemes (cutoff >= %s)...',
            $months,
            $schemes->count(),
            $cutoff
        ));

        // Build per-scheme date coverage map: isin → [min_date, max_date] of existing rows
        // Used to skip individual dates already in DB (within the covered range)
        // and to skip the API call entirely for fully-covered schemes.
        $existingRange = [];
        if (!$this->option('force')) {
            DB::table('mutual_fund_prices')
                ->select('isin', DB::raw('MIN(nav_date) as min_d'), DB::raw('MAX(nav_date) as max_d'))
                ->where('nav_date', '>=', $cutoff)
                ->groupBy('isin')
                ->orderBy('isin')
                ->chunk(5000, function ($rows) use (&$existingRange) {
                    foreach ($rows as $r) {
                        $existingRange[$r->isin] = [$r->min_d, $r->max_d];
                    }
                });
        }

        // A scheme is "fully covered" if its DB range starts at/before cutoff
        // and extends to within 7 days of today (accounts for weekends/holidays).
        $recentThreshold = now()->subDays(7)->format('Y-m-d');

        $bar     = $this->output->createProgressBar($schemes->count());
        $bar->start();

        $flushed = 0;
        $skipped = 0;
        $failed  = 0;
        $failedSchemes = [];

        foreach ($schemes as $scheme) {
            // Fetch with retries
            $json        = null;
            $httpSuccess = false;
            for ($attempt = 0; $attempt < self::HTTP_RETRIES; $attempt++) {
                if ($attempt > 0) {
                    sleep(self::HTTP_BACKOFF[$attempt - 1]);
                }
                try {
                    $response = Http::timeout(30)->withoutVerifying()
                        ->get(self::MFAPI_BASE . '/' . $scheme->scheme_code);

                    if ($response->successful()) {
                        $httpSuccess = true;
                        $body = $response->json();
                        if (!empty($body['data'])) {
                            $json = $body;
                        }
                        break;
                    }
                    // Non-2xx: retry
                } catch (\Exception $e) {
                    Log::warning('sync:mf-history HTTP retry after exception', [
                        'scheme_code' => $scheme->scheme_code,
                        'attempt' => $attempt + 1,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if (!$httpSuccess) {
                $failed++;
                $failedSchemes[] = $scheme->scheme_code;
                Log::error('sync:mf-history failed to fetch scheme data', [
                    'scheme_code' => $scheme->scheme_code,
                    'isin' => $scheme->isin,
                ]);
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            if (empty($json['data'])) {
                $skipped++;
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            // Fully covered: DB already has data from cutoff to ≥ recent threshold
            $range = $existingRange[$scheme->isin] ?? null;
            if (
                !$this->option('force') && $range
                && $range[0] <= $cutoff
                && $range[1] >= $recentThreshold
            ) {
                $skipped++;
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            // 1. Build full NAV map from API (strictly >= cutoff)
            $allNavs = [];
            $latestDate = null;
            foreach ($json['data'] as $entry) {
                $d = $this->parseMfApiDate($entry['date'] ?? '');
                if (!$d || $d < $cutoff) continue;

                if (!$latestDate || $d > $latestDate) $latestDate = $d;

                $v = (float)($entry['nav'] ?? 0);
                if ($v <= 0) continue;
                $allNavs[$d] = $v;
            }

            // Skip stale schemes where the latest data doesn't even reach our history window
            if (!$latestDate || $latestDate < $cutoff) {
                $skipped++;
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            if (empty($allNavs)) {
                $skipped++;
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            ksort($allNavs);
            $allDates      = array_keys($allNavs);
            $allNavVals    = array_values($allNavs);
            $allTimestamps = array_map('strtotime', $allDates);

            // Determine which dates need to be inserted/updated
            $newDates = [];
            foreach ($allDates as $idx => $d) {
                if (
                    !$this->option('force') && $range
                    && $d >= $range[0] && $d <= $range[1]
                ) {
                    continue; // already in DB
                }
                $newDates[$idx] = $d;
            }

            if (empty($newDates)) {
                $skipped++;
                $bar->advance();
                usleep(self::SLEEP_MS);
                continue;
            }

            // Compute rows using the FULL nav array so period returns resolve correctly
            $schemeRows = [];
            foreach ($newDates as $j => $date) {
                $currentNav = $allNavVals[$j];
                $row = [
                    'mf_id'    => $this->resolveFundKey($scheme),
                    'isin'     => $scheme->isin,
                    'nav_date' => $date,
                    'nav'      => $currentNav,
                ];

                foreach (self::PERIODS as $p => [$method, $val]) {
                    $targetTs = $this->targetTs($date, $method, $val);
                    $idx      = $this->closestIdx($allTimestamps, $j, $targetTs, 10);
                    if ($idx !== null && $allNavVals[$idx] > 0) {
                        $refNav          = $allNavVals[$idx];
                        $row["chg_{$p}"] = round((($currentNav - $refNav) / $refNav) * 100, 4);
                        $row["val_{$p}"] = $refNav;
                    } else {
                        $row["chg_{$p}"] = null;
                        $row["val_{$p}"] = null;
                    }
                }

                $schemeRows[] = $row;
            }

            $written = false;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    try {
                        DB::reconnect();
                    } catch (\Throwable $t) {
                    }
                    DB::beginTransaction();
                    $this->flushBuffer($schemeRows);
                    DB::commit();
                    $flushed += count($schemeRows);
                    $written = true;
                    break;
                } catch (\Exception $e) {
                    try {
                        DB::rollBack();
                    } catch (\Throwable $t) {
                    }
                    if ($attempt < 3) usleep(500000);
                }
            }
            if (!$written) {
                $failed++;
                $failedSchemes[] = $scheme->scheme_code;
                Log::error('sync:mf-history failed while writing scheme rows', [
                    'scheme_code' => $scheme->scheme_code,
                    'isin' => $scheme->isin,
                    'row_count' => count($schemeRows),
                ]);
            }

            $bar->advance();
            usleep(self::SLEEP_MS);
        }

        $bar->finish();
        $this->newLine();

        $this->info('────────────────────────────────────────────────────────────────');
        $this->info('  Sync Summary');
        $this->info('────────────────────────────────────────────────────────────────');
        $this->table(
            ['Category', 'Count'],
            [
                ['Total Schemes', $schemes->count()],
                ['Rows Upserted', $flushed],
                ['Schemes Skipped', $skipped],
                ['Schemes Failed', $failed],
            ]
        );
        $this->info('────────────────────────────────────────────────────────────────');

        if (!empty($failedSchemes)) {
            $this->warn('Failed scheme codes: ' . implode(', ', $failedSchemes));
        }

        Log::channel('single')->info('sync:mf-history complete', [
            'months' => $months,
            'upserted' => $flushed,
            'skipped' => $skipped,
            'failed' => $failed,
            'failed_codes' => $failedSchemes,
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function resolveFundKey(object $scheme): int
    {
        if (isset($scheme->id) && is_numeric($scheme->id)) {
            return (int) $scheme->id;
        }

        return (int) $scheme->scheme_code;
    }

    // -------------------------------------------------------------------------

    private function flushBuffer(array $rows): int
    {
        $updateCols = [
            'mf_id',
            'nav',
            'chg_1d',
            'val_1d',
            'chg_3d',
            'val_3d',
            'chg_7d',
            'val_7d',
            'chg_1m',
            'val_1m',
            'chg_3m',
            'val_3m',
            'chg_6m',
            'val_6m',
            'chg_9m',
            'val_9m',
            'chg_1y',
            'val_1y',
            'chg_3y',
            'val_3y',
        ];
        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            DB::table('mutual_fund_prices')->upsert($chunk, ['isin', 'nav_date'], $updateCols);
        }
        return count($rows);
    }

    private function targetTs(string $fromDate, string $method, int $val): int
    {
        $c = Carbon::createFromFormat('Y-m-d', $fromDate);
        match ($method) {
            'subDays'   => $c->subDays($val),
            'subMonth'  => $c->subMonth(),
            'subMonths' => $c->subMonths($val),
            'subYear'   => $c->subYear(),
            'subYears'  => $c->subYears($val),
        };
        return $c->timestamp;
    }

    /**
     * Binary search in sorted $timestamps[0..$maxIdx-1] for the entry
     * closest to $targetTs within $windowDays. Returns index or null.
     */
    private function closestIdx(array $timestamps, int $maxIdx, int $targetTs, int $windowDays): ?int
    {
        if ($maxIdx === 0) return null;

        $windowSec = $windowDays * 86400;
        $lo = 0;
        $hi = $maxIdx - 1;

        while ($lo < $hi) {
            $mid = ($lo + $hi) >> 1;
            if ($timestamps[$mid] < $targetTs) $lo = $mid + 1;
            else $hi = $mid;
        }

        $best     = null;
        $bestDiff = PHP_INT_MAX;

        foreach ([$lo - 1, $lo] as $idx) {
            if ($idx < 0 || $idx >= $maxIdx) continue;
            $diff = abs($timestamps[$idx] - $targetTs);
            if ($diff <= $windowSec && $diff < $bestDiff) {
                $bestDiff = $diff;
                $best     = $idx;
            }
        }

        return $best;
    }

    private function parseMfApiDate(string $d): ?string
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $d)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
