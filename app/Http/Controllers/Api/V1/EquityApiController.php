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
            $query->where('market_cap_category', $request->market_cap);
        }

        if ($request->has('symbol')) {
            $query->where(function($q) use ($request) {
                $q->where('nse_symbol', 'LIKE', '%' . $request->symbol . '%')
                  ->orWhere('bse_symbol', 'LIKE', '%' . $request->symbol . '%');
            });
        }

        $equities = $query->select('isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'listing_date', 'face_value')
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
        // Advanced Normalization: 
        $input = strtolower(str_replace(['-', ' '], '', $cap)); // 'largecap', 'midcap', 'small'
        
        $map = [
            'largecap' => 'Large Cap',
            'midcap'   => 'Mid Cap',
            'smallcap' => 'Small Cap',
            'small'    => 'Small Cap',
            'microcap' => 'Small/Micro Cap (Est.)',
            'micro'    => 'Small/Micro Cap (Est.)',
            'bond'     => 'Bond (SGB)',
            'sgb'      => 'Bond (SGB)',
            'debt'     => 'Debt Instrument'
        ];

        $normalized = $map[$input] ?? str_replace('-', ' ', ucwords(strtolower($cap)));

        $equities = Equity::where('market_cap_category', $normalized)
            ->where('is_active', true)
            ->select('isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'listing_date', 'face_value')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'category' => $normalized,
            'data' => $equities
        ]);
    }

    /**
     * Search equities by symbol or company name.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        if (empty($query)) return response()->json(['success' => false, 'message' => 'Query parameter q is required'], 400);

        $equities = Equity::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('company_name', 'LIKE', "%{$query}%")
                  ->orWhere('nse_symbol', 'LIKE', "%{$query}%")
                  ->orWhere('bse_symbol', 'LIKE', "%{$query}%")
                  ->orWhere('isin', 'LIKE', "%{$query}%");
            })
            ->select('isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'data' => $equities]);
    }

    /**
     * Get stocks with highest turnover (traded value).
     */
    public function topTurnover(Request $request)
    {
        $date = \App\Models\EquityPrice::max('traded_date');
        $exchange = $request->get('exchange', 'nse'); 
        $col = $exchange === 'bse' ? 'bse_turnover' : 'nse_turnover';

        $stocks = \App\Models\EquityPrice::where('traded_date', $date)
            ->whereNotNull($col)
            ->orderBy($col, 'desc')
            ->limit(10)
            ->with('equity')
            ->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'exchange' => strtoupper($exchange), 'data' => $stocks]);
    }

    /**
     * Get recently listed equities.
     */
    public function newListings()
    {
        $equities = Equity::where('is_active', true)
            ->whereNotNull('listing_date')
            ->orderBy('listing_date', 'desc')
            ->limit(20)
            ->get(['isin', 'company_name', 'nse_symbol', 'bse_symbol', 'listing_date', 'industry']);

        return response()->json(['success' => true, 'data' => $equities]);
    }

    /**
     * Get peer equities (same industry).
     */
    public function peers(string $isin)
    {
        $equity = Equity::where('isin', $isin)->first();
        if (!$equity || empty($equity->industry)) {
            return response()->json(['success' => false, 'message' => 'Industry info not available for this ISIN'], 404);
        }

        $peers = Equity::where('industry', $equity->industry)
            ->where('isin', '!=', $isin)
            ->where('is_active', true)
            ->orderBy('market_cap', 'desc')
            ->limit(10)
            ->get(['isin', 'company_name', 'nse_symbol', 'bse_symbol', 'market_cap', 'market_cap_category']);

        return response()->json(['success' => true, 'industry' => $equity->industry, 'data' => $peers]);
    }

    /**
     * Get market capitalization distribution summary.
     */
    public function marketCapDistribution()
    {
        $stats = Equity::where('is_active', true)
            ->whereNotNull('market_cap_category')
            ->groupBy('market_cap_category')
            ->selectRaw('market_cap_category, count(*) as count')
            ->get();

        return response()->json(['success' => true, 'data' => $stats]);
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
