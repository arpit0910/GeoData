<?php

use App\Http\Controllers\Api\V1\EquityApiController;
use App\Http\Controllers\Api\V1\IndexApiController;
use App\Http\Controllers\Api\V1\MarketApiController;
use App\Http\Controllers\Api\V1\MfApiController;
use Illuminate\Support\Facades\Route;

Route::get('/equities', [EquityApiController::class, 'index']);
Route::get('/equities/search', [EquityApiController::class, 'search']);
Route::get('/equities/filter/market-cap/{cap}', [EquityApiController::class, 'byMarketCap']);

Route::get('/equities/analysis/top-gainers', [EquityApiController::class, 'topGainers']);
Route::get('/equities/analysis/top-losers', [EquityApiController::class, 'topLosers']);
Route::get('/equities/analysis/top-turnover', [EquityApiController::class, 'topTurnover']);
Route::get('/equities/analysis/high-volume', [EquityApiController::class, 'highVolume']);
Route::get('/equities/analysis/new-listings', [EquityApiController::class, 'newListings']);
Route::get('/equities/analysis/market-cap-stats', [EquityApiController::class, 'marketCapDistribution']);
Route::get('/equities/analysis/gap-movers', [EquityApiController::class, 'gapMovers']);
Route::get('/equities/analysis/intraday-movers', [EquityApiController::class, 'intradayMovers']);
Route::get('/equities/analysis/wide-range-stocks', [EquityApiController::class, 'wideRangeStocks']);
Route::get('/equities/analysis/high-activity', [EquityApiController::class, 'highActivity']);
Route::get('/equities/analysis/nse-bse-spread', [EquityApiController::class, 'nseBseSpread']);
Route::get('/equities/analysis/consistent-performers', [EquityApiController::class, 'consistentPerformers']);
Route::get('/equities/analysis/52-week-extremes', [EquityApiController::class, 'weekExtremes']);
Route::get('/equities/analysis/sector-heatmap', [EquityApiController::class, 'sectorHeatmap']);

Route::get('/equity/{isin}', [EquityApiController::class, 'show']);
Route::get('/equity/{isin}/peers', [EquityApiController::class, 'peers']);
Route::get('/equity/{isin}/history', [EquityApiController::class, 'history']);
Route::get('/equity/{isin}/metrics', [EquityApiController::class, 'metrics']);
Route::get('/equity/{isin}/ohlc', [EquityApiController::class, 'ohlc']);
Route::get('/equity/{isin}/dual-exchange', [EquityApiController::class, 'dualExchange']);
Route::get('/equity/{isin}/activity-metrics', [EquityApiController::class, 'activityMetrics']);

Route::get('/indices/snapshot', [IndexApiController::class, 'snapshot']);
Route::get('/indices/search', [IndexApiController::class, 'search']);
Route::get('/indices/analysis/top-gainers', [IndexApiController::class, 'topGainers']);
Route::get('/indices/analysis/top-losers', [IndexApiController::class, 'topLosers']);
Route::get('/indices/analysis/valuation-comparison', [IndexApiController::class, 'valuationComparison']);
Route::get('/indices/analysis/ohlc-summary', [IndexApiController::class, 'ohlcSummary']);
Route::get('/indices/{index_code}/metrics', [IndexApiController::class, 'metrics']);
Route::get('/indices/{index_code}/history', [IndexApiController::class, 'history']);
Route::get('/indices/{index_code}/valuation', [IndexApiController::class, 'valuation']);
Route::get('/indices/{index_code}/valuation-history', [IndexApiController::class, 'valuationHistory']);

Route::get('/mf/list', [MfApiController::class, 'list']);
Route::get('/mf/search', [MfApiController::class, 'search']);
Route::get('/mf/filters', [MfApiController::class, 'filters']);
Route::get('/mf/compare', [MfApiController::class, 'compare']);
Route::get('/mf/analysis/top-gainers', [MfApiController::class, 'topGainers']);
Route::get('/mf/analysis/top-losers', [MfApiController::class, 'topLosers']);
Route::get('/mf/analysis/category-returns', [MfApiController::class, 'categoryReturns']);
Route::get('/mf/analysis/amc-performance', [MfApiController::class, 'amcPerformance']);
Route::get('/mf/analysis/consistent-performers', [MfApiController::class, 'consistentPerformers']);
Route::get('/mf/details/{isin}', [MfApiController::class, 'details']);
Route::get('/mf/history/{isin}', [MfApiController::class, 'history']);
Route::get('/mf/{isin}/similar-funds', [MfApiController::class, 'similarFunds']);

Route::get('/market/snapshot', [MarketApiController::class, 'snapshot']);
Route::get('/market/heatmap', [MarketApiController::class, 'heatmap']);
Route::get('/market/breadth', [MarketApiController::class, 'breadth']);
