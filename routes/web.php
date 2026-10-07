<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminBrandController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFinancialController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminNilaController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminProductMediaController;
use App\Http\Controllers\Admin\AdminProductVariantController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminSiteContentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('intro');
})->name('intro');

Route::get('/home', HomeController::class)->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::patch('products/{product}/hero', [AdminProductController::class, 'toggleHero'])->name('products.hero.toggle');
        Route::get('products/{product}/media', [AdminProductMediaController::class, 'index'])->name('products.media.index');
        Route::post('products/{product}/media', [AdminProductMediaController::class, 'store'])->name('products.media.store');
        Route::patch('products/{product}/media/{media}', [AdminProductMediaController::class, 'update'])->name('products.media.update');
        Route::post('products/{product}/media/reorder', [AdminProductMediaController::class, 'reorder'])->name('products.media.reorder');
        Route::delete('products/{product}/media/{media}', [AdminProductMediaController::class, 'destroy'])->name('products.media.destroy');
        Route::resource('products.variants', AdminProductVariantController::class)->except(['show']);
        Route::get('variant-lookup', [AdminProductVariantController::class, 'lookup'])->name('variant-lookup');
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::post('media/{type}/{id}', [AdminMediaController::class, 'store'])->name('media.store');
        Route::delete('media/{type}/{id}/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::get('inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
        Route::get('nila', [AdminNilaController::class, 'index'])->name('nila.index');
        Route::post('inventory', [AdminInventoryController::class, 'store'])->name('inventory.store');
        Route::get('content/about', [AdminSiteContentController::class, 'about'])->name('content.about');
        Route::post('content/about', [AdminSiteContentController::class, 'updateAbout'])->name('content.about.update');
        Route::get('contact', [AdminSiteContentController::class, 'contact'])->name('contact.index');
        Route::post('contact/settings', [AdminSiteContentController::class, 'updateContact'])->name('content.contact.update');
        Route::get('contact/{message}', [AdminSiteContentController::class, 'showContact'])->name('contact.show');
        Route::patch('contact/{message}/status', [AdminSiteContentController::class, 'updateContactStatus'])->name('contact.status');
        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::get('accounting', [AdminFinancialController::class, 'index'])->name('accounting.index');
        Route::post('accounting', [AdminFinancialController::class, 'store'])->name('accounting.store');
        Route::get('accounting/{transaction}', [AdminFinancialController::class, 'show'])->name('accounting.show');
    });
