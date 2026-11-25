<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MediaService;
use App\Services\HeroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class HomepageImageController extends Controller
{
    public function update(Request $request, string $type, int $owner): RedirectResponse
    {
        return $this->mutate($request, $type, $owner, false);
    }

    public function destroy(Request $request, string $type, int $owner): RedirectResponse
    {
        return $this->mutate($request, $type, $owner, true);
    }

    private function mutate(Request $request, string $type, int $id, bool $remove): RedirectResponse
    {
        [$model, $relation, $collection, $directory, $limit] = match ($type) {
            'brand' => [Brand::class, 'logoMedia', 'logo', 'brands', 10240],
            'category' => [Category::class, 'coverMedia', 'cover', 'categories', 10240],
            'product' => [Product::class, 'primaryGalleryMedia', 'gallery', 'products', 4096],
            'variant' => [ProductVariant::class, 'primaryGalleryMedia', 'gallery', 'products/variants', 4096],
            default => abort(404),
        };

        $request->validate([
            'media_id' => ['present', 'nullable', 'integer', 'min:1'],
            'image' => $remove ? ['prohibited'] : ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . $limit, 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $newPath = null;
        $old = null;
        try {
            DB::transaction(function () use ($request, $model, $id, $relation, $collection, $directory, $remove, &$newPath, &$old): void {
                $owner = $model::query()->lockForUpdate()->findOrFail($id);
                $current = $owner->{$relation}()->first();
                $expected = $request->filled('media_id') ? $request->integer('media_id') : null;
                if ($expected !== $current?->id || ($remove && !$current)) {
                    throw ValidationException::withMessages(['image' => 'تصویر تغییر کرده است؛ صفحه را تازه کنید.']);
                }

                if ($current) {
                    $old = clone $current;
                }
                if ($remove) {
                    $current->delete();
                    return;
                }

                $file = $request->file('image');
                $newPath = $file->store($directory . '/' . $id, 'public');
                abort_unless(is_string($newPath) && $newPath !== '', 500, 'ذخیره تصویر انجام نشد.');
                $dimensions = getimagesize($file->getPathname());
                $attributes = [
                    'disk' => 'public',
                    'path' => $newPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'alt_text' => $current?->alt_text ?: ($owner instanceof ProductVariant ? $owner->product?->name : $owner->name),
                    'uploaded_by' => auth()->id(),
                ];

                if ($current) {
                    // Preserve the selected gallery position and its crop metadata.
                    $current->update($attributes);
                } else {
                    $owner->media()->create($attributes + ['collection' => $collection, 'sort_order' => 0]);
                }
            });
        } catch (Throwable $exception) {
            if (is_string($newPath) && $newPath !== '') {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }

        if ($old && !str_starts_with(ltrim((string) $old->path, '/'), 'demo-products/')) {
            // The live DB reference has already committed; a failed cleanup must not remove the replacement.
            try {
                app(MediaService::class)->deleteMedia($old);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
        app(HeroService::class)->invalidate();

        return redirect()->route('commerce-admin.homepage.index')
            ->with('status', $remove ? 'تصویر حذف شد.' : 'تصویر ذخیره شد.');
    }
}
