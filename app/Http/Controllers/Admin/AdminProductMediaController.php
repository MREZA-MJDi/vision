<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use App\Services\HeroService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProductMediaController extends Controller
{
    public function index(Product $product): View
    {
        $product->load('galleryMedia');

        return view('admin.products.media', [
            'product' => $product,
            'media' => $product->galleryMedia,
        ]);
    }

    public function store(
        Request $request,
        Product $product,
        MediaService $media,
        HeroService $hero
    ): RedirectResponse {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,avif',
                'max:5120',
                'dimensions:min_width=300,min_height=300,max_width=6000,max_height=6000',
            ],
        ]);

        $nextOrder = ((int) $product->galleryMedia()->max('sort_order')) + ($product->galleryMedia()->exists() ? 1 : 0);

        DB::transaction(function () use ($validated, $product, $media, &$nextOrder): void {
            foreach ($validated['images'] as $file) {
                $media->attach(
                    $product,
                    'gallery',
                    $file,
                    'products/' . $product->id,
                    $product->name,
                    $nextOrder++
                );
            }
        });

        if ($product->is_hero) {
            $hero->invalidate();
        }

        return back()->with('success', 'تصاویر محصول با موفقیت اضافه شدند.');
    }

    public function update(
        Request $request,
        Product $product,
        Media $media,
        HeroService $hero
    ): RedirectResponse {
        abort_unless(
            $media->mediable_type === $product->getMorphClass()
                && (int) $media->mediable_id === (int) $product->id
                && $media->collection === 'gallery',
            404
        );

        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:180'],
        ]);

        $media->update([
            'alt_text' => $validated['alt_text'] ?? null,
        ]);

        if ($product->is_hero) {
            $hero->invalidate();
        }

        return back()->with('success', 'متن جایگزین تصویر ذخیره شد.');
    }

    public function reorder(
        Request $request,
        Product $product,
        HeroService $hero
    ): JsonResponse {
        $validated = $request->validate([
            'media' => ['required', 'array', 'min:1'],
            'media.*' => ['integer', 'distinct'],
        ]);

        $requestedIds = array_map('intval', $validated['media']);
        $ownedIds = $product->galleryMedia()
            ->whereIn('id', $requestedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($ownedIds) !== count($requestedIds)) {
            return response()->json([
                'message' => 'ترتیب تصاویر معتبر نیست.',
            ], 422);
        }

        DB::transaction(function () use ($product, $requestedIds): void {
            foreach ($requestedIds as $sortOrder => $mediaId) {
                Media::query()
                    ->whereKey($mediaId)
                    ->where('mediable_type', $product->getMorphClass())
                    ->where('mediable_id', $product->id)
                    ->where('collection', 'gallery')
                    ->update(['sort_order' => $sortOrder]);
            }
        });

        if ($product->is_hero) {
            $hero->invalidate();
        }

        return response()->json([
            'message' => 'ترتیب تصاویر ذخیره شد.',
        ]);
    }

    public function destroy(
        Product $product,
        Media $media,
        MediaService $mediaService,
        HeroService $hero
    ): RedirectResponse {
        abort_unless(
            $media->mediable_type === $product->getMorphClass()
                && (int) $media->mediable_id === (int) $product->id
                && $media->collection === 'gallery',
            404
        );

        DB::transaction(function () use ($media, $mediaService): void {
            $mediaService->deleteMedia($media);
            $media->delete();
        });

        if ($product->is_hero) {
            $hero->invalidate();
        }

        return back()->with('success', 'تصویر حذف شد.');
    }
}
