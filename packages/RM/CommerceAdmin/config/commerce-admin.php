<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commerce Admin package defaults
    |--------------------------------------------------------------------------
    | The host application owns routes, controllers, authorization, schema and
    | business rules. Override model/formatter adapters in the host config file.
    */
    'store_name' => env('COMMERCE_ADMIN_STORE_NAME', env('APP_NAME', 'Commerce Store')),
    'brand_name' => env('COMMERCE_ADMIN_BRAND_NAME', env('APP_NAME', 'Commerce Admin')),
    'asset_mode' => env('COMMERCE_ADMIN_ASSET_MODE', 'published'),

    'models' => [
        'user' => App\\Models\\User::class,
        'product' => App\\Models\\Product::class,
        'variant' => App\\Models\\ProductVariant::class,
        'category' => App\\Models\\Category::class,
        'brand' => App\\Models\\Brand::class,
        'order' => App\\Models\\Order::class,
        'order_item' => App\\Models\\OrderItem::class,
        'payment' => App\\Models\\Payment::class,
        'media' => App\\Models\\Media::class,
        'inventory_movement' => App\\Models\\InventoryMovement::class,
        'review' => App\\Models\\ProductReview::class,
    ],

    'formatters' => [
        'money' => \\Rm\\CommerceAdmin\\Support\\MoneyFormatter::class,
        'date' => \\Rm\\CommerceAdmin\\Support\\JalaliDateFormatter::class,
    ],

    'admin_flag_column' => 'is_admin',
    'login_throttle_attempts' => 5,
    'login_throttle_seconds' => 60,
    'currency' => env('COMMERCE_ADMIN_CURRENCY', 'IRR'),
    'currency_symbol' => env('COMMERCE_ADMIN_CURRENCY_SYMBOL', 'تومان'),
    'tax_rate' => (float) env('COMMERCE_ADMIN_TAX_RATE', 0),
    'shipping_flat_rate' => (float) env('COMMERCE_ADMIN_SHIPPING_FLAT_RATE', 0),

    'status_labels' => [
        'pending' => 'در انتظار',
        'confirmed' => 'تأییدشده',
        'preparing' => 'در حال آماده‌سازی',
        'shipped' => 'ارسال‌شده',
        'out_for_delivery' => 'در حال ارسال',
        'delivered' => 'تحویل‌شده',
        'cancelled' => 'لغوشده',
        'returned' => 'مرجوع‌شده',
        'paid' => 'پرداخت‌شده',
        'failed' => 'ناموفق',
        'refunded' => 'بازپرداخت‌شده',
        'approved' => 'تأییدشده',
        'rejected' => 'ردشده',
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'draft' => 'پیش‌نویس',
    ],
    'movement_labels' => [
        'sale' => 'فروش',
        'return' => 'مرجوعی',
        'adjustment' => 'اصلاح موجودی',
        'purchase' => 'خرید',
    ],
    'items_per_page' => 10,
    'media_disk' => env('COMMERCE_ADMIN_MEDIA_DISK', 'public'),
    'media_directory' => 'products',
    'category_media_directory' => 'categories',
];
