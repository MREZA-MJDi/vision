<?php

namespace App\Support;

final class Money
{
    public static function format(mixed $value, ?string $symbol = null): string
    {
        $amount = is_numeric($value) ? (float) $value : 0.0;
        $symbol = trim($symbol ?? (string) config('commerce-admin.currency_symbol', 'تومان'));

        return self::number($amount) . ($symbol !== '' ? ' ' . $symbol : '');
    }

    public static function number(int|float $value): string
    {
        return self::persianDigits(number_format($value, 0, '.', '٬'));
    }

    public static function persianDigits(string $value): string
    {
        return strtr($value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
