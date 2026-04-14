<?php

namespace App\Services;

use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EquityService
{
    /**
     * Calculate returns for 7D, 1M, 3M, 6M, 1Y, and 5Y.
     *
     * @param string $isin
     * @return array|null
     */
    public function getReturns(string $isin): ?array
    {
        return Cache::remember("equity_returns_{$isin}", 86400, function () use ($isin) {
            $equity = Equity::where('isin', $isin)->first();
            
            if (!$equity) {
                return null;
            }

            $currentPrice = $equity->prices()
                ->orderBy('traded_date', 'desc')
                ->first();

            if (!$currentPrice) {
                return null;
            }

            $currentClose = $currentPrice->nse_close ?: $currentPrice->bse_close;
            
            return [
                'isin'          => $isin,
                'name'          => $equity->company_name,
                'nse_symbol'    => $equity->nse_symbol,
                'bse_symbol'    => $equity->bse_symbol,
                'industry'      => $equity->industry,
                'market_cap'    => $equity->market_cap,
                'market_cap_category' => $equity->market_cap_category,
                'listing_date'  => $equity->listing_date ? $equity->listing_date->format('Y-m-d') : null,
                'face_value'    => $equity->face_value,
                'current_price' => $currentClose,
                'last_updated'  => $currentPrice->traded_date->format('Y-m-d'),
                'metrics' => [
                    'nse' => [
                        '1d_chg' => $currentPrice->nse_chg_1d ? round($currentPrice->nse_chg_1d, 2) . '%' : null,
                        '3d_chg' => $currentPrice->nse_chg_3d ? round($currentPrice->nse_chg_3d, 2) . '%' : null,
                        '7d_chg' => $currentPrice->nse_chg_7d ? round($currentPrice->nse_chg_7d, 2) . '%' : null,
                        '1m_chg' => $currentPrice->nse_chg_1m ? round($currentPrice->nse_chg_1m, 2) . '%' : null,
                        '3m_chg' => $currentPrice->nse_chg_3m ? round($currentPrice->nse_chg_3m, 2) . '%' : null,
                        '6m_chg' => $currentPrice->nse_chg_6m ? round($currentPrice->nse_chg_6m, 2) . '%' : null,
                        '9m_chg' => $currentPrice->nse_chg_9m ? round($currentPrice->nse_chg_9m, 2) . '%' : null,
                        '1y_chg' => $currentPrice->nse_chg_1y ? round($currentPrice->nse_chg_1y, 2) . '%' : null,
                        '3y_chg' => $currentPrice->nse_chg_3y ? round($currentPrice->nse_chg_3y, 2) . '%' : null,
                        'gap_pct' => $currentPrice->nse_gap_pct ? round($currentPrice->nse_gap_pct, 2) . '%' : null,
                        'range_pct' => $currentPrice->nse_range_pct ? round($currentPrice->nse_range_pct, 2) . '%' : null,
                        'intraday_chg_pct' => $currentPrice->nse_intraday_chg_pct ? round($currentPrice->nse_intraday_chg_pct, 2) . '%' : null,
                        'avg_ticket_size' => $currentPrice->nse_avg_ticket_size ? round($currentPrice->nse_avg_ticket_size, 2) : null,
                    ],
                    'bse' => [
                        '1d_chg' => $currentPrice->bse_chg_1d ? round($currentPrice->bse_chg_1d, 2) . '%' : null,
                        '3d_chg' => $currentPrice->bse_chg_3d ? round($currentPrice->bse_chg_3d, 2) . '%' : null,
                        '7d_chg' => $currentPrice->bse_chg_7d ? round($currentPrice->bse_chg_7d, 2) . '%' : null,
                        '1m_chg' => $currentPrice->bse_chg_1m ? round($currentPrice->bse_chg_1m, 2) . '%' : null,
                        '3m_chg' => $currentPrice->bse_chg_3m ? round($currentPrice->bse_chg_3m, 2) . '%' : null,
                        '6m_chg' => $currentPrice->bse_chg_6m ? round($currentPrice->bse_chg_6m, 2) . '%' : null,
                        '9m_chg' => $currentPrice->bse_chg_9m ? round($currentPrice->bse_chg_9m, 2) . '%' : null,
                        '1y_chg' => $currentPrice->bse_chg_1y ? round($currentPrice->bse_chg_1y, 2) . '%' : null,
                        '3y_chg' => $currentPrice->bse_chg_3y ? round($currentPrice->bse_chg_3y, 2) . '%' : null,
                        'gap_pct' => $currentPrice->bse_gap_pct ? round($currentPrice->bse_gap_pct, 2) . '%' : null,
                        'range_pct' => $currentPrice->bse_range_pct ? round($currentPrice->bse_range_pct, 2) . '%' : null,
                        'intraday_chg_pct' => $currentPrice->bse_intraday_chg_pct ? round($currentPrice->bse_intraday_chg_pct, 2) . '%' : null,
                        'avg_ticket_size' => $currentPrice->bse_avg_ticket_size ? round($currentPrice->bse_avg_ticket_size, 2) : null,
                    ]
                ]
            ];
        });
    }

    /**
     * Get the nearest previous available trading day's price.
     *
     * @param int $equityId
     * @param Carbon $date
     * @return EquityPrice|null
     */
    private function getHistoricalPrice(int $equityId, Carbon $date): ?EquityPrice
    {
        return EquityPrice::where('equity_id', $equityId)
            ->where('traded_date', '<=', $date)
            ->orderBy('traded_date', 'desc')
            ->first();
    }
}
