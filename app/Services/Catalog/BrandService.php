<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class BrandService
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
            Brand::class
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
            attributes: [],
            prefix: 'brand',
            collection: 'logo',
            directory: (string) config(
                'commerce-admin.brand_media_directory',
                'brands'
            ),
            file: $image,
            remove: (bool) ($data['remove_logo'] ?? false),
            alt: array_key_exists('logo_alt', $data)
                ? (string) $data['logo_alt']
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

                if ($record->products()->withTrashed()->exists()) {
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
