<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CountryController;

Route::get('/', function () {
    return view('dashboard');
});

Route::prefix('user')->name('user.')->group(function () {
        Route::get('/list', [UserController::class, 'index'])->name('list');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::get('/show/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/edit/{user}', [UserController::class, 'edit'])->name('edit');
        Route::put('/update/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/delete/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-status/{user}', [UserController::class, 'toggleStatus'])->name('toggle-status');
});

Route::prefix('countries')->name('countries.')->group(function () {
    Route::get('/', [CountryController::class, 'index'])->name('index');
    Route::get('/create', [CountryController::class, 'create'])->name('create');
    Route::post('/', [CountryController::class, 'store'])->name('store');
    Route::get('/{country}/edit', [CountryController::class, 'edit'])->name('edit');
    Route::put('/{country}', [CountryController::class, 'update'])->name('update');
    Route::delete('/{country}', [CountryController::class, 'destroy'])->name('destroy');
});

use App\Http\Controllers\RegionController;

Route::prefix('regions')->name('regions.')->group(function () {
    Route::get('/', [RegionController::class, 'index'])->name('index');
    Route::get('/create', [RegionController::class, 'create'])->name('create');
    Route::post('/', [RegionController::class, 'store'])->name('store');
    Route::get('/{region}/edit', [RegionController::class, 'edit'])->name('edit');
    Route::put('/{region}', [RegionController::class, 'update'])->name('update');
    Route::delete('/{region}', [RegionController::class, 'destroy'])->name('destroy');
});

use App\Http\Controllers\SubRegionController;

Route::prefix('subregions')->name('subregions.')->group(function () {
    Route::get('/', [SubRegionController::class, 'index'])->name('index');
    Route::get('/create', [SubRegionController::class, 'create'])->name('create');
    Route::post('/', [SubRegionController::class, 'store'])->name('store');
    Route::get('/{subregion}/edit', [SubRegionController::class, 'edit'])->name('edit');
    Route::put('/{subregion}', [SubRegionController::class, 'update'])->name('update');
    Route::delete('/{subregion}', [SubRegionController::class, 'destroy'])->name('destroy');
});

use App\Http\Controllers\TimezoneController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;

Route::resource('timezones', TimezoneController::class);
Route::resource('states', StateController::class);
Route::resource('cities', CityController::class);
