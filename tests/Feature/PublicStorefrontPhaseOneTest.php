<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CommerceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicStorefrontPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_persian_rtl_and_separate_from_the_shop_fitting_room(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('VISION — فروشگاه پوشاک')
            ->assertSee('VISION')
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک')
            ->assertSee('انتخاب بر اساس استایل')
            ->assertSee('home-hero__frame', false)
            ->assertSee('home-category-card__image', false)
            ->assertSee('home-featured', false)
            ->assertSee('home-brand-story', false)
            ->assertSee('home-benefits', false)
            ->assertSee('store-footer__column', false)
            ->assertSee('data-cart-add', false)
            ->assertDontSee('data-fitting-room', false)
            ->assertSee('images.unsplash.com');
    }

    public function test_shop_has_real_catalog_controls_and_the_bounded_product_grid(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $response = $this->get('/shop?sort=price_asc');

        $response->assertOk()
            ->assertSee('data-fitting-room', false)
            ->assertSee('data-store-sort', false)
            ->assertSee('data-product-grid', false)
            ->assertSee('مرتب‌سازی')
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک')
            ->assertSee('تومان');
    }

    public function test_product_detail_uses_the_real_variant_selector_and_persian_metadata(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $this->get('/products/demo-essential-cotton-tee')
            ->assertOk()
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('data-variant-select', false)
            ->assertSee('data-variant-color-pick', false)
            ->assertSee('افزودن به سبد خرید')
            ->assertSee('محصولات منتخب')
            ->assertSee('قهوه‌ای')
            ->assertSee('1,090,000 تومان');
    }

    public function test_shop_query_count_does_not_grow_linearly_with_catalog_card_count(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $category = \App\Models\Category::query()->where('slug', 'demo-apparel')->firstOrFail();
        $brand = \App\Models\Brand::query()->where('slug', 'vision-demo')->firstOrFail();
        $queryCount = 0;

        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $beforeSmallCatalog = $queryCount;
        $this->get('/shop')->assertOk();
        $smallCatalogQueries = $queryCount - $beforeSmallCatalog;

        for ($index = 1; $index <= 15; $index++) {
            $product = \App\Models\Product::query()->create([
                'category_id' => $category->getKey(),
                'brand_id' => $brand->getKey(),
                'name' => 'N-plus-one audit product ' . $index,
                'slug' => 'n-plus-one-audit-product-' . $index,
                'short_description' => 'Catalog eager-load regression test',
                'is_active' => true,
                'is_featured' => false,
                'is_hero' => false,
                'is_taxable' => false,
                'sort_order' => $index + 100,
            ]);

            \App\Models\ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => 'N1-AUDIT-' . $index,
                'size' => 'M',
                'color' => 'مشکی',
                'color_code' => '#17191C',
                'price' => 100000,
                'sale_price' => null,
                'stock' => 5,
                'low_stock_threshold' => 1,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        $beforeLargeCatalog = $queryCount;
        $this->get('/shop')->assertOk();
        $largeCatalogQueries = $queryCount - $beforeLargeCatalog;

        $this->assertLessThanOrEqual(
            $smallCatalogQueries + 3,
            $largeCatalogQueries,
            'Shop query count should remain bounded as more products are rendered; a growing difference may indicate N+1 queries.'
        );
    }


    public function test_homepage_query_count_stays_bounded_as_the_catalog_grows(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $category = \App\Models\Category::query()->where('slug', 'demo-apparel')->firstOrFail();
        $brand = \App\Models\Brand::query()->where('slug', 'vision-demo')->firstOrFail();
        $queryCount = 0;

        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $beforeSmallCatalog = $queryCount;
        $this->get('/')->assertOk();
        $smallHomepageQueries = $queryCount - $beforeSmallCatalog;

        for ($index = 1; $index <= 15; $index++) {
            $product = \App\Models\Product::query()->create([
                'category_id' => $category->getKey(),
                'brand_id' => $brand->getKey(),
                'name' => 'Homepage n-plus-one audit product ' . $index,
                'slug' => 'homepage-n-plus-one-audit-product-' . $index,
                'short_description' => 'Homepage eager-load regression test',
                'is_active' => true,
                'is_featured' => false,
                'is_hero' => false,
                'is_taxable' => false,
                'sort_order' => $index + 100,
            ]);

            \App\Models\ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => 'HOME-N1-AUDIT-' . $index,
                'size' => 'M',
                'color' => 'مشکی',
                'color_code' => '#17191C',
                'price' => 100000,
                'sale_price' => null,
                'stock' => 5,
                'low_stock_threshold' => 1,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        $beforeLargeCatalog = $queryCount;
        $this->get('/')->assertOk();
        $largeHomepageQueries = $queryCount - $beforeLargeCatalog;

        $this->assertLessThanOrEqual(
            $smallHomepageQueries + 4,
            $largeHomepageQueries,
            'Homepage query count should remain bounded as more products are added; a growing difference may indicate N+1 queries.'
        );
    }

    public function test_sale_and_price_filters_use_the_displayed_active_variant_when_all_variants_are_sold_out(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $product = Product::query()
            ->where('slug', 'demo-essential-cotton-tee')
            ->firstOrFail();

        ProductVariant::query()
            ->where('product_id', $product->getKey())
            ->update(['stock' => 0]);

        $firstVariant = ProductVariant::query()
            ->where('product_id', $product->getKey())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->firstOrFail();

        $firstVariant->forceFill(['price' => 1290000, 'sale_price' => 1000000])->save();

        $this->get('/shop?sale=1&price_max=1050000')
            ->assertOk()
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک');
    }
}
