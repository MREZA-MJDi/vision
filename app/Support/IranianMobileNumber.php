<?php

namespace App\Support;

final class IranianMobileNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        if (preg_match('/[^0-9+\s().-]/', $value) === 1) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '98')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && preg_match('/^09\d{9}$/', $value) === 1;
    }
}
