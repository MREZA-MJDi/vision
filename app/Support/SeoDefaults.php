<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SeoDefaults
{
    public static function title(string $name, ?string $provided): string
    {
        $title = trim((string) $provided);

        return Str::limit($title !== '' ? $title : trim($name), 60, '');
    }

    public static function description(string $name, ?string $provided, string|array|null $fallback = null): string
    {
        $description = trim(strip_tags((string) $provided));

        if ($description === '') {
            foreach (is_array($fallback) ? $fallback : [$fallback] as $candidate) {
                $candidate = trim(strip_tags((string) $candidate));
                if ($candidate !== '') {
                    $description = $candidate;
                    break;
                }
            }
        }

        if ($description === '') {
            $description = 'مشاهده و خرید ' . trim($name) . ' در فروشگاه.';
        }

        return Str::limit(preg_replace('/\s+/u', ' ', $description) ?: $description, 160, '');
    }
}
