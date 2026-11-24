<?php

namespace Tests\Feature;

use App\Models\FittingRoomLook;
use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_search_variants_outside_the_default_recent_list(): void
    {
        $admin = User::query()->create([
            'name' => 'Studio Search Admin',
            'email' => 'studio-search@example.test',
            'password' => 'studio-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();
        $product = Product::query()->create([
            'name' => 'Unique Studio Garment', 'slug' => 'unique-studio-garment',
            'audience' => 'women', 'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->getKey(), 'sku' => 'STUDIO-SEARCH-UNIQUE',
            'price' => 120000, 'stock' => 2, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('commerce-admin.try-on.index', ['variant_search' => 'STUDIO-SEARCH-UNIQUE']))
            ->assertOk()
            ->assertSee('STUDIO-SEARCH-UNIQUE')
            ->assertSee('جست‌وجو در کل محصولات');
    }

    public function test_admin_variant_picker_paginates_large_catalogues(): void
    {
        $admin = User::query()->create([
            'name' => 'Pagination Admin',
            'email' => 'pagination-admin@example.test',
            'password' => 'test-password',
        ]);
        $admin->forceFill(['is_admin' => true])->save();
        $product = Product::query()->create([
            'name' => 'Bulk Studio Product', 'slug' => 'bulk-studio-product',
            'audience' => 'women', 'is_active' => true,
        ]);
        foreach (range(1, 101) as $index) {
            ProductVariant::query()->create([
                'product_id' => $product->getKey(),
                'sku' => sprintf('STUDIO-BULK-%03d', $index),
                'price' => 100000, 'stock' => 2, 'is_active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('commerce-admin.try-on.index', ['variant_search' => 'STUDIO-BULK']))
            ->assertOk()
            ->assertSee('صفحه بعد')
            ->assertSee('STUDIO-BULK-101')
            ->assertDontSee('STUDIO-BULK-001');

        $this->get(route('commerce-admin.try-on.index', ['variant_search' => 'STUDIO-BULK', 'variant_page' => 2]))
            ->assertOk()
            ->assertSee('STUDIO-BULK-001');
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
            ->assertSee('id="shop-config"', false)
            ->assertDontSee('s0.jpg', false)
            ->assertDontSee('TB.jpg', false)
            ->assertDontSee('"garments":', false);

        $this->getJson(route('shop.studio'))
            ->assertOk()
            ->assertJsonPath('base', 'https://assets.example.test/s0.jpg')
            ->assertJsonPath('garments.0.scene', 'https://assets.example.test/T.jpg')
            ->assertJsonPath('garments.1.scene', 'https://assets.example.test/B.jpg')
            ->assertJsonPath('combinations.' . $variants['top']->id . '.' . $variants['bottom']->id, 'https://assets.example.test/TB.jpg')
            ->assertJsonPath('suggestions.' . $variants['top']->id, 'https://assets.example.test/S.jpg');
    }

    public function test_admin_can_upload_shop_media_and_unpublish_a_look(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $admin = User::query()->create([
            'name' => 'Studio Media Admin',
            'email' => 'studio-media@example.test',
            'password' => 'studio-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();
        $product = Product::query()->create([
            'name' => 'Studio Upload Garment',
            'slug' => 'studio-upload-garment',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'STUDIO-UPLOAD',
            'price' => 150000,
            'stock' => 4,
            'is_active' => true,
        ]);
        $image = static fn (string $name) => UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAIAAAA2iEnWAAAAEklEQVR4nGM4cPb9hVX7GVAoAJUuDiBJkuwvAAAAAElFTkSuQmCC'
        ));

        $this->actingAs($admin)
            ->post(route('commerce-admin.try-on.mannequins.store'), [
                'code' => 'upload-mannequin',
                'label' => 'Upload mannequin',
                'audience' => 'all',
                'is_active' => 1,
                'base_image' => $image('base.png'),
            ])->assertRedirect()->assertSessionHasNoErrors();

        $mannequin = FittingRoomMannequin::query()->firstOrFail();
        $fields = [
            'mannequin_id' => $mannequin->id,
            'code' => 'upload-look',
            'label' => 'Upload look',
            'top_variant_id' => $variant->id,
        ];

        $this->post(route('commerce-admin.try-on.looks.store'), $fields + [
            'is_active' => 1,
            'preview_top' => $image('top.png'),
            'preview_suggested' => $image('suggestion.png'),
            'top_product_image' => $image('card.png'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $look = FittingRoomLook::query()->firstOrFail();
        $this->assertTrue($look->is_active);
        Storage::disk('public')->assertExists($variant->primaryGalleryMedia()->firstOrFail()->path);
        $this->get(route('shop.index'))->assertOk()
            ->assertSee('data-t="' . $variant->id . '"', false);
        $this->getJson(route('shop.studio'))->assertOk()
            ->assertJsonPath('suggestions.' . $variant->id, $look->previewSuggestedMedia->url);

        $this->put(route('commerce-admin.try-on.looks.update', $look), $fields)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($look->fresh()->is_active);
        $this->get(route('shop.index'))->assertOk()->assertDontSee('data-t="' . $variant->id . '"', false);
    }

    public function test_public_fitting_studio_never_mixes_scenes_from_different_mannequins(): void
    {
        $first = FittingRoomMannequin::query()->create([
            'code' => 'first-studio', 'label' => 'First', 'audience' => 'all',
            'pose_key' => 'front-neutral', 'canvas_width' => 420, 'canvas_height' => 630,
            'is_active' => true, 'sort_order' => 0,
        ]);
        $second = FittingRoomMannequin::query()->create([
            'code' => 'second-studio', 'label' => 'Second', 'audience' => 'all',
            'pose_key' => 'front-neutral', 'canvas_width' => 420, 'canvas_height' => 630,
            'is_active' => true, 'sort_order' => 1,
        ]);
        foreach ([$first, $second] as $mannequin) {
            $media = $mannequin->media()->create([
                'collection' => 'fitting-room-mannequin', 'disk' => 'public',
                'path' => 'https://assets.example.test/base-' . $mannequin->code . '.jpg',
                'original_name' => 'base.jpg', 'mime_type' => 'image/jpeg',
                'size' => 1, 'width' => 420, 'height' => 630, 'sort_order' => 0,
            ]);
            $mannequin->base_media_id = $media->getKey();
            $mannequin->save();

            $look = FittingRoomLook::query()->create([
                'mannequin_id' => $mannequin->getKey(),
                'code' => 'look-' . $mannequin->code, 'label' => 'Look',
                'is_active' => true, 'sort_order' => 0,
            ]);
            $scene = $look->media()->create([
                'collection' => 'fitting-room-scene', 'disk' => 'public',
                'path' => 'https://assets.example.test/scene-' . $mannequin->code . '.jpg',
                'original_name' => 'scene.jpg', 'mime_type' => 'image/jpeg',
                'size' => 1, 'width' => 420, 'height' => 630, 'sort_order' => 0,
            ]);
            $look->preview_top_media_id = $scene->getKey();
            $look->save();
        }

        $this->getJson(route('shop.studio'))->assertOk()
            ->assertJsonPath('base', 'https://assets.example.test/base-first-studio.jpg')
            ->assertDontSee('base-second-studio.jpg', false);
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
            ->assertSee('اتاق پرو استودیویی</a>', false);
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
