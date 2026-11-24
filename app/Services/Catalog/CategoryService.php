<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class CategoryService
{
    public function __construct(
        private readonly TaxonomyWriter $writer,
    ) {}

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return (string) config(
            'commerce-admin.models.brand',
            Category::class
        );
    }

    public function save(
        Model $record,
        array $data,
        ?UploadedFile $image = null,
    ): Model {
        return $this->writer->save(
            record: $record,
            data: $data,
            attributes: ['parent_id' => $data['parent_id'] ?? null, 'sort_order' => (int) ($data['sort_order'] ?? 0)],
            prefix: 'category',
            collection: 'cover',
            directory: (string) config(
                'commerce-admin.category_media_directory',
                'categories'
            ),
            file: $image,
            remove: (bool) ($data['remove_cover'] ?? false),
            alt: array_key_exists('cover_alt', $data)
                ? (string) $data['cover_alt']
                : null,
        );
    }

    public function destroy(Model $record): bool
    {
        return $record->getConnection()->transaction(
            function () use ($record): bool {
                $record = $record->newQuery()
                    ->lockForUpdate()
                    ->findOrFail($record->getKey());

                if ($record->products()->withTrashed()->exists() || $record->children()->withTrashed()->exists()) {
                    $record->forceFill([
                        'is_active' => false,
                    ])->save();

                    return false;
                }

                $record->delete();

                return true;
            }
        );
    }
}
