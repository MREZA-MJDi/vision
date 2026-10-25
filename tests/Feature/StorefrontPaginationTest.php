<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_catalog_is_paginated_on_the_server_and_keeps_search_global(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $product = Product::query()->create([
                'name' => sprintf('Paging Product %02d', $index),
                'slug' => sprintf('paging-product-%02d', $index),
                'audience' => 'all',
                'is_active' => true,
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);

            ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => sprintf('PAGING-%03d', $index),
                'price' => 100000 + ($index * 1000),
                'stock' => 3,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);
        }

        $this->get('/shop')
            ->assertOk()
            ->assertSee('class="store-pagination"', false)
            ->assertSee('"currentPage":1', false)
            ->assertSee('"total":13', false)
            ->assertSee('Paging Product 13')
            ->assertDontSee('Paging Product 01');

        $this->get('/shop?page=2')
            ->assertOk()
            ->assertSee('class="store-pagination"', false)
            ->assertSee('"currentPage":2', false)
            ->assertSee('"total":13', false)
            ->assertSee('Paging Product 01')
            ->assertDontSee('Paging Product 13');

        // Search runs in the database across every page, not just the 12 cards
        // currently serialized for the browser.
        $this->get('/shop?q=Paging%20Product%2001')
            ->assertOk()
            ->assertSee('"total":1', false)
            ->assertSee('Paging Product 01')
            ->assertDontSee('Paging Product 13');
    }
}
