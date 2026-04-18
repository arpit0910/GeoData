<?php

namespace App\Services;

use App\Models\Index;
use App\Models\IndexPrice;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IndexService
{
    /**
     * Get the latest market snapshot for all indices.
     */
    public function getSnapshot(?string $exchange = null): Collection
    {
        return Cache::remember('index_snapshot_' . ($exchange ?? 'all'), 3600, function () use ($exchange) {
            $latestDate = IndexPrice::max('traded_date');
            
            if (!$latestDate) return collect();

            $query = Index::query();
            if ($exchange) {
                $query->where('exchange', strtoupper($exchange));
            }

            return $query->with(['prices' => function($q) use ($latestDate) {
                $q->where('traded_date', $latestDate);
            }])->get()->map(function($idx) {
                $latest = $idx->prices->first();
                return [
                    'code'           => $idx->index_code,
                    'name'           => $idx->index_name,
                    'exchange'       => $idx->exchange,
                    'category'       => $idx->category,
                    'open'           => $latest?->open,
                    'high'           => $latest?->high,
                    'low'            => $latest?->low,
                    'close'          => $latest?->close,
                    'prev_close'     => $latest?->prev_close,
                    'change'         => $latest?->close - $latest?->prev_close,
                    'change_percent' => $latest?->change_percent,
                    'volume'         => $latest?->volume,
                    'turnover'       => $latest?->turnover,
                    'updated_at'     => $latest?->traded_date->format('Y-m-d'),
                    'valuation' => [
                        'pe' => $latest?->pe_ratio,
                        'pb' => $latest?->pb_ratio,
                        'div_yield' => $latest?->div_yield,
                    ]
                ];
            });
        });
    }

    /**
     * Search indices by name or code.
     */
    public function searchIndices(string $query): Collection
    {
        return Index::where('index_code', 'like', "%{$query}%")
            ->orWhere('index_name', 'like', "%{$query}%")
            ->limit(20)
            ->get();
    }

    /**
     * Get detailed performance metrics for an index.
     */
    public function getPerformance(string $code): array
    {
        $current = IndexPrice::where('index_code', $code)
            ->orderBy('traded_date', 'desc')
            ->first();

        if (!$current) return [];

        return [
            'code'           => $code,
            'current_close'  => $current->close,
            'last_updated'   => $current->traded_date->format('Y-m-d'),
            'returns' => [
                '1d' => $current->chg_1d,
                '3d' => $current->chg_3d,
                '7d' => $current->chg_7d,
                '1m' => $current->chg_1m,
                '3m' => $current->chg_3m,
                '6m' => $current->chg_6m,
                '9m' => $current->chg_9m,
                '1y' => $current->chg_1y,
                '3y' => $current->chg_3y,
            ],
            'historical_values' => [
                '1d' => $current->val_1d,
                '3d' => $current->val_3d,
                '7d' => $current->val_7d,
                '1m' => $current->val_1m,
                '3m' => $current->val_3m,
                '6m' => $current->val_6m,
                '9m' => $current->val_9m,
                '1y' => $current->val_1y,
                '3y' => $current->val_3y,
            ]
        ];
    }

    /**
     * Get top movers (gainers/losers) based on a specific period.
     */
    public function getTopMovers(string $period = '1d', string $direction = 'desc', int $limit = 10): Collection
    {
        $latestDate = IndexPrice::max('traded_date');
        if (!$latestDate) return collect();

        $column = "chg_{$period}";
        // Validate column exists, fallback to chg_1d
        if (!in_array($period, ['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'])) {
            $column = 'chg_1d';
        }

        return IndexPrice::with('index')
            ->where('traded_date', $latestDate)
            ->whereNotNull($column)
            ->orderBy($column, $direction)
            ->limit($limit)
            ->get()
            ->map(function($price) use ($column) {
                return [
                    'code' => $price->index_code,
                    'name' => $price->index->index_name,
                    'exchange' => $price->index->exchange,
                    'close' => $price->close,
                    'change_pct' => $price->$column,
                    'traded_date' => $price->traded_date->format('Y-m-d')
                ];
            });
    }

    /**
     * Get historical data for charting.
     */
    public function getHistory(string $code, ?string $start = null, ?string $end = null): Collection
    {
        $query = IndexPrice::where('index_code', $code);

        if ($start) $query->where('traded_date', '>=', $start);
        if ($end) $query->where('traded_date', '<=', $end);

        return $query->orderBy('traded_date', 'asc')->get();
    }
}
