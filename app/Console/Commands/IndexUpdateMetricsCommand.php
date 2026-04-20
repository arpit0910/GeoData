<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\IndexPrice;
use Carbon\Carbon;

class IndexUpdateMetricsCommand extends Command
{
    protected $signature = 'indices:update-metrics
                            {--date= : Recalculate only this date (YYYY-MM-DD)}
                            {--from= : Start of date range}
                            {--to=   : End of date range (defaults to today)}';

    protected $description = 'Re-calculate analytical metrics (returns, gap, intraday, range) for existing index price records';

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        DB::disableQueryLog();

        $this->info("Starting analytical metrics update for indices...");

        $query = IndexPrice::orderBy('traded_date', 'asc');

        if ($this->option('date')) {
            $query->where('traded_date', $this->option('date'));
        } elseif ($this->option('from')) {
            $from = $this->option('from');
            $to   = $this->option('to') ?: now()->format('Y-m-d');
            $query->whereBetween('traded_date', [$from, $to]);
        }

        $dates = $query->distinct()->pluck('traded_date')->unique()->values();

        if ($dates->isEmpty()) {
            $this->warn("No index price records found.");
            return Command::SUCCESS;
        }

        $this->info("Processing " . $dates->count() . " date(s)...");
        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $i => $date) {
            $this->calculateForDate(Carbon::parse($date));
            $bar->advance();

            if ($i % 50 === 0) {
                gc_collect_cycles();
                // Refresh connection only every 50 dates to avoid connection churn
                try { DB::reconnect(); } catch (\Exception $e) {}
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Analytics update complete.");
        return Command::SUCCESS;
    }

    private function calculateForDate(Carbon $date): void
    {
        $prices = IndexPrice::where('traded_date', $date->format('Y-m-d'))->get();
        if ($prices->isEmpty()) return;

        // Accurate calendar targets — subMonths/subYears are precise;
        // subDays(30) drifts by days over longer periods.
        $periodTargets = [
            '1d' => $date->copy()->subDay(),
            '3d' => $date->copy()->subDays(3),
            '7d' => $date->copy()->subDays(7),
            '1m' => $date->copy()->subMonth(),
            '3m' => $date->copy()->subMonths(3),
            '6m' => $date->copy()->subMonths(6),
            '9m' => $date->copy()->subMonths(9),
            '1y' => $date->copy()->subYear(),
            '3y' => $date->copy()->subYears(3),
        ];

        // Fetch all trading dates in the relevant range once — no per-index queries.
        $oldestTarget = $date->copy()->subYears(3)->subDays(15)->format('Y-m-d');
        $tradingDates = IndexPrice::where('traded_date', '<', $date->format('Y-m-d'))
            ->where('traded_date', '>=', $oldestTarget)
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->pluck('traded_date')
            ->map(fn($d) => Carbon::parse($d instanceof Carbon ? $d->format('Y-m-d') : (string)$d));

        // For each period, build a ±10-day window sorted by proximity so each
        // index uses the closest available trading date.
        $dateWindowMap = [];
        foreach ($periodTargets as $period => $target) {
            $dateWindowMap[$period] = $tradingDates
                ->filter(fn($d) => abs($d->diffInDays($target)) <= 10)
                ->sortBy(fn($d) => abs($d->diffInDays($target)))
                ->map(fn($d) => $d->format('Y-m-d'))
                ->values()
                ->toArray();
        }

        $allTargetDates = collect($dateWindowMap)->flatten()->filter()->unique()->values()->toArray();

        // Single bulk fetch covers all periods for all indices.
        $historicalData = IndexPrice::whereIn('traded_date', $allTargetDates)
            ->get()
            ->groupBy('index_code');

        foreach ($prices as $price) {
            $code          = $price->index_code;
            $history       = $historicalData->get($code);
            $historyByDate = $history
                ? $history->keyBy(fn($item) => $item->traded_date instanceof Carbon
                    ? $item->traded_date->format('Y-m-d')
                    : (string)$item->traded_date)
                : null;

            // Auto-fill prev_close from the 1d window — no extra query needed.
            if (!$price->prev_close && $historyByDate) {
                foreach ($dateWindowMap['1d'] as $pd) {
                    $last = $historyByDate->get($pd);
                    if ($last && $last->close > 0) {
                        $price->prev_close = $last->close;
                        break;
                    }
                }
            }

            // Core analytics
            if ($price->prev_close && $price->prev_close > 0) {
                if ($price->open) {
                    $price->gap_pct = (($price->open - $price->prev_close) / $price->prev_close) * 100;
                }
                $price->range_pct = (($price->high - $price->low) / $price->prev_close) * 100;
            }
            if ($price->open && $price->open > 0) {
                $price->intraday_chg_pct = (($price->close - $price->open) / $price->open) * 100;
            }

            // chg_1d — from prev_close (val_1d column does not exist in schema)
            if ($price->prev_close && $price->prev_close > 0 && $price->close > 0) {
                $price->chg_1d = (($price->close - $price->prev_close) / $price->prev_close) * 100;
            }

            // Historical period returns (3d → 3y) — val_* and chg_* both exist for these
            if ($historyByDate) {
                foreach ($dateWindowMap as $key => $candidates) {
                    if ($key === '1d') continue; // handled above via prev_close
                    foreach ($candidates as $candidateDate) {
                        $pastPrice = $historyByDate->get($candidateDate);
                        if ($pastPrice && $pastPrice->close > 0) {
                            $price->{"val_{$key}"} = $pastPrice->close;
                            if ($price->close > 0) {
                                $price->{"chg_{$key}"} = (($price->close - $pastPrice->close) / $pastPrice->close) * 100;
                            }
                            break;
                        }
                    }
                }
            }

            $price->save();
        }

        unset($prices, $tradingDates, $historicalData, $dateWindowMap, $allTargetDates);
    }
}
