<?php

namespace Tests\Feature;

use App\Models\FittingRoomLook;
use App\Models\FittingRoomMannequin;
use App\Models\ProductVariant;
use Database\Seeders\FittingRoomAssetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopAssetSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_seed_populates_catalog_and_fitting_room_and_can_be_repeated(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $this->seed(FittingRoomAssetSeeder::class);
        $this->assertDatabaseCount('products', 17);
        $this->assertDatabaseCount('product_variants', 17);
        $this->assertDatabaseCount('fitting_room_mannequins', 1);
        $this->assertDatabaseCount('fitting_room_looks', 12);
        $variant = ProductVariant::query()->where('sku', 'VIS-CHESTNUT-COAT-BROWN')->firstOrFail();
        $this->assertNotNull($variant->primaryGalleryMedia);
        Storage::disk('public')->assertExists($variant->primaryGalleryMedia->path);
        $mannequin = FittingRoomMannequin::query()->where('code', 'vision-studio-default')->firstOrFail();
        Storage::disk('public')->assertExists($mannequin->baseMedia->path);
        $look = FittingRoomLook::query()->where('code', 'look-03')->firstOrFail();
        $this->assertNotNull($look->preview_top_media_id);
        $this->assertDatabaseHas('fitting_room_look_items', [
            'look_id' => $look->id, 'product_variant_id' => $variant->id, 'slot' => 'top',
        ]);

        $variant->update(['price' => 1234567]);
        $mediaCount = \App\Models\Media::query()->count();
        $this->seed(FittingRoomAssetSeeder::class);
        $this->assertDatabaseCount('products', 17);
        $this->assertDatabaseCount('product_variants', 17);
        $this->assertDatabaseCount('fitting_room_looks', 12);
        $this->assertSame($mediaCount, \App\Models\Media::query()->count());
        $this->assertEquals(1234567, $variant->fresh()->price);

        $this->get('/shop')->assertOk()->assertSee('اتاق پرو');
        $this->get('/products/vision-chestnut-coat')->assertOk()->assertSee('پالتوی بلند قهوه‌ای');
        $this->getJson(route('shop.studio'))->assertOk();
    }
}
