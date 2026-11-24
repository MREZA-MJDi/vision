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
        $this->withoutVite();
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('lang="fa" dir="rtl"', false);
        $response->assertSee('home-hero__frame', false);
        $response->assertSee('استایل تو؛<br>امضای تو.', false);
        $response->assertDontSee('home-featured', false);
        $response->assertDontSee('home-brand-story', false);
        $response->assertSee('vision-home-benefits', false);
        $response->assertDontSee('vision-shop-collections', false);
        $response->assertSee('vision-shop-story', false);
        $response->assertSee('نماد کسب‌وکار', false);
        $response->assertSee('vision-footer', false);
        $response->assertDontSee('data-fitting-room', false);
    }

    public function test_shop_footer_has_contact_and_both_trust_slots(): void
    {
        $this->withoutVite();
        $this->get('/shop')->assertOk()
            ->assertSee('تماس با ما')
            ->assertSee('نماد اعتماد الکترونیکی')
            ->assertSee('نماد کسب‌وکار');
    }

    public function test_shop_exposes_progressive_catalog_and_fitting_controls(): void
    {
        $this->withoutVite();

        $this->get('/shop')
            ->assertOk()
            ->assertSee('id="fit"', false)
            ->assertSee('id="fit-recommend-grid"', false)
            ->assertSee('id="stage"', false)
            ->assertSee('id="saveCompareA"', false)
            ->assertSee('id="addset"', false);
    }

    public function test_shop_contains_filters_and_the_studio_drawer(): void
    {
        $this->withoutVite();
        $this->get('/shop')
            ->assertOk()
            ->assertSee('فیلترها')
            ->assertSee('id="fit"', false)
            ->assertSee('id="stage"', false)
            ->assertSee('id="grid"', false)
            ->assertSee('id="cats"', false)
            ->assertSee('اتاق پرو')
            ->assertDontSee('data-virtual-try-on', false)
            ->assertDontSee('data-vto-inline', false)
            ->assertSee('id="shop-config"', false);

        $html = (string) $this->get('/shop')->getContent();
        $this->assertNotFalse(strpos($html, 'id="fit"'), 'The fitting-room drawer must render on Shop.');
        $this->assertNotFalse(strpos($html, '<section class="shop-results"'), 'The product grid must render on the same page.');
    }

    public function test_shop_cards_use_real_variant_media_without_legacy_fitting_room_markup(): void
    {
        $this->withoutVite();

        $product = Product::query()->create([
            'name' => 'Photo-backed Shop Product',
            'slug' => 'photo-backed-shop-product',
            'short_description' => 'Variant-specific image preview.',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'PHOTO-BACKED-SHOP-001',
            'size' => 'M',
            'color' => 'Black',
            'price' => 129000,
            'stock' => 4,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);
        $photoUrl = 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a';
        $variant->media()->create([
            'collection' => 'gallery',
            'disk' => 'public',
            'path' => $photoUrl,
            'original_name' => 'black-tee.jpg',
            'mime_type' => 'image/jpeg',
            'alt_text' => 'Photo-backed Shop Product — Black',
            'sort_order' => 0,
        ]);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('photo-1583743814966-8936f5b7be1a', false)
            ->assertSee($product->slug, false)
            ->assertSee('data-product="' . $variant->getKey() . '"', false)
            ->assertDontSee('data-virtual-try-on', false)
            ->assertDontSee('data-vto-select-product', false)
            ->assertDontSee('data-fit-product', false);

        $this->get(route('products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('src="' . $photoUrl . '"', false)
            ->assertSee('data-image="' . $photoUrl . '"', false)
            ->assertDontSee('data-virtual-try-on', false)
            ->assertSee('امتحان این محصول در اتاق پرو استودیویی');
    }

    public function test_product_catalog_lists_active_products_and_hides_inactive_products(): void
    {
        $this->withoutVite();
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
        $this->withoutVite();
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
            ->assertSee('data-product="' . $available->getKey() . '"', false)
            ->assertDontSee('data-product="' . $soldOut->getKey() . '"', false);
    }

    public function test_product_detail_shows_variant_and_returns_404_for_inactive_product(): void
    {
        $this->withoutVite();

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
        $response->assertSee('۱۰٬۹۰۰');

        $product->update(['is_active' => false]);
        $this->get('/products/the-everyday-icon')->assertNotFound();
    }

    public function test_shop_returns_one_page_of_cards_and_keeps_filters_on_next_page(): void
    {
        $this->withoutVite();
        $category = Category::query()->create(['name' => 'پوشاک', 'slug' => 'paged-clothing', 'is_active' => true]);
        foreach (range(1, 14) as $index) {
            $product = Product::query()->create([
                'name' => "Paged Garment {$index}",
                'slug' => "paged-garment-{$index}",
                'category_id' => $category->id,
                'is_active' => true,
            ]);
            $product->variants()->create([
                'sku' => "PAGED-{$index}",
                'price' => $index * 100,
                'stock' => 2,
                'is_active' => true,
            ]);
        }

        $filters = ['category' => $category->slug, 'sort' => 'price_asc'];
        $first = $this->get(route('shop.index', $filters))->assertOk();
        $this->assertSame(12, substr_count($first->getContent(), 'class="shop-product-card"'));
        $first->assertSee('rel="next"', false)->assertDontSee('paged-garment-14?', false);
        $next = $this->get(route('shop.index', $filters + ['page' => 2]))->assertOk();
        $this->assertSame(2, substr_count($next->getContent(), 'class="shop-product-card"'));
        $next->assertSee('paged-garment-14?', false)->assertSee('rel="prev"', false);
    }

}
