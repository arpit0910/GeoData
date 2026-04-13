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
                'current_price' => $currentClose,
                'last_updated'  => $currentPrice->traded_date->format('Y-m-d'),
                'metrics' => [
                    'nse' => [
                        '1d_chg' => $currentPrice->nse_chg_1d ? round($currentPrice->nse_chg_1d, 2) . '%' : null,
                        '3d_chg' => $currentPrice->nse_chg_3d ? round($currentPrice->nse_chg_3d, 2) . '%' : null,
                        '7d_chg' => $currentPrice->nse_chg_7d ? round($currentPrice->nse_chg_7d, 2) . '%' : null,
                        '1m_chg' => $currentPrice->nse_chg_1m ? round($currentPrice->nse_chg_1m, 2) . '%' : null,
                    ],
                    'bse' => [
                        '1d_chg' => $currentPrice->bse_chg_1d ? round($currentPrice->bse_chg_1d, 2) . '%' : null,
                        '3d_chg' => $currentPrice->bse_chg_3d ? round($currentPrice->bse_chg_3d, 2) . '%' : null,
                        '7d_chg' => $currentPrice->bse_chg_7d ? round($currentPrice->bse_chg_7d, 2) . '%' : null,
                        '1m_chg' => $currentPrice->bse_chg_1m ? round($currentPrice->bse_chg_1m, 2) . '%' : null,
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
