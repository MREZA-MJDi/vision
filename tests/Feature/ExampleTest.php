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

    public function test_admin_dashboard_and_login_routes_are_removed(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/products')->assertNotFound();
        $this->get('/login')->assertNotFound();

        $this->assertFalse(Route::has('admin.dashboard'));
        $this->assertFalse(Route::has('admin.products.index'));
        $this->assertFalse(Route::has('login'));
        $this->assertFalse(Route::has('logout'));
    }
}
