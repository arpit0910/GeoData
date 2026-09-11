<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketDataController extends Controller
{
    public function __construct(private readonly MarketDataService $marketDataService)
    {
    }

    public function equityQuote(string $isin): JsonResponse
    {
        $isin = strtoupper(trim($isin));
        if (!preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)) {
            return response()->json(['success' => false, 'message' => 'Invalid ISIN.'], 422);
        }
        $quotes = app(\App\Services\EquityQuoteService::class)->latest($isin);
        return response()->json([
            'success' => $quotes !== [],
            'isin' => $isin,
            'data' => $quotes,
        ], $quotes === [] ? 404 : 200);
    }

    /**
     * GET /api/v1/market/stocks?symbols=AAPL,MSFT,TSLA
     */
    public function stocks(Request $request): JsonResponse
    {
        $symbols = $this->marketDataService->normalizeSymbols(
            $request->query('symbols'),
            ['AAPL', 'MSFT', 'TSLA', 'AMZN', 'GOOGL']
        );

        return response()->json([
            'success' => true,
            'type' => 'stocks',
            'symbols' => $symbols,
            'data' => $this->marketDataService->getQuotes($symbols),
        ]);
    }

    /**
     * GET /api/v1/market/indices?symbols=^GSPC,^DJI,^IXIC
     */
    public function indices(Request $request): JsonResponse
    {
        $symbols = $this->marketDataService->normalizeSymbols(
            $request->query('symbols'),
            ['^GSPC', '^DJI', '^IXIC']
        );

        return response()->json([
            'success' => true,
            'type' => 'indices',
            'symbols' => $symbols,
            'data' => $this->marketDataService->getQuotes($symbols),
        ]);
    }

    /**
     * GET /api/v1/market/quote/{symbol}
     */
    public function quote(string $symbol): JsonResponse
    {
        $symbol = trim($symbol);

        return response()->json([
            'success' => true,
            'symbol' => strtoupper($symbol),
            'data' => $this->marketDataService->getQuote($symbol),
        ]);
    }
}
