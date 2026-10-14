<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceAdminRenderAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_tabs_and_primary_record_views_render_from_database_records(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::query()->create([
            'name' => 'Render Audit Customer',
            'email' => 'render-customer@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);
        $category = Category::query()->create([
            'name' => 'Render Audit Category',
            'slug' => 'render-audit-category',
            'is_active' => true,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Render Audit Brand',
            'slug' => 'render-audit-brand',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'brand_id' => $brand->getKey(),
            'name' => 'Render Audit Product',
            'slug' => 'render-audit-product',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'RENDER-AUDIT-001',
            'size' => 'M',
            'price' => 25,
            'stock' => 4,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'user_id' => $customer->getKey(),
            'order_number' => 'VS-RENDER-AUDIT-001',
            'customer_name' => $customer->name,
            'customer_phone' => '555-0102',
            'customer_email' => $customer->email,
            'shipping_address' => '1 Render Audit Road',
            'shipping_city' => 'Baku',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 25,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 25,
            'placed_at' => now(),
        ]);
        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'product_name' => $product->name,
            'variant_name' => $variant->display_name,
            'sku' => $variant->sku,
            'unit_price' => 25,
            'quantity' => 1,
            'line_total' => 25,
            'tax_amount' => 0,
        ]);

        $this->actingAs($admin);

        $paths = [
            route('commerce-admin.dashboard'),
            route('commerce-admin.analytics'),
            route('commerce-admin.products.index'),
            route('commerce-admin.products.create'),
            route('commerce-admin.products.show', $product->getKey()),
            route('commerce-admin.products.edit', $product->getKey()),
            route('commerce-admin.categories.index'),
            route('commerce-admin.categories.create'),
            route('commerce-admin.categories.edit', $category->getKey()),
            route('commerce-admin.brands.index'),
            route('commerce-admin.brands.create'),
            route('commerce-admin.brands.edit', $brand->getKey()),
            route('commerce-admin.customers.index'),
            route('commerce-admin.customers.show', $customer->getKey()),
            route('commerce-admin.payments.index'),
            route('commerce-admin.inventory.index'),
            route('commerce-admin.reviews.index'),
            route('commerce-admin.orders.index'),
            route('commerce-admin.orders.create'),
            route('commerce-admin.orders.show', $order->getKey()),
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_form_requests_reject_invalid_product_order_inventory_and_login_input(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create([
            'name' => 'Validation Category',
            'slug' => 'validation-category',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Validation Product',
            'slug' => 'validation-product',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'VALIDATION-001',
            'price' => 10,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('commerce-admin.products.store'), [
                'name' => 'Invalid Sale Product',
                'status' => 'active',
                'variants' => [[
                    'sku' => 'INVALID-SALE-001',
                    'price' => '10.00',
                    'sale_price' => '10.00',
                    'stock' => '1',
                    'low_stock_threshold' => '1',
                    'is_active' => '1',
                ]],
            ])
            ->assertSessionHasErrors('variants.0.sale_price');

        $this->assertDatabaseMissing('products', ['name' => 'Invalid Sale Product']);

        $this->post(route('commerce-admin.orders.store'), [
            'customer_name' => 'Invalid Order',
            'payment_method' => 'cash_on_delivery',
            'shipping_cost' => 0,
            'items' => [],
        ])->assertSessionHasErrors('items');

        $this->post(route('commerce-admin.inventory.adjust', $variant->getKey()), [
            'adjustment' => 0,
            'note' => 'No change',
        ])->assertSessionHasErrors('adjustment');

        auth()->logout();

        $this->post(route('commerce-admin.login.store'), [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_bulk_archive_archives_only_selected_products_and_keeps_order_history(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create([
            'name' => 'Bulk Archive Category',
            'slug' => 'bulk-archive-category',
            'is_active' => true,
        ]);
        $selected = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Selected for Archive',
            'slug' => 'selected-for-archive',
            'is_active' => true,
        ]);
        $untouched = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Keep Active',
            'slug' => 'keep-active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('commerce-admin.products.bulk-archive'), [
                'product_ids' => [$selected->getKey()],
            ])
            ->assertRedirect(route('commerce-admin.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('products', ['id' => $selected->getKey()]);
        $this->assertDatabaseHas('products', ['id' => $untouched->getKey(), 'deleted_at' => null]);
    }

    public function test_admin_hero_selection_drives_homepage_and_replaces_previous_selection(): void
    {
        $admin = $this->makeAdmin();
        $oldHero = Product::query()->create([
            'name' => 'Previous Hero',
            'slug' => 'previous-hero',
            'is_active' => true,
            'is_hero' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $oldHero->getKey(),
            'sku' => 'PREVIOUS-HERO-001',
            'price' => 20,
            'stock' => 2,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('commerce-admin.products.store'), [
                'name' => 'New Homepage Hero',
                'status' => 'active',
                'is_hero' => '1',
                'variants' => [[
                    'price' => '35.00',
                    'stock' => '2',
                    'low_stock_threshold' => '1',
                    'is_active' => '1',
                ]],
            ])
            ->assertRedirect();

        $newHero = Product::query()->where('slug', 'new-homepage-hero')->firstOrFail();
        $this->assertTrue((bool) $newHero->is_hero);
        $this->assertFalse((bool) $oldHero->fresh()->is_hero);

        $this->get('/')->assertOk()->assertSee('New Homepage Hero')->assertSee('store-hero-product', false);
        $this->get('/products')->assertOk()->assertDontSee('store-hero-product', false);
    }

    public function test_order_variant_search_is_bounded_and_excludes_inactive_catalog_records(): void
    {
        $admin = $this->makeAdmin();
        $activeProduct = Product::query()->create([
            'name' => 'Searchable Active Shoe',
            'slug' => 'searchable-active-shoe',
            'is_active' => true,
        ]);
        $activeVariant = ProductVariant::query()->create([
            'product_id' => $activeProduct->getKey(),
            'sku' => 'SEARCH-ACTIVE-001',
            'price' => 40,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $activeProduct->getKey(),
            'sku' => 'SEARCH-INACTIVE-VARIANT',
            'price' => 40,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => false,
        ]);
        $inactiveProduct = Product::query()->create([
            'name' => 'Searchable Inactive Shoe',
            'slug' => 'searchable-inactive-shoe',
            'is_active' => false,
        ]);
        ProductVariant::query()->create([
            'product_id' => $inactiveProduct->getKey(),
            'sku' => 'SEARCH-INACTIVE-PRODUCT',
            'price' => 40,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('commerce-admin.orders.variants.search', ['q' => 'Searchable']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeVariant->getKey())
            ->assertJsonPath('data.0.label', 'Searchable Active Shoe · SEARCH-ACTIVE-001');
    }

    public function test_order_create_preserves_selected_variants_after_validation_redirect(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::query()->create([
            'name' => 'Old Input Product',
            'slug' => 'old-input-product',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'OLD-INPUT-001',
            'price' => 15,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('commerce-admin.orders.create'))
            ->post(route('commerce-admin.orders.store'), [
                'customer_name' => 'Invalid Email Order',
                'customer_email' => 'invalid-email',
                'payment_method' => 'cash_on_delivery',
                'shipping_cost' => 0,
                'items' => [['variant_id' => $variant->getKey(), 'quantity' => 2]],
            ])
            ->assertRedirect(route('commerce-admin.orders.create'))
            ->assertSessionHasErrors('customer_email');

        $this->get(route('commerce-admin.orders.create'))
            ->assertOk()
            ->assertSee('data-order-initial-variant-ids="' . $variant->getKey() . '"', false)
            ->assertSee('name="items[0][quantity]" value="2"', false);
    }

    public function test_order_list_query_count_stays_constant_as_order_count_grows(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::query()->create([
            'name' => 'Query Count Customer',
            'email' => 'query-count-customer@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Query Count Product',
            'slug' => 'query-count-product',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'QUERY-COUNT-001',
            'price' => 12,
            'stock' => 20,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $createOrder = function (int $number) use ($customer, $product, $variant): void {
            $order = Order::query()->create([
                'user_id' => $customer->getKey(),
                'order_number' => sprintf('VS-QUERY-%04d', $number),
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '555-0199',
                'shipping_address' => '1 Query Test Road',
                'shipping_city' => 'Baku',
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'cash_on_delivery',
                'subtotal' => 12,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => 12,
                'placed_at' => now(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->getKey(),
                'product_id' => $product->getKey(),
                'product_variant_id' => $variant->getKey(),
                'product_name' => $product->name,
                'variant_name' => $variant->display_name,
                'sku' => $variant->sku,
                'unit_price' => 12,
                'quantity' => 1,
                'line_total' => 12,
                'tax_amount' => 0,
            ]);
        };

        $createOrder(1);
        $connection = DB::connection();
        $connection->enableQueryLog();

        $this->actingAs($admin)->get(route('commerce-admin.orders.index'))->assertOk();
        $singleOrderQueryCount = count($connection->getQueryLog());

        for ($number = 2; $number <= 10; $number++) {
            $createOrder($number);
        }

        $connection->flushQueryLog();
        $this->get(route('commerce-admin.orders.index'))->assertOk();
        $tenOrderQueryCount = count($connection->getQueryLog());
        $connection->disableQueryLog();

        $this->assertSame($singleOrderQueryCount, $tenOrderQueryCount);
    }

    public function test_malformed_old_order_items_do_not_break_order_form_rendering(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('commerce-admin.orders.create'))
            ->post(route('commerce-admin.orders.store'), [
                'customer_name' => 'Malformed Items',
                'payment_method' => 'cash_on_delivery',
                'shipping_cost' => 0,
                'items' => ['not-an-item-row'],
            ])
            ->assertRedirect(route('commerce-admin.orders.create'))
            ->assertSessionHasErrors('items.0.variant_id');

        $this->get(route('commerce-admin.orders.create'))->assertOk();
    }

    private function makeAdmin(): User
    {
        $admin = User::query()->create([
            'name' => 'Render Audit Admin',
            'email' => 'render-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }
}
