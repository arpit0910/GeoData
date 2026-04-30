<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComputeMfReturnsCommand extends Command
{
    protected $signature = 'compute:mf-returns 
                            {--isin= : Single ISIN to process} 
                            {--force : Re-calculate even if returns already exist}';

    protected $description = 'Compute historical performance returns for Mutual Funds from saved NAV data';

    private const BATCH_SIZE = 500;
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

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $isinArg = $this->option('isin');
        $force   = $this->option('force');

        $query = DB::table('mutual_fund_prices')
            ->select('isin')
            ->distinct();

        if ($isinArg) {
            $query->where('isin', $isinArg);
        }

        $isins = $query->pluck('isin');

        if ($isins->isEmpty()) {
            $this->warn('No NAV data found in mutual_fund_prices.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Computing returns for %d ISINs...', $isins->count()));
        $bar = $this->output->createProgressBar($isins->count());
        $bar->start();

        foreach ($isins as $isin) {
            $this->processIsin($isin, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return Command::SUCCESS;
    }

    private function processIsin(string $isin, bool $force): void
    {
        // Fetch all prices for this ISIN sorted by date
        $prices = DB::table('mutual_fund_prices')
            ->where('isin', $isin)
            ->orderBy('nav_date', 'asc')
            ->get();

        if ($prices->isEmpty()) return;

        $allDates      = $prices->pluck('nav_date')->all();
        $allNavs       = $prices->pluck('nav')->all();
        $allTimestamps = array_map('strtotime', $allDates);
        $count         = count($allDates);

        $updates = [];

        foreach ($prices as $i => $row) {
            // Skip if already computed and not forced
            if (!$force && $row->chg_1d !== null) continue;

            $currentNav = (float)$row->nav;
            $updateRow  = [];
            $hasData    = false;

            foreach (self::PERIODS as $p => [$method, $val]) {
                $targetTs = $this->targetTs($row->nav_date, $method, $val);
                $idx      = $this->closestIdx($allTimestamps, $i, $targetTs, self::WINDOW_DAYS);

                if ($idx !== null && (float)$allNavs[$idx] > 0) {
                    $refNav          = (float)$allNavs[$idx];
                    $updateRow["chg_{$p}"] = round((($currentNav - $refNav) / $refNav) * 100, 4);
                    $updateRow["val_{$p}"] = $refNav;
                    $hasData = true;
                } else {
                    $updateRow["chg_{$p}"] = null;
                    $updateRow["val_{$p}"] = null;
                }
            }

            if ($hasData || $force) {
                $updates[] = array_merge(['nav_date' => $row->nav_date], $updateRow);
            }
        }

        if (!empty($updates)) {
            foreach (array_chunk($updates, self::BATCH_SIZE) as $chunk) {
                // We use a manual loop or upsert. Since ISIN is constant here:
                foreach ($chunk as $u) {
                    $date = $u['nav_date'];
                    unset($u['nav_date']);
                    DB::table('mutual_fund_prices')
                        ->where('isin', $isin)
                        ->where('nav_date', $date)
                        ->update($u);
                }
            }
        }
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
}
