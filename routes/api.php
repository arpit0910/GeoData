<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarketDataController;
use App\Http\Controllers\Api\V1\GeoAnalysisController;
use App\Http\Controllers\Api\V1\OcrController;
use App\Http\Controllers\Api\V1\SetuGeoController;
use App\Http\Controllers\Api\V1\UpstoxController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [AuthController::class, 'token']);
    Route::post('/webhooks/razorpay', [SubscriptionController::class, 'handleWebhook'])->name('api.razorpay.webhook');
    Route::get('/upstox/callback', [UpstoxController::class, 'callback'])->name('api.upstox.callback');

    Route::get('/ocr/health', [OcrController::class, 'health']);
    Route::prefix('market')->group(function () {
        Route::get('/stocks', [MarketDataController::class, 'stocks']);
        Route::get('/mutual-funds', [MarketDataController::class, 'mutualFunds']);
        Route::get('/mf', [MarketDataController::class, 'mutualFunds']);
        Route::get('/isin/{isin}', [MarketDataController::class, 'byIsin']);
        Route::get('/equity/{isin}', [MarketDataController::class, 'byIsin']);
        Route::post('/sync', [MarketDataController::class, 'sync']);
        Route::get('/indices', [MarketDataController::class, 'indices']);
        Route::get('/quote/{symbol}', [MarketDataController::class, 'quote']);
        Route::get('/news', [MarketDataController::class, 'news']);
        Route::get('/corporate-actions', [MarketDataController::class, 'corporateActions']);
        Route::get('/splits', [MarketDataController::class, 'splits']);
        Route::get('/bonuses', [MarketDataController::class, 'bonuses']);
        Route::get('/dividends', [MarketDataController::class, 'dividends']);
    });

    Route::middleware(['auth:sanctum', 'subscription', 'api.credits'])->group(function () {
        Route::post('/ocr/extract', [OcrController::class, 'extract']);
        Route::get('/user/usage', [SetuGeoController::class, 'usage']);
        Route::get('/user/usage-breakdown', [SetuGeoController::class, 'usageBreakdown']);
        Route::get('/user/usage-history', [SetuGeoController::class, 'usageHistory']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/geospatial/statistics', [GeoAnalysisController::class, 'stats']);
    });

    Route::middleware(['auth:sanctum', 'subscription:address_api', 'api.credits'])
        ->group(base_path('routes/api/address.php'));

    Route::middleware(['auth:sanctum', 'subscription:banking_currency_api', 'api.credits'])
        ->group(base_path('routes/api/banking-currency.php'));

    Route::middleware(['auth:sanctum', 'subscription:ifsc_api', 'api.credits'])
        ->group(base_path('routes/api/ifsc.php'));

    Route::middleware(['auth:sanctum', 'subscription:india_pincode_api', 'api.credits'])
        ->group(base_path('routes/api/india-pincode.php'));

    Route::middleware(['auth:sanctum', 'subscription:stocks_mutual_funds_api', 'api.credits'])
        ->group(base_path('routes/api/stocks-mutual-funds.php'));

    Route::fallback(function () {
        return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
    });
});
