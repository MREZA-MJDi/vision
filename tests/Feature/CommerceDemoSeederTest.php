<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CommerceDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertSame(
            'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=max&w=1200&q=85',
            $black->primaryGalleryMedia->path
        );
        $this->assertSame('image/jpeg', $black->primaryGalleryMedia->mime_type);
        $this->assertSame('Unsplash', $black->primaryGalleryMedia->metadata['source']);
        $this->assertSame('Unsplash License', $black->primaryGalleryMedia->metadata['license']);

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
            ->assertSee('photo-1618354691373-d851c5c3a990')
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

    public function test_reseeding_replaces_only_legacy_demo_illustrations_and_preserves_admin_media_changes(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(CommerceDemoSeeder::class);

        $black = ProductVariant::query()->where('sku', 'VISION-DEMO-TEE-BLACK')->firstOrFail();
        $white = ProductVariant::query()->where('sku', 'VISION-DEMO-TEE-WHITE')->firstOrFail();

        // Simulate a database seeded by the former illustration-based version.
        $blackImage = $black->primaryGalleryMedia()->firstOrFail();
        $blackImage->update([
            'path' => 'demo-products/tee-black.svg',
            'mime_type' => 'image/svg+xml',
            'metadata' => null,
        ]);

        // A custom admin-uploaded path is intentionally not managed by the demo seeder.
        $whiteImage = $white->primaryGalleryMedia()->firstOrFail();
        $whiteImage->update(['path' => 'products/7/variants/' . $white->getKey() . '/admin-white.jpg']);

        $this->seed(CommerceDemoSeeder::class);

        $this->assertSame(
            'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=max&w=1200&q=85',
            $black->primaryGalleryMedia()->firstOrFail()->path
        );
        $this->assertSame(
            'products/7/variants/' . $white->getKey() . '/admin-white.jpg',
            $white->primaryGalleryMedia()->firstOrFail()->path
        );

        // Deleting a demo image in Admin remains a deliberate action on the next seed.
        $white->primaryGalleryMedia()->firstOrFail()->delete();
        $this->seed(CommerceDemoSeeder::class);
        $this->assertNull($white->fresh()->primaryGalleryMedia()->first());
    }

    public function test_reseeding_preserves_admin_edits_and_does_not_rewrite_inventory_history(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(CommerceDemoSeeder::class);

        $product = Product::query()->where('slug', 'demo-essential-cotton-tee')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'VISION-DEMO-TEE-BLACK')->firstOrFail();
        $movement = \App\Models\InventoryMovement::query()
            ->where('product_variant_id', $variant->getKey())
            ->where('note', 'Initial stock set by CommerceDemoSeeder.')
            ->firstOrFail();

        $product->update(['name' => 'Edited from Admin']);
        $variant->update(['price' => 1777000, 'stock' => 2]);

        $this->seed(CommerceDemoSeeder::class);

        $this->assertSame('Edited from Admin', $product->fresh()->name);
        $this->assertSame(1777000.0, (float) $variant->fresh()->price);
        $this->assertSame(2, (int) $variant->fresh()->stock);
        $this->assertSame(15, (int) $movement->fresh()->quantity);
        $this->assertSame(15, (int) $movement->fresh()->stock_after);
        $this->assertDatabaseCount('inventory_movements', 5);
    }

    public function test_reseeding_does_not_restore_a_demo_product_archived_in_admin(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(CommerceDemoSeeder::class);
        $product = Product::query()->where('slug', 'demo-essential-cotton-tee')->firstOrFail();
        $productId = $product->getKey();
        $product->delete();

        $this->seed(CommerceDemoSeeder::class);

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertSame(1, Product::query()->count());
        $this->assertDatabaseCount('products', 2);
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
