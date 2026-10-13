<?php

namespace Tests\Feature;

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
}
