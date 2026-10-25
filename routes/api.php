<?php

use App\Http\Controllers\Api\VirtualTryOnController;
use Illuminate\Support\Facades\Route;

Route::prefix('virtual-try-on')
    ->as('api.virtual-try-on.')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('/bootstrap', [VirtualTryOnController::class, 'bootstrap'])->name('bootstrap');
        Route::get('/catalog', [VirtualTryOnController::class, 'catalog'])->name('catalog');
        Route::post('/preview', [VirtualTryOnController::class, 'preview'])
            ->middleware('throttle:30,1')
            ->name('preview');
    });
