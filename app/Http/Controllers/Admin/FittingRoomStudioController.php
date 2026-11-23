<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FittingRoomLook;
use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FittingRoomStudioController extends Controller
{
    public function index(): View
    {
        $mannequins = FittingRoomMannequin::query()
            ->with(['baseMedia', 'looks.previewTopMedia', 'looks.previewBottomMedia', 'looks.previewCombinedMedia', 'looks.previewSuggestedMedia', 'looks.items.variant.product'])
            ->withCount('looks')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with(['product:id,name,slug,audience', 'primaryGalleryMedia'])
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get();

        return view('admin.fitting-room-studio.index', compact('mannequins', 'variants'));
    }

    public function storeMannequin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:fitting_room_mannequins,code'],
            'label' => ['required', 'string', 'max:160'],
            'audience' => ['required', Rule::in(['women', 'men', 'all'])],
            'pose_key' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'base_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
        ]);

        $storedPath = null;
        try {
            $mannequin = DB::transaction(function () use ($request, $data, &$storedPath): FittingRoomMannequin {
                $mannequin = FittingRoomMannequin::query()->create([
                    'code' => trim($data['code']),
                    'label' => trim($data['label']),
                    'audience' => $data['audience'],
                    'pose_key' => $data['pose_key'] ?? 'front-neutral',
                    'canvas_width' => 640,
                    'canvas_height' => 960,
                    'is_active' => $request->boolean('is_active'),
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                ]);

                $media = $this->storeMedia($mannequin, $request->file('base_image'), 'fitting-room-mannequin', $mannequin->label, $storedPath);
                $mannequin->base_media_id = $media->getKey();
                $mannequin->canvas_width = (int) $media->width;
                $mannequin->canvas_height = (int) $media->height;
                $mannequin->save();

                return $mannequin;
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $exception;
        }

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'مانکن به گالری استودیو اضافه شد.');
    }

    public function updateMannequin(Request $request, FittingRoomMannequin $mannequin): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'audience' => ['required', Rule::in(['women', 'men', 'all'])],
            'pose_key' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'base_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
        ]);

        $storedPath = null;
        $obsolete = null;
        try {
            DB::transaction(function () use ($request, $data, $mannequin, &$storedPath, &$obsolete): void {
                $mannequin->label = trim($data['label']);
                $mannequin->audience = $data['audience'];
                $mannequin->pose_key = $data['pose_key'] ?? $mannequin->pose_key;
                $mannequin->sort_order = (int) ($data['sort_order'] ?? $mannequin->sort_order);
                $mannequin->is_active = $request->boolean('is_active');

                if ($request->hasFile('base_image')) {
                    $obsolete = $mannequin->baseMedia;
                    $media = $this->storeMedia($mannequin, $request->file('base_image'), 'fitting-room-mannequin', $mannequin->label, $storedPath);
                    $mannequin->base_media_id = $media->getKey();
                    $mannequin->canvas_width = (int) $media->width;
                    $mannequin->canvas_height = (int) $media->height;
                }

                $mannequin->save();
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $exception;
        }

        $this->deleteMediaIfUnused($obsolete);

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'مانکن به‌روزرسانی شد.');
    }

    public function storeLook(Request $request): RedirectResponse
    {
        return $this->saveLook($request, new FittingRoomLook());
    }

    public function updateLook(Request $request, FittingRoomLook $look): RedirectResponse
    {
        return $this->saveLook($request, $look);
    }

    private function saveLook(Request $request, FittingRoomLook $look): RedirectResponse
    {
        $creating = !$look->exists;
        $codeRule = Rule::unique('fitting_room_looks', 'code')
            ->where('mannequin_id', (int) $request->input('mannequin_id'));
        if (!$creating) {
            $codeRule->ignore($look->getKey());
        }

        $data = $request->validate([
            'mannequin_id' => ['required', 'integer', 'exists:fitting_room_mannequins,id'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', $codeRule],
            'label' => ['required', 'string', 'max:160'],
            'top_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'bottom_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'fit_y_top' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'fit_y_bottom' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'preview_top' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
            'preview_bottom' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
            'preview_combined' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
            'preview_suggested' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:ratio=2/3'],
            'top_product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'bottom_product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $mannequin = FittingRoomMannequin::query()->with('baseMedia')->findOrFail((int) $data['mannequin_id']);
        if (!$mannequin->baseMedia?->url) {
            throw ValidationException::withMessages([
                'mannequin_id' => 'ابتدا برای مانکن انتخاب‌شده یک تصویر پایهٔ معتبر ثبت کن.',
            ]);
        }

        $topVariantId = (int) ($data['top_variant_id'] ?? 0);
        $bottomVariantId = (int) ($data['bottom_variant_id'] ?? 0);
        $existingTopVariantId = $look->exists
            ? (int) $look->items()->where('slot', 'top')->value('product_variant_id')
            : 0;
        $existingBottomVariantId = $look->exists
            ? (int) $look->items()->where('slot', 'bottom')->value('product_variant_id')
            : 0;
        $mannequinChanged = $look->exists && (int) $look->mannequin_id !== (int) $mannequin->getKey();
        $topVariantChanged = $mannequinChanged || $topVariantId !== $existingTopVariantId;
        $bottomVariantChanged = $mannequinChanged || $bottomVariantId !== $existingBottomVariantId;
        $topVariantRequested = $topVariantId > 0;
        $bottomVariantRequested = $bottomVariantId > 0;

        // Reuse a saved scene only while both its mannequin and mapped variant
        // remain unchanged. A different garment must never inherit old imagery.
        $hasTopScene = $request->hasFile('preview_top')
            || (!$topVariantChanged && filled($look->previewTopMedia?->url));
        $hasBottomScene = $request->hasFile('preview_bottom')
            || (!$bottomVariantChanged && filled($look->previewBottomMedia?->url));
        $hasCombinedScene = $request->hasFile('preview_combined')
            || (!$mannequinChanged && !$topVariantChanged && !$bottomVariantChanged && filled($look->previewCombinedMedia?->url));

        if (!$topVariantRequested && !$bottomVariantRequested) {
            throw ValidationException::withMessages(['top_variant_id' => 'برای هر ترکیب حداقل یک واریانت بالاپوش یا پایین‌تنه انتخاب کن.']);
        }
        if ($topVariantRequested && !$hasTopScene) {
            throw ValidationException::withMessages([
                'preview_top' => 'برای این مانکن و واریانت، تصویر تازهٔ حالت T را آپلود کن.',
            ]);
        }
        if ($bottomVariantRequested && !$hasBottomScene) {
            throw ValidationException::withMessages([
                'preview_bottom' => 'برای این مانکن و واریانت، تصویر تازهٔ حالت B را آپلود کن.',
            ]);
        }
        if ($topVariantRequested && $bottomVariantRequested && !$hasCombinedScene) {
            throw ValidationException::withMessages([
                'preview_combined' => 'برای این جفت واریانت، تصویر تازهٔ حالت TB را آپلود کن.',
            ]);
        }
        if ($topVariantId > 0 && $topVariantId === $bottomVariantId) {
            throw ValidationException::withMessages([
                'bottom_variant_id' => 'بالاپوش و پایین‌تنه باید دو واریانت جدا باشند.',
            ]);
        }

        $variantIds = array_values(array_filter([$topVariantId ?: null, $bottomVariantId ?: null]));

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        foreach (['top_variant_id' => 'top', 'bottom_variant_id' => 'bottom'] as $field => $slot) {
            $id = (int) ($data[$field] ?? 0);
            if (!$id) {
                continue;
            }

            $variant = $variants->get($id);
            $product = $variant?->product;
            $audienceMatches = $product && (
                $mannequin->audience === 'all'
                || $product->audience === $mannequin->audience
            );

            if (!$variant || !$variant->is_active || !$product || !$product->is_active || !$audienceMatches) {
                throw ValidationException::withMessages([
                    $field => 'محصول باید فعال باشد و گروه آن با مانکن انتخاب‌شده سازگار باشد.',
                ]);
            }
        }

        $storedPaths = [];
        $obsoleteMedia = [];
        try {
            DB::transaction(function () use (
                $request,
                $data,
                $look,
                $mannequin,
                $variants,
                $topVariantId,
                $bottomVariantId,
                $topVariantRequested,
                $bottomVariantRequested,
                $mannequinChanged,
                $topVariantChanged,
                $bottomVariantChanged,
                &$storedPaths,
                &$obsoleteMedia
            ): void {
                if ($look->exists && $look->mannequin_id != $mannequin->getKey()) {
                    $look->items()->delete();
                }

                $look->mannequin_id = $mannequin->getKey();
                $look->code = trim($data['code']);
                $look->label = trim($data['label']);
                $look->is_active = $request->boolean('is_active', true);
                $look->sort_order = (int) ($data['sort_order'] ?? 0);
                $look->save();

                foreach ([
                    'preview_top' => ['previewTopMedia', 'preview_top_media_id', 'تصویر بالاپوش'],
                    'preview_bottom' => ['previewBottomMedia', 'preview_bottom_media_id', 'تصویر پایین‌تنه'],
                    'preview_combined' => ['previewCombinedMedia', 'preview_combined_media_id', 'تصویر ست کامل'],
                    'preview_suggested' => ['previewSuggestedMedia', 'preview_suggested_media_id', 'تصویر ست پیشنهادی'],
                ] as $input => [$relation, $foreignKey, $alt]) {
                    if ($request->hasFile($input)) {
                        $obsolete = $look->{$relation};
                        if ($obsolete) {
                            $obsoleteMedia[] = $obsolete;
                        }

                        $media = $this->storeMedia(
                            $look,
                            $request->file($input),
                            'fitting-room-scene',
                            $look->label . ' — ' . $alt,
                            $storedPaths
                        );
                        $look->{$foreignKey} = $media->getKey();

                        continue;
                    }

                    // Remove scenes that no longer describe the saved mapping.
                    $mustClear = match ($input) {
                        'preview_top' => !$topVariantRequested,
                        'preview_bottom' => !$bottomVariantRequested,
                        'preview_combined' => !$topVariantRequested || !$bottomVariantRequested,
                        'preview_suggested' => $mannequinChanged || $topVariantChanged || $bottomVariantChanged,
                        default => false,
                    };

                    if ($mustClear) {
                        $obsolete = $look->{$relation};
                        if ($obsolete) {
                            $obsoleteMedia[] = $obsolete;
                        }
                        $look->{$foreignKey} = null;
                    }
                }

                $look->save();

                foreach ([
                    'top_variant_id' => ['slot' => 'top', 'fit' => 'fit_y_top', 'default' => 0.38],
                    'bottom_variant_id' => ['slot' => 'bottom', 'fit' => 'fit_y_bottom', 'default' => 0.66],
                ] as $field => $mapping) {
                    $variantId = $field === 'top_variant_id'
                        ? $topVariantId
                        : $bottomVariantId;
                    if (!$variantId) {
                        $look->items()->where('slot', $mapping['slot'])->delete();
                        continue;
                    }

                    FittingRoomLookItem::query()->updateOrCreate(
                        ['look_id' => $look->getKey(), 'slot' => $mapping['slot']],
                        [
                            'product_variant_id' => $variantId,
                            'fit_y' => (float) ($data[$mapping['fit']] ?? $mapping['default']),
                            'is_active' => true,
                            'sort_order' => $mapping['slot'] === 'top' ? 10 : 20,
                        ]
                    );
                }

                foreach ([
                    'top_product_image' => 'top_variant_id',
                    'bottom_product_image' => 'bottom_variant_id',
                ] as $input => $variantField) {
                    $variantId = (int) ($data[$variantField] ?? 0);
                    if ($variantId && $request->hasFile($input)) {
                        $variant = $variants->get($variantId);
                        if (!$variant) {
                            throw ValidationException::withMessages([
                                $variantField => 'برای آپلود تصویر، یک واریانت معتبر انتخاب کن.',
                            ]);
                        }
                        $this->storeVariantGalleryImage($variant, $request->file($input), $storedPaths);
                    }
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        foreach ($obsoleteMedia as $media) {
            $this->deleteMediaIfUnused($media);
        }

        return redirect()
            ->route('commerce-admin.try-on.index')
            ->with('status', $creating ? 'ست به گالری استودیو اضافه شد.' : 'ست پیشنهادی ذخیره شد.');
    }

    public function destroyLook(FittingRoomLook $look): RedirectResponse
    {
        $media = collect([
            $look->previewTopMedia,
            $look->previewBottomMedia,
            $look->previewCombinedMedia,
            $look->previewSuggestedMedia,
        ])->filter();

        $look->delete();

        foreach ($media as $item) {
            $this->deleteMediaIfUnused($item);
        }

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'ست از گالری حذف شد.');
    }

    public function destroyMannequin(FittingRoomMannequin $mannequin): RedirectResponse
    {
        $media = collect([$mannequin->baseMedia]);
        foreach ($mannequin->looks as $look) {
            $media = $media->merge([
                $look->previewTopMedia,
                $look->previewBottomMedia,
                $look->previewCombinedMedia,
                $look->previewSuggestedMedia,
            ]);
        }

        $mannequin->delete();

        foreach ($media->filter() as $item) {
            $this->deleteMediaIfUnused($item);
        }

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'مانکن و ست‌های وابسته از گالری حذف شدند.');
    }

    /**
     * Save an uploaded image through the existing Media table and public disk.
     * Both mannequin and scene images are editable dashboard records, not hard-coded paths.
     */
    private function storeMedia(
        FittingRoomMannequin|FittingRoomLook $owner,
        UploadedFile $file,
        string $collection,
        string $alt,
        string|array|null &$storedPath
    ): Media {
        $directory = $owner instanceof FittingRoomMannequin
            ? 'fitting-room/mannequins/' . $owner->getKey()
            : 'fitting-room/looks/' . $owner->getKey();

        $path = $file->store($directory, 'public');
        if (!is_string($path) || $path === '') {
            throw new \RuntimeException('ذخیرهٔ تصویر انجام نشد.');
        }

        if (is_array($storedPath)) {
            $storedPath[] = $path;
        } else {
            $storedPath = $path;
        }

        $size = @getimagesize($file->getPathname());

        return $owner->media()->create([
            'collection' => $collection,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => (int) ($size[0] ?? 640),
            'height' => (int) ($size[1] ?? 960),
            'alt_text' => $alt,
            'sort_order' => 0,
            'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
        ]);
    }

    private function storeVariantGalleryImage(ProductVariant $variant, UploadedFile $file, array &$storedPaths): Media
    {
        $path = $file->store('products/variants/' . $variant->getKey(), 'public');
        if (!is_string($path) || $path === '') {
            throw new \RuntimeException('ذخیرهٔ تصویر واریانت انجام نشد.');
        }
        $storedPaths[] = $path;

        $morphType = $variant->getMorphClass();
        Media::query()
            ->where('mediable_type', $morphType)
            ->where('mediable_id', $variant->getKey())
            ->where('collection', 'gallery')
            ->increment('sort_order');

        $size = @getimagesize($file->getPathname());

        return Media::query()->create([
            'mediable_type' => $morphType,
            'mediable_id' => $variant->getKey(),
            'collection' => 'gallery',
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => (int) ($size[0] ?? 0),
            'height' => (int) ($size[1] ?? 0),
            'alt_text' => (string) ($variant->product?->name ?? 'VISION product variant'),
            'sort_order' => 0,
            'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
        ]);
    }

    private function deleteMediaIfUnused(?Media $media): void
    {
        if (!$media) {
            return;
        }

        $path = $media->path;
        $disk = $media->disk ?: 'public';

        // Preview foreign keys can be shared after a replacement or deletion;
        // never remove a media record that is still referenced.
        if (FittingRoomMannequin::query()->where('base_media_id', $media->getKey())->exists()
            || FittingRoomLook::query()->whereIn('preview_top_media_id', [$media->getKey()])
                ->orWhere('preview_bottom_media_id', $media->getKey())
                ->orWhere('preview_combined_media_id', $media->getKey())
                ->orWhere('preview_suggested_media_id', $media->getKey())
                ->exists()) {
            return;
        }

        $media->delete();
        if ($path && !Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk($disk)->delete($path);
        }
    }
}
