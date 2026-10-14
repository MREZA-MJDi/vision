<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_public_storefront_without_authentication(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('New Arrivals');
        $response->assertSee('Fitting room');
        $response->assertSee('Filters');
    }

    public function test_product_catalog_lists_active_products_and_hides_inactive_products(): void
    {
        $category = Category::create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'is_active' => true,
        ]);

        $visible = Product::create([
            'category_id' => $category->id,
            'name' => 'The Sculpted Heel',
            'slug' => 'the-sculpted-heel',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $visible->id,
            'sku' => 'VISION-SHOE-001',
            'size' => '38',
            'color' => 'Black',
            'price' => 7900,
            'stock' => 3,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Hidden Sample',
            'slug' => 'hidden-sample',
            'is_active' => false,
        ]);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('The Sculpted Heel');
        $response->assertDontSee('Hidden Sample');
    }

    public function test_catalog_quick_add_uses_an_in_stock_variant_when_the_first_variant_is_sold_out(): void
    {
        $product = Product::query()->create([
            'name' => 'Mixed Stock Product',
            'slug' => 'mixed-stock-product',
            'is_active' => true,
        ]);

        $soldOut = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'MIXED-STOCK-BLACK',
            'color' => 'Black',
            'price' => 100,
            'stock' => 0,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $available = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'MIXED-STOCK-WHITE',
            'color' => 'White',
            'price' => 120,
            'stock' => 4,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertSee('data-cart-add="' . $available->getKey() . '"', false)
            ->assertDontSee('data-cart-add="' . $soldOut->getKey() . '"', false);
    }

    public function test_product_detail_shows_variant_and_returns_404_for_inactive_product(): void
    {
        $category = Category::create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Everyday Icon',
            'slug' => 'the-everyday-icon',
            'short_description' => 'An everyday statement.',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VISION-BAG-001',
            'color' => 'Tan',
            'price' => 12900,
            'sale_price' => 10900,
            'stock' => 4,
            'is_active' => true,
        ]);

        $response = $this->get('/products/the-everyday-icon');
        $response->assertOk();
        $response->assertSee('The Everyday Icon');
        $response->assertSee('An everyday statement.');
        $response->assertSee('10,900');

        $product->update(['is_active' => false]);
        $this->get('/products/the-everyday-icon')->assertNotFound();
    }
}
