<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroPlaylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hero_returns_to_one_curated_product_without_a_color_playlist(): void
    {
        $this->withoutVite();

        $product = Product::query()->create([
            'name' => 'Hero Color Sample',
            'slug' => 'hero-color-sample',
            'short_description' => 'یک انتخاب برای تست Hero.',
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
            ->assertSee('data-home-hero', false)
            ->assertSee('Hero Color Sample')
            ->assertDontSee('data-home-hero-slide', false)
            ->assertDontSee('home-hero__variant-picker', false)
            ->assertDontSee('data-theme=', false);
    }
}
