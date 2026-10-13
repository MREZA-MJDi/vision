<?php

use Illuminate\Support\Facades\Route;
use Rm\CommerceAdmin\Http\Controllers\AuthController;
use Rm\CommerceAdmin\Http\Controllers\DashboardController;
use Rm\CommerceAdmin\Http\Controllers\OrderController;
use Rm\CommerceAdmin\Http\Controllers\ProductController;
use Rm\CommerceAdmin\Http\Middleware\EnsureAdmin;

Route::middleware('web')->prefix('admin')->as('commerce-admin.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::post('/logout', [AuthController::class, 'destroy'])->middleware(EnsureAdmin::class)->name('logout');

    Route::middleware(EnsureAdmin::class)->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
        Route::delete('/products/{product}/media/{media}', [ProductController::class, 'deleteMedia'])->whereNumber('product')->whereNumber('media')->name('products.media.destroy');

        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->whereNumber('review')->name('reviews.status');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->whereNumber('order')->name('orders.status');
        Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePayment'])->whereNumber('order')->name('orders.payment');
    });
});
