<?php

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

Route::post('/v1/auth/token', [App\Http\Controllers\Api\AuthController::class, 'token']);

Route::middleware(['auth:sanctum', 'api.credits'])->prefix('v1')->group(function() {
    Route::get('/region/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'regions']);
    Route::get('/subregion/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'subregions']);
    Route::get('/timezone/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'timezones']);
    Route::get('/country/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'countries']);
    Route::get('/state/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'states']);
    Route::get('/city/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'cities']);
    Route::get('/pincode/list', [App\Http\Controllers\Api\V1\GeoDataController::class, 'pincodes']);
    Route::get('/pincode/search', [App\Http\Controllers\Api\V1\GeoDataController::class, 'pincodeSearch']);
    Route::get('/user/usage', [App\Http\Controllers\Api\V1\GeoDataController::class, 'usage']);

    // Fallback for logging non-existent API endpoints under v1
    Route::fallback(function() {
        return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
    });
});

Route::post('/webhooks/razorpay', [App\Http\Controllers\SubscriptionController::class, 'handleWebhook']);
