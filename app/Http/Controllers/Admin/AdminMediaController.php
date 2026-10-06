<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\ProductVariant;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMediaController extends Controller
{
    public function store(Request $request, string $type, int $id, MediaService $media): RedirectResponse
    {
        [$model, $collection, $directory] = $this->target($type, $id);

        $validated = $request->validate([
            'media_file' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm',
                'max:10240',
            ],
            'alt_text' => ['nullable', 'string', 'max:180'],
        ]);

        $file = $validated['media_file'];
        $mime = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');

        if (! $isVideo) {
            validator(
                ['file' => $file],
                ['file' => ['image', 'dimensions:min_width=300,min_height=300,max_width=6000,max_height=6000']]
            )->validate();
        }

        DB::transaction(function () use ($model, $collection, $directory, $file, $media, $validated): void {
            $media->replace(
                $model,
                $collection,
                $file,
                $directory,
                $validated['alt_text'] ?? $model->getAttribute('name')
            );
        });

        return back()->with('success', 'رسانه با موفقیت ذخیره شد.');
    }

    public function destroy(string $type, int $id, Media $media, MediaService $mediaService): RedirectResponse
    {
        [$model, $collection] = $this->target($type, $id);

        abort_unless(
            $media->mediable_type === $model->getMorphClass()
                && (int) $media->mediable_id === (int) $model->getKey()
                && $media->collection === $collection,
            404
        );

        DB::transaction(function () use ($media, $mediaService): void {
            $mediaService->deleteMedia($media);
            $media->delete();
        });

        return back()->with('success', 'رسانه حذف شد.');
    }

    private function target(string $type, int $id): array
    {
        return match ($type) {
            'brand' => [Brand::query()->findOrFail($id), 'logo', 'brands'],
            'category' => [Category::query()->findOrFail($id), 'cover', 'categories'],
            'variant' => [ProductVariant::query()->findOrFail($id), 'gallery', 'variants/' . $id],
            default => abort(404),
        };
    }
}
