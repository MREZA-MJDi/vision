<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\StorefrontLaunchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontLaunchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_seed_creates_editable_hero_products_with_variant_media_idempotently(): void
    {
        $this->seed(StorefrontLaunchSeeder::class);
        $this->seed(StorefrontLaunchSeeder::class);

        $puffer = Product::query()->where('slug', 'vision-solar-puffer')->firstOrFail();
        $coat = Product::query()->where('slug', 'vision-chestnut-coat')->firstOrFail();

        $this->assertTrue((bool) $puffer->is_hero);
        $this->assertTrue((bool) $coat->is_hero);
        $this->assertSame('#E96512', $puffer->activeVariants()->firstOrFail()->color_code);
        $this->assertSame('#704231', $coat->activeVariants()->firstOrFail()->color_code);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('media', ['path' => 'seed-products/vision-orange-puffer-v1.png']);
        $this->assertDatabaseHas('media', ['path' => 'seed-products/vision-chestnut-coat-v1.png']);
        $this->assertTrue(Storage::disk('public')->exists('seed-products/vision-orange-puffer-v1.png'));
        $this->assertTrue(Storage::disk('public')->exists('seed-products/vision-chestnut-coat-v1.png'));
    }
}
