<?php

return [
    'order_status' => [
        'pending' => 'در انتظار تأیید',
        'confirmed' => 'تأیید شده',
        'preparing' => 'در حال آماده‌سازی',
        'shipped' => 'ارسال شده',
        'out_for_delivery' => 'در مسیر تحویل',
        'delivered' => 'تحویل شده',
        'cancelled' => 'لغو شده',
        'returned' => 'مرجوع شده',
    ],
    'payment_status' => [
        'pending' => 'در انتظار پرداخت',
        'paid' => 'پرداخت‌شده',
        'failed' => 'پرداخت ناموفق',
        'refunded' => 'مبلغ بازپرداخت‌شده',
    ],
    'payment_method' => [
        'cash_on_delivery' => 'پرداخت هنگام تحویل',
        'bank_transfer' => 'واریز بانکی',
    ],
];
