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
            ->assertSee('مرحلهٔ اول هنوز تکمیل نشده است.')
            ->assertDontSee('name="preview_top"', false)
            ->assertDontSee('name="preview_bottom"', false)
            ->assertDontSee('name="preview_combined"', false);

        $mannequin = FittingRoomMannequin::query()->create([
            'code' => 'admin-gallery-mannequin',
            'label' => 'Admin gallery mannequin',
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
            'path' => 'https://assets.example.test/admin-gallery-base.jpg',
            'original_name' => 'admin-gallery-base.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'width' => 640,
            'height' => 960,
            'alt_text' => 'Admin gallery base',
            'sort_order' => 0,
        ]);
        $mannequin->forceFill(['base_media_id' => $base->getKey()])->save();

        $this->actingAs($admin)
            ->get(route('commerce-admin.try-on.index'))
            ->assertOk()
            ->assertSee('name="preview_top"', false)
            ->assertSee('name="preview_bottom"', false)
            ->assertSee('name="preview_combined"', false)
            ->assertSee('data-fr-scene-input="top"', false)
            ->assertSee('data-fr-scene-input="bottom"', false)
            ->assertSee('data-fr-scene-input="combined"', false)
            ->assertDontSee('name="preview_suggested"', false)
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

        $unmappedProduct = Product::query()->create([
            'name' => 'شلوار مستقل',
            'slug' => 'unmapped-independent-trousers',
            'audience' => 'women',
            'is_active' => true,
        ]);
        $unmappedVariant = ProductVariant::query()->create([
            'product_id' => $unmappedProduct->getKey(),
            'sku' => 'UNMAPPED-TROUSERS-001',
            'price' => 140000,
            'stock' => 4,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);
        $unmappedVariant->media()->create([
            'collection' => 'gallery',
            'disk' => 'public',
            'path' => 'https://assets.example.test/unmapped-trousers.png',
            'original_name' => 'unmapped-trousers.png',
            'mime_type' => 'image/png',
            'size' => 1,
            'width' => 420,
            'height' => 420,
            'alt_text' => 'Unmapped trousers',
            'sort_order' => 0,
        ]);

        $response = $this->get('/shop');
        $response->assertOk()
            ->assertSee('window.SHOP', false)
            ->assertSee('"name":"شلوار مستقل"', false)
            ->assertSee('"slot":"bottom"', false)
            ->assertSee('"scene":null', false)
            ->assertSee('unmapped-trousers.png', false)
            ->assertSee('assets.example.test', false)
            ->assertSee('"T":', false)
            ->assertSee('"B":', false)
            ->assertSee('"TB":', false)
            ->assertSee('"S":', false)
            ->assertSee('"slot":"top"', false)
            ->assertSee('"slot":"bottom"', false);
    }

    public function test_shop_includes_outfit_recommendations_and_saved_style_comparison(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('fit-recommend-grid', false)
            ->assertSee('fit-recommendations', false)
            ->assertDontSee('data-rec-slot', false)
            ->assertDontSee('fit-recommend-caption', false)
            ->assertDontSee('stageMessage', false)
            ->assertDontSee('روی چوب‌لباسی هر محصول', false)
            ->assertSee('<details class="fit-compare"', false)
            ->assertSee('fit-compare-grid', false)
            ->assertSee('saveCompareA', false)
            ->assertSee('saveCompareB', false)
            ->assertDontSee('اتاق پرو استودیویی</a>', false);
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


    public function test_shop_try_on_does_not_require_a_predefined_tb_pair_and_replaces_same_slot(): void
    {
        $source = file_get_contents(resource_path('js/vision-shop.js'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString('برای این ست، عکس یکپارچهٔ مانکن ثبت نشده.', $source);
        $this->assertStringNotContainsString('برای پرو مجازی، این محصول را در استودیو به ست و تصویر مانکن وصل کن.', $source);
        $this->assertStringContainsString('if (item.slot === product.slot && W.has(Number(item.id))) W.delete(Number(item.id));', $source);
        $this->assertStringContainsString('exactOutfitScene(top, bottom)', $source);
        $this->assertStringContainsString('getProductLayer(product, slot)', $source);
    }

    public function test_remapping_a_look_requires_fresh_scene_photos_for_the_new_garment_pair(): void
    {
        $admin = User::query()->create([
            'name' => 'Studio Admin',
            'email' => 'studio-remap@example.test',
            'password' => 'studio-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $mannequin = FittingRoomMannequin::query()->create([
            'code' => 'remap-test-mannequin',
            'label' => 'Remap test mannequin',
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
            'path' => 'https://assets.example.test/remap-base.jpg',
            'original_name' => 'remap-base.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'width' => 640,
            'height' => 960,
            'alt_text' => 'Remap base',
            'sort_order' => 0,
        ]);
        $mannequin->forceFill(['base_media_id' => $base->getKey()])->save();

        $variants = [];
        foreach ([
            ['name' => 'Original top', 'slug' => 'remap-original-top', 'sku' => 'REMAP-ORIGINAL-TOP'],
            ['name' => 'Replacement top', 'slug' => 'remap-replacement-top', 'sku' => 'REMAP-REPLACEMENT-TOP'],
            ['name' => 'Original bottom', 'slug' => 'remap-original-bottom', 'sku' => 'REMAP-ORIGINAL-BOTTOM'],
        ] as $item) {
            $product = Product::query()->create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'audience' => 'women',
                'is_active' => true,
            ]);
            $variants[$item['sku']] = ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => $item['sku'],
                'price' => 120000,
                'stock' => 3,
                'low_stock_threshold' => 1,
                'is_active' => true,
            ]);
        }

        $look = FittingRoomLook::query()->create([
            'mannequin_id' => $mannequin->getKey(),
            'code' => 'remap-test-look',
            'label' => 'Original outfit',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $scenes = [];
        foreach ([
            'preview_top' => 'original-top.jpg',
            'preview_bottom' => 'original-bottom.jpg',
            'preview_combined' => 'original-combined.jpg',
        ] as $field => $filename) {
            $scenes[$field] = $look->media()->create([
                'collection' => 'fitting-room-scene',
                'disk' => 'public',
                'path' => 'https://assets.example.test/' . $filename,
                'original_name' => $filename,
                'mime_type' => 'image/jpeg',
                'size' => 1,
                'width' => 420,
                'height' => 630,
                'alt_text' => $filename,
                'sort_order' => 0,
            ]);
        }
        $look->forceFill([
            'preview_top_media_id' => $scenes['preview_top']->getKey(),
            'preview_bottom_media_id' => $scenes['preview_bottom']->getKey(),
            'preview_combined_media_id' => $scenes['preview_combined']->getKey(),
        ])->save();

        FittingRoomLookItem::query()->create([
            'look_id' => $look->getKey(),
            'product_variant_id' => $variants['REMAP-ORIGINAL-TOP']->getKey(),
            'slot' => 'top',
            'fit_y' => 0.38,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        FittingRoomLookItem::query()->create([
            'look_id' => $look->getKey(),
            'product_variant_id' => $variants['REMAP-ORIGINAL-BOTTOM']->getKey(),
            'slot' => 'bottom',
            'fit_y' => 0.66,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)
            ->from(route('commerce-admin.try-on.index'))
            ->put(route('commerce-admin.try-on.looks.update', $look), [
                'mannequin_id' => $mannequin->getKey(),
                'code' => $look->code,
                'label' => 'Changed top without new scenes',
                'top_variant_id' => $variants['REMAP-REPLACEMENT-TOP']->getKey(),
                'bottom_variant_id' => $variants['REMAP-ORIGINAL-BOTTOM']->getKey(),
                'fit_y_top' => 0.38,
                'fit_y_bottom' => 0.66,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('preview_top');

        $this->actingAs($admin)
            ->from(route('commerce-admin.try-on.index'))
            ->put(route('commerce-admin.try-on.looks.update', $look), [
                'mannequin_id' => $mannequin->getKey(),
                'code' => $look->code,
                'label' => 'Changed top with only a new top scene',
                'top_variant_id' => $variants['REMAP-REPLACEMENT-TOP']->getKey(),
                'bottom_variant_id' => $variants['REMAP-ORIGINAL-BOTTOM']->getKey(),
                'fit_y_top' => 0.38,
                'fit_y_bottom' => 0.66,
                'is_active' => '1',
                'preview_top' => \Illuminate\Http\UploadedFile::fake()->image('replacement-top.jpg', 420, 630),
            ])
            ->assertSessionHasErrors('preview_combined');

        $this->assertDatabaseHas('fitting_room_look_items', [
            'look_id' => $look->getKey(),
            'slot' => 'top',
            'product_variant_id' => $variants['REMAP-ORIGINAL-TOP']->getKey(),
        ]);
        $this->assertDatabaseHas('fitting_room_looks', [
            'id' => $look->getKey(),
            'preview_top_media_id' => $scenes['preview_top']->getKey(),
            'preview_combined_media_id' => $scenes['preview_combined']->getKey(),
        ]);
    }

}
