<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Middleware\Admin\EnsureAdmin;

Route::prefix('admin')->as('commerce-admin.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::post('/logout', [AuthController::class, 'destroy'])->middleware(EnsureAdmin::class)->name('logout');

    Route::middleware(EnsureAdmin::class)->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
        Route::get('/analytics/export', [DashboardController::class, 'export'])->name('analytics.export');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->whereNumber('category')->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category')->name('categories.destroy');

        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->whereNumber('brand')->name('brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->whereNumber('brand')->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->whereNumber('brand')->name('brands.destroy');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->whereNumber('variant')->name('inventory.adjust');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/bulk-archive', [ProductController::class, 'bulkArchive'])->name('products.bulk-archive');
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
