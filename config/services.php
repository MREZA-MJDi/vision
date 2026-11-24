<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    // Online payment gateways stay disabled until the merchant enables them
    // and provides the credentials issued for the actual VISION merchant account.
    'payment_gateways' => [
        'snappay' => [
            'enabled' => env('SNAPPAY_ENABLED', false),
            'base_url' => env('SNAPPAY_BASE_URL', 'https://api.snapppay.ir'),
            'client_id' => env('SNAPPAY_CLIENT_ID'),
            'client_secret' => env('SNAPPAY_CLIENT_SECRET'),
            'username' => env('SNAPPAY_USERNAME'),
            'password' => env('SNAPPAY_PASSWORD'),
        ],
        'torobpay' => [
            'enabled' => env('TOROBPAY_ENABLED', false),
            'base_url' => env('TOROBPAY_BASE_URL', 'https://api.torobpay.com'),
            'client_id' => env('TOROBPAY_CLIENT_ID'),
            'client_secret' => env('TOROBPAY_CLIENT_SECRET'),
            'username' => env('TOROBPAY_USERNAME'),
            'password' => env('TOROBPAY_PASSWORD'),
            // Confirm settlement behavior with TorobPay for the contracted terminal.
            'auto_settle' => env('TOROBPAY_AUTO_SETTLE', false),
        ],
        'digipay' => [
            'enabled' => env('DIGIPAY_ENABLED', false),
            'base_url' => env('DIGIPAY_BASE_URL', 'https://api.mydigipay.com'),
            'sandbox' => env('DIGIPAY_SANDBOX', false),
            'client_id' => env('DIGIPAY_CLIENT_ID'),
            'client_secret' => env('DIGIPAY_CLIENT_SECRET'),
            'username' => env('DIGIPAY_USERNAME'),
            'password' => env('DIGIPAY_PASSWORD'),
            'ticket_type' => (int) env('DIGIPAY_TICKET_TYPE', 11),
            'seller_id' => env('DIGIPAY_SELLER_ID'),
            'supplier_id' => env('DIGIPAY_SUPPLIER_ID'),
            'category_id' => env('DIGIPAY_CATEGORY_ID'),
        ],
    ],

    'torob' => [
        'product_api_enabled' => env('TOROB_PRODUCT_API_ENABLED', true),
        // Public verification key published in Torob's Product API v3 token guide.
        'public_key' => env('TOROB_PUBLIC_KEY', 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0='),
    ],

    'storefront' => [
        'phone' => env('STOREFRONT_PHONE_LINK'),
        'instagram' => env('STOREFRONT_INSTAGRAM_URL'),
        'whatsapp' => env('STOREFRONT_WHATSAPP_URL'),
    ],

];
