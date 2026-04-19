<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncMfHistoryCommand extends Command
{
    protected $signature = 'sync:mf-history
                            {months=12 : Months of history to fetch per scheme}
                            {--scheme= : Single scheme_code to sync (debug/backfill)}
                            {--force : Re-insert even if records already exist}
                            {--concurrency=10 : Parallel HTTP requests per batch}';

    protected $description = 'Fetch historical NAVs + compute period returns from mfapi.in';

    private const MFAPI_BASE = 'https://api.mfapi.in/mf';
    private const NAV_CHUNK  = 500; // SQLite limit: 32766 vars / 21 cols = 1560 max; 500 is safe
    private const SLEEP_US   = 800000; // 0.8 s between batches

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

        $months      = (int) $this->argument('months');
        $concurrency = max(1, min((int) $this->option('concurrency'), 20));
        $cutoff      = now()->subMonths($months)->startOfMonth()->format('Y-m-d');
        $singleCode  = $this->option('scheme');

        $query = DB::table('mutual_funds')->select('isin', 'scheme_code')->where('is_active', 1);
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
            'Syncing %d months of history + returns for %d schemes (concurrency=%d, cutoff=%s)...',
            $months,
            $schemes->count(),
            $concurrency,
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

        foreach ($schemes->chunk($concurrency) as $batch) {
            $batchArr = $batch->values()->all();

            try {
                $responses = Http::pool(function ($pool) use ($batchArr) {
                    $reqs = [];
                    foreach ($batchArr as $i => $s) {
                        $reqs[$i] = $pool->as($i)->timeout(20)->withoutVerifying()
                            ->get(self::MFAPI_BASE . '/' . $s->scheme_code);
                    }
                    return $reqs;
                });
            } catch (\Exception $e) {
                $failed += count($batchArr);
                $bar->advance(count($batchArr));
                usleep(self::SLEEP_US);
                continue;
            }

            foreach ($batchArr as $i => $scheme) {
                $response = $responses[$i] ?? null;
                if (!$response || $response instanceof \Throwable || !$response->successful()) {
                    $failed++;
                    continue;
                }

                $json = $response->json();
                if (empty($json['data'])) {
                    $skipped++;
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
                    continue;
                }

                // Parse NAVs within range, skipping dates already in DB
                $navs = [];
                foreach ($json['data'] as $entry) {
                    $d = $this->parseMfApiDate($entry['date'] ?? '');
                    if (!$d || $d < $cutoff) continue;

                    // Skip dates within the already-covered range
                    if (
                        !$this->option('force') && $range
                        && $d >= $range[0] && $d <= $range[1]
                    ) {
                        continue;
                    }

                    $v = (float)($entry['nav'] ?? 0);
                    if ($v <= 0) continue;
                    $navs[$d] = $v;
                }

                if (empty($navs)) {
                    $skipped++;
                    continue;
                }

                ksort($navs);
                $dates      = array_keys($navs);
                $navVals    = array_values($navs);
                $timestamps = array_map('strtotime', $dates);
                $count      = count($dates);

                $schemeRows = [];
                for ($j = 0; $j < $count; $j++) {
                    $currentNav = $navVals[$j];
                    $row = [
                        'isin'     => $scheme->isin,
                        'nav_date' => $dates[$j],
                        'nav'      => $currentNav,
                    ];

                    foreach (self::PERIODS as $p => [$method, $val]) {
                        $targetTs = $this->targetTs($dates[$j], $method, $val);
                        $idx      = $this->closestIdx($timestamps, $j, $targetTs, 10);
                        if ($idx !== null && $navVals[$idx] > 0) {
                            $refNav        = $navVals[$idx];
                            $row["chg_{$p}"] = round((($currentNav - $refNav) / $refNav) * 100, 4);
                            $row["val_{$p}"] = $refNav;
                        } else {
                            $row["chg_{$p}"] = null;
                            $row["val_{$p}"] = null;
                        }
                    }

                    $schemeRows[] = $row;
                }

                // Flush per-scheme inside explicit transaction — each commit writes directly
                // to the main SQLite DB file (DELETE journal mode), so data survives any crash.
                DB::beginTransaction();
                try {
                    $this->flushBuffer($schemeRows);
                    DB::commit();
                    $flushed += count($schemeRows);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                }
            }

            $bar->advance(count($batchArr));
            usleep(self::SLEEP_US);
        }

        $bar->finish();
        $this->newLine();
        $this->info(sprintf(
            'Done. Rows upserted: %d | Skipped: %d | Failed: %d',
            $flushed,
            $skipped,
            $failed
        ));

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function flushBuffer(array $rows): int
    {
        $updateCols = [
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
