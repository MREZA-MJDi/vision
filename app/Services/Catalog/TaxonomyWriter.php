<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Media;
use App\Support\SeoDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Throwable;

final class TaxonomyWriter
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly TaxonomyMediaService $media,
    ) {}

    public function save(
        Model $record, array $data, array $attributes,
        string $prefix, string $collection, string $directory,
        ?UploadedFile $file, bool $remove, ?string $alt,
    ): Model {
        $newMedia = null;
        try {
            return $record->getConnection()->transaction(function () use (
                $record, $data, $attributes, $prefix, $collection, $directory,
                $file, $remove, $alt, &$newMedia
            ): Model {
                if ($record->exists) {
                    $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
                }
                $record->fill([
                    ...$attributes,
                    'name' => $data['name'],
                    'slug' => $this->slugs->resolve($record, $data['name'], $data['slug'] ?? null, $prefix),
                    'description' => $data['description'] ?? null,
                    'meta_title' => SeoDefaults::title($data['name'], $data['meta_title'] ?? null),
                    'meta_description' => SeoDefaults::description($data['name'], $data['meta_description'] ?? null, $data['description'] ?? null),
                    'is_active' => $data['status'] === 'active',
                ]);
                $record->save();
                $newMedia = $this->media->sync($record, $collection, $directory, $file, $remove, $alt, $data['name']);
                return $record;
            });
        } catch (Throwable $exception) {
            if ($newMedia instanceof Media && ! $newMedia->newQuery()->whereKey($newMedia->getKey())->exists()) {
                $this->media->discard($newMedia);
            }
            throw $exception;
        }
    }
}
