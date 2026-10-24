<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FittingRoomStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_fitting_room_studio_exposes_mannequin_and_scene_galleries(): void
    {
        $admin = User::query()->create([
            'name' => 'Studio Admin',
            'email' => 'studio-admin@example.test',
            'password' => 'studio-password-123',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('commerce-admin.try-on.index'))
            ->assertOk()
            ->assertSee('Fitting Room Studio')
            ->assertSee('name="base_image"', false)
            ->assertSee('name="preview_top"', false)
            ->assertSee('name="preview_bottom"', false)
            ->assertSee('name="preview_combined"', false)
            ->assertSee('name="preview_suggested"', false)
            ->assertSee('name="top_variant_id"', false)
            ->assertSee('name="bottom_variant_id"', false);
    }

    public function test_selected_outfit_variants_are_added_to_the_database_cart_together(): void
    {
        $variantIds = [];

        foreach ([
            ['name' => 'Fitting Top', 'slug' => 'fitting-top', 'sku' => 'FIT-TOP-001', 'price' => 120000],
            ['name' => 'Fitting Bottom', 'slug' => 'fitting-bottom', 'sku' => 'FIT-BOTTOM-001', 'price' => 180000],
        ] as $item) {
            $product = Product::query()->create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'audience' => 'women',
                'is_active' => true,
            ]);

            $variant = ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => $item['sku'],
                'price' => $item['price'],
                'stock' => 3,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);

            $variantIds[] = $variant->getKey();
        }

        $this->postJson(route('cart.items.bulk'), ['variant_ids' => $variantIds])
            ->assertCreated()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', 'ست انتخاب‌شده به سبد خرید اضافه شد.');

        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variantIds[0], 'quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variantIds[1], 'quantity' => 1]);
    }

    public function test_bulk_add_rejects_a_set_when_one_variant_is_out_of_stock(): void
    {
        $variantIds = [];

        foreach ([2, 0] as $index => $stock) {
            $product = Product::query()->create([
                'name' => 'Stock Set Product ' . $index,
                'slug' => 'stock-set-product-' . $index,
                'audience' => 'women',
                'is_active' => true,
            ]);

            $variant = ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => 'STOCK-SET-' . $index,
                'price' => 100000,
                'stock' => $stock,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);

            $variantIds[] = $variant->getKey();
        }

        $this->postJson(route('cart.items.bulk'), ['variant_ids' => $variantIds])
            ->assertUnprocessable();

        $this->assertDatabaseCount('cart_items', 0);
    }
}
