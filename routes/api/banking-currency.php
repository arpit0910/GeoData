<?php

use App\Http\Controllers\Api\V1\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/currency/exchange', [SetuGeoController::class, 'currencyExchange']);
Route::get('/currency/convert', [SetuGeoController::class, 'currencyConvert']);

Route::get('/banks', [SetuGeoController::class, 'banks']);
Route::get('/banks/{bank}/branches', [SetuGeoController::class, 'bankBranches']);
Route::get('/banks/{bank}/coverage', [SetuGeoController::class, 'bankCoverage']);
Route::get('/bank/{bank}/branches', [SetuGeoController::class, 'bankBranches']);
Route::get('/bank/{bank}/coverage', [SetuGeoController::class, 'bankCoverage']);
Route::get('/branch/search', [SetuGeoController::class, 'branchSearch']);
Route::get('/bank/branches/search', [SetuGeoController::class, 'branchSearch']);
Route::get('/cities/{city}/banks', [SetuGeoController::class, 'banksInCity']);
Route::get('/city/{city}/banks', [SetuGeoController::class, 'banksInCity']);
Route::get('/states/{state}/banks', [SetuGeoController::class, 'banksInState']);
Route::get('/state/{state}/banks', [SetuGeoController::class, 'banksInState']);
Route::get('/countries/{country}/banks', [SetuGeoController::class, 'countryBanks']);
Route::get('/country/{country}/banks', [SetuGeoController::class, 'countryBanks']);
Route::get('/pincodes/{pincode}/banks', [SetuGeoController::class, 'pincodeBanks']);
Route::get('/pincode/{pincode}/banks', [SetuGeoController::class, 'pincodeBanks']);

Route::get('/banks/digital-coverage', [SetuGeoController::class, 'bankDigitalCoverage']);
Route::get('/bank/{bank}/swift-branches', [SetuGeoController::class, 'swiftBranches']);
