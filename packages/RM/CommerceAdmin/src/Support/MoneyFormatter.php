<?php

namespace Rm\\CommerceAdmin\\Support;

final class MoneyFormatter
{
    public function format(mixed $value, ?string $symbol = null): string
    {
        $amount = is_numeric($value) ? (float) $value : 0.0;
        $symbol = trim($symbol ?? (string) config('commerce-admin.currency_symbol', 'تومان'));

        return number_format($amount, 0, '.', ',') . ($symbol !== '' ? ' ' . $symbol : '');
    }
}
