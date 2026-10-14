<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CommerceDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommerceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_opt_in_seed_creates_real_catalog_variants_images_and_inventory_history(): void
    {
        config([
            'commerce-admin.currency_symbol' => 'تومان',
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(CommerceDemoSeeder::class);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('brands', 1);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 5);
        $this->assertDatabaseCount('media', 5);
        $this->assertDatabaseCount('inventory_movements', 5);

        $black = ProductVariant::query()
            ->with(['product', 'primaryGalleryMedia'])
            ->where('sku', 'VISION-DEMO-TEE-BLACK')
            ->firstOrFail();

        $this->assertSame('demo-essential-cotton-tee', $black->product->slug);
        $this->assertSame('مشکی', $black->color);
        $this->assertSame('demo-products/tee-black.svg', $black->primaryGalleryMedia->path);
        $this->assertTrue(Storage::disk('public')->exists('demo-products/tee-black.svg'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $black->getKey(),
            'type' => 'adjustment',
            'quantity' => 15,
            'stock_after' => 15,
            'reference_id' => $black->product->getKey(),
            'note' => 'Initial stock set by CommerceDemoSeeder.',
        ]);

        $this->get('/products/demo-essential-cotton-tee')
            ->assertOk()
            ->assertSee('data-variant-pick', false)
            ->assertSee('data-image=', false)
            ->assertSee('tee-black.svg')
            ->assertSee('1,290,000 تومان')
            ->assertSee('1,090,000 تومان');
    }

    public function test_demo_seeders_are_idempotent(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(CommerceDemoSeeder::class);
        $this->seed(CommerceDemoSeeder::class);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('brands', 1);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 5);
        $this->assertDatabaseCount('media', 5);
        $this->assertDatabaseCount('inventory_movements', 5);
    }

    public function test_normal_database_seeder_does_not_add_demo_catalog_records(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_variants', 0);
    }
}
