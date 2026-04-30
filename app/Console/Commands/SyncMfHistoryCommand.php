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
                            {--force : Re-insert even if records already exist}
                            {--skip-master : Skip refreshing the master fund list from mfapi.in}';

    protected $description = 'Fetch historical NAVs + compute period returns from mfapi.in';

    private const MFAPI_BASE    = 'https://api.mfapi.in/mf';
    private const NAV_CHUNK     = 500;
    private const SLEEP_MS      = 300000; // 0.3 s between each scheme (avoid rate limiting)
    private const HTTP_RETRIES  = 5;
    private const HTTP_BACKOFF  = [1, 2, 4, 8, 16]; // seconds per retry attempt

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $months     = (int) $this->argument('months');
        $cutoff     = now()->subMonths($months)->startOfMonth()->format('Y-m-d');

        $singleCode = $this->option('scheme');

        $this->refreshMaster();

        $query = DB::table('mutual_funds')->where('is_active', 1);
        if ($singleCode) $query->where('scheme_code', $singleCode);
        $schemes = $query->get();

        if ($schemes->isEmpty()) {
            $this->error('No schemes found after master refresh. Check API connectivity.');
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

            // Prepare rows for bulk insert (raw NAVs only)
            $schemeRows = [];
            foreach ($newDates as $j => $date) {
                $schemeRows[] = [
                    'mf_id'    => $this->resolveFundKey($scheme),
                    'isin'     => $scheme->isin,
                    'nav_date' => $date,
                    'nav'      => $allNavVals[$j],
                ];
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
        $updateCols = ['mf_id', 'nav'];
        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            DB::table('mutual_fund_prices')->upsert($chunk, ['isin', 'nav_date'], $updateCols);
        }
        return count($rows);
    }

    private function parseMfApiDate(string $d): ?string
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $d)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function refreshMaster(): void
    {
        if ($this->option('skip-master')) {
            $this->info('Skipping master list refresh.');
            return;
        }

        $this->info('Refreshing master scheme list from mfapi.in...');
        try {
            $list = null;
            $success = false;
            
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                if ($attempt > 0) sleep($attempt * 2);

                try {
                    $response = Http::connectTimeout(30)
                        ->timeout(120)
                        ->withoutVerifying()
                        ->get(self::MFAPI_BASE);

                    if ($response->successful()) {
                        $list = $response->json();
                        if (!empty($list)) {
                            $success = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn("Master refresh attempt $attempt failed: " . $e->getMessage());
                }
            }

            if (!$success) {
                $this->error('Failed to fetch master list after multiple attempts.');
                return;
            }

            $this->info(sprintf('Processing %d schemes for auto-discovery...', count($list)));
            $bar = $this->output->createProgressBar(count($list));
            $bar->start();

            $chunks = array_chunk($list, 1000);
            foreach ($chunks as $chunk) {
                $rows = [];
                foreach ($chunk as $item) {
                    $isin = $item['isinGrowth'] ?? $item['isinDivReinvestment'] ?? null;
                    if (!$isin || strlen($isin) !== 12) continue;

                    $rows[] = [
                        'isin'          => $isin,
                        'scheme_code'   => (string)$item['schemeCode'],
                        'scheme_name'   => substr($item['schemeName'], 0, 300),
                        'isin_reinvest' => $item['isinDivReinvestment'] ?? null,
                        'is_active'     => 1,
                        'updated_at'    => now(),
                    ];
                }

                if (!empty($rows)) {
                    // Match by ISIN, update name and code if changed
                    DB::table('mutual_funds')->upsert(
                        $rows,
                        ['isin'],
                        ['scheme_code', 'scheme_name', 'isin_reinvest', 'is_active', 'updated_at']
                    );
                }
                $bar->advance(count($chunk));
            }

            $bar->finish();
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('Master refresh failed: ' . $e->getMessage());
        }
    }
}
