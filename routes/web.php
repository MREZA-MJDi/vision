<?php

use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerCommerceController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront and customer commerce
|--------------------------------------------------------------------------
|
| Product and cart input is untrusted. Prices, tax, shipping and stock are
| resolved and validated server-side during checkout.
|
*/
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.index');
Route::get('/products', [StorefrontController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])
    ->where('product', '[a-z0-9-]+')
    ->name('products.show');

Route::get('/cart/summary', [CustomerCommerceController::class, 'summary'])->name('cart.summary');
Route::get('/cart', [CustomerCommerceController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CustomerCommerceController::class, 'storeItem'])
    ->middleware('throttle:30,1')
    ->name('cart.items.store');
Route::patch('/cart/items/{item}', [CustomerCommerceController::class, 'updateItem'])
    ->whereNumber('item')
    ->name('cart.items.update');
Route::delete('/cart/items/{item}', [CustomerCommerceController::class, 'destroyItem'])
    ->whereNumber('item')
    ->name('cart.items.destroy');

Route::get('/checkout', [CustomerCommerceController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [CustomerCommerceController::class, 'placeOrder'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');
Route::get('/order-confirmation/{token}', [CustomerCommerceController::class, 'complete'])
    ->where('token', '[0-9a-fA-F-]{36}')
    ->name('checkout.complete');

/*
|--------------------------------------------------------------------------
| Customer authentication and order history
|--------------------------------------------------------------------------
*/
Route::get('/login', [CustomerAuthController::class, 'create'])->name('login');
Route::post('/login', [CustomerAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::get('/register', [CustomerAuthController::class, 'registerForm'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('register.store');

Route::post('/logout', [CustomerAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/account', [CustomerAccountController::class, 'show'])
    ->middleware('auth')
    ->name('account.show');
Route::get('/account/orders/{order}', [CustomerAccountController::class, 'order'])
    ->middleware('auth')
    ->whereNumber('order')
    ->name('account.orders.show');

/*
|--------------------------------------------------------------------------
| Application-owned admin routes
|--------------------------------------------------------------------------
|
| The Admin controllers, middleware and validation belong to this application.
| CommerceAdmin is only the reusable Blade/CSS/JS presentation kit.
|
*/
require __DIR__.'/admin.php';
