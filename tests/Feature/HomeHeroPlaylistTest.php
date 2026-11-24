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
            ['HERO-COLOR-BLACK', 'مشکی', '#171719', 1200, 2, 3],
            ['HERO-COLOR-ORANGE', 'نارنجی', '#e96512', 1300, 0, 0],
            ['HERO-COLOR-CREAM', 'کرم', '#DCCDB6', 1400, 1, 3],
        ] as [$sku, $color, $colorCode, $price, $sortOrder, $stock]) {
            ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => $sku,
                'size' => 'M',
                'color' => $color,
                'color_code' => $colorCode,
                'price' => $price,
                'stock' => $stock,
                'sort_order' => $sortOrder,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);
        }

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('--home-hero-base: #e96512', false)
            ->assertSeeInOrder(['data-theme="#e96512"', 'data-theme="#DCCDB6"', 'data-theme="#171719"'], false)
            ->assertSee('data-home-hero-stock>فعلاً ناموجود', false)
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

    public function test_home_hero_contains_at_most_seven_dashboard_products(): void
    {
        $this->withoutVite();

        $included = [];
        $excluded = [];

        foreach (range(0, 8) as $position) {
            $product = Product::query()->create([
                'name' => "Hero test {$position}",
                'slug' => "hero-test-{$position}",
                'is_active' => true,
                'is_hero' => $position > 0,
                'sort_order' => $position,
            ]);
            $product->variants()->create([
                'sku' => "HERO-TEST-{$position}",
                'price' => 100,
                'stock' => 2,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);

            if ($position === 0 || $position === 8) {
                $excluded[] = $product->getKey();
            } else {
                $included[] = $product->getKey();
            }
        }

        $response = $this->get(route('storefront.index'))->assertOk();

        $response->assertSeeInOrder(array_map(fn ($id) => "data-home-hero-product=\"{$id}\"", $included), false);

        foreach ($excluded as $id) {
            $response->assertDontSee("data-home-hero-product=\"{$id}\"", false);
        }
    }
}
