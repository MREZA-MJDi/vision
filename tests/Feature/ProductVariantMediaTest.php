<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_separate_images_for_each_variant_and_storefront_links_the_correct_image(): void
    {
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Variant Media Admin',
            'email' => 'variant-media-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->post(route('commerce-admin.products.store'), [
                'name' => 'Variant Photo Tee',
                'slug' => '',
                'status' => 'active',
                'variants' => [
                    [
                        'sku' => 'VARIANT-PHOTO-WHITE',
                        'color' => 'White',
                        'color_code' => '#F4F0E6',
                        'size' => 'M',
                        'price' => '1,200,000',
                        'sale_price' => '',
                        'stock' => '5',
                        'low_stock_threshold' => '2',
                        'is_active' => '1',
                        'images' => [UploadedFile::fake()->image('white-tee.png', 400, 500)],
                    ],
                    [
                        'sku' => 'VARIANT-PHOTO-BLACK',
                        'color' => 'Black',
                        'color_code' => '#17191C',
                        'size' => 'M',
                        'price' => '1,300,000',
                        'sale_price' => '',
                        'stock' => '7',
                        'low_stock_threshold' => '2',
                        'is_active' => '1',
                        'images' => [UploadedFile::fake()->image('black-tee.png', 400, 500)],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'variant-photo-tee')->firstOrFail();
        $white = ProductVariant::query()->where('sku', 'VARIANT-PHOTO-WHITE')->firstOrFail();
        $black = ProductVariant::query()->where('sku', 'VARIANT-PHOTO-BLACK')->firstOrFail();

        $whiteMedia = $white->primaryGalleryMedia()->firstOrFail();
        $blackMedia = $black->primaryGalleryMedia()->firstOrFail();

        $this->assertNotSame($whiteMedia->path, $blackMedia->path);
        $this->assertSame($white->getKey(), (int) $whiteMedia->mediable_id);
        $this->assertSame($black->getKey(), (int) $blackMedia->mediable_id);
        $this->assertSame(ProductVariant::class, $blackMedia->mediable_type);
        Storage::disk('public')->assertExists($whiteMedia->path);
        Storage::disk('public')->assertExists($blackMedia->path);

        $this->get(route('products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee($whiteMedia->url)
            ->assertSee($blackMedia->url)
            ->assertSee('data-variant-pick', false);

        $this->assertDatabaseCount('media', 2);
    }
}
