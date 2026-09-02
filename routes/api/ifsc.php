<?php

use App\Http\Controllers\Api\V1\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/branch/{ifsc}', [SetuGeoController::class, 'branchInfo']);
Route::get('/bank/ifsc/{ifsc}', [SetuGeoController::class, 'branchInfo']);
