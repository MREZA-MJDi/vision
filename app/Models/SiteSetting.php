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

    public static function contactValues(): array
    {
        return Cache::remember('site:contact-settings', now()->addMinutes(15), function (): array {
            return Schema::hasTable('site_settings')
                ? static::query()
                    ->whereIn('key', [
                        'contact.phone',
                        'contact.email',
                        'contact.address',
                        'contact.working_hours',
                    ])
                    ->pluck('value', 'key')
                    ->all()
                : [];
        });
    }
}
