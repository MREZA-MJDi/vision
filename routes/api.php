<?php

use App\Http\Controllers\Api\TorobProductSyncController;
use Illuminate\Support\Facades\Route;

// Torob calls this endpoint to synchronize real active product variants.
Route::post('/torob_api/v3/products', [TorobProductSyncController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('torob.products.sync');

// The old virtual-try-on API has been retired.
// The storefront studio reads scene and variant mappings through its database-backed page payload.
