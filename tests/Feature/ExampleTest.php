<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_storefront_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('New Arrivals')
            ->assertSee('Fitting room')
            ->assertSee('Your edit starts here.');
    }

    public function test_the_shop_alias_uses_the_same_public_storefront(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('New Arrivals');
    }

    public function test_admin_kit_routes_exist_and_protect_private_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('commerce-admin.login'));
        $this->get('/admin/products')->assertRedirect(route('commerce-admin.login'));
        $this->get('/admin/login')->assertOk()->assertSee('Sign in to your store');

        $this->assertTrue(Route::has('commerce-admin.dashboard'));
        $this->assertTrue(Route::has('commerce-admin.products.index'));
        $this->assertTrue(Route::has('commerce-admin.orders.index'));
        $this->assertTrue(Route::has('commerce-admin.login'));
    }

    public function test_an_administrator_can_render_the_database_backed_dashboard(): void
    {
        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();

        $this->actingAs($administrator)
            ->get(route('commerce-admin.dashboard'))
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee('Total Revenue')
            ->assertSee('Store Activity')
            ->assertSee('Latest Orders');
    }

    public function test_reference_commerce_screens_render_and_remain_connected_to_catalog_and_orders(): void
    {
        $administrator = User::factory()->create();
        $administrator->forceFill(['is_admin' => true])->save();

        $this->actingAs($administrator)
            ->get(route('commerce-admin.analytics'))
            ->assertOk()
            ->assertSee('E-Commerce Dashboard')
            ->assertSee('Returning Rate')
            ->assertSee('Sales by Location')
            ->assertSee('Customer Reviews');

        $export = $this->get(route('commerce-admin.analytics.export'));
        $export->assertOk();
        $this->assertStringContainsString('text/csv', (string) $export->headers->get('content-type'));
        $this->assertStringContainsString('commerce-analytics.csv', (string) $export->headers->get('content-disposition'));

        $this->get(route('commerce-admin.products.index'))
            ->assertOk()
            ->assertSee('TOTAL SALES')
            ->assertSee('Rating')
            ->assertSee('Product list');

        $this->get(route('commerce-admin.products.create'))
            ->assertOk()
            ->assertSee('Product information');

        $this->post(route('commerce-admin.products.store'), [
            'name' => 'Reference View Sample',
            'slug' => '',
            'status' => 'active',
            'is_taxable' => '1',
            'variants' => [
                [
                    'sku' => 'REF-VIEW-001',
                    'barcode' => 'REF-VIEW-001',
                    'size' => 'M',
                    'color' => 'Black',
                    'color_code' => '#000000',
                    'price' => '49.00',
                    'sale_price' => '',
                    'stock' => '5',
                    'low_stock_threshold' => '2',
                    'is_active' => '1',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Reference View Sample')->firstOrFail();

        $this->get(route('commerce-admin.products.show', $product->getKey()))
            ->assertOk()
            ->assertSee('Reference View Sample')
            ->assertSee('Variants');

        $this->get(route('commerce-admin.orders.index'))
            ->assertOk()
            ->assertSee('Orders');
    }
}
