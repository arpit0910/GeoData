<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketApiController extends Controller
{
    /**
     * GET /market/snapshot
     * Cross-asset market dashboard — latest index levels, top equity movers, and top MF performers in one call.
     * Designed for dashboard widgets and home screens.
     */
    public function snapshot(Request $request): JsonResponse
    {
        // Latest indices
        $indexDate = DB::table('indices_prices')->max('traded_date');
        $indices = DB::table('indices_prices as p')
            ->join('indices as i', 'p.index_code', '=', 'i.index_code')
            ->where('p.traded_date', $indexDate)
            ->whereIn('i.index_code', ['NIFTY 50', 'NIFTY BANK', 'SENSEX', 'NIFTY IT', 'NIFTY MIDCAP 100'])
            ->select('i.index_code', 'i.index_name', 'i.exchange', 'p.close', 'p.change_percent', 'p.chg_1d', 'p.traded_date')
            ->orderBy('i.index_name')
            ->get();

        // Top 5 equity gainers and losers (NSE, today)
        $equityDate = DB::table('equity_prices')->max('traded_date');
        $topGainers = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $equityDate)
            ->whereNotNull('p.nse_chg_1d')
            ->orderBy('p.nse_chg_1d', 'desc')
            ->select('e.isin', 'e.company_name', 'e.nse_symbol', 'p.nse_close as close', 'p.nse_chg_1d as chg_1d', 'p.traded_date')
            ->limit(5)->get();

        $topLosers = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $equityDate)
            ->whereNotNull('p.nse_chg_1d')
            ->orderBy('p.nse_chg_1d', 'asc')
            ->select('e.isin', 'e.company_name', 'e.nse_symbol', 'p.nse_close as close', 'p.nse_chg_1d as chg_1d', 'p.traded_date')
            ->limit(5)->get();

        // Top 5 MF performers (1-day)
        $mfDate = DB::table('mutual_fund_prices')->max('nav_date');
        $topMfGainers = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $mfDate)
            ->whereNotNull('p.chg_1d')
            ->orderBy('p.chg_1d', 'desc')
            ->select('m.isin', 'm.scheme_name', 'm.category', 'p.nav', 'p.chg_1d', 'p.nav_date')
            ->limit(5)->get();

        return response()->json([
            'success' => true,
            'as_of' => [
                'indices'         => $indexDate,
                'equities'        => $equityDate,
                'mutual_funds'    => $mfDate,
            ],
            'indices'         => $indices,
            'equity_gainers'  => $topGainers,
            'equity_losers'   => $topLosers,
            'mf_top_gainers'  => $topMfGainers,
        ]);
    }

    /**
     * GET /market/heatmap
     * Sector-wise equity returns + MF category returns for any period.
     * Single response for portfolio managers to see where money is flowing.
     * Query: period=1m
     */
    public function heatmap(Request $request): JsonResponse
    {
        $validPeriods = ['1d','3d','7d','1m','3m','6m','9m','1y','3y'];
        $period = $request->get('period', '1m');
        if (!in_array($period, $validPeriods)) {
            return response()->json(['success' => false, 'message' => 'Invalid period. Valid: ' . implode(', ', $validPeriods)], 422);
        }

        $equityDate = DB::table('equity_prices')->max('traded_date');
        $mfDate     = DB::table('mutual_fund_prices')->max('nav_date');

        // Equity sector heatmap
        $equitySectors = DB::table('equity_prices as p')
            ->join('equities as e', 'p.equity_id', '=', 'e.id')
            ->where('p.traded_date', $equityDate)
            ->whereNotNull('e.industry')
            ->whereNotNull("p.nse_chg_{$period}")
            ->groupBy('e.industry')
            ->orderBy('avg_return', 'desc')
            ->select(
                'e.industry',
                DB::raw("ROUND(AVG(p.nse_chg_{$period}), 4) as avg_return"),
                DB::raw('COUNT(*) as stock_count'),
                DB::raw("SUM(CASE WHEN p.nse_chg_{$period} > 0 THEN 1 ELSE 0 END) as gainers"),
                DB::raw("SUM(CASE WHEN p.nse_chg_{$period} < 0 THEN 1 ELSE 0 END) as losers")
            )->get();

        // MF category heatmap
        $mfCategories = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $mfDate)
            ->whereNotNull('m.category')
            ->whereNotNull("p.chg_{$period}")
            ->groupBy('m.category')
            ->orderBy('avg_return', 'desc')
            ->select(
                'm.category',
                DB::raw("ROUND(AVG(p.chg_{$period}), 4) as avg_return"),
                DB::raw('COUNT(*) as fund_count'),
                DB::raw("SUM(CASE WHEN p.chg_{$period} > 0 THEN 1 ELSE 0 END) as positive_funds"),
                DB::raw("SUM(CASE WHEN p.chg_{$period} < 0 THEN 1 ELSE 0 END) as negative_funds")
            )->get();

        return response()->json([
            'success'          => true,
            'period'           => $period,
            'equity_date'      => $equityDate,
            'mf_date'          => $mfDate,
            'equity_sectors'   => $equitySectors,
            'mf_categories'    => $mfCategories,
        ]);
    }

    /**
     * GET /market/breadth
     * Advance-decline ratio for equities on the latest trading day.
     * Simple but powerful market sentiment signal — more advancers = bullish breadth.
     * Query: exchange=nse|bse, period=1d
     */
    public function breadth(Request $request): JsonResponse
    {
        $exchange = strtolower($request->get('exchange', 'nse'));
        $period   = $request->get('period', '1d');
        $validPeriods = ['1d','3d','7d','1m','3m','6m','9m','1y','3y'];
        if (!in_array($period, $validPeriods)) {
            return response()->json(['success' => false, 'message' => 'Invalid period.'], 422);
        }

        $col  = $exchange === 'bse' ? "bse_chg_{$period}" : "nse_chg_{$period}";
        $date = DB::table('equity_prices')->max('traded_date');

        $stats = DB::table('equity_prices')
            ->where('traded_date', $date)
            ->whereNotNull($col)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN {$col} > 0 THEN 1 ELSE 0 END) as advancers,
                SUM(CASE WHEN {$col} < 0 THEN 1 ELSE 0 END) as decliners,
                SUM(CASE WHEN {$col} = 0 THEN 1 ELSE 0 END) as unchanged,
                ROUND(AVG({$col}), 4) as avg_return,
                ROUND(MAX({$col}), 4) as best_return,
                ROUND(MIN({$col}), 4) as worst_return
            ")
            ->first();

        $adRatio = $stats->decliners > 0
            ? round($stats->advancers / $stats->decliners, 4)
            : null;

        $sentiment = match(true) {
            $adRatio === null       => 'neutral',
            $adRatio >= 2.0         => 'strongly_bullish',
            $adRatio >= 1.2         => 'bullish',
            $adRatio >= 0.8         => 'neutral',
            $adRatio >= 0.5         => 'bearish',
            default                 => 'strongly_bearish',
        };

        return response()->json([
            'success'          => true,
            'exchange'         => strtoupper($exchange),
            'period'           => $period,
            'traded_date'      => $date,
            'total_stocks'     => $stats->total,
            'advancers'        => $stats->advancers,
            'decliners'        => $stats->decliners,
            'unchanged'        => $stats->unchanged,
            'advance_decline_ratio' => $adRatio,
            'avg_return'       => $stats->avg_return,
            'best_return'      => $stats->best_return,
            'worst_return'     => $stats->worst_return,
            'sentiment'        => $sentiment,
        ]);
    }
}
