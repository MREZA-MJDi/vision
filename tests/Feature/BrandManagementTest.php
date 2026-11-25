<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_brand_and_public_home_shows_it_without_products(): void
    {
        Storage::fake('public');
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('commerce-admin.brands.store'), [
            'name' => 'Editorial Brand',
            'status' => 'active',
            'logo_image' => UploadedFile::fake()->image('brand.png', 560, 500),
        ])->assertSessionHasNoErrors()->assertRedirect(route('commerce-admin.brands.index'));

        $brand = Brand::query()->where('name', 'Editorial Brand')->firstOrFail();
        $this->assertNotNull($brand->logoMedia);
        Storage::disk('public')->assertExists($brand->logoMedia->path);
        $this->get(route('commerce-admin.brands.edit', $brand->getKey()))->assertOk();
        $this->put(route('commerce-admin.brands.update', $brand->getKey()), [
            'name' => 'Updated Editorial Brand',
            'status' => 'active',
            'logo_alt' => 'Updated image',
            'logo_image' => UploadedFile::fake()->image('replacement.png', 560, 500),
        ])->assertSessionHasNoErrors()->assertRedirect(route('commerce-admin.brands.index'));
        $brand = $brand->fresh();
        $this->assertSame('Updated Editorial Brand', $brand->name);
        $this->assertSame('Updated image', $brand->logoMedia->alt_text);
        Storage::disk('public')->assertExists($brand->logoMedia->path);
        $this->get('/')->assertOk()->assertSee('Updated Editorial Brand');
        $this->put(route('commerce-admin.brands.update', $brand->getKey()), [
            'name' => $brand->name, 'status' => 'draft',
        ])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertDontSee('Updated Editorial Brand');
    }
}
