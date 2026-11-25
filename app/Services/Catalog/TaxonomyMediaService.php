<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Throwable;

final class TaxonomyMediaService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Must be called inside the catalog transaction.
     */
    public function sync(
        Model $record,
        string $collection,
        string $directory,
        ?UploadedFile $file,
        bool $remove,
        ?string $alt,
        string $fallbackAlt,
    ): ?Media {
        $connection = $record->getConnection();

        $existing = $record->media()
            ->where('collection', $collection)
            ->lockForUpdate()
            ->get();

        $altText = trim($alt ?? '');

        if ($altText === '') {
            $altText = $fallbackAlt;
        }

        if ($file !== null) {
            $newMedia = $this->mediaService->attach(
                $record,
                $collection,
                $file,
                $directory,
                $altText
            );

            try {
                foreach ($existing as $media) {
                    $media->delete();
                }
            } catch (Throwable $exception) {
                $this->discard($newMedia);

                throw $exception;
            }

            $connection->afterCommit(function () use ($existing): void {
                foreach ($existing as $media) {
                    $this->discard($media);
                }
            });

            return $newMedia;
        }

        if ($remove) {
            foreach ($existing as $media) {
                $media->delete();
            }

            $connection->afterCommit(function () use ($existing): void {
                foreach ($existing as $media) {
                    $this->discard($media);
                }
            });

            return null;
        }

        if ($alt !== null) {
            foreach ($existing as $media) {
                $media->forceFill([
                    'alt_text' => $altText,
                ])->save();
            }
        }

        return null;
    }

    public function discard(Media $media): void
    {
        try {
            $this->mediaService->deleteMedia($media);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
