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
    public function getSnapshot(): Collection
    {
        return Cache::remember('index_snapshot', 86400, function () {
            $latestDate = IndexPrice::max('traded_date');
            
            if (!$latestDate) return collect();

            return Index::with(['prices' => function($q) use ($latestDate) {
                $q->where('traded_date', $latestDate);
            }])->get()->map(function($idx) {
                $latest = $idx->prices->first();
                return [
                    'code'           => $idx->index_code,
                    'name'           => $idx->index_name,
                    'exchange'       => $idx->exchange,
                    'category'       => $idx->category,
                    'ltp'            => $latest?->close,
                    'prev_close'     => $latest?->prev_close,
                    'change'         => $latest?->close - $latest?->prev_close,
                    'change_percent' => $latest?->change_percent,
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
     * Get detailed performance metrics for an index.
     */
    public function getPerformance(string $code): array
    {
        $current = IndexPrice::where('index_code', $code)
            ->orderBy('traded_date', 'desc')
            ->first();

        if (!$current) return [];

        $currentClose = $current->close;
        $date = $current->traded_date;

        return [
            'code'           => $code,
            'current_close'  => $currentClose,
            'last_updated'   => $date->format('Y-m-d'),
            'returns' => [
                '7d'  => $this->calculateReturn($code, $currentClose, $date->copy()->subDays(7)),
                '1m'  => $this->calculateReturn($code, $currentClose, $date->copy()->subMonth()),
                '3m'  => $this->calculateReturn($code, $currentClose, $date->copy()->subMonths(3)),
                '1y'  => $this->calculateReturn($code, $currentClose, $date->copy()->subYear()),
            ]
        ];
    }

    /**
     * Calculate return for a specific horizon.
     */
    private function calculateReturn(string $code, float $currentClose, Carbon $targetDate): ?float
    {
        $historical = IndexPrice::where('index_code', $code)
            ->where('traded_date', '<=', $targetDate)
            ->orderBy('traded_date', 'desc')
            ->first();

        if (!$historical || $historical->close <= 0) return null;

        return (($currentClose - $historical->close) / $historical->close) * 100;
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
