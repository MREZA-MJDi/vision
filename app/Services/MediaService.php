<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class MediaService
{
    public function storeUploaded(
        UploadedFile $file,
        string $directory
    ): string {
        abort_unless(
            $file->isValid(),
            422,
            'فایل تصویر معتبر نیست.'
        );

        $directory = trim($directory, '/');

        abort_if(
            $directory === '',
            422,
            'مسیر ذخیره‌سازی تصویر معتبر نیست.'
        );

        $path = $file->store($directory, 'public');

        abort_if(
            ! $path,
            500,
            'ذخیره تصویر انجام نشد.'
        );

        return $path;
    }

    public function attach(
        Model $model,
        string $collection,
        UploadedFile $file,
        string $directory,
        ?string $altText = null,
        int $sortOrder = 0
    ): Media {
        $path = $this->storeUploaded($file, $directory);

        [$width, $height] = $this->dimensions($file);

        try {
            return $model->media()->create([
                'collection' => $collection,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'alt_text' => $altText,
                'sort_order' => $sortOrder,
                'uploaded_by' => auth()->id(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }
    }

    public function replace(
        Model $model,
        string $collection,
        UploadedFile $file,
        string $directory,
        ?string $altText = null
    ): Media {
        $old = $model->media()
            ->where('collection', $collection)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $media = $this->attach(
            $model,
            $collection,
            $file,
            $directory,
            $altText,
            $old?->sort_order ?? 0
        );

        try {
            if ($old) {
                $this->deleteMedia($old);
                $old->delete();
            }
        } catch (Throwable $e) {
            $this->deleteMedia($media);
            $media->delete();

            throw $e;
        }

        return $media;
    }

    public function removeCollection(
        Model $model,
        string $collection
    ): void {
        $media = $model->media()
            ->where('collection', $collection)
            ->get();

        foreach ($media as $item) {
            $this->deleteMedia($item);
            $item->delete();
        }
    }

    private function dimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getRealPath());

        return [
            $size[0] ?? null,
            $size[1] ?? null,
        ];
    }

    public function delete(?string $path): void
    {
        if (! $path || $this->isExternal($path)) {
            return;
        }

        $path = $this->normalizePath($path);

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    public function deleteMedia(Media $media): void
    {
        if (! $media->path || $this->isExternal($media->path)) {
            return;
        }

        $disk = $media->disk ?: 'public';
        $path = $this->normalizePath($media->path);

        if ($path !== '') {
            Storage::disk($disk)->delete($path);
        }
    }

    public function exists(?string $value): bool
    {
        $value = trim((string) $value);

        if (
            $value === ''
            || $this->isExternal($value)
        ) {
            return false;
        }

        return Storage::disk('public')
            ->exists($this->normalizePath($value));
    }

    private function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return ltrim($path, '/');
    }

    private function isExternal(string $value): bool
    {
        return (bool) preg_match(
            '/^https?:\/\//i',
            trim($value)
        );
    }
}
