<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

final class JalaliDateFormatter
{
    private const MONTHS = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    public function format(DateTimeInterface|string|null $value, string $pattern = 'j F Y'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            $date = $value instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($value)
                : new DateTimeImmutable($value);
        } catch (Throwable) {
            return '—';
        }

        [$year, $month, $day] = self::toJalali(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j')
        );

        $values = [
            'Y' => (string) $year,
            'y' => substr((string) $year, -2),
            'm' => sprintf('%02d', $month),
            'n' => (string) $month,
            'd' => sprintf('%02d', $day),
            'j' => (string) $day,
            'F' => self::MONTHS[$month],
            'M' => self::MONTHS[$month],
            'H' => $date->format('H'),
            'h' => $date->format('h'),
            'i' => $date->format('i'),
            's' => $date->format('s'),
            'a' => $date->format('a'),
            'A' => $date->format('A'),
        ];

        $formatted = preg_replace_callback(
            '/\\\\.|[YymndjFMHisahA]/',
            static function (array $match) use ($values): string {
                $token = $match[0];

                return str_starts_with($token, '\\')
                    ? substr($token, 1)
                    : ($values[$token] ?? $token);
            },
            $pattern
        ) ?? $pattern;

        return strtr($formatted, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    /** Convert a Gregorian date to the Solar Hijri calendar. */
    private static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gMonthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jy = 0;

        $gy -= 1600;
        $gm -= 1;
        $gd -= 1;

        $gDayNo = 365 * $gy
            + intdiv($gy + 3, 4)
            - intdiv($gy + 99, 100)
            + intdiv($gy + 399, 400);

        for ($i = 0; $i < $gm; $i++) {
            $gDayNo += $gMonthDays[$i];
        }

        if ($gm > 1 && (($gy + 1600) % 4 === 0 && (($gy + 1600) % 100 !== 0 || ($gy + 1600) % 400 === 0))) {
            $gDayNo++;
        }

        $gDayNo += $gd;
        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;

        $jy = 979 + (33 * $jNp) + (4 * intdiv($jDayNo, 1461));
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 1, 365);
            $jDayNo = ($jDayNo - 1) % 365;
        }

        $jMonthDays = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $jm = 1;
        foreach ($jMonthDays as $index => $monthDays) {
            if ($jDayNo < $monthDays) {
                $jm = $index + 1;
                break;
            }

            $jDayNo -= $monthDays;
            $jm = min(12, $index + 2);
        }

        return [$jy, $jm, $jDayNo + 1];
    }
}
