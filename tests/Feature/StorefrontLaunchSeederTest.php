<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\StorefrontLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontLaunchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_seed_creates_editable_database_records_idempotently_without_phantom_media(): void
    {
        Storage::fake('public');
        $this->seed(StorefrontLaunchSeeder::class);
        $this->seed(StorefrontLaunchSeeder::class);

        $puffer = Product::query()->where('slug', 'vision-solar-puffer')->firstOrFail();
        $coat = Product::query()->where('slug', 'vision-chestnut-coat')->firstOrFail();

        $this->assertTrue((bool) $puffer->is_hero);
        $this->assertTrue((bool) $coat->is_hero);
        $this->assertNotNull($puffer->getKey());
        $this->assertNotNull($coat->getKey());
        $this->assertSame('#E96512', $puffer->activeVariants()->firstOrFail()->color_code);
        $this->assertSame('#704231', $coat->activeVariants()->firstOrFail()->color_code);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('product_variants', ['product_id' => $puffer->getKey(), 'sku' => 'VIS-SOLAR-PUFFER-ORANGE']);
        $this->assertDatabaseHas('product_variants', ['product_id' => $coat->getKey(), 'sku' => 'VIS-CHESTNUT-COAT-BROWN']);
        $this->assertDatabaseMissing('media', ['path' => 'seed-products/vision-orange-puffer-v1.png']);
        $this->assertDatabaseMissing('media', ['path' => 'seed-products/vision-chestnut-coat-v1.png']);

        $admin = User::query()->create([
            'name' => 'Seed CRUD Admin',
            'email' => 'seed-crud-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();
        $variant = $puffer->variants()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('commerce-admin.products.update', $puffer->getKey()), [
                'name' => 'Edited Seeded Product',
                'slug' => $puffer->slug,
                'status' => 'active',
                'is_featured' => '1',
                'is_hero' => '1',
                'variants' => [[
                    'id' => $variant->getKey(),
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'color_code' => $variant->color_code,
                    'price' => (string) $variant->price,
                    'sale_price' => '',
                    'stock' => (string) $variant->stock,
                    'low_stock_threshold' => (string) $variant->low_stock_threshold,
                    'is_active' => '1',
                ]],
            ])
            ->assertRedirect(route('commerce-admin.products.show', $puffer->getKey()))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $puffer->getKey(), 'name' => 'Edited Seeded Product', 'is_hero' => true]);

        $this->delete(route('commerce-admin.products.destroy', $puffer->getKey()))
            ->assertRedirect(route('commerce-admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $puffer->getKey()]);
    }
}
