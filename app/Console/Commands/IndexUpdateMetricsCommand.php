<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IndexPrice;
use Carbon\Carbon;

class IndexUpdateMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:update-metrics {--date= : Specific date to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-calculate analytical metrics for existing index price records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Starting analytical metrics update for indices...");

        $query = IndexPrice::query();
        if ($this->option('date')) {
            $query->where('traded_date', $this->option('date'));
        }

        $dates = $query->orderBy('traded_date', 'asc')->pluck('traded_date')->unique();

        if ($dates->isEmpty()) {
            $this->warn("No index price records found to update.");
            return 0;
        }

        $bar = $this->output->createProgressBar(count($dates));
        $bar->start();

        foreach ($dates as $date) {
            $this->calculateForDate(Carbon::parse($date));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Analytics update completed successfully.");
        return 0;
    }

    private function calculateForDate(Carbon $date): void
    {
        $prices = IndexPrice::where('traded_date', $date->format('Y-m-d'))->get();
        if ($prices->isEmpty()) return;

        // Pre-fetch all available unique dates DESC to find closest preceding dates for returns
        $availableDates = IndexPrice::where('traded_date', '<=', $date->format('Y-m-d'))
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->pluck('traded_date')
            ->map(fn($d) => $d instanceof Carbon ? $d->format('Y-m-d') : (string)$d)
            ->toArray();

        // Calculate target calendar dates
        $map = [
            '1d' => 1,
            '3d' => 3,
            '7d' => 7,
            '1m' => 30,
            '3m' => 90,
            '6m' => 180,
            '9m' => 270,
            '1y' => 365,
            '3y' => 1095
        ];

        $resolvedDates = [];
        foreach ($map as $key => $d) {
            $target = $date->copy()->subDays($d)->format('Y-m-d');
            foreach ($availableDates as $avail) {
                if ($avail <= $target) {
                    $resolvedDates[$key] = $avail;
                    break;
                }
            }
        }

        $historicalData = IndexPrice::whereIn('traded_date', array_unique(array_values($resolvedDates)))
            ->get()
            ->groupBy('index_code');

        foreach ($prices as $price) {
            $code = $price->index_code;

            // 0. Auto-fill prev_close if missing
            if (!$price->prev_close) {
                $lastDate = null;
                foreach ($availableDates as $avail) {
                    if ($avail < $date->format('Y-m-d')) {
                        $lastDate = $avail;
                        break;
                    }
                }
                if ($lastDate) {
                    $lastPrice = IndexPrice::where('index_code', $code)
                        ->where('traded_date', $lastDate)
                        ->first();
                    if ($lastPrice) {
                        $price->prev_close = $lastPrice->close;
                    }
                }
            }

            // 1. Core Analytics (Gap, Intraday, Range)
            if ($price->prev_close && $price->prev_close > 0) {
                if ($price->open) {
                    $price->gap_pct = (($price->open - $price->prev_close) / $price->prev_close) * 100;
                }
                $price->range_pct = (($price->high - $price->low) / $price->prev_close) * 100;
            }
            if ($price->open && $price->open > 0) {
                $price->intraday_chg_pct = (($price->close - $price->open) / $price->open) * 100;
            }

            // 2. Historical Returns using pre-fetched data
            $history = $historicalData->get($code);
            if ($history) {
                $historyByDate = $history->keyBy(fn($item) => $item->traded_date instanceof Carbon ? $item->traded_date->format('Y-m-d') : $item->traded_date);
                foreach ($resolvedDates as $key => $targetDate) {
                    $pastPrice = $historyByDate->get($targetDate);
                    if ($pastPrice && $pastPrice->close > 0) {
                        $price->{"val_{$key}"} = $pastPrice->close;
                        if ($price->close > 0) {
                            $price->{"chg_{$key}"} = (($price->close - $pastPrice->close) / $pastPrice->close) * 100;
                        }
                    }
                }
            }

            $price->save();
        }
    }
}
