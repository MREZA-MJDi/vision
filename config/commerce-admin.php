<?php

return [
    /*
    | Host-application model adapters keep business logic outside the UI kit.
    */
    'models' => [
        'user' => App\Models\User::class,
        'product' => App\Models\Product::class,
        'variant' => App\Models\ProductVariant::class,
        'category' => App\Models\Category::class,
        'brand' => App\Models\Brand::class,
        'order' => App\Models\Order::class,
        'order_item' => App\Models\OrderItem::class,
        'payment' => App\Models\Payment::class,
        'media' => App\Models\Media::class,
        'inventory_movement' => App\Models\InventoryMovement::class,
        'review' => App\Models\ProductReview::class,
    ],
    'admin_flag_column' => 'is_admin',
    'guard' => 'web',
    'login_throttle_attempts' => 5,
    'login_throttle_seconds' => 60,
    'currency' => env('COMMERCE_ADMIN_CURRENCY', 'USD'),
    'currency_symbol' => env('COMMERCE_ADMIN_CURRENCY_SYMBOL', '$'),
    // Fractional rate: set COMMERCE_ADMIN_TAX_RATE=0.08 for 8%; no tax is assumed by default.
    'tax_rate' => (float) env('COMMERCE_ADMIN_TAX_RATE', 0),
    // Flat shipping amount; checkout ignores client-supplied shipping prices.
    'shipping_flat_rate' => (float) env('COMMERCE_ADMIN_SHIPPING_FLAT_RATE', 0),
    'items_per_page' => 10,
    'media_disk' => env('COMMERCE_ADMIN_MEDIA_DISK', 'public'),
    'media_directory' => 'products',
];
