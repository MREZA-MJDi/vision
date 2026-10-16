<?php

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;

return [
    /*
    |--------------------------------------------------------------------------
    | Host application integration
    |--------------------------------------------------------------------------
    |
    | Point these values at the host app's Eloquent models when reusing this
    | module. The selected variant model should expose a product() relation,
    | effective_price and display_name accessors, and the media model should
    | expose the url accessor used by this project.
    |
    */
    'models' => [
        'product' => Product::class,
        'variant' => ProductVariant::class,
        'media' => Media::class,
        'mannequin' => TryOnMannequin::class,
        'asset' => TryOnAsset::class,
    ],

    'routes' => [
        'product_detail' => 'products.show',
    ],

    'page_size' => 24,
    'currency_code' => 'IRT',

    'canvas' => [
        'width' => 1024,
        'height' => 1536,
        'formats' => ['svg', 'png', 'webp'],
    ],
];
