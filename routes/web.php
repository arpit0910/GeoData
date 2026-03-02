<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return view('admin.dashboard');
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
