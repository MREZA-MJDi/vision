<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FittingRoomLook;
use App\Models\FittingRoomMannequin;
use App\Models\Media;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InitialCatalogSeeder extends Seeder
{
    private const MARKER = 'imports.sizen.initial-catalog.v1';

    public function run(): void
    {
        $this->importFrom(base_path('forvision'));
    }

    public function importFrom(string $root): void
    {
        if (SiteSetting::query()->where('key', self::MARKER)->exists()) {
            $this->command?->info('Catalog already imported. Existing edits and deletions are preserved.');
            return;
        }

        $manifest = json_decode(file_get_contents($root . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['version'] ?? null) !== 1 || empty($manifest['products'])) {
            throw new RuntimeException('Invalid initial catalog manifest.');
        }

        $written = [];
        try {
            DB::transaction(function () use ($root, $manifest, &$written): void {
                // Unique durable marker: one successful import, even after products are deleted.
                SiteSetting::query()->create(['key' => self::MARKER, 'value' => now()->toIso8601String()]);
                $categories = [];
                $variants = [];
                foreach ($manifest['products'] as $row) {
                    $category = $row['category'];
                    if (!isset($categories[$category])) {
                        $record = Category::withTrashed()->where('slug', 'sizen-' . $category)->first();
                        if ($record?->trashed()) {
                            throw new RuntimeException('Deleted category collision: ' . $category);
                        }
                        $categories[$category] = $record ?? Category::query()->create([
                            'slug' => 'sizen-' . $category,
                            'name' => $manifest['categories'][$category],
                            'is_active' => true,
                        ]);
                    }
                    $price = $row['price'] ?? null;
                    if ($price !== null && (!is_numeric($price) || $price <= 0)) {
                        throw new RuntimeException('Provide a positive verified price or null.');
                    }
                    $stock = $row['stock'] ?? 0;
                    if (!is_int($stock) || $stock < 0) {
                        throw new RuntimeException('Invalid stock.');
                    }
                    $publish = ($row['publish'] ?? false) === true;
                    if (($row['price_status'] ?? null) === 'proposed' && $stock !== 0) {
                        throw new RuntimeException('Proposed catalog prices require zero unverified inventory.');
                    }
                    if ($publish && $price === null) {
                        throw new RuntimeException('Publishing requires a price.');
                    }
                    if (!in_array($row['audience'] ?? null, [null, 'men', 'women'], true)) {
                        throw new RuntimeException('Invalid product audience.');
                    }
                    $product = Product::query()->create([
                        'category_id' => $categories[$category]->id,
                        'name' => $row['name'],
                        'slug' => 'sizen-' . $row['code'],
                        'audience' => $row['audience'] ?? null,
                        'short_description' => $row['short_description'] ?? null,
                        'description' => $row['description'] ?? null,
                        'meta_title' => $row['name'],
                        'meta_description' => $row['short_description'] ?? null,
                        'is_hero' => $publish && ($row['hero'] ?? false) === true,
                        'is_active' => $publish,
                    ]);
                    $variant = $product->variants()->create([
                        'sku' => 'SIZEN-' . strtoupper($row['code']),
                        'size' => $row['size'] ?? null,
                        'color' => $row['color'],
                        'color_code' => $row['color_code'] ?? null,
                        'price' => $price ?? 0,
                        'stock' => $stock,
                        'low_stock_threshold' => 5,
                        'is_active' => true,
                    ]);
                    $variants[$row['code']] = $variant;
                    $this->media($product, $root, $row['image'], 'gallery', 'products/' . $product->id, $product->name, false, $written);
                    $this->media($variant, $root, $row['image'], 'gallery', 'products/variants/' . $variant->id, $product->name, false, $written);
                }

                foreach ($manifest['models'] ?? [] as $row) {
                    // Fully dressed temporary references are explicitly labelled as such.
                    $active = ($row['active'] ?? false) === true;
                    if ($active && empty($row['base'])) {
                        throw new RuntimeException('Active fitting model requires a base image.');
                    }
                    $mannequin = FittingRoomMannequin::query()->create([
                        'code' => $row['code'],
                        'label' => $row['label'],
                        'audience' => $row['audience'],
                        'pose_key' => $row['pose_key'] ?? 'front-neutral',
                        'canvas_width' => 1024,
                        'canvas_height' => 1536,
                        'is_active' => $active,
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                    ]);
                    if (!empty($row['base'])) {
                        $base = $this->media($mannequin, $root, $row['base'], 'fitting-room-mannequin', 'fitting-room/mannequins/' . $mannequin->id, $row['label'], true, $written);
                        $mannequin->update(['base_media_id' => $base->id, 'canvas_width' => $base->width, 'canvas_height' => $base->height]);
                    }
                    foreach ($row['looks'] ?? [] as $scene) {
                        $variant = $variants[$scene['product']] ?? throw new RuntimeException('Unknown scene variant.');
                        if ($variant->product->audience !== $mannequin->audience) {
                            throw new RuntimeException('Scene audience mismatch.');
                        }
                        $slot = $scene['slot'];
                        if (!in_array($slot, ['top', 'bottom'], true)) {
                            throw new RuntimeException('Unsupported fitting slot.');
                        }
                        $look = $mannequin->looks()->create([
                            'code' => $scene['product'],
                            'label' => $scene['label'],
                            'is_active' => $active && ($scene['active'] ?? false) === true && $variant->product->is_active,
                        ]);
                        $preview = $this->media($look, $root, $scene['image'], 'fitting-room-scene', 'fitting-room/looks/' . $look->id, $look->label, true, $written);
                        $look->update(['preview_' . $slot . '_media_id' => $preview->id]);
                        $look->items()->create([
                            'product_variant_id' => $variant->id,
                            'slot' => $slot,
                            'fit_y' => $slot === 'top' ? 0.38 : 0.66,
                            'is_active' => true,
                            'sort_order' => $slot === 'top' ? 10 : 20,
                        ]);
                    }
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($written);
            throw $exception;
        }

        $this->command?->info('Initial catalog imported. Review prices, stock, audiences and fitting scenes in the dashboard before publishing.');
    }

    private function media(Model $owner, string $root, string $file, string $collection, string $directory, string $alt, bool $scene, array &$written): Media
    {
        $rootPath = realpath($root);
        $source = realpath($root . '/' . $file);
        if (!$rootPath || !$source || !str_starts_with($source, $rootPath . DIRECTORY_SEPARATOR) || !is_file($source)) {
            throw new RuntimeException('Missing or invalid asset: ' . $file);
        }

        $size = filesize($source);
        $image = getimagesize($source);
        if (!$image || !$size || $size > ($scene ? 8192 : 4096) * 1024
            || !in_array($image['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)
            || ($scene && $image[0] * 3 !== $image[1] * 2)) {
            throw new RuntimeException('Invalid image format, dimensions or size: ' . $file);
        }

        $extension = match ($image['mime']) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        };
        $path = $directory . '/' . Str::uuid() . '.' . $extension;
        $stream = fopen($source, 'rb');
        if (!$stream) {
            throw new RuntimeException('Cannot open asset: ' . $file);
        }
        $written[] = $path;
        try {
            if (!Storage::disk('public')->put($path, $stream)) {
                throw new RuntimeException('Cannot store asset: ' . $file);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        return $owner->media()->create([
            'collection' => $collection,
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($source),
            'mime_type' => $image['mime'],
            'size' => $size,
            'width' => $image[0],
            'height' => $image[1],
            'alt_text' => $alt,
            'sort_order' => 0,
        ]);
    }
}
