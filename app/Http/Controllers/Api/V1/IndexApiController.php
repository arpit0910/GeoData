<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
}
