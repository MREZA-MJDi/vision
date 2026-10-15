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
        $this->withoutVite();
        $this->seed(CommerceDemoSeeder::class);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('VISION — فروشگاه پوشاک')
            ->assertSee('property="og:site_name"', false)
            ->assertSee('data-tiamir-intro', false)
            ->assertDontSee('data-tiamir-replay', false)
            ->assertSee('data-tiamir-hint', false)
            ->assertSee('data-first-duration="3200"', false)
            ->assertSee('data-return-duration="1900"', false)
            ->assertSee('Created by R MAJIDI')
            ->assertSee('https://tiamir.com/wp-content/uploads/2026/09/Tiamir-white.png', false)
            ->assertSee('VISION')
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک')
            ->assertSee('انتخاب بر اساس استایل')
            ->assertSee('home-hero__frame', false)
            ->assertSee('home-category-card__image', false)
            ->assertDontSee('home-featured', false)
            ->assertDontSee('home-brand-story', false)
            ->assertDontSee('home-benefits', false)
            ->assertSee('store-footer__column', false)
            ->assertDontSee('data-cart-add', false)
            ->assertDontSee('data-fitting-room', false)
            ->assertSee('images.unsplash.com');
    }

    public function test_shop_has_real_catalog_controls_and_the_bounded_product_grid(): void
    {
        $this->seed(CommerceDemoSeeder::class);

        $response = $this->get('/shop?sort=price_asc');

        $response->assertOk()
            ->assertDontSee('data-tiamir-intro', false)
            ->assertDontSee('data-fitting-room', false)
            ->assertSee('store-tryon-link', false)
            ->assertSee('اتاق پرو مجازی')
            ->assertSee('data-store-sort', false)
            ->assertSee('data-product-grid', false)
            ->assertSee('مرتب‌سازی')
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک')
            ->assertSee('تومان');
    }

    public function test_product_detail_uses_the_real_variant_selector_and_persian_metadata(): void
    {
        $this->withoutVite();
        $this->seed(CommerceDemoSeeder::class);

        $this->get('/products/demo-essential-cotton-tee')
            ->assertOk()
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('property="og:type" content="product"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('data-variant-select', false)
            ->assertSee('data-variant-color-pick', false)
            ->assertSee('data-virtual-try-on', false)
            ->assertSee('data-vto-expand', false)
            ->assertSee('data-initial-variant-id', false)
            ->assertDontSee('data-variant-preview-modal', false)
            ->assertSee('افزودن به سبد خرید')
            ->assertSee('محصولات منتخب')
            ->assertSee('قهوه‌ای')
            ->assertSee('1,090,000 تومان');
    }

    public function test_product_detail_variant_media_queries_remain_bounded_as_variant_count_grows(): void
    {
        $this->withoutVite();
        $this->seed(CommerceDemoSeeder::class);

        $product = Product::query()
            ->where('slug', 'demo-essential-cotton-tee')
            ->firstOrFail();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $beforeSmallCatalog = $queryCount;
        $this->get('/products/demo-essential-cotton-tee')->assertOk();
        $smallProductQueries = $queryCount - $beforeSmallCatalog;

        for ($index = 1; $index <= 15; $index++) {
            ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => 'DETAIL-N1-' . $product->getKey() . '-' . $index,
                'size' => 'L' . $index,
                'color' => 'رنگ آزمایشی ' . $index,
                'color_code' => '#777777',
                'price' => 120000,
                'sale_price' => null,
                'stock' => 5,
                'low_stock_threshold' => 1,
                'is_active' => true,
                'sort_order' => $index + 100,
            ]);
        }

        $beforeLargeCatalog = $queryCount;
        $this->get('/products/demo-essential-cotton-tee')->assertOk();
        $largeProductQueries = $queryCount - $beforeLargeCatalog;

        $this->assertLessThanOrEqual(
            $smallProductQueries + 3,
            $largeProductQueries,
            'Product detail query count should stay bounded as variant count grows; eager loading variant media prevents N+1 queries.'
        );
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

        $category = \App\Models\Category::query()
            ->where('slug', 'demo-apparel')
            ->firstOrFail();

        $brand = \App\Models\Brand::query()
            ->where('slug', 'vision-demo')
            ->firstOrFail();

        // Keep the homepage's independent brand-story-product query active
        // in both measurements. Without these baseline products, adding the
        // audit products changes the rendering path and adds a fixed set of
        // eager-load queries that is unrelated to N+1 growth.
        for ($index = 1; $index <= 8; $index++) {
            $product = \App\Models\Product::query()->create([
                'category_id' => $category->getKey(),
                'brand_id' => $brand->getKey(),
                'name' => 'Homepage query baseline product ' . $index,
                'slug' => 'homepage-query-baseline-product-' . $index,
                'short_description' => 'Homepage query-count baseline fixture',
                'is_active' => true,
                'is_featured' => false,
                'is_hero' => false,
                'is_taxable' => false,
                'sort_order' => 89 + $index,
            ]);

            \App\Models\ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => 'HOME-N1-BASELINE-' . $index,
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

        $queryCount = 0;
        $sqlLog = [];

        DB::listen(function ($query) use (&$queryCount, &$sqlLog): void {
            $queryCount++;
            $sqlLog[] = $query->sql;
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
        $beforeLargeSqlLog = count($sqlLog);

        $this->get('/')->assertOk();

        $largeHomepageQueries = $queryCount - $beforeLargeCatalog;

        $this->assertLessThanOrEqual(
            $smallHomepageQueries + 4,
            $largeHomepageQueries,
            'Homepage query count should remain bounded as more products are added; a growing difference may indicate N+1 queries. SQL: '
                . implode(' | ', array_slice($sqlLog, $beforeLargeSqlLog))
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
