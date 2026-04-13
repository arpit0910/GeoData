<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equity;
use App\Services\EquityService;
use App\Http\Resources\EquityPriceResource;
use App\Http\Requests\GetMetricsRequest;
use Illuminate\Http\JsonResponse;

class EquityApiController extends Controller
{
    /**
     * List all available equities (supports industry & symbol filtering).
     */
    public function index(Request $request)
    {
        $query = Equity::where('is_active', true);

        if ($request->has('industry')) {
            $query->where('industry', $request->industry);
        }

        if ($request->has('market_cap')) {
            $query->where('market_cap', $request->market_cap);
        }

        if ($request->has('symbol')) {
            $query->where(function($q) use ($request) {
                $q->where('nse_symbol', 'LIKE', '%' . $request->symbol . '%')
                  ->orWhere('bse_symbol', 'LIKE', '%' . $request->symbol . '%');
            });
        }

        $equities = $query->select('isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => $equities
        ]);
    }

    /**
     * Get top gainers for the latest trading day.
     */
    public function topGainers(Request $request)
    {
        $date = \App\Models\EquityPrice::max('traded_date');
        $exchange = $request->get('exchange', 'nse'); // default to nse
        $col = $exchange === 'bse' ? 'bse_chg_1d' : 'nse_chg_1d';

        $stocks = \App\Models\EquityPrice::where('traded_date', $date)
            ->whereNotNull($col)
            ->orderBy($col, 'desc')
            ->limit(10)
            ->with('equity')
            ->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'exchange' => strtoupper($exchange), 'data' => $stocks]);
    }

    /**
     * Get top losers for the latest trading day.
     */
    public function topLosers(Request $request)
    {
        $date = \App\Models\EquityPrice::max('traded_date');
        $exchange = $request->get('exchange', 'nse'); 
        $col = $exchange === 'bse' ? 'bse_chg_1d' : 'nse_chg_1d';

        $stocks = \App\Models\EquityPrice::where('traded_date', $date)
            ->whereNotNull($col)
            ->orderBy($col, 'asc')
            ->limit(10)
            ->with('equity')
            ->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'exchange' => strtoupper($exchange), 'data' => $stocks]);
    }

    /**
     * Get stocks with highest trading volume.
     */
    public function highVolume(Request $request)
    {
        $date = \App\Models\EquityPrice::max('traded_date');
        $exchange = $request->get('exchange', 'nse'); 
        $col = $exchange === 'bse' ? 'bse_volume' : 'nse_volume';

        $stocks = \App\Models\EquityPrice::where('traded_date', $date)
            ->whereNotNull($col)
            ->orderBy($col, 'desc')
            ->limit(10)
            ->with('equity')
            ->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'exchange' => strtoupper($exchange), 'data' => $stocks]);
    }

    /**
     * Get equities by market capitalization category.
     */
    public function byMarketCap(string $cap)
    {
        // Normalize: 'large-cap' -> 'Large Cap', 'midcap' -> 'Mid Cap'
        $normalized = str_replace('-', ' ', ucwords(strtolower($cap)));
        if (!str_contains($normalized, 'Cap')) $normalized .= ' Cap';

        $equities = Equity::where('market_cap', $normalized)
            ->where('is_active', true)
            ->paginate(100);

        return response()->json([
            'success' => true,
            'category' => $normalized,
            'data' => $equities
        ]);
    }

    /**
     * Get historical price data for a specific ISIN (last 30 trading days).
     */
    public function history(string $isin)
    {
        $equity = Equity::where('isin', $isin)->first();
        if (!$equity) return response()->json(['success' => false, 'message' => 'ISIN not found'], 404);

        $history = \App\Models\EquityPrice::where('equity_id', $equity->id)
            ->orderBy('traded_date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'isin' => $isin,
            'company_name' => $equity->company_name,
            'data' => $history
        ]);
    }

    /**
     * Get detailed information for a specific ISIN (Static info).
     */
    public function show(string $isin)
    {
        $equity = Equity::where('isin', $isin)->first();

        if (!$equity) {
            return response()->json([
                'success' => false,
                'message' => 'Equity not found for ISIN: ' . $isin
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $equity
        ]);
    }

    /**
     * Get stock performance metrics for a specific ISIN (Pre-calculated metrics).
     */
    public function metrics(GetMetricsRequest $request, string $isin, EquityService $service)
    {
        try {
            $data = $service->getReturns($isin);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No historical data found for ISIN: ' . $isin
                ], 404);
            }

            return new EquityPriceResource($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating metrics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
