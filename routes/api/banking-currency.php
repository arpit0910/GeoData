<?php

use App\Http\Controllers\Api\V1\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/currency/exchange', [SetuGeoController::class, 'currencyExchange']);
Route::get('/currency/convert', [SetuGeoController::class, 'currencyConvert']);

Route::get('/banks', [SetuGeoController::class, 'banks']);
Route::get('/bank/{bank}/branches', [SetuGeoController::class, 'bankBranches']);
Route::get('/bank/{bank}/coverage', [SetuGeoController::class, 'bankCoverage']);
Route::get('/bank/branches/search', [SetuGeoController::class, 'branchSearch']);
Route::get('/bank/ifsc/{ifsc}', [SetuGeoController::class, 'branchInfo']);
Route::get('/city/{city}/banks', [SetuGeoController::class, 'banksInCity']);
Route::get('/state/{state}/banks', [SetuGeoController::class, 'banksInState']);
Route::get('/country/{country}/banks', [SetuGeoController::class, 'countryBanks']);
Route::get('/pincode/{pincode}/banks', [SetuGeoController::class, 'pincodeBanks']);

Route::get('/banks/digital-coverage', [SetuGeoController::class, 'bankDigitalCoverage']);
Route::get('/bank/{bank}/swift-branches', [SetuGeoController::class, 'swiftBranches']);
