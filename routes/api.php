<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\GeoDataController;
use App\Http\Controllers\Api\V1\GeoAnalysisController;
use App\Http\Controllers\SubscriptionController;

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

Route::prefix('v1')->group(function() {
    
    Route::post('/auth/token', [AuthController::class, 'token']);
    Route::post('/webhooks/razorpay', [SubscriptionController::class, 'handleWebhook'])->name('api.razorpay.webhook');

    // Free Analytical & System APIs (No credit deduction)
    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/geospatial/statistics', [GeoAnalysisController::class, 'stats']);
    });

    // Credit-based GeoData & Analytical APIs
    Route::middleware(['auth:sanctum', 'api.credits'])->group(function() {
        // Core GeoData
        Route::get('/regions', [GeoDataController::class, 'regions']);
        Route::get('/sub-regions', [GeoDataController::class, 'subregions']);
        Route::get('/timezones', [GeoDataController::class, 'timezones']);
        Route::get('/countries', [GeoDataController::class, 'countries']);
        Route::get('/states', [GeoDataController::class, 'states']);
        Route::get('/cities', [GeoDataController::class, 'cities']);
        Route::get('/pincodes', [GeoDataController::class, 'pincodes']);
        Route::get('/pincodes/search', [GeoDataController::class, 'pincodeSearch']);
        Route::get('/user/usage', [GeoDataController::class, 'usage']);

        // Analytical Geospatial Utilities
        Route::get('/geospatial/distance', [GeoAnalysisController::class, 'distance']);
        Route::get('/geospatial/nearby', [GeoAnalysisController::class, 'nearby']);

        Route::fallback(function() {
            return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
        });
    });
});
