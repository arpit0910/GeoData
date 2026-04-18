<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\SetuGeoController;
use App\Http\Controllers\Api\V1\GeoAnalysisController;
use App\Http\Controllers\Api\V1\EquityApiController;
use App\Http\Controllers\Api\V1\IndexApiController;
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
        Route::get('/bank/{bank}/branches', [SetuGeoController::class, 'bankBranches']);
        Route::get('/bank/{bank}/coverage', [SetuGeoController::class, 'bankCoverage']);
        Route::get('/bank/branches/search', [SetuGeoController::class, 'branchSearch']);
        Route::get('/bank/ifsc/{ifsc}', [SetuGeoController::class, 'branchInfo']);
        Route::get('/city/{city}/banks', [SetuGeoController::class, 'banksInCity']);
        Route::get('/state/{state}/banks', [SetuGeoController::class, 'banksInState']);

        // Hierarchical Drill-Down APIs
        Route::get('/countries/compare', [SetuGeoController::class, 'countriesCompare']);
        Route::get('/country/{country}', [SetuGeoController::class, 'countryDetail']);
        Route::get('/country/{country}/states', [SetuGeoController::class, 'countryStates']);
        Route::get('/country/{country}/cities', [SetuGeoController::class, 'countryCities']);
        Route::get('/country/{country}/timezones', [SetuGeoController::class, 'countryTimezones']);
        Route::get('/country/{country}/banks', [SetuGeoController::class, 'countryBanks']);
        Route::get('/country/{country}/neighbors', [SetuGeoController::class, 'countryNeighbors']);
        
        Route::get('/state/{state}', [SetuGeoController::class, 'stateDetail']);
        Route::get('/state/{state}/cities', [SetuGeoController::class, 'stateCities']);
        
        Route::get('/city/{city}', [SetuGeoController::class, 'cityDetail']);
        Route::get('/pincode/{pincode}/banks', [SetuGeoController::class, 'pincodeBanks']);

        // Address & Currency Utility APIs
        Route::get('/currency/convert', [SetuGeoController::class, 'currencyConvert']);
        Route::get('/address/validate', [SetuGeoController::class, 'addressValidate']);
        Route::get('/address/autocomplete', [SetuGeoController::class, 'addressAutocomplete']);
        Route::get('/timezone/convert', [SetuGeoController::class, 'timezoneConvert']);
        
        Route::get('/user/usage', [SetuGeoController::class, 'usage']);

        // Analytical Geospatial Utilities
        Route::get('/geospatial/distance', [GeoAnalysisController::class, 'distance']);
        Route::get('/geospatial/nearby', [GeoAnalysisController::class, 'nearby']);
        Route::get('/geospatial/geocode', [GeoAnalysisController::class, 'geocode']);
        Route::get('/geospatial/boundary', [GeoAnalysisController::class, 'boundary']);
        Route::get('/geospatial/cluster', [GeoAnalysisController::class, 'cluster']);

        // Equity Analytical APIs
        Route::get('/equities', [EquityApiController::class, 'index']);
        Route::get('/equities/search', [EquityApiController::class, 'search']);
        Route::get('/equities/analysis/top-gainers', [EquityApiController::class, 'topGainers']);
        Route::get('/equities/analysis/top-losers', [EquityApiController::class, 'topLosers']);
        Route::get('/equities/analysis/top-turnover', [EquityApiController::class, 'topTurnover']);
        Route::get('/equities/analysis/high-volume', [EquityApiController::class, 'highVolume']);
        Route::get('/equities/analysis/new-listings', [EquityApiController::class, 'newListings']);
        Route::get('/equities/analysis/market-cap-stats', [EquityApiController::class, 'marketCapDistribution']);
        Route::get('/equities/filter/market-cap/{cap}', [EquityApiController::class, 'byMarketCap']);
        Route::get('/equity/{isin}', [EquityApiController::class, 'show']);
        Route::get('/equity/{isin}/peers', [EquityApiController::class, 'peers']);
        Route::get('/equity/{isin}/history', [EquityApiController::class, 'history']);
        Route::get('/equity/{isin}/metrics', [EquityApiController::class, 'metrics']);

        // Index Analytical APIs
        Route::get('/indices/snapshot', [IndexApiController::class, 'snapshot']);
        Route::get('/indices/search', [IndexApiController::class, 'search']);
        Route::get('/indices/analysis/top-gainers', [IndexApiController::class, 'topGainers']);
        Route::get('/indices/analysis/top-losers', [IndexApiController::class, 'topLosers']);
        Route::get('/indices/{index_code}/metrics', [IndexApiController::class, 'metrics']);
        Route::get('/indices/{index_code}/history', [IndexApiController::class, 'history']);

        Route::fallback(function() {
            return response()->json(['success' => false, 'message' => 'API Endpoint not found.'], 404);
        });
    });
});
