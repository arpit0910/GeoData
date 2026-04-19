<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equity;
use App\Models\EquityPrice;
use App\Services\EquityService;
use App\Http\Resources\EquityPriceResource;
use App\Http\Requests\GetMetricsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    /**
     * Stocks that gapped up or down significantly at market open.
     * direction=up|down, min_pct=2, exchange=nse|bse, limit=20
     */
    public function gapMovers(Request $request): JsonResponse
    {
        $exchange  = strtolower($request->get('exchange', 'nse'));
        $direction = $request->get('direction', 'up');
        $minPct    = abs((float)$request->get('min_pct', 1));
        $limit     = min((int)$request->get('limit', 20), 50);
        $col       = $exchange === 'bse' ? 'bse_gap_pct' : 'nse_gap_pct';
        $date      = EquityPrice::max('traded_date');

        $query = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull("p.{$col}");

        $query = $direction === 'down'
            ? $query->where("p.{$col}", '<=', -$minPct)->orderBy("p.{$col}", 'asc')
            : $query->where("p.{$col}", '>=', $minPct)->orderBy("p.{$col}", 'desc');

        $data = $query->select(
            'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry',
            'p.traded_date',
            DB::raw("p.{$col} as gap_pct"),
            DB::raw($exchange === 'bse' ? 'p.bse_open as open, p.bse_close as close, p.bse_prev_close as prev_close' : 'p.nse_open as open, p.nse_close as close, p.nse_prev_close as prev_close')
        )->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'direction' => $direction, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks with the strongest intraday move (open to close).
     * direction=up|down, exchange=nse|bse, limit=20
     */
    public function intradayMovers(Request $request): JsonResponse
    {
        $exchange  = strtolower($request->get('exchange', 'nse'));
        $direction = $request->get('direction', 'up');
        $limit     = min((int)$request->get('limit', 20), 50);
        $col       = $exchange === 'bse' ? 'bse_intraday_chg_pct' : 'nse_intraday_chg_pct';
        $date      = EquityPrice::max('traded_date');

        $query = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull("p.{$col}");

        $query = $direction === 'down'
            ? $query->orderBy("p.{$col}", 'asc')
            : $query->orderBy("p.{$col}", 'desc');

        $data = $query->select(
            'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry',
            'p.traded_date',
            DB::raw("p.{$col} as intraday_chg_pct"),
            DB::raw($exchange === 'bse' ? 'p.bse_open as open, p.bse_close as close' : 'p.nse_open as open, p.nse_close as close')
        )->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'direction' => $direction, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks with the widest intraday price range (high-low as % of prev close).
     * Useful for identifying high-volatility trading opportunities.
     */
    public function wideRangeStocks(Request $request): JsonResponse
    {
        $exchange = strtolower($request->get('exchange', 'nse'));
        $limit    = min((int)$request->get('limit', 20), 50);
        $col      = $exchange === 'bse' ? 'bse_range_pct' : 'nse_range_pct';
        $date     = EquityPrice::max('traded_date');

        $data = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull("p.{$col}")
            ->orderBy("p.{$col}", 'desc')
            ->select(
                'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry',
                'p.traded_date',
                DB::raw("p.{$col} as range_pct"),
                DB::raw($exchange === 'bse' ? 'p.bse_high as high, p.bse_low as low, p.bse_prev_close as prev_close' : 'p.nse_high as high, p.nse_low as low, p.nse_prev_close as prev_close')
            )
            ->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks with the most number of trades — signals retail/institutional activity.
     * More meaningful than raw volume for spotting attention stocks.
     */
    public function highActivity(Request $request): JsonResponse
    {
        $exchange = strtolower($request->get('exchange', 'nse'));
        $limit    = min((int)$request->get('limit', 20), 50);
        $col      = $exchange === 'bse' ? 'bse_trades' : 'nse_trades';
        $date     = EquityPrice::max('traded_date');

        $data = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull("p.{$col}")
            ->orderBy("p.{$col}", 'desc')
            ->select(
                'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry',
                'p.traded_date',
                DB::raw("p.{$col} as trades"),
                DB::raw($exchange === 'bse' ? 'p.bse_volume as volume, p.bse_turnover as turnover, p.bse_avg_price as avg_price, p.bse_close as close' : 'p.nse_volume as volume, p.nse_turnover as turnover, p.nse_avg_price as avg_price, p.nse_close as close')
            )
            ->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks with notable price difference between NSE and BSE on the same day.
     * Real-time arbitrage signal — unique to dual-listed Indian markets.
     */
    public function nseBseSpread(Request $request): JsonResponse
    {
        $minSpread = (float)$request->get('min_spread', 0.5);
        $limit     = min((int)$request->get('limit', 20), 50);
        $date      = EquityPrice::max('traded_date');

        $data = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->where('p.spread', '>=', $minSpread)
            ->whereNotNull('p.nse_close')
            ->whereNotNull('p.bse_close')
            ->where('p.nse_close', '>', 0)
            ->where('p.bse_close', '>', 0)
            ->orderBy('p.spread', 'desc')
            ->select(
                'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry',
                'p.traded_date', 'p.nse_close', 'p.bse_close', 'p.spread',
                DB::raw('ROUND((p.spread / p.nse_close) * 100, 4) as spread_pct')
            )
            ->limit($limit)->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks consistently positive across all selected return periods.
     * Steady compounders — positive in 1m, 3m, 6m, and 1y simultaneously.
     */
    public function consistentPerformers(Request $request): JsonResponse
    {
        $exchange = strtolower($request->get('exchange', 'nse'));
        $periods  = $request->get('periods', '1m,3m,6m,1y');
        $limit    = min((int)$request->get('limit', 20), 100);
        $date     = EquityPrice::max('traded_date');
        $prefix   = $exchange === 'bse' ? 'bse' : 'nse';

        $validPeriods = ['1d','3d','7d','1m','3m','6m','9m','1y','3y'];
        $requestedPeriods = array_filter(
            array_map('trim', explode(',', $periods)),
            fn($p) => in_array($p, $validPeriods)
        );

        if (empty($requestedPeriods)) {
            return response()->json(['success' => false, 'message' => 'No valid periods specified.'], 422);
        }

        $query = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date);

        foreach ($requestedPeriods as $period) {
            $query->where("p.{$prefix}_chg_{$period}", '>', 0);
        }

        $selectCols = ['e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry', 'e.market_cap_category', 'p.traded_date'];
        foreach ($requestedPeriods as $period) {
            $selectCols[] = "p.{$prefix}_chg_{$period} as chg_{$period}";
        }

        $data = $query->select($selectCols)->orderBy("p.{$prefix}_chg_1y", 'desc')->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'periods' => array_values($requestedPeriods), 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Stocks near their 52-week high or low.
     * position=near_high|near_low, threshold=5 (within 5% of extreme)
     */
    public function weekExtremes(Request $request): JsonResponse
    {
        $exchange  = strtolower($request->get('exchange', 'nse'));
        $position  = $request->get('position', 'near_high');
        $threshold = (float)$request->get('threshold', 5);
        $limit     = min((int)$request->get('limit', 20), 100);
        $prefix    = $exchange === 'bse' ? 'bse' : 'nse';
        $date      = EquityPrice::max('traded_date');

        // val_1y is the reference price 1 year ago; close is today's price
        // Near 52-week high: close is within threshold% BELOW its 52w high (approximated as max of close vs 1y-ago close)
        // We use: (close - val_1y) / val_1y * 100 as proxy for 52w return
        $query = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull("p.{$prefix}_chg_1y")
            ->whereNotNull("p.{$prefix}_close")
            ->where("p.{$prefix}_close", '>', 0);

        if ($position === 'near_low') {
            // Near 52w low: 1y return is very negative (stock near its yearly bottom)
            $query->where("p.{$prefix}_chg_1y", '<=', -$threshold)->orderBy("p.{$prefix}_chg_1y", 'asc');
        } else {
            // Near 52w high: 1y return is strongly positive (stock near its yearly top)
            $query->where("p.{$prefix}_chg_1y", '>=', $threshold)->orderBy("p.{$prefix}_chg_1y", 'desc');
        }

        $data = $query->select(
            'e.isin', 'e.company_name', 'e.nse_symbol', 'e.bse_symbol', 'e.industry', 'e.market_cap_category',
            'p.traded_date',
            DB::raw("p.{$prefix}_close as close"),
            DB::raw("p.{$prefix}_chg_1y as chg_1y"),
            DB::raw("p.{$prefix}_val_1y as price_1y_ago")
        )->limit($limit)->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'position' => $position, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * Average returns grouped by industry/sector for a given period.
     * Sector rotation signal — see which sectors are leading and lagging.
     */
    public function sectorHeatmap(Request $request): JsonResponse
    {
        $exchange = strtolower($request->get('exchange', 'nse'));
        $period   = $request->get('period', '1m');
        $prefix   = $exchange === 'bse' ? 'bse' : 'nse';
        $date     = EquityPrice::max('traded_date');

        $validPeriods = ['1d','3d','7d','1m','3m','6m','9m','1y','3y'];
        if (!in_array($period, $validPeriods)) {
            return response()->json(['success' => false, 'message' => 'Invalid period. Valid: ' . implode(', ', $validPeriods)], 422);
        }

        $data = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $date)
            ->whereNotNull('e.industry')
            ->whereNotNull("p.{$prefix}_chg_{$period}")
            ->groupBy('e.industry')
            ->orderBy('avg_return', 'desc')
            ->select(
                'e.industry',
                DB::raw("ROUND(AVG(p.{$prefix}_chg_{$period}), 4) as avg_return"),
                DB::raw("COUNT(*) as stock_count"),
                DB::raw("SUM(CASE WHEN p.{$prefix}_chg_{$period} > 0 THEN 1 ELSE 0 END) as gainers"),
                DB::raw("SUM(CASE WHEN p.{$prefix}_chg_{$period} < 0 THEN 1 ELSE 0 END) as losers"),
                DB::raw("ROUND(MAX(p.{$prefix}_chg_{$period}), 4) as best_return"),
                DB::raw("ROUND(MIN(p.{$prefix}_chg_{$period}), 4) as worst_return")
            )
            ->get();

        return response()->json(['success' => true, 'exchange' => strtoupper($exchange), 'period' => $period, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * OHLC data for a specific equity on a specific date or date range.
     * GET /equity/{isin}/ohlc?date=YYYY-MM-DD  or  ?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function ohlc(Request $request, string $isin): JsonResponse
    {
        $equity = Equity::where('isin', $isin)->first();
        if (!$equity) return response()->json(['success' => false, 'message' => 'ISIN not found.'], 404);

        $query = DB::table('equity_prices as p')
            ->where('p.equity_id', $equity->id);

        if ($date = $request->get('date')) {
            $query->where('p.traded_date', $date);
        } elseif ($request->from && $request->to) {
            $query->whereBetween('p.traded_date', [$request->from, $request->to]);
        } else {
            $query->orderBy('p.traded_date', 'desc')->limit(1);
        }

        $data = $query->select(
            'p.traded_date',
            'p.nse_open', 'p.nse_high', 'p.nse_low', 'p.nse_close', 'p.nse_prev_close', 'p.nse_volume', 'p.nse_turnover',
            'p.bse_open', 'p.bse_high', 'p.bse_low', 'p.bse_close', 'p.bse_prev_close', 'p.bse_volume', 'p.bse_turnover',
            'p.spread', 'p.nse_gap_pct', 'p.nse_intraday_chg_pct', 'p.nse_range_pct'
        )->orderBy('p.traded_date')->get();

        return response()->json(['success' => true, 'isin' => $isin, 'company_name' => $equity->company_name, 'data' => $data]);
    }

    /**
     * NSE vs BSE side-by-side price comparison for the same stock.
     * Shows spread, price discovery differences, and which exchange leads.
     */
    public function dualExchange(Request $request, string $isin): JsonResponse
    {
        $equity = Equity::where('isin', $isin)->first();
        if (!$equity) return response()->json(['success' => false, 'message' => 'ISIN not found.'], 404);

        $days = min((int)$request->get('days', 30), 365);

        $data = DB::table('equity_prices as p')
            ->where('p.equity_id', $equity->id)
            ->whereNotNull('p.nse_close')
            ->whereNotNull('p.bse_close')
            ->where('p.nse_close', '>', 0)
            ->where('p.bse_close', '>', 0)
            ->orderBy('p.traded_date', 'desc')
            ->limit($days)
            ->select(
                'p.traded_date',
                'p.nse_open', 'p.nse_high', 'p.nse_low', 'p.nse_close', 'p.nse_volume', 'p.nse_trades',
                'p.bse_open', 'p.bse_high', 'p.bse_low', 'p.bse_close', 'p.bse_volume', 'p.bse_trades',
                'p.spread',
                DB::raw('ROUND((p.spread / p.nse_close) * 100, 4) as spread_pct'),
                DB::raw('CASE WHEN p.nse_close > p.bse_close THEN "NSE" WHEN p.bse_close > p.nse_close THEN "BSE" ELSE "Equal" END as price_leader')
            )
            ->get()->reverse()->values();

        return response()->json(['success' => true, 'isin' => $isin, 'company_name' => $equity->company_name, 'nse_symbol' => $equity->nse_symbol, 'bse_symbol' => $equity->bse_symbol, 'data' => $data]);
    }

    /**
     * Trading activity metrics trend — trades, turnover, avg ticket size over time.
     * Helps identify if institutional (large tickets) or retail (small tickets) are driving price.
     */
    public function activityMetrics(Request $request, string $isin): JsonResponse
    {
        $equity = Equity::where('isin', $isin)->first();
        if (!$equity) return response()->json(['success' => false, 'message' => 'ISIN not found.'], 404);

        $days = min((int)$request->get('days', 30), 365);

        $data = DB::table('equity_prices as p')
            ->where('p.equity_id', $equity->id)
            ->orderBy('p.traded_date', 'desc')
            ->limit($days)
            ->select(
                'p.traded_date',
                'p.nse_trades', 'p.nse_volume', 'p.nse_turnover', 'p.nse_avg_price', 'p.nse_avg_ticket_size',
                'p.bse_trades', 'p.bse_volume', 'p.bse_turnover', 'p.bse_avg_price', 'p.bse_avg_ticket_size'
            )
            ->get()->reverse()->values();

        return response()->json(['success' => true, 'isin' => $isin, 'company_name' => $equity->company_name, 'data' => $data]);
    }
}
