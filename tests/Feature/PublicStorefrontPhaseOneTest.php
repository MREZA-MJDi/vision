<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CommerceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('تی‌شرت پنبه‌ای کلاسیک')
            ->assertSee('دسته‌بندی‌ها')
            ->assertDontSee('data-fitting-room', false)
            ->assertDontSee('images.unsplash.com');
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
