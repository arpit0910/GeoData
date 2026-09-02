<?php

use App\Http\Controllers\Api\V1\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/india/pincode/{pincode}', [SetuGeoController::class, 'indiaPincode']);
Route::get('/pincode/{pincode}', [SetuGeoController::class, 'indiaPincode']);
