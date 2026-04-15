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
    public function snapshot(): JsonResponse
    {
        try {
            $data = $this->service->getSnapshot();
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
