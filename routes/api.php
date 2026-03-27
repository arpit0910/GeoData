<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\GeoDataController;
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

    Route::middleware(['auth:sanctum', 'api.credits'])->group(function() {
        Route::get('/region/list', [GeoDataController::class, 'regions']);
        Route::get('/subregion/list', [GeoDataController::class, 'subregions']);
        Route::get('/timezone/list', [GeoDataController::class, 'timezones']);
        Route::get('/country/list', [GeoDataController::class, 'countries']);
        Route::get('/state/list', [GeoDataController::class, 'states']);
        Route::get('/city/list', [GeoDataController::class, 'cities']);
        Route::get('/pincode/list', [GeoDataController::class, 'pincodes']);
        Route::get('/pincode/search', [GeoDataController::class, 'pincodeSearch']);
        Route::get('/user/usage', [GeoDataController::class, 'usage']);

        Route::fallback(function() {
            return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
        });
    });
});
