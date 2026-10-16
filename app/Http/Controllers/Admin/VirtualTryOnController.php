<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;
use Rm\VirtualTryOn\Support\VirtualTryOnConfig;

class VirtualTryOnController extends Controller
{
    public function index(Request $request): View
    {
        $mannequins = TryOnMannequin::query()
            ->with(['baseMedia', 'assets.media', 'assets.variant.product'])
            ->orderBy('audience')
            ->orderBy('sort_order')
            ->get();

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with(['product:id,name,audience', 'tryOnAssets.media', 'tryOnAssets.mannequin'])
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return view('commerce-admin::try-on.index', compact('mannequins', 'variants'));
    }

    public function updateMannequin(Request $request, int $mannequin): RedirectResponse
    {
        $record = TryOnMannequin::query()->findOrFail($mannequin);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
            'base_image' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096', 'dimensions:width=1024,height=1536'],
        ]);

        $disk = (string) config('commerce-admin.media_disk', 'public');
        $obsolete = null;
        $storedPath = null;

        try {
        DB::transaction(function () use ($request, $record, $validated, $disk, &$obsolete, &$storedPath): void {
            $record->label = trim($validated['label']);
            $record->is_active = (bool) ($validated['is_active'] ?? false);

            if ($request->hasFile('base_image')) {
                $file = $request->file('base_image');
                $path = $file->store('virtual-try-on/mannequins/' . $record->getKey(), $disk);
                $storedPath = $path;
                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('ذخیره‌سازی تصویر مانکن انجام نشد.');
                }
                $obsolete = $record->baseMedia;
                $media = Media::query()->create([
                    'collection' => 'try-on-mannequin',
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'width' => 1024,
                    'height' => 1536,
                    'alt_text' => $record->label,
                    'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                ]);
                $record->base_media_id = $media->getKey();
            }

            $record->save();
        });
        } catch (\Throwable $exception) {
            if ($storedPath) Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }

        if ($obsolete) {
            $oldPath = $obsolete->path;
            $oldDisk = $obsolete->disk ?: $disk;
            $obsolete->delete();
            if ($oldPath && ! Str::startsWith($oldPath, ['http://', 'https://'])) {
                Storage::disk($oldDisk)->delete($oldPath);
            }
        }

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'تنظیمات مانکن ذخیره شد.');
    }

    public function updateVariant(Request $request, int $variant): RedirectResponse
    {
        $record = ProductVariant::query()
            ->with(['product', 'tryOnAssets.media', 'tryOnAssets.mannequin'])
            ->findOrFail($variant);
        $asset = $record->tryOnAssets->first();
        $validated = $request->validate([
            'mannequin_id' => ['required', 'integer', 'exists:try_on_mannequins,id'],
            'slot' => ['required', 'string', 'in:' . implode(',', array_keys(VirtualTryOnConfig::SLOT_ORDER))],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'layer_image' => ['nullable', 'image', 'mimes:png,webp', 'max:4096', 'dimensions:width=1024,height=1536'],
        ]);

        $mannequin = TryOnMannequin::query()->active()
            ->whereKey($validated['mannequin_id'])
            ->where('audience', $record->product?->audience)
            ->first();

        if (! $mannequin) {
            throw ValidationException::withMessages(['mannequin_id' => 'مانکن باید فعال و هم‌گروه با محصول باشد.']);
        }

        if (! $request->hasFile('layer_image') && ! $asset) {
            throw ValidationException::withMessages(['layer_image' => 'برای ثبت لایه، تصویر شفاف PNG یا WebP را انتخاب کن.']);
        }

        $disk = (string) config('commerce-admin.media_disk', 'public');
        $storedPath = null;
        $obsoleteMedia = null;

        try {
        DB::transaction(function () use ($request, $record, $asset, $mannequin, $validated, $disk, &$storedPath, &$obsoleteMedia): void {
            if ($request->hasFile('layer_image')) {
                $file = $request->file('layer_image');
                $storedPath = $file->store('virtual-try-on/layers/' . $record->getKey(), $disk);
                if (! is_string($storedPath) || $storedPath === '') {
                    throw new \RuntimeException('ذخیره‌سازی تصویر لایه انجام نشد.');
                }
                $media = $record->media()->create([
                    'collection' => 'try-on-layer',
                    'disk' => $disk,
                    'path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'width' => 1024,
                    'height' => 1536,
                    'alt_text' => trim(($record->product?->name ?? 'Product') . ($record->color ? ' - ' . $record->color : '')),
                    'sort_order' => 0,
                    'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                ]);
                if ($asset) {
                    $obsoleteMedia = $asset->media;
                    $asset->media_id = $media->getKey();
                    $asset->try_on_mannequin_id = $mannequin->getKey();
                    $asset->slot = $validated['slot'];
                    $asset->is_active = (bool) ($validated['is_active'] ?? false);
                    $asset->is_default = (bool) ($validated['is_default'] ?? false);
                    $asset->save();
                } else {
                    $asset = TryOnAsset::query()->create([
                        'product_variant_id' => $record->getKey(),
                        'try_on_mannequin_id' => $mannequin->getKey(),
                        'media_id' => $media->getKey(),
                        'slot' => $validated['slot'],
                        'is_active' => (bool) ($validated['is_active'] ?? false),
                        'is_default' => (bool) ($validated['is_default'] ?? false),
                    ]);
                }
            } elseif ($asset) {
                $asset->try_on_mannequin_id = $mannequin->getKey();
                $asset->slot = $validated['slot'];
                $asset->is_active = (bool) ($validated['is_active'] ?? false);
                $asset->is_default = (bool) ($validated['is_default'] ?? false);
                $asset->save();
            }

            if ($asset && $asset->is_default) {
                TryOnAsset::query()
                    ->where('try_on_mannequin_id', $asset->try_on_mannequin_id)
                    ->where('slot', $asset->slot)
                    ->where('id', '<>', $asset->getKey())
                    ->update(['is_default' => false]);
            }
        });
        } catch (\Throwable $exception) {
            if ($storedPath) Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }

        if ($obsoleteMedia) {
            $path = $obsoleteMedia->path;
            $oldDisk = $obsoleteMedia->disk ?: $disk;
            $obsoleteMedia->delete();
            if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
                Storage::disk($oldDisk)->delete($path);
            }
        }

        return redirect()->route('commerce-admin.try-on.index')->with('status', 'تصویر اتاق پرو ذخیره شد.');
    }
}
