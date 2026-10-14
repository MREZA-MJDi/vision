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

/**
 * Creates a small, editable first catalog rather than presentation-only demo
 * records. It is idempotent and deliberately never restores soft-deleted items
 * or overwrites administrator edits/uploads.
 */
class StorefrontLaunchSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = Category::query()->firstOrCreate(
                ['slug' => 'outerwear'],
                ['name' => 'پوشاک رویی', 'description' => 'کاپشن و کت برای فصل‌های خنک.', 'is_active' => true, 'sort_order' => 10]
            );
            $brand = Brand::query()->firstOrCreate(
                ['slug' => 'vision'],
                ['name' => 'VISION', 'description' => 'کالکشن اصلی VISION.', 'is_active' => true]
            );

            $this->createProduct($category, $brand, [
                'slug' => 'vision-solar-puffer',
                'name' => 'کاپشن پافر سولار',
                'short_description' => 'سبک، گرم و درخشان؛ برای روزهایی که می‌خواهی دیده شوی.',
                'description' => 'کاپشن پافر با یقهٔ ایستاده و دوخت‌های حجم‌دار؛ رنگ Hero از همین وریانت خوانده می‌شود.',
                'sort_order' => 10,
                'variant' => ['sku' => 'VIS-SOLAR-PUFFER-ORANGE', 'color' => 'نارنجی آفتابی', 'color_code' => '#E96512', 'size' => 'M', 'price' => 4890000, 'stock' => 12, 'path' => 'seed-products/vision-orange-puffer-v1.png'],
            ]);
            $this->createProduct($category, $brand, [
                'slug' => 'vision-chestnut-coat',
                'name' => 'کت پشمی چستنات',
                'short_description' => 'فرم ساختارمند، بافت پشمی و رنگ عمیق قهوه‌ای.',
                'description' => 'کت دولایهٔ پشمی برای استایل روزمره و رسمی؛ با تغییر اسلاید، Hero به طیف همین وریانت می‌رود.',
                'sort_order' => 20,
                'variant' => ['sku' => 'VIS-CHESTNUT-COAT-BROWN', 'color' => 'قهوه‌ای چستنات', 'color_code' => '#704231', 'size' => 'M', 'price' => 6490000, 'stock' => 8, 'path' => 'seed-products/vision-chestnut-coat-v1.png'],
            ]);
        });
    }

    private function createProduct(Category $category, Brand $brand, array $definition): void
    {
        $product = Product::withTrashed()->firstOrNew(['slug' => $definition['slug']]);
        if ($product->exists && $product->trashed()) {
            return;
        }
        if (! $product->exists) {
            $product->fill([
                'name' => $definition['name'], 'category_id' => $category->getKey(), 'brand_id' => $brand->getKey(),
                'short_description' => $definition['short_description'], 'description' => $definition['description'],
                'meta_title' => $definition['name'] . ' | VISION', 'meta_description' => $definition['short_description'],
                'is_active' => true, 'is_featured' => true, 'is_hero' => true, 'is_taxable' => false,
                'sort_order' => $definition['sort_order'],
            ])->save();
        }

        $item = $definition['variant'];
        $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $item['sku']]);
        if ($variant->exists && $variant->trashed()) {
            return;
        }
        if (! $variant->exists) {
            $variant->fill([
                'product_id' => $product->getKey(), 'sku' => $item['sku'], 'size' => $item['size'],
                'color' => $item['color'], 'color_code' => $item['color_code'], 'price' => $item['price'],
                'stock' => $item['stock'], 'low_stock_threshold' => 2, 'is_active' => true, 'sort_order' => 0,
            ])->save();
        }

        $mediaIdentity = [
            'disk' => 'public',
            'path' => $item['path'],
            'mediable_type' => $variant->getMorphClass(),
            'mediable_id' => $variant->getKey(),
        ];

        if (Storage::disk('public')->exists($item['path'])) {
            Media::query()->firstOrCreate($mediaIdentity, [
                'collection' => 'gallery',
                'sort_order' => 0,
                'original_name' => basename($item['path']),
                'mime_type' => 'image/png',
                'alt_text' => $product->name . ' — ' . $item['color'],
                'metadata' => ['source' => 'VISION launch asset', 'theme_color' => $item['color_code']],
            ]);
        } else {
            // Remove legacy metadata for image files that are not present.
            Media::query()->where($mediaIdentity)->delete();
        }
    }
}
