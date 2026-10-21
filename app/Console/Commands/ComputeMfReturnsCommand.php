<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComputeMfReturnsCommand extends Command
{
    protected $signature = 'compute:mf-returns
                            {--isin=     : Single ISIN to process}
                            {--from=     : Recalculate NAV dates from YYYY-MM-DD}
                            {--to=       : Recalculate NAV dates through YYYY-MM-DD}
                            {--force     : Re-calculate even if returns already exist}
                            {--isin-batch=50 : ISINs fetched per SQL batch}
                            {--write-chunk=2000 : Rows per upsert call}';

    protected $description = 'Compute historical performance returns for Mutual Funds from saved NAV data';

    private const WINDOW_DAYS = 10;

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

    private const UPDATE_COLS = [
        'chg_1d', 'val_1d', 'chg_3d', 'val_3d',
        'chg_7d', 'val_7d', 'chg_1m', 'val_1m',
        'chg_3m', 'val_3m', 'chg_6m', 'val_6m',
        'chg_9m', 'val_9m', 'chg_1y', 'val_1y',
        'chg_3y', 'val_3y',
    ];

    protected bool $shouldStop = false;

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        DB::disableQueryLog();
        $this->setupSignals();

        $isinArg    = $this->option('isin');
        $force      = (bool) $this->option('force');
        $isinBatch  = max(1, (int) ($this->option('isin-batch')  ?: 50));
        $writeChunk = max(1, (int) ($this->option('write-chunk') ?: 2000));
        $startedAt  = microtime(true);
        try {
            $from = $this->option('from') ? Carbon::createFromFormat('!Y-m-d', $this->option('from')) : null;
            $to = $this->option('to') ? Carbon::createFromFormat('!Y-m-d', $this->option('to')) : null;
            if (($this->option('from') && (! $from || $from->format('Y-m-d') !== $this->option('from')))
                || ($this->option('to') && (! $to || $to->format('Y-m-d') !== $this->option('to')))
                || ($from && $to && $from->gt($to))) {
                throw new \InvalidArgumentException();
            }
        } catch (\Throwable) {
            $this->error('The --from and --to values must use YYYY-MM-DD format, with --from no later than --to.');
            return Command::INVALID;
        }
        $fromDate = $from?->toDateString();
        $toDate = $to?->toDateString();

        Log::info('[compute:mf-returns] started', [
            'isin'        => $isinArg,
            'force'       => $force,
            'isin_batch'  => $isinBatch,
            'write_chunk' => $writeChunk,
        ]);

        $query = DB::table('mutual_fund_prices')->select('isin')->distinct();
        if ($isinArg) $query->where('isin', $isinArg);
        $isins = $query->pluck('isin');

        if ($isins->isEmpty()) {
            $this->warn('No NAV data found in mutual_fund_prices.');
            Log::warning('[compute:mf-returns] no data found');
            return Command::SUCCESS;
        }

        $total = $isins->count();
        $this->info(sprintf('Computing returns for %d ISINs (isin-batch=%d, write-chunk=%d)...', $total, $isinBatch, $writeChunk));
        Log::info('[compute:mf-returns] processing', ['total_isins' => $total]);

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %estimated:-6s%');
        $bar->start();

        $processed   = 0;
        $rowsWritten = 0;
        $failed      = 0;

        foreach ($isins->chunk($isinBatch) as $batch) {
            if ($this->shouldStop) {
                $this->newLine();
                $this->warn("Stop signal — exiting after {$processed}/{$total} ISINs.");
                Log::warning('[compute:mf-returns] stopped by signal', ['processed' => $processed, 'total' => $total]);
                break;
            }

            $t = microtime(true);
            [$batchRows, $batchFailed] = $this->processBatch($batch->all(), $force, $fromDate, $toDate);
            $computeMs = round((microtime(true) - $t) * 1000);

            if (!empty($batchRows)) {
                $tw = microtime(true);
                try {
                    foreach (array_chunk($batchRows, $writeChunk) as $chunk) {
                        DB::table('mutual_fund_prices')->upsert($chunk, ['isin', 'nav_date'], self::UPDATE_COLS);
                    }
                    $rowsWritten += count($batchRows);
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error('Batch write failed: ' . $e->getMessage());
                    Log::error('[compute:mf-returns] batch write failed', [
                        'isins'   => $batch->all(),
                        'rows'    => count($batchRows),
                        'error'   => $e->getMessage(),
                    ]);
                    $batchFailed += $batch->count();
                }
                $writeMs = round((microtime(true) - $tw) * 1000);
            } else {
                $writeMs = 0;
            }

            $processed += $batch->count();
            $failed    += $batchFailed;
            $bar->advance($batch->count());

            Log::info('[compute:mf-returns] batch done', [
                'processed'   => $processed,
                'total'       => $total,
                'rows'        => count($batchRows),
                'compute_ms'  => $computeMs,
                'write_ms'    => $writeMs,
            ]);
        }

        $bar->finish();
        $this->newLine();

        $elapsed = round(microtime(true) - $startedAt, 1);
        $summary = "Done — ISINs: {$processed}/{$total} | Rows written: {$rowsWritten} | Failed: {$failed} | {$elapsed}s";
        $this->info($summary);
        Log::info('[compute:mf-returns] complete', [
            'processed'    => $processed,
            'total'        => $total,
            'rows_written' => $rowsWritten,
            'failed'       => $failed,
            'elapsed_s'    => $elapsed,
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    private function processBatch(array $isins, bool $force, ?string $from, ?string $to): array
    {
        // One SELECT for the entire ISIN batch
        $grouped = DB::table('mutual_fund_prices')
            ->whereIn('isin', $isins)
            ->orderBy('isin')
            ->orderBy('nav_date')
            ->select('isin', 'nav_date', 'nav', 'mf_id', 'chg_1d')
            ->get()
            ->groupBy('isin');

        $rows   = [];
        $failed = 0;

        foreach ($isins as $isin) {
            $prices = $grouped->get($isin);
            if (!$prices || $prices->isEmpty()) continue;

            $allDates      = $prices->pluck('nav_date')->all();
            $allNavs       = $prices->pluck('nav')->all();
            $allTimestamps = array_map('strtotime', $allDates);

            foreach ($prices as $i => $row) {
                $navDate = substr((string) $row->nav_date, 0, 10);
                if (($from && $navDate < $from) || ($to && $navDate > $to)) continue;
                if (!$force && $row->chg_1d !== null) continue;

                $currentNav = (float) $row->nav;
                $updateRow  = [
                    'isin'     => $isin,
                    'nav_date' => $row->nav_date,
                    'nav'      => $currentNav,
                    'mf_id'    => $row->mf_id,
                ];

                $hasData = false;
                foreach (self::PERIODS as $p => [$method, $val]) {
                    $targetTs = $this->targetTs($row->nav_date, $method, $val);
                    $idx      = $this->closestIdx($allTimestamps, $allNavs, $i, $targetTs, self::WINDOW_DAYS);

                    if ($idx !== null && (float) $allNavs[$idx] > 0) {
                        $refNav                = (float) $allNavs[$idx];
                        $updateRow["chg_{$p}"] = round((($currentNav - $refNav) / $refNav) * 100, 4);
                        $updateRow["val_{$p}"] = $refNav;
                        $hasData = true;
                    } else {
                        $updateRow["chg_{$p}"] = null;
                        $updateRow["val_{$p}"] = null;
                    }
                }

                if ($hasData || $force) {
                    $rows[] = $updateRow;
                }
            }
        }

        return [$rows, $failed];
    }

    // ── Signal / helpers ──────────────────────────────────────────────────────

    private function setupSignals(): void
    {
        if (!extension_loaded('pcntl')) return;
        pcntl_async_signals(true);
        $handler = function (int $sig) {
            $this->shouldStop = true;
            $label = match ($sig) { SIGTERM => 'SIGTERM', SIGINT => 'SIGINT', default => "SIG{$sig}" };
            $this->warn("\n{$label} received — stopping after current batch.");
            Log::warning('[compute:mf-returns] signal received', ['signal' => $label]);
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
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

    private function closestIdx(array $timestamps, array $navs, int $maxIdx, int $targetTs, int $windowDays): ?int
    {
        if ($maxIdx === 0) return null;

        $windowSec = $windowDays * 86400;
        $lo = 0;
        $hi = $maxIdx - 1;
        if ($timestamps[$lo] > $targetTs) return null;

        while ($lo < $hi) {
            $mid = ($lo + $hi + 1) >> 1;
            if ($timestamps[$mid] <= $targetTs) $lo = $mid;
            else $hi = $mid - 1;
        }

        $idx = $lo;
        while ($idx >= 0) {
            $diff = $targetTs - $timestamps[$idx];
            if ($diff > $windowSec) return null;
            if ((float) $navs[$idx] > 0) return $idx;
            $idx--;
        }

        return null;
    }
}
