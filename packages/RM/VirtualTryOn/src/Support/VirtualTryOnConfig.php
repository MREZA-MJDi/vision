<?php

namespace Rm\VirtualTryOn\Support;

final class VirtualTryOnConfig
{
    public const AUDIENCES = ['women', 'men'];

    public const SLOT_ORDER = [
        'shoes' => 10,
        'bottom' => 20,
        'top' => 30,
        'outerwear' => 40,
        'accessory' => 50,
    ];

    public const SLOT_LABELS = [
        'top' => 'بالاپوش',
        'outerwear' => 'لایهٔ رویی',
        'bottom' => 'پایین‌تنه',
        'shoes' => 'کفش',
        'accessory' => 'اکسسوری',
    ];

    public static function slots(): array
    {
        $slots = [];
        foreach (self::SLOT_ORDER as $key => $zIndex) {
            $slots[] = ['key' => $key, 'label' => self::SLOT_LABELS[$key], 'z_index' => $zIndex];
        }
        return $slots;
    }

    public static function isAudience(string $audience): bool
    {
        return in_array($audience, self::AUDIENCES, true);
    }

    public static function isSlot(string $slot): bool
    {
        return array_key_exists($slot, self::SLOT_ORDER);
    }

    public static function zIndex(string $slot): int
    {
        return self::SLOT_ORDER[$slot] ?? 0;
    }
}
