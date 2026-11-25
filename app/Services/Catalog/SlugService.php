<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class SlugService
{
    public function resolve(Model $record, string $name, ?string $requested, string $prefix): string
    {
        $requested = trim($requested ?? '');
        if ($requested === '' && $record->exists && filled($record->slug)) {
            return $record->slug;
        }

        $base = Str::slug($requested !== '' ? $requested : $name);
        $base = substr($base ?: $prefix . '-' . Str::lower(Str::random(8)), 0, 240);
        $candidate = $base;
        $suffix = 2;

        while ($record->newQueryWithoutScopes()->where('slug', $candidate)
            ->when($record->exists, fn ($query) => $query->where($record->getKeyName(), '<>', $record->getKey()))
            ->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }
}
