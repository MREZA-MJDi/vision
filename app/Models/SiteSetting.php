<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            'site-setting:' . $key,
            now()->addMinutes(15),
            fn () => static::query()->where('key', $key)->value('value')
        ) ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)]
        );

        Cache::forget('site-setting:' . $key);

        if (str_starts_with($key, 'contact.')) {
            Cache::forget('site:contact-settings');
        }
    }

    /**
     * Public homepage contact values, restricted to known social domains.
     * This prevents a malformed or legacy setting from becoming an unsafe href.
     */
    public static function homeContactValues(): array
    {
        $values = static::contactValues();

        foreach ([
            'contact.instagram' => ['instagram.com', 'www.instagram.com'],
            'contact.telegram' => ['t.me', 'telegram.me', 'www.telegram.me'],
        ] as $key => $hosts) {
            $url = trim((string) ($values[$key] ?? ''));
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            $values[$key] = $url !== ''
                && $scheme === 'https'
                && in_array($host, $hosts, true)
                ? $url
                : '';
        }

        $phone = trim((string) ($values['contact.phone'] ?? ''));
        $digits = preg_replace('/\\D+/', '', $phone) ?? '';
        $phoneDigits = preg_replace('/\\D+/', '', $phone) ?? '';
        $values['contact.phone_href'] = strlen($digits) >= 7
            && preg_match('/^[0-9+().\\s-]{7,40}$/', $phone)
                ? ((str_starts_with($phone, '+') ? '+' : '') . $phoneDigits)
                : '';

        return $values;
    }

    public static function contactValues(): array
    {
        return Cache::remember('site:contact-settings', now()->addMinutes(15), function (): array {
            return Schema::hasTable('site_settings')
                ? static::query()
                    ->whereIn('key', [
                        'contact.phone',
                        'contact.email',
                        'contact.instagram',
                        'contact.telegram',
                        'contact.address',
                        'contact.working_hours',
                    ])
                    ->pluck('value', 'key')
                    ->all()
                : [];
        });
    }
}
