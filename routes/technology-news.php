<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TechnologyNewsController;
use App\Http\Controllers\Admin\TechnologyNewsController as AdminNews;

Route::get('/technology-news', [TechnologyNewsController::class, 'index'])->name('technology-news.index')->middleware('throttle:60,1,technology-news-public');
Route::get('/technology-news/{slug}', [TechnologyNewsController::class, 'show'])->name('technology-news.show')->middleware('throttle:60,1,technology-news-public');

Route::middleware(['auth', 'throttle:30,1,technology-news-admin'])->prefix('admin/technology-news')->name('admin.technology-news.')->group(function () {
    Route::get('/', [AdminNews::class, 'index'])->name('index');
    Route::get('/sources', [AdminNews::class, 'manageSources'])->name('sources.index');
    Route::get('/sync-logs', [AdminNews::class, 'syncLogs'])->name('logs');
    Route::get('/duplicates', [AdminNews::class, 'duplicateCandidates'])->name('duplicates.index');
    Route::get('/{id}', [AdminNews::class, 'show'])->name('show')->whereNumber('id');
    Route::post('/{id}/regenerate', [AdminNews::class, 'regenerate'])->name('regenerate')->whereNumber('id')->middleware('throttle:3,1,technology-news-generation');
    Route::post('/sync', [AdminNews::class, 'sync'])->name('sync')->middleware('throttle:3,1,technology-news-sync');
    Route::post('/sources', [AdminNews::class, 'storeSource'])->name('sources.store');
    Route::patch('/sources/{id}', [AdminNews::class, 'updateSource'])->name('sources.update')->whereNumber('id');
    Route::post('/sources/{id}/sync', [AdminNews::class, 'sync'])->name('sources.sync')->whereNumber('id')->middleware('throttle:3,1,technology-news-sync');
    Route::patch('/articles/{id}', [AdminNews::class, 'updateArticle'])->name('articles.update')->whereNumber('id');
    Route::post('/duplicates/{id}', [AdminNews::class, 'duplicate'])->name('duplicates')->whereNumber('id');
});
