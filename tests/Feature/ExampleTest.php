<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
