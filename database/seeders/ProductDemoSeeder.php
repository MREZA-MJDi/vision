<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CategoryDemoSeeder::class, BrandDemoSeeder::class]);

        $apparel = Category::query()->where('slug', 'demo-apparel')->first();
        $accessories = Category::query()->where('slug', 'demo-accessories')->first();
        $brand = Brand::query()->where('slug', 'vision-demo')->first();

        if (! $apparel || ! $accessories || ! $brand) {
            $this->command?->warn('Demo products were skipped because a demo category or brand is archived or unavailable. Existing records were left unchanged.');

            return;
        }

        $products = [
            [
                'slug' => 'demo-essential-cotton-tee',
                'name' => 'تی‌شرت پنبه‌ای کلاسیک',
                'category_id' => $apparel->getKey(),
                'short_description' => 'یک محصول نمونه با رنگ‌های قهوه‌ای، سفید و مشکی؛ هر رنگ تصویر مستقل دارد.',
                'description' => 'نمونهٔ واقعی دیتابیس برای آزمایش انتخاب رنگ، پیش‌نمایش تصویر، قیمت و موجودی هر تنوع.',
                'is_featured' => true,
                'sort_order' => 10,
                'variants' => [
                    ['sku' => 'VISION-DEMO-TEE-COFFEE', 'color' => 'قهوه‌ای', 'color_code' => '#805B43', 'size' => 'M', 'price' => 1290000, 'sale_price' => null, 'stock' => 12, 'threshold' => 3, 'image' => 'tee-coffee.svg'],
                    ['sku' => 'VISION-DEMO-TEE-WHITE', 'color' => 'سفید', 'color_code' => '#F4F0E6', 'size' => 'M', 'price' => 1190000, 'sale_price' => 1090000, 'stock' => 8, 'threshold' => 3, 'image' => 'tee-white.svg'],
                    ['sku' => 'VISION-DEMO-TEE-BLACK', 'color' => 'مشکی', 'color_code' => '#17191C', 'size' => 'M', 'price' => 1290000, 'sale_price' => null, 'stock' => 15, 'threshold' => 4, 'image' => 'tee-black.svg'],
                ],
            ],
            [
                'slug' => 'demo-structured-tote',
                'name' => 'کیف دستی ساختارمند',
                'category_id' => $accessories->getKey(),
                'short_description' => 'نمونه محصول با تصویر جداگانه برای هر رنگ کیف.',
                'description' => 'با انتخاب رنگ مشکی یا شتری، عکس، قیمت و موجودی همان تنوع نمایش داده می‌شود.',
                'is_featured' => false,
                'sort_order' => 20,
                'variants' => [
                    ['sku' => 'VISION-DEMO-TOTE-TAN', 'color' => 'شتری', 'color_code' => '#BA9166', 'size' => 'Standard', 'price' => 1450000, 'sale_price' => null, 'stock' => 6, 'threshold' => 2, 'image' => 'tote-tan.svg'],
                    ['sku' => 'VISION-DEMO-TOTE-BLACK', 'color' => 'مشکی', 'color_code' => '#25282B', 'size' => 'Standard', 'price' => 1490000, 'sale_price' => null, 'stock' => 4, 'threshold' => 2, 'image' => 'tote-black.svg'],
                ],
            ],
        ];

        DB::transaction(function () use ($products, $brand): void {
            foreach ($products as $definition) {
                $product = Product::withTrashed()->firstOrNew(['slug' => $definition['slug']]);
                if ($product->exists) {
                    // Respect both administrator edits and soft-deletes. Seeding is
                    // an initializer, not a reset button for catalog data.
                    if ($product->trashed()) {
                        continue;
                    }
                } else {
                    $product->fill([
                        'name' => $definition['name'],
                        'slug' => $definition['slug'],
                        'category_id' => $definition['category_id'],
                        'brand_id' => $brand->getKey(),
                        'short_description' => $definition['short_description'],
                        'description' => $definition['description'],
                        'meta_title' => $definition['name'] . ' | VISION',
                        'meta_description' => $definition['short_description'],
                        'is_active' => true,
                        'is_featured' => $definition['is_featured'],
                        'is_hero' => false,
                        'is_taxable' => false,
                        'sort_order' => $definition['sort_order'],
                    ])->save();
                }

                foreach ($definition['variants'] as $sortOrder => $variantDefinition) {
                    $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $variantDefinition['sku']]);
                    if ($variant->exists) {
                        if ($variant->trashed()) {
                            continue;
                        }
                        if ((int) $variant->product_id !== (int) $product->getKey()) {
                            $this->command?->warn("Demo SKU [{$variant->sku}] belongs to another product; it was left unchanged.");
                            continue;
                        }

                        // Keep price, stock, color, state and image deletions made in Admin.
                        continue;
                    }

                    $variant->fill([
                        'product_id' => $product->getKey(),
                        'sku' => $variantDefinition['sku'],
                        'size' => $variantDefinition['size'],
                        'color' => $variantDefinition['color'],
                        'color_code' => $variantDefinition['color_code'],
                        'price' => $variantDefinition['price'],
                        'sale_price' => $variantDefinition['sale_price'],
                        'stock' => $variantDefinition['stock'],
                        'low_stock_threshold' => $variantDefinition['threshold'],
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                    ])->save();

                    $relativePath = 'demo-products/' . $variantDefinition['image'];
                    // The sample artwork is version-controlled under storage/app/public.
                    // Never create a media row pointing at an asset missing from disk.
                    if (! Storage::disk('public')->exists($relativePath)) {
                        $this->command?->warn("Demo image [{$relativePath}] is missing; skipped attaching it to {$variant->sku}.");
                        continue;
                    }

                    $media = Media::query()->firstOrCreate([
                        'disk' => 'public',
                        'path' => $relativePath,
                    ], [
                        'mediable_type' => $variant->getMorphClass(),
                        'mediable_id' => $variant->getKey(),
                        'collection' => 'gallery',
                        'original_name' => $variantDefinition['image'],
                        'mime_type' => 'image/svg+xml',
                        'size' => Storage::disk('public')->size($relativePath),
                        'width' => 640,
                        'height' => 800,
                        'alt_text' => $product->name . ' — ' . $variantDefinition['color'],
                        'sort_order' => 0,
                        'uploaded_by' => null,
                    ]);
                }
            }
        });

        $this->command?->info('Demo products, their variants and variant-specific images are ready.');
    }
}
