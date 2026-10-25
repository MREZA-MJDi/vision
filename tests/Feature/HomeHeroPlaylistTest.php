<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroPlaylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hero_uses_each_active_variants_saved_color_code(): void
    {
        $this->withoutVite();

        $product = Product::query()->create([
            'name' => 'Hero Color Sample',
            'slug' => 'hero-color-sample',
            'short_description' => 'یک انتخاب رنگی برای تست Hero.',
            'is_active' => true,
            'is_featured' => true,
            'is_hero' => true,
        ]);

        foreach ([
            ['HERO-COLOR-BLACK', 'مشکی', '#171719', 1200],
            ['HERO-COLOR-ORANGE', 'نارنجی', '#e96512', 1300],
            ['HERO-COLOR-CREAM', 'کرم', '#DCCDB6', 1400],
        ] as [$sku, $color, $colorCode, $price]) {
            ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => $sku,
                'size' => 'M',
                'color' => $color,
                'color_code' => $colorCode,
                'price' => $price,
                'stock' => 3,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);
        }

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('data-home-hero-slide', false)
            ->assertSee('data-theme="#171719"', false)
            ->assertSee('data-theme="#e96512"', false)
            ->assertSee('data-theme="#DCCDB6"', false)
            ->assertSee('data-label="مشکی"', false)
            ->assertSee('data-label="نارنجی"', false)
            ->assertSee('data-label="کرم"', false)
            ->assertSee('data-home-hero-title', false)
            ->assertSee('data-home-hero-price', false)
            ->assertSee('data-home-hero-product-link', false);
    }
}
