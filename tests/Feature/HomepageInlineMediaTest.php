<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageInlineMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_replace_and_delete_the_canonical_homepage_images(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $category = Category::query()->create(['name' => 'Coats', 'slug' => 'coats', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $product = Product::query()->create([
            'name' => 'Coat', 'slug' => 'coat', 'category_id' => $category->id,
            'brand_id' => $brand->id, 'is_active' => true, 'is_hero' => true, 'audience' => 'women',
        ]);
        $variant = $product->variants()->create(['sku' => 'COAT', 'price' => 10000, 'stock' => 1, 'is_active' => true]);
        foreach ([
            ['category', $category, 'coverMedia'],
            ['brand', $brand, 'logoMedia'],
            ['product', $product, 'primaryGalleryMedia'],
            ['variant', $variant, 'primaryGalleryMedia'],
        ] as [$type, $owner, $relation]) {
            $parameters = ['type' => $type, 'owner' => $owner->id];
            $update = route('commerce-admin.homepage.images.update', $parameters);
            $destroy = route('commerce-admin.homepage.images.destroy', $parameters);
            $this->put($update, ['media_id' => null, 'image' => UploadedFile::fake()->image('first.png', 80, 120)])->assertRedirect(route('commerce-admin.homepage.index'));
            $first = $owner->fresh()->{$relation};
            Storage::disk('public')->assertExists($first->path);
            $this->get('/')->assertOk()->assertSee($first->url, false);
            $this->get(route('commerce-admin.homepage.index'))->assertOk()->assertSee($first->url, false);

            $this->put($update, ['media_id' => null, 'image' => UploadedFile::fake()->image('stale.png')])->assertSessionHasErrors('image');
            $this->put($update, ['media_id' => $first->id, 'image' => UploadedFile::fake()->image('replacement.png', 80, 120)])->assertRedirect(route('commerce-admin.homepage.index'));
            $replacement = $owner->fresh()->{$relation};
            $this->assertSame($first->id, $replacement->id);
            $this->assertNotSame($first->path, $replacement->path);
            Storage::disk('public')->assertMissing($first->path);
            Storage::disk('public')->assertExists($replacement->path);
            $this->get('/')->assertOk()->assertSee($replacement->url, false);
            $this->delete($destroy, ['media_id' => $replacement->id])->assertRedirect(route('commerce-admin.homepage.index'));
            Storage::disk('public')->assertMissing($replacement->path);
            $this->assertNull($owner->fresh()->{$relation});
        }
    }

    public function test_customer_cannot_modify_homepage_images_and_invalid_file_is_rejected(): void
    {
        Storage::fake('public');
        $category = Category::query()->create(['name' => 'Coats', 'slug' => 'coats', 'is_active' => true]);
        $url = route('commerce-admin.homepage.images.update', ['type' => 'category', 'owner' => $category->id]);
        $customer = User::factory()->create();
        $this->actingAs($customer)->put($url, ['media_id' => null, 'image' => UploadedFile::fake()->image('first.png')])->assertForbidden();
        $customer->forceFill(['is_admin' => true])->save();
        $this->actingAs($customer->fresh())->put($url, ['media_id' => null, 'image' => UploadedFile::fake()->create('payload.txt', 1, 'text/plain')])->assertSessionHasErrors('image');
        $this->assertNull($category->fresh()->coverMedia);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
