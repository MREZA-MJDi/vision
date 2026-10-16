<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;

/**
 * Creates a small, real try-on catalog from the transparent demo assets shipped
 * with Vision. It never overwrites an existing product, variant, mannequin base,
 * or admin-managed try-on mapping.
 */
class VirtualTryOnDemoSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('public');
        $mannequin = TryOnMannequin::query()->where('code', 'women-front')->first();

        if (! $mannequin) {
            $this->command?->warn('Virtual Try-On demo data was skipped: run VirtualTryOnMannequinSeeder first.');

            return;
        }

        $basePath = 'virtual-try-on/demo/mannequin-neutral.webp';
        if ($disk->exists($basePath) && ! $mannequin->base_media_id) {
            $baseMedia = Media::query()->firstOrCreate([
                'disk' => 'public',
                'path' => $basePath,
            ], [
                'mediable_type' => $mannequin->getMorphClass(),
                'mediable_id' => $mannequin->getKey(),
                'collection' => 'try-on-base',
                'original_name' => basename($basePath),
                'mime_type' => 'image/webp',
                'size' => $disk->size($basePath),
                'width' => 320,
                'height' => 480,
                'alt_text' => $mannequin->label,
                'sort_order' => 0,
                'uploaded_by' => null,
            ]);

            $mannequin->update(['base_media_id' => $baseMedia->getKey()]);
        }

        $definitions = [
            [
                'product_slug' => 'vto-demo-coat',
                'product_name' => 'پالتو کلاسیک',
                'description' => 'پالتو با لایهٔ شفاف آماده برای پیش‌نمایش روی مانکن.',
                'sku' => 'VTO-DEMO-COAT-CHOCOLATE',
                'color' => 'قهوه‌ای',
                'color_code' => '#704231',
                'price' => 6490000,
                'path' => 'virtual-try-on/demo/coat-chocolate.webp',
                'slot' => 'outerwear',
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'product_slug' => 'vto-demo-coat',
                'product_name' => 'پالتو کلاسیک',
                'description' => 'پالتو با لایهٔ شفاف آماده برای پیش‌نمایش روی مانکن.',
                'sku' => 'VTO-DEMO-COAT-CREAM',
                'color' => 'کرم',
                'color_code' => '#E3D7C6',
                'price' => 6590000,
                'path' => 'virtual-try-on/demo/coat-cream.webp',
                'slot' => 'outerwear',
                'is_default' => false,
                'sort_order' => 11,
            ],
            [
                'product_slug' => 'vto-demo-puffer',
                'product_name' => 'کاپشن پافر',
                'description' => 'کاپشن آمادهٔ پرو برای تست تعویض لباس.',
                'sku' => 'VTO-DEMO-PUFFER-BLACK',
                'color' => 'مشکی',
                'color_code' => '#26252A',
                'price' => 4890000,
                'path' => 'virtual-try-on/demo/puffer-black.webp',
                'slot' => 'outerwear',
                'is_default' => false,
                'sort_order' => 20,
            ],
            [
                'product_slug' => 'vto-demo-sweater',
                'product_name' => 'پلیور یقه‌اسکی',
                'description' => 'پلیور روشن با لایهٔ آمادهٔ مانکن.',
                'sku' => 'VTO-DEMO-SWEATER-CREAM',
                'color' => 'کرم',
                'color_code' => '#D9C6B2',
                'price' => 2390000,
                'path' => 'virtual-try-on/demo/sweater-cream.webp',
                'slot' => 'top',
                'is_default' => true,
                'sort_order' => 30,
            ],
            [
                'product_slug' => 'vto-demo-shirt',
                'product_name' => 'پیراهن ساده',
                'description' => 'پیراهن سفید با لایهٔ آمادهٔ پرو.',
                'sku' => 'VTO-DEMO-SHIRT-WHITE',
                'color' => 'سفید',
                'color_code' => '#F4F0E6',
                'price' => 1790000,
                'path' => 'virtual-try-on/demo/shirt-white.webp',
                'slot' => 'top',
                'is_default' => false,
                'sort_order' => 40,
            ],
            [
                'product_slug' => 'vto-demo-trousers',
                'product_name' => 'شلوار پارچه‌ای',
                'description' => 'شلوار با لایهٔ شفاف هم‌تراز با مانکن.',
                'sku' => 'VTO-DEMO-TROUSERS-BLACK',
                'color' => 'مشکی',
                'color_code' => '#242329',
                'price' => 2990000,
                'path' => 'virtual-try-on/demo/trousers-black.webp',
                'slot' => 'bottom',
                'is_default' => true,
                'sort_order' => 50,
            ],
            [
                'product_slug' => 'vto-demo-trousers',
                'product_name' => 'شلوار پارچه‌ای',
                'description' => 'شلوار با لایهٔ شفاف هم‌تراز با مانکن.',
                'sku' => 'VTO-DEMO-TROUSERS-CREAM',
                'color' => 'کرم',
                'color_code' => '#D6C7B4',
                'price' => 3090000,
                'path' => 'virtual-try-on/demo/trousers-cream.webp',
                'slot' => 'bottom',
                'is_default' => false,
                'sort_order' => 51,
            ],
        ];

        DB::transaction(function () use ($definitions, $mannequin, $disk): void {
            foreach ($definitions as $definition) {
                $path = $definition['path'];
                if (! $disk->exists($path)) {
                    $this->command?->warn("Virtual Try-On layer [{$path}] is missing; its demo mapping was skipped.");

                    continue;
                }

                $product = Product::withTrashed()->firstOrNew(['slug' => $definition['product_slug']]);
                if ($product->exists && $product->trashed()) {
                    continue;
                }

                if (! $product->exists) {
                    $product->fill([
                        'name' => $definition['product_name'],
                        'slug' => $definition['product_slug'],
                        'audience' => 'women',
                        'short_description' => $definition['description'],
                        'description' => $definition['description'],
                        'meta_title' => $definition['product_name'] . ' | VISION',
                        'meta_description' => $definition['description'],
                        'is_active' => true,
                        'is_featured' => false,
                        'is_hero' => false,
                        'is_taxable' => false,
                        'sort_order' => $definition['sort_order'],
                    ])->save();
                }

                $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $definition['sku']]);
                if ($variant->exists && $variant->trashed()) {
                    continue;
                }

                if ($variant->exists && (int) $variant->product_id !== (int) $product->getKey()) {
                    $this->command?->warn("Virtual Try-On demo SKU [{$definition['sku']}] belongs to another product; it was left unchanged.");

                    continue;
                }

                if (! $variant->exists) {
                    $variant->fill([
                        'product_id' => $product->getKey(),
                        'sku' => $definition['sku'],
                        'size' => 'M',
                        'color' => $definition['color'],
                        'color_code' => $definition['color_code'],
                        'price' => $definition['price'],
                        'sale_price' => null,
                        'stock' => 10,
                        'low_stock_threshold' => 2,
                        'is_active' => true,
                        'sort_order' => $definition['sort_order'],
                    ])->save();
                }

                // An existing mapping is admin-owned. Do not replace its pose,
                // slot, image or default flag when the seeder runs again.
                if (TryOnAsset::query()->where('product_variant_id', $variant->getKey())->exists()) {
                    continue;
                }

                $media = Media::query()->firstOrCreate([
                    'disk' => 'public',
                    'path' => $path,
                ], [
                    'mediable_type' => $variant->getMorphClass(),
                    'mediable_id' => $variant->getKey(),
                    'collection' => 'try-on-layer',
                    'original_name' => basename($path),
                    'mime_type' => 'image/webp',
                    'size' => $disk->size($path),
                    'width' => 320,
                    'height' => 480,
                    'alt_text' => $definition['product_name'] . ' — ' . $definition['color'],
                    'sort_order' => 0,
                    'metadata' => [
                        'source' => 'VISION supplied demo collage',
                        'optimized_canvas' => '320x480 (2:3)',
                    ],
                    'uploaded_by' => null,
                ]);

                TryOnAsset::query()->create([
                    'product_variant_id' => $variant->getKey(),
                    'try_on_mannequin_id' => $mannequin->getKey(),
                    'media_id' => $media->getKey(),
                    'slot' => $definition['slot'],
                    'is_active' => true,
                    'is_default' => $definition['is_default'],
                ]);
            }
        });

        $this->command?->info('Virtual Try-On demo mannequin and seven transparent clothing layers are ready.');
    }
}
