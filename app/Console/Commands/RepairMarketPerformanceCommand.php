<?php

namespace App\Console\Commands;

use App\Models\EquityPrice;
use App\Models\IndexPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairMarketPerformanceCommand extends Command
{
    protected $signature = 'market:repair-performance-values
                            {--date= : Repair only a single trading date (YYYY-MM-DD)}
                            {--from= : Start date for repair range}
                            {--to= : End date for repair range (defaults to today)}
                            {--equities-only : Repair only equity NSE/BSE performance values}
                            {--indices-only : Repair only index historical return values}';

    protected $description = 'One-time repair for equity NSE/BSE performance and index historical return values';

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        DB::disableQueryLog();

        $repairEquities = ! $this->option('indices-only');
        $repairIndices = ! $this->option('equities-only');

        $this->info('Starting one-time market performance repair...');

        if ($repairEquities) {
            $this->repairEquities();
        }

        if ($repairIndices) {
            $this->repairIndices();
        }

        $this->info('Market performance repair completed.');

        return Command::SUCCESS;
    }

    private function repairEquities(): void
    {
        $dates = $this->getRepairDates('equity_prices');

        if ($dates->isEmpty()) {
            $this->warn('No equity price records found for the requested period.');
            return;
        }

        $this->info('Repairing NSE/BSE equity performance for ' . $dates->count() . ' date(s)...');
        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $index => $date) {
            $this->repairEquityDate(Carbon::parse($date));
            $bar->advance();

            if ($index > 0 && $index % 50 === 0) {
                gc_collect_cycles();
                try {
                    DB::reconnect();
                } catch (\Throwable $ignored) {
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function repairIndices(): void
    {
        $dates = $this->getRepairDates('indices_prices');

        if ($dates->isEmpty()) {
            $this->warn('No index price records found for the requested period.');
            return;
        }

        $this->info('Repairing index historical returns for ' . $dates->count() . ' date(s)...');
        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $index => $date) {
            $this->repairIndexDate(Carbon::parse($date));
            $bar->advance();

            if ($index > 0 && $index % 50 === 0) {
                gc_collect_cycles();
                try {
                    DB::reconnect();
                } catch (\Throwable $ignored) {
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function getRepairDates(string $table)
    {
        $query = DB::table($table)->select('traded_date')->distinct()->orderBy('traded_date');

        if ($this->option('date')) {
            $query->where('traded_date', $this->option('date'));
        } elseif ($this->option('from')) {
            $query->whereBetween('traded_date', [
                $this->option('from'),
                $this->option('to') ?: now()->format('Y-m-d'),
            ]);
        } elseif ($this->option('to')) {
            $query->where('traded_date', '<=', $this->option('to'));
        }

        return $query->pluck('traded_date');
    }

    private function repairEquityDate(Carbon $date): void
    {
        $dateStr = $date->format('Y-m-d');
        $prices = EquityPrice::where('traded_date', $dateStr)->get();

        if ($prices->isEmpty()) {
            return;
        }

        $windowMap = $this->buildEquityWindowMap($date);
        $targetDates = collect($windowMap)->flatten()->filter()->unique()->values()->all();
        $equityIds = $prices->pluck('equity_id')->filter()->unique()->values()->all();

        $historicalData = empty($targetDates) || empty($equityIds)
            ? collect()
            : EquityPrice::whereIn('equity_id', $equityIds)
                ->whereIn('traded_date', $targetDates)
                ->select('equity_id', 'traded_date', 'nse_close', 'bse_close')
                ->get()
                ->groupBy('equity_id');

        $updates = [];
        $now = now();

        foreach ($prices as $price) {
            $history = $historicalData->get($price->equity_id);
            $historyByDate = $history
                ? $history->keyBy(fn($item) => $item->traded_date instanceof Carbon ? $item->traded_date->format('Y-m-d') : (string) $item->traded_date)
                : collect();

            $update = [
                'id' => $price->id,
                'spread' => ($price->nse_close > 0 && $price->bse_close > 0) ? abs($price->nse_close - $price->bse_close) : 0,
                'nse_gap_pct' => $price->nse_prev_close > 0 ? (($price->nse_open - $price->nse_prev_close) / $price->nse_prev_close) * 100 : null,
                'nse_range_pct' => $price->nse_prev_close > 0 ? (($price->nse_high - $price->nse_low) / $price->nse_prev_close) * 100 : null,
                'nse_intraday_chg_pct' => $price->nse_open > 0 ? (($price->nse_close - $price->nse_open) / $price->nse_open) * 100 : null,
                'nse_avg_ticket_size' => $price->nse_trades > 0 ? $price->nse_turnover / $price->nse_trades : null,
                'bse_gap_pct' => $price->bse_prev_close > 0 ? (($price->bse_open - $price->bse_prev_close) / $price->bse_prev_close) * 100 : null,
                'bse_range_pct' => $price->bse_prev_close > 0 ? (($price->bse_high - $price->bse_low) / $price->bse_prev_close) * 100 : null,
                'bse_intraday_chg_pct' => $price->bse_open > 0 ? (($price->bse_close - $price->bse_open) / $price->bse_open) * 100 : null,
                'bse_avg_ticket_size' => $price->bse_trades > 0 ? $price->bse_turnover / $price->bse_trades : null,
                'updated_at' => $now,
            ];

            foreach (['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'] as $period) {
                $update["nse_chg_{$period}"] = null;
                $update["nse_val_{$period}"] = null;
                $update["bse_chg_{$period}"] = null;
                $update["bse_val_{$period}"] = null;
            }

            if ($price->nse_prev_close > 0 && $price->nse_close > 0) {
                $update['nse_chg_1d'] = (($price->nse_close - $price->nse_prev_close) / $price->nse_prev_close) * 100;
                $update['nse_val_1d'] = $price->nse_prev_close;
            }

            if ($price->bse_prev_close > 0 && $price->bse_close > 0) {
                $update['bse_chg_1d'] = (($price->bse_close - $price->bse_prev_close) / $price->bse_prev_close) * 100;
                $update['bse_val_1d'] = $price->bse_prev_close;
            }

            foreach ($windowMap as $period => $candidateDates) {
                foreach ($candidateDates as $candidateDate) {
                    $pastPrice = $historyByDate->get($candidateDate);
                    if (! $pastPrice) {
                        continue;
                    }

                    if ($period !== '1d' && $pastPrice->nse_close > 0 && $price->nse_close > 0) {
                        $update["nse_chg_{$period}"] = (($price->nse_close - $pastPrice->nse_close) / $pastPrice->nse_close) * 100;
                        $update["nse_val_{$period}"] = $pastPrice->nse_close;
                    }

                    if ($period !== '1d' && $pastPrice->bse_close > 0 && $price->bse_close > 0) {
                        $update["bse_chg_{$period}"] = (($price->bse_close - $pastPrice->bse_close) / $pastPrice->bse_close) * 100;
                        $update["bse_val_{$period}"] = $pastPrice->bse_close;
                    }

                    break;
                }
            }

            $updates[] = $update;
        }

        if (! empty($updates)) {
            foreach (array_chunk($updates, 200) as $chunk) {
                EquityPrice::upsert($chunk, ['id'], $this->equityUpdateColumns());
            }
        }
    }

    private function repairIndexDate(Carbon $date): void
    {
        $dateStr = $date->format('Y-m-d');
        $prices = IndexPrice::where('traded_date', $dateStr)->get();

        if ($prices->isEmpty()) {
            return;
        }

        $windowMap = $this->buildIndexWindowMap($date);
        $targetDates = collect($windowMap)->flatten()->filter()->unique()->values()->all();
        $indexCodes = $prices->pluck('index_code')->filter()->unique()->values()->all();

        $historicalData = empty($targetDates) || empty($indexCodes)
            ? collect()
            : IndexPrice::whereIn('index_code', $indexCodes)
                ->whereIn('traded_date', $targetDates)
                ->get()
                ->groupBy('index_code');

        $updates = [];
        $now = now();

        foreach ($prices as $price) {
            $history = $historicalData->get($price->index_code);
            $historyByDate = $history
                ? $history->keyBy(fn($item) => $item->traded_date instanceof Carbon ? $item->traded_date->format('Y-m-d') : (string) $item->traded_date)
                : collect();

            $update = [
                'id' => $price->id,
                'gap_pct' => $price->prev_close > 0 && $price->open ? (($price->open - $price->prev_close) / $price->prev_close) * 100 : null,
                'range_pct' => $price->prev_close > 0 ? (($price->high - $price->low) / $price->prev_close) * 100 : null,
                'intraday_chg_pct' => $price->open > 0 ? (($price->close - $price->open) / $price->open) * 100 : null,
                'chg_1d' => $price->prev_close > 0 && $price->close > 0 ? (($price->close - $price->prev_close) / $price->prev_close) * 100 : null,
                'updated_at' => $now,
            ];

            foreach (['3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'] as $period) {
                $update["chg_{$period}"] = null;
                $update["val_{$period}"] = null;
            }

            foreach ($windowMap as $period => $candidateDates) {
                if ($period === '1d') {
                    continue;
                }

                foreach ($candidateDates as $candidateDate) {
                    $pastPrice = $historyByDate->get($candidateDate);
                    if (! $pastPrice || $pastPrice->close <= 0 || $price->close <= 0) {
                        continue;
                    }

                    $update["val_{$period}"] = $pastPrice->close;
                    $update["chg_{$period}"] = $this->calculateIndexReturnPercent(
                        (float) $price->close,
                        (float) $pastPrice->close,
                        $period,
                        $date,
                        Carbon::parse($candidateDate)
                    );

                    break;
                }
            }

            $updates[] = $update;
        }

        if (! empty($updates)) {
            foreach (array_chunk($updates, 300) as $chunk) {
                IndexPrice::upsert($chunk, ['id'], $this->indexUpdateColumns());
            }
        }
    }

    private function buildEquityWindowMap(Carbon $date): array
    {
        $periods = ['1d' => 1, '3d' => 3, '7d' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '9m' => 270, '1y' => 365, '3y' => 1095];
        $windowMap = [];
        $tradingDates = DB::table('equity_prices')
            ->where('traded_date', '<', $date->format('Y-m-d'))
            ->orderBy('traded_date', 'desc')
            ->limit(1200)
            ->pluck('traded_date');

        foreach ($periods as $label => $days) {
            $target = $date->copy()->subDays($days);
            $windowMap[$label] = $tradingDates
                ->filter(fn($tradedDate) => abs(Carbon::parse($tradedDate)->diffInDays($target)) <= 7)
                ->sortBy(fn($tradedDate) => abs(Carbon::parse($tradedDate)->diffInDays($target)))
                ->values()
                ->toArray();
        }

        return $windowMap;
    }

    private function buildIndexWindowMap(Carbon $date): array
    {
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

        $oldestTarget = $date->copy()->subYears(3)->subDays(15)->format('Y-m-d');
        $tradingDates = IndexPrice::where('traded_date', '<', $date->format('Y-m-d'))
            ->where('traded_date', '>=', $oldestTarget)
            ->distinct()
            ->orderBy('traded_date', 'desc')
            ->pluck('traded_date')
            ->map(fn($tradedDate) => Carbon::parse($tradedDate instanceof Carbon ? $tradedDate->format('Y-m-d') : (string) $tradedDate));

        $windowMap = [];
        foreach ($periodTargets as $period => $target) {
            $windowMap[$period] = $tradingDates
                ->filter(fn($tradedDate) => abs($tradedDate->diffInDays($target)) <= 10)
                ->sortBy(fn($tradedDate) => abs($tradedDate->diffInDays($target)))
                ->map(fn($tradedDate) => $tradedDate->format('Y-m-d'))
                ->values()
                ->toArray();
        }

        return $windowMap;
    }

    private function calculateIndexReturnPercent(
        float $currentClose,
        float $pastClose,
        string $period,
        Carbon $currentDate,
        Carbon $referenceDate
    ): ?float {
        if ($currentClose <= 0 || $pastClose <= 0) {
            return null;
        }

        if (in_array($period, ['1y', '3y'], true)) {
            $years = max($referenceDate->diffInDays($currentDate) / 365.2425, 1 / 365.2425);
            return ((pow($currentClose / $pastClose, 1 / $years) - 1) * 100);
        }

        return (($currentClose - $pastClose) / $pastClose) * 100;
    }

    private function equityUpdateColumns(): array
    {
        return [
            'spread',
            'nse_gap_pct',
            'nse_range_pct',
            'nse_intraday_chg_pct',
            'nse_avg_ticket_size',
            'bse_gap_pct',
            'bse_range_pct',
            'bse_intraday_chg_pct',
            'bse_avg_ticket_size',
            'nse_chg_1d',
            'nse_val_1d',
            'nse_chg_3d',
            'nse_val_3d',
            'nse_chg_7d',
            'nse_val_7d',
            'nse_chg_1m',
            'nse_val_1m',
            'nse_chg_3m',
            'nse_val_3m',
            'nse_chg_6m',
            'nse_val_6m',
            'nse_chg_9m',
            'nse_val_9m',
            'nse_chg_1y',
            'nse_val_1y',
            'nse_chg_3y',
            'nse_val_3y',
            'bse_chg_1d',
            'bse_val_1d',
            'bse_chg_3d',
            'bse_val_3d',
            'bse_chg_7d',
            'bse_val_7d',
            'bse_chg_1m',
            'bse_val_1m',
            'bse_chg_3m',
            'bse_val_3m',
            'bse_chg_6m',
            'bse_val_6m',
            'bse_chg_9m',
            'bse_val_9m',
            'bse_chg_1y',
            'bse_val_1y',
            'bse_chg_3y',
            'bse_val_3y',
            'updated_at',
        ];
    }

    private function indexUpdateColumns(): array
    {
        return [
            'gap_pct',
            'range_pct',
            'intraday_chg_pct',
            'chg_1d',
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
            'updated_at',
        ];
    }
}
