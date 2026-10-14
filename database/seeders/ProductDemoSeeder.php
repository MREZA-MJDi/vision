<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                    ['sku' => 'VISION-DEMO-TEE-COFFEE', 'color' => 'قهوه‌ای', 'color_code' => '#805B43', 'size' => 'M', 'price' => 1290000, 'sale_price' => null, 'stock' => 12, 'threshold' => 3, 'image_url' => 'https://images.unsplash.com/photo-1565292849963-a50ad3c5ddd5?auto=format&fit=max&w=1200&q=85', 'image_name' => 'tee-coffee-unsplash.jpg', 'image_source' => 'https://unsplash.com/photos/woman-wearing-brown-t-shirt-NdTH6-ykBW4', 'image_credit' => 'Felix Uresti'],
                    ['sku' => 'VISION-DEMO-TEE-WHITE', 'color' => 'سفید', 'color_code' => '#F4F0E6', 'size' => 'M', 'price' => 1190000, 'sale_price' => 1090000, 'stock' => 8, 'threshold' => 3, 'image_url' => 'https://images.unsplash.com/photo-1618677603286-0ec56cb6e1b5?auto=format&fit=max&w=1200&q=85', 'image_name' => 'tee-white-unsplash.jpg', 'image_source' => 'https://unsplash.com/photos/white-crew-neck-t-shirt-gEupiRvyxh0', 'image_credit' => 'Mediamodifier'],
                    ['sku' => 'VISION-DEMO-TEE-BLACK', 'color' => 'مشکی', 'color_code' => '#17191C', 'size' => 'M', 'price' => 1290000, 'sale_price' => null, 'stock' => 15, 'threshold' => 4, 'image_url' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=max&w=1200&q=85', 'image_name' => 'tee-black-unsplash.jpg', 'image_source' => 'https://unsplash.com/photos/black-crew-neck-t-shirt-6Nub980bI3I', 'image_credit' => 'Ryan Hoffman'],
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
                    ['sku' => 'VISION-DEMO-TOTE-TAN', 'color' => 'شتری', 'color_code' => '#BA9166', 'size' => 'Standard', 'price' => 1450000, 'sale_price' => null, 'stock' => 6, 'threshold' => 2, 'image_url' => 'https://images.unsplash.com/photo-1535981444082-2a5dc0548ef3?auto=format&fit=max&w=1200&q=85', 'image_name' => 'tote-tan-unsplash.jpg', 'image_source' => 'https://unsplash.com/photos/brown-tote-bag-on-white-surface--zBSu3rYjNs', 'image_credit' => 'Mel Poole'],
                    ['sku' => 'VISION-DEMO-TOTE-BLACK', 'color' => 'مشکی', 'color_code' => '#25282B', 'size' => 'Standard', 'price' => 1490000, 'sale_price' => null, 'stock' => 4, 'threshold' => 2, 'image_url' => 'https://images.unsplash.com/photo-1546938576-04917ec516ee?auto=format&fit=max&w=1200&q=85', 'image_name' => 'tote-black-unsplash.jpg', 'image_source' => 'https://unsplash.com/photos/black-tote-bag-9aZC_v4mLwE', 'image_credit' => 'SJ / uxsj_ph'],
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

                        // Replace only the old illustrations shipped by this demo seeder. A blank gallery means an admin intentionally removed the photo, and uploaded admin photos are never overwritten.
                        $legacyDemoImage = $variant->galleryMedia()
                            ->where('path', 'like', 'demo-products/%')
                            ->first();
                        if ($legacyDemoImage) {
                            $legacyDemoImage->update($this->demoImageAttributes($product, $variantDefinition));
                        }
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

                    Media::query()->firstOrCreate([
                        'disk' => 'public',
                        'path' => $variantDefinition['image_url'],
                    ], array_merge([
                        'mediable_type' => $variant->getMorphClass(),
                        'mediable_id' => $variant->getKey(),
                        'collection' => 'gallery',
                        'sort_order' => 0,
                        'uploaded_by' => null,
                    ], $this->demoImageAttributes($product, $variantDefinition)));
                }
            }
        });

        $this->command?->info('Demo products, their variants and real photo-backed variant media are ready.');
    }

    /**
     * Real, freely usable Unsplash demo photos. Admin-uploaded files are not
     * stored in this external-photo shape and remain untouched by reseeding.
     */
    private function demoImageAttributes(Product $product, array $definition): array
    {
        return [
            'path' => $definition['image_url'],
            'original_name' => $definition['image_name'],
            'mime_type' => 'image/jpeg',
            'size' => null,
            'width' => null,
            'height' => null,
            'alt_text' => $product->name . ' — ' . $definition['color'],
            'metadata' => [
                'source' => 'Unsplash',
                'source_url' => $definition['image_source'],
                'creator' => $definition['image_credit'],
                'license' => 'Unsplash License',
            ],
        ];
    }
}
