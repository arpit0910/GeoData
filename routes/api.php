<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\SetuGeoController;
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

    // Credit-based SetuGeo & Analytical APIs
    Route::middleware(['auth:sanctum', 'api.credits'])->group(function() {
        // Core SetuGeo APIs
        Route::get('/regions', [SetuGeoController::class, 'regions']);
        Route::get('/sub-regions', [SetuGeoController::class, 'subregions']);
        Route::get('/timezones', [SetuGeoController::class, 'timezones']);
        Route::get('/countries', [SetuGeoController::class, 'countries']);
        Route::get('/states', [SetuGeoController::class, 'states']);
        Route::get('/cities', [SetuGeoController::class, 'cities']);
        Route::get('/pincodes', [SetuGeoController::class, 'pincodes']);
        Route::get('/pincodes/search', [SetuGeoController::class, 'pincodeSearch']);
        Route::get('/currency/exchange', [SetuGeoController::class, 'currencyExchange']);
        
        // Bank APIs
        Route::get('/banks', [SetuGeoController::class, 'banks']);
        Route::get('/banks/{bank}/branches', [SetuGeoController::class, 'bankBranches']);
        Route::get('/banks/{bank}/coverage', [SetuGeoController::class, 'bankCoverage']);
        Route::get('/branch/search', [SetuGeoController::class, 'branchSearch']);
        Route::get('/branch/{ifsc}', [SetuGeoController::class, 'branchInfo']);
        Route::get('/cities/{city}/banks', [SetuGeoController::class, 'banksInCity']);
        Route::get('/states/{state}/banks', [SetuGeoController::class, 'banksInState']);

        // Hierarchical Drill-Down APIs
        Route::get('/countries/compare', [SetuGeoController::class, 'countriesCompare']);
        Route::get('/countries/{country}', [SetuGeoController::class, 'countryDetail']);
        Route::get('/countries/{country}/states', [SetuGeoController::class, 'countryStates']);
        Route::get('/countries/{country}/cities', [SetuGeoController::class, 'countryCities']);
        Route::get('/countries/{country}/timezones', [SetuGeoController::class, 'countryTimezones']);
        Route::get('/countries/{country}/banks', [SetuGeoController::class, 'countryBanks']);
        Route::get('/countries/{country}/neighbors', [SetuGeoController::class, 'countryNeighbors']);
        
        Route::get('/states/{state}', [SetuGeoController::class, 'stateDetail']);
        Route::get('/states/{state}/cities', [SetuGeoController::class, 'stateCities']);
        
        Route::get('/cities/{city}', [SetuGeoController::class, 'cityDetail']);
        Route::get('/pincodes/{pincode}/banks', [SetuGeoController::class, 'pincodeBanks']);

        // Address & Currency Utility APIs
        Route::get('/currency/convert', [SetuGeoController::class, 'currencyConvert']);
        Route::get('/address/validate', [SetuGeoController::class, 'addressValidate']);
        Route::get('/address/autocomplete', [SetuGeoController::class, 'addressAutocomplete']);
        Route::get('/timezones/convert', [SetuGeoController::class, 'timezoneConvert']);
        
        Route::get('/user/usage', [SetuGeoController::class, 'usage']);

        // Analytical Geospatial Utilities
        Route::get('/geospatial/distance', [GeoAnalysisController::class, 'distance']);
        Route::get('/geospatial/nearby', [GeoAnalysisController::class, 'nearby']);
        Route::get('/geospatial/geocode', [GeoAnalysisController::class, 'geocode']);
        Route::get('/geospatial/boundary', [GeoAnalysisController::class, 'boundary']);
        Route::get('/geospatial/cluster', [GeoAnalysisController::class, 'cluster']);

        Route::fallback(function() {
            return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
        });
    });
});
