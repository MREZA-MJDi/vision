<?php

return [
    /*
    | Existing commerce models are adapters. Projects can replace these classes
    | without forking the UI. Models must expose conventional commerce fields
    | and relationships (see docs/commerce-admin-kit.md).
    */
    'models' => [
        'user' => App\Models\User::class,
        'product' => App\Models\Product::class,
        'variant' => App\Models\ProductVariant::class,
        'category' => App\Models\Category::class,
        'brand' => App\Models\Brand::class,
        'order' => App\Models\Order::class,
        'order_item' => App\Models\OrderItem::class,
        'media' => App\Models\Media::class,
        'inventory_movement' => App\Models\InventoryMovement::class,
    ],

    'admin_flag_column' => 'is_admin',
    'guard' => 'web',
    'login_throttle_attempts' => 5,
    'login_throttle_seconds' => 60,
    'currency' => env('COMMERCE_ADMIN_CURRENCY', 'USD'),
    'currency_symbol' => env('COMMERCE_ADMIN_CURRENCY_SYMBOL', '$'),
    'items_per_page' => 10,
    'media_disk' => env('COMMERCE_ADMIN_MEDIA_DISK', 'public'),
    'media_directory' => 'products',
];
