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
     * List all available equities.
     */
    public function index(Request $request)
    {
        $equities = Equity::where('is_active', true)
            ->select('isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => $equities
        ]);
    }

    /**
     * Get detailed information for a specific ISIN.
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
     * Get stock performance metrics for a specific ISIN.
     *
     * @param GetMetricsRequest $request
     * @param string $isin
     * @param EquityService $service
     * @return EquityPriceResource|JsonResponse
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
