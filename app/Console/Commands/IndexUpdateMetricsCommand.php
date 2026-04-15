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

        foreach ($prices as $price) {
            $code = $price->index_code;

            // 0. Auto-fill prev_close if missing
            if (!$price->prev_close) {
                $lastPrice = IndexPrice::where('index_code', $code)
                    ->where('traded_date', '<', $date->format('Y-m-d'))
                    ->orderBy('traded_date', 'desc')
                    ->first();
                if ($lastPrice) {
                    $price->prev_close = $lastPrice->close;
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

            // 2. Historical Returns
            $price->chg_1d = $this->getHistoricalReturn($code, $date, 1);
            $price->chg_3d = $this->getHistoricalReturn($code, $date, 3);
            $price->chg_7d = $this->getHistoricalReturn($code, $date, 7);
            $price->chg_1m = $this->getHistoricalReturn($code, $date, 30);
            $price->chg_3m = $this->getHistoricalReturn($code, $date, 90);
            $price->chg_6m = $this->getHistoricalReturn($code, $date, 180);
            $price->chg_1y = $this->getHistoricalReturn($code, $date, 365);
            $price->chg_3y = $this->getHistoricalReturn($code, $date, 1095);

            $price->save();
        }
    }

    private function getHistoricalReturn(string $code, Carbon $currentDate, int $daysAgo): ?float
    {
        $targetDate = $currentDate->copy()->subDays($daysAgo);
        
        $pastPrice = IndexPrice::where('index_code', $code)
            ->where('traded_date', '<=', $targetDate->format('Y-m-d'))
            ->orderBy('traded_date', 'desc')
            ->first();
            
        if ($pastPrice && $pastPrice->close > 0) {
            $currentPrice = IndexPrice::where('index_code', $code)
                ->where('traded_date', $currentDate->format('Y-m-d'))
                ->first();
                
            if ($currentPrice && $currentPrice->close > 0) {
                return (($currentPrice->close - $pastPrice->close) / $pastPrice->close) * 100;
            }
        }
        
        return null;
    }
}
