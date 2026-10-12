<?php

namespace App\Services;

use App\Models\Equity;
use App\Models\Index as MarketIndex;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockIndexComparisonService
{
    private const PERIODS = ['1m' => 1, '3m' => 3, '6m' => 6, '1y' => 12, '3y' => 36, '5y' => 60];

    public function compare(Equity $equity, MarketIndex $index, string $period = '1y'): array
    {
        $months = self::PERIODS[$period] ?? self::PERIODS['1y'];
        $from = Carbon::today('Asia/Kolkata')->subMonthsNoOverflow($months)->toDateString();

        $stock = DB::table('equity_prices')
            ->where('equity_id', $equity->id)
            ->whereDate('traded_date', '>=', $from)
            ->orderBy('traded_date')
            ->get(['traded_date', 'nse_close', 'bse_close'])
            ->mapWithKeys(function ($row) {
                $close = $row->nse_close ?? $row->bse_close;
                return $close !== null && (float) $close > 0
                    ? [Carbon::parse($row->traded_date)->toDateString() => (float) $close]
                    : [];
            });

        $benchmark = DB::table('indices_prices')
            ->where('index_code', $index->index_code)
            ->whereDate('traded_date', '>=', $from)
            ->whereNotNull('close')
            ->where('close', '>', 0)
            ->orderBy('traded_date')
            ->pluck('close', 'traded_date')
            ->mapWithKeys(fn ($close, $date) => [Carbon::parse($date)->toDateString() => (float) $close]);

        $dates = $stock->keys()->intersect($benchmark->keys())->sort()->values();
        $stockBase = $dates->isEmpty() ? null : $stock[$dates->first()];
        $indexBase = $dates->isEmpty() ? null : $benchmark[$dates->first()];
        $series = $dates->map(function (string $date) use ($stock, $benchmark, $stockBase, $indexBase) {
            $stockReturn = (($stock[$date] / $stockBase) - 1) * 100;
            $indexReturn = (($benchmark[$date] / $indexBase) - 1) * 100;

            return [
                'date' => $date,
                'stock_close' => round($stock[$date], 4),
                'index_close' => round($benchmark[$date], 4),
                'stock_return' => round($stockReturn, 4),
                'index_return' => round($indexReturn, 4),
                'relative_return' => round($stockReturn - $indexReturn, 4),
            ];
        })->values();

        $last = $series->last();

        return [
            'stock' => [
                'isin' => $equity->isin,
                'name' => $equity->company_name,
                'symbol' => $equity->nse_symbol ?: $equity->bse_symbol,
            ],
            'index' => [
                'code' => $index->index_code,
                'name' => $index->index_name,
                'exchange' => $index->exchange,
            ],
            'period' => $period,
            'summary' => [
                'start_date' => $series->first()['date'] ?? null,
                'end_date' => $last['date'] ?? null,
                'aligned_points' => $series->count(),
                'stock_return' => $last['stock_return'] ?? null,
                'index_return' => $last['index_return'] ?? null,
                'outperformance' => $last['relative_return'] ?? null,
            ],
            'live' => [
                'stock' => DB::table('equity_quotes')->where('isin', $equity->isin)
                    ->orderByDesc('fetched_at')->first(['price', 'change_percent', 'fetched_at']),
                'index' => DB::table('index_quotes')->where('index_code', $index->index_code)
                    ->first(['price', 'change_percent', 'fetched_at']),
            ],
            'data' => $series->all(),
        ];
    }

    public static function periods(): array
    {
        return array_keys(self::PERIODS);
    }
}
