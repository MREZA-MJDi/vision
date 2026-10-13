<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront
|--------------------------------------------------------------------------
|
| The public storefront is the only application surface exposed by web routes.
| The former admin dashboard, its management endpoints, and admin login have
| been removed. Catalog records and commerce data remain in the database.
|
*/
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.index');
