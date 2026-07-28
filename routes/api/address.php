<?php

use App\Http\Controllers\Api\V1\GeoAnalysisController;
use App\Http\Controllers\Api\V1\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/regions', [SetuGeoController::class, 'regions']);
Route::get('/sub-regions', [SetuGeoController::class, 'subregions']);
Route::get('/timezones', [SetuGeoController::class, 'timezones']);
Route::get('/countries', [SetuGeoController::class, 'countries']);
Route::get('/states', [SetuGeoController::class, 'states']);
Route::get('/cities', [SetuGeoController::class, 'cities']);
Route::get('/pincodes', [SetuGeoController::class, 'pincodes']);
Route::get('/pincodes/search', [SetuGeoController::class, 'pincodeSearch']);

Route::get('/countries/compare', [SetuGeoController::class, 'countriesCompare']);
Route::get('/countries/economic-profile', [SetuGeoController::class, 'economicProfile']);
Route::get('/countries/tax-data', [SetuGeoController::class, 'taxData']);
Route::get('/countries/analysis/regional-gdp', [SetuGeoController::class, 'regionalGdp']);
Route::get('/country/{country}', [SetuGeoController::class, 'countryDetail']);
Route::get('/countries/{country}', [SetuGeoController::class, 'countryDetail']);
Route::get('/country/{country}/states', [SetuGeoController::class, 'countryStates']);
Route::get('/countries/{country}/states', [SetuGeoController::class, 'countryStates']);
Route::get('/country/{country}/cities', [SetuGeoController::class, 'countryCities']);
Route::get('/countries/{country}/cities', [SetuGeoController::class, 'countryCities']);
Route::get('/country/{country}/timezones', [SetuGeoController::class, 'countryTimezones']);
Route::get('/countries/{country}/timezones', [SetuGeoController::class, 'countryTimezones']);
Route::get('/country/{country}/neighbors', [SetuGeoController::class, 'countryNeighbors']);
Route::get('/countries/{country}/neighbors', [SetuGeoController::class, 'countryNeighbors']);

Route::get('/state/{state}', [SetuGeoController::class, 'stateDetail']);
Route::get('/states/{state}', [SetuGeoController::class, 'stateDetail']);
Route::get('/state/{state}/cities', [SetuGeoController::class, 'stateCities']);
Route::get('/states/{state}/cities', [SetuGeoController::class, 'stateCities']);
Route::get('/city/{city}', [SetuGeoController::class, 'cityDetail']);
Route::get('/cities/{city}', [SetuGeoController::class, 'cityDetail']);

Route::get('/address/validate', [SetuGeoController::class, 'addressValidate']);
Route::get('/address/autocomplete', [SetuGeoController::class, 'addressAutocomplete']);
Route::get('/timezone/convert', [SetuGeoController::class, 'timezoneConvert']);
Route::get('/timezones/convert', [SetuGeoController::class, 'timezoneConvert']);

Route::get('/geospatial/distance', [GeoAnalysisController::class, 'distance']);
Route::get('/geospatial/nearby', [GeoAnalysisController::class, 'nearby']);
Route::get('/geospatial/geocode', [GeoAnalysisController::class, 'geocode']);
Route::get('/geospatial/boundary', [GeoAnalysisController::class, 'boundary']);
Route::get('/geospatial/cluster', [GeoAnalysisController::class, 'cluster']);
Route::get('/country/{country}/economic-summary', [SetuGeoController::class, 'economicSummary']);
