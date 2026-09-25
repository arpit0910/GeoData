<?php

use App\Http\Controllers\Api\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/india/pincode/{pincode}', [SetuGeoController::class, 'indiaPincode']);
Route::get('/pincode/{pincode}', [SetuGeoController::class, 'indiaPincode']);
