<?php

namespace Tests\Feature;

use App\Models\FittingRoomLook;
use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
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
        ]);
        $admin->forceFill(['is_admin' => true])->save();

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

    public function test_shop_payload_uses_database_scene_urls_and_variant_mappings(): void
    {
        $mannequin = FittingRoomMannequin::query()->create([
            'code' => 'test-women-front',
            'label' => 'Test mannequin',
            'audience' => 'women',
            'pose_key' => 'front-neutral',
            'canvas_width' => 640,
            'canvas_height' => 960,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $base = $mannequin->media()->create([
            'collection' => 'fitting-room-mannequin',
            'disk' => 'public',
            'path' => 'https://assets.example.test/s0.jpg',
            'original_name' => 's0.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'width' => 640,
            'height' => 960,
            'alt_text' => 'Test mannequin',
            'sort_order' => 0,
        ]);
        $mannequin->base_media_id = $base->getKey();
        $mannequin->save();

        $variants = [];
        foreach ([
            ['name' => 'Studio top', 'slug' => 'studio-top', 'sku' => 'STUDIO-TOP-001', 'slot' => 'top'],
            ['name' => 'Studio bottom', 'slug' => 'studio-bottom', 'sku' => 'STUDIO-BOTTOM-001', 'slot' => 'bottom'],
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
                'price' => 120000,
                'stock' => 3,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);
            $variant->media()->create([
                'collection' => 'gallery',
                'disk' => 'public',
                'path' => 'https://assets.example.test/' . $item['slot'] . '.png',
                'original_name' => $item['slot'] . '.png',
                'mime_type' => 'image/png',
                'size' => 1,
                'width' => 420,
                'height' => 420,
                'alt_text' => $item['name'],
                'sort_order' => 0,
            ]);
            $variants[$item['slot']] = $variant;
        }

        $look = FittingRoomLook::query()->create([
            'mannequin_id' => $mannequin->getKey(),
            'code' => 'test-outfit',
            'label' => 'Test outfit',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $sceneIds = [];
        foreach ([
            'preview_top' => ['collection' => 'fitting-room-scene', 'path' => 'https://assets.example.test/T.jpg'],
            'preview_bottom' => ['collection' => 'fitting-room-scene', 'path' => 'https://assets.example.test/B.jpg'],
            'preview_combined' => ['collection' => 'fitting-room-scene', 'path' => 'https://assets.example.test/TB.jpg'],
            'preview_suggested' => ['collection' => 'fitting-room-scene', 'path' => 'https://assets.example.test/S.jpg'],
        ] as $key => $scene) {
            $media = $look->media()->create([
                'collection' => $scene['collection'],
                'disk' => 'public',
                'path' => $scene['path'],
                'original_name' => basename($scene['path']),
                'mime_type' => 'image/jpeg',
                'size' => 1,
                'width' => 420,
                'height' => 630,
                'alt_text' => $key,
                'sort_order' => 0,
            ]);
            $sceneIds[$key] = $media->getKey();
        }

        $look->fill([
            'preview_top_media_id' => $sceneIds['preview_top'],
            'preview_bottom_media_id' => $sceneIds['preview_bottom'],
            'preview_combined_media_id' => $sceneIds['preview_combined'],
            'preview_suggested_media_id' => $sceneIds['preview_suggested'],
        ])->save();

        FittingRoomLookItem::query()->create([
            'look_id' => $look->getKey(),
            'product_variant_id' => $variants['top']->getKey(),
            'slot' => 'top',
            'fit_y' => 0.38,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        FittingRoomLookItem::query()->create([
            'look_id' => $look->getKey(),
            'product_variant_id' => $variants['bottom']->getKey(),
            'slot' => 'bottom',
            'fit_y' => 0.66,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->get('/shop');
        $response->assertOk()
            ->assertSee('window.SHOP', false)
            ->assertSee('assets.example.test', false)
            ->assertSee('"T":', false)
            ->assertSee('"B":', false)
            ->assertSee('"TB":', false)
            ->assertSee('"S":', false)
            ->assertSee('"slot":"top"', false)
            ->assertSee('"slot":"bottom"', false);
    }

    public function test_shop_uses_the_real_combined_photo_when_a_single_garment_has_no_isolated_scene(): void
    {
        $mannequin = FittingRoomMannequin::query()->create([
            'code' => 'combined-only-mannequin',
            'label' => 'Combined only mannequin',
            'audience' => 'women',
            'pose_key' => 'front-neutral',
            'canvas_width' => 640,
            'canvas_height' => 960,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $base = $mannequin->media()->create([
            'collection' => 'fitting-room-mannequin',
            'disk' => 'public',
            'path' => 'https://assets.example.test/base-only.jpg',
            'original_name' => 'base-only.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'width' => 640,
            'height' => 960,
            'alt_text' => 'Combined only mannequin',
            'sort_order' => 0,
        ]);
        $mannequin->base_media_id = $base->getKey();
        $mannequin->save();

        $product = Product::query()->create([
            'name' => 'Combined only top',
            'slug' => 'combined-only-top',
            'audience' => 'women',
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'COMBINED-TOP-001',
            'price' => 120000,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $look = FittingRoomLook::query()->create([
            'mannequin_id' => $mannequin->getKey(),
            'code' => 'combined-only-look',
            'label' => 'Combined only look',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $combined = $look->media()->create([
            'collection' => 'fitting-room-scene',
            'disk' => 'public',
            'path' => 'https://assets.example.test/combined-only.jpg',
            'original_name' => 'combined-only.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'width' => 420,
            'height' => 630,
            'alt_text' => 'Model wearing the mapped garment',
            'sort_order' => 0,
        ]);
        $look->preview_combined_media_id = $combined->getKey();
        $look->save();

        FittingRoomLookItem::query()->create([
            'look_id' => $look->getKey(),
            'product_variant_id' => $variant->getKey(),
            'slot' => 'top',
            'fit_y' => 0.38,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('combined-only.jpg', false)
            ->assertSee('"slot":"top"', false)
            ->assertSee('"o":' . $look->getKey(), false);
    }

    public function test_shop_keeps_outfit_recommendations_compact_and_removes_comparison_copy(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('fit-backdrop', false)
            ->assertSee('fit-recommend-grid', false)
            ->assertSee('fit-recommendations', false)
            ->assertDontSee('fit-rec-copy', false)
            ->assertDontSee('fit-compare', false)
            ->assertDontSee('pcount', false)
            ->assertDontSee('id="picks"', false)
            ->assertDontSee('id="tot"', false)
            ->assertDontSee('data-rec-slot', false)
            ->assertDontSee('fit-recommend-caption', false)
            ->assertDontSee('stageMessage', false)
            ->assertDontSee('روی چوب‌لباسی هر محصول', false)
            ->assertDontSee('اتاق پرو استودیویی</a>', false)
            ->assertSee('id="addset"', false)
            ->assertSee('id="strip"', false);
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
