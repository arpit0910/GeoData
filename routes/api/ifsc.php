<?php

use App\Http\Controllers\Api\SetuGeoController;
use Illuminate\Support\Facades\Route;

Route::get('/branch/{ifsc}', [SetuGeoController::class, 'branchInfo']);
Route::get('/bank/ifsc/{ifsc}', [SetuGeoController::class, 'branchInfo']);
