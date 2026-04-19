<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexApiController extends Controller
{
    private IndexService $service;

    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    /**
     * Get latest market snapshot for all indices.
     */
    public function snapshot(Request $request): JsonResponse
    {
        try {
            $exchange = $request->get('exchange');
            $data = $this->service->getSnapshot($exchange);
            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            Log::error("Index snapshot failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Search indices by name or code.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q');
            if (!$query) return response()->json(['success' => false, 'message' => 'Query parameter is required'], 400);

            $data = $this->service->searchIndices($query);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Index search failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get top gainers for a specific period.
     */
    public function topGainers(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '1d');
            $limit = $request->get('limit', 10);
            $data = $this->service->getTopMovers($period, 'desc', $limit);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Index top gainers failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get top losers for a specific period.
     */
    public function topLosers(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '1d');
            $limit = $request->get('limit', 10);
            $data = $this->service->getTopMovers($period, 'asc', $limit);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Index top losers failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get performance metrics for a specific index.
     */
    public function metrics(string $code): JsonResponse
    {
        try {
            $data = $this->service->getPerformance(str_replace('_', ' ', $code));
            if (empty($data)) return response()->json(['success' => false, 'message' => 'Index not found'], 404);
            
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Index metrics failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get historical data for charting.
     */
    public function history(Request $request, string $code): JsonResponse
    {
        try {
            $start = $request->get('start_date');
            $end = $request->get('end_date');

            $data = $this->service->getHistory(str_replace('_', ' ', $code), $start, $end);

            return response()->json([
                'success' => true,
                'index_code' => $code,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            Log::error("Index history failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Latest PE ratio, PB ratio, and dividend yield for a specific index.
     * Premium valuation data — tells you if the index is cheap or expensive vs history.
     */
    public function valuation(Request $request, string $code): JsonResponse
    {
        $indexCode = str_replace('_', ' ', $code);
        $exists = DB::table('indices')->where('index_code', $indexCode)->exists();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Index not found.'], 404);

        $date = $request->get('date') ?: DB::table('indices_prices')->where('index_code', $indexCode)->max('traded_date');

        $row = DB::table('indices_prices')
            ->where('index_code', $indexCode)
            ->where('traded_date', $date)
            ->select('traded_date', 'close', 'pe_ratio', 'pb_ratio', 'div_yield')
            ->first();

        if (!$row) return response()->json(['success' => false, 'message' => 'No valuation data for this date.'], 404);

        return response()->json(['success' => true, 'index_code' => $indexCode, 'data' => $row]);
    }

    /**
     * PE ratio, PB ratio, and dividend yield trend over time for an index.
     * Use this to spot valuation extremes and mean-reversion opportunities.
     */
    public function valuationHistory(Request $request, string $code): JsonResponse
    {
        $indexCode = str_replace('_', ' ', $code);
        $exists = DB::table('indices')->where('index_code', $indexCode)->exists();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Index not found.'], 404);

        $months = min((int)($request->get('months', 12)), 120);
        $from   = $request->get('from') ?: now()->subMonths($months)->format('Y-m-d');
        $to     = $request->get('to')   ?: now()->format('Y-m-d');

        $data = DB::table('indices_prices')
            ->where('index_code', $indexCode)
            ->whereBetween('traded_date', [$from, $to])
            ->whereNotNull('pe_ratio')
            ->orderBy('traded_date')
            ->select('traded_date', 'close', 'pe_ratio', 'pb_ratio', 'div_yield')
            ->get();

        return response()->json(['success' => true, 'index_code' => $indexCode, 'from' => $from, 'to' => $to, 'count' => $data->count(), 'data' => $data]);
    }

    /**
     * Compare PE ratio, PB ratio, and dividend yield across all indices on the latest date.
     * Single call to see which indices are cheap or expensive relative to each other.
     */
    public function valuationComparison(Request $request): JsonResponse
    {
        $exchange = $request->get('exchange');
        $date     = DB::table('indices_prices')->max('traded_date');

        $query = DB::table('indices_prices as p')
            ->join('indices as i', 'p.index_code', '=', 'i.index_code')
            ->where('p.traded_date', $date)
            ->whereNotNull('p.pe_ratio');

        if ($exchange) $query->where('i.exchange', strtoupper($exchange));

        $data = $query->select(
            'i.index_code', 'i.index_name', 'i.exchange', 'i.category',
            'p.close', 'p.pe_ratio', 'p.pb_ratio', 'p.div_yield',
            'p.chg_1d', 'p.chg_1m', 'p.chg_1y'
        )->orderBy('p.pe_ratio', 'asc')->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'data' => $data]);
    }

    /**
     * OHLC summary for all indices on the latest trading day.
     * One call to get opening, high, low, close, gap, and intraday move for every index.
     */
    public function ohlcSummary(Request $request): JsonResponse
    {
        $exchange = $request->get('exchange');
        $date     = $request->get('date') ?: DB::table('indices_prices')->max('traded_date');

        $query = DB::table('indices_prices as p')
            ->join('indices as i', 'p.index_code', '=', 'i.index_code')
            ->where('p.traded_date', $date);

        if ($exchange) $query->where('i.exchange', strtoupper($exchange));

        $data = $query->select(
            'i.index_code', 'i.index_name', 'i.exchange', 'i.category',
            'p.traded_date', 'p.open', 'p.high', 'p.low', 'p.close', 'p.prev_close',
            'p.change_percent', 'p.volume', 'p.turnover',
            'p.gap_pct', 'p.intraday_chg_pct', 'p.range_pct'
        )->orderBy('i.index_name')->get();

        return response()->json(['success' => true, 'traded_date' => $date, 'data' => $data]);
    }
}
