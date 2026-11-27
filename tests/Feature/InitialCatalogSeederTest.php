<?php

namespace Tests\Feature;

use App\Models\FittingRoomLook;
use App\Models\FittingRoomMannequin;
use App\Models\Media;
use App\Models\Product;
use App\Models\SiteSetting;
use Database\Seeders\InitialCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class InitialCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->source = sys_get_temp_dir() . '/vision-catalog-' . Str::uuid();
        File::ensureDirectoryExists($this->source);
        file_put_contents($this->source . '/item.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAIAAAA2iEnWAAAAFUlEQVR4nGP8//8/AwMDEwMDA4ICADkbAwP+wj6MAAAAAElFTkSuQmCC'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->source);
        parent::tearDown();
    }

    private function fixture(bool $missing = false): void
    {
        $row = [
            'code' => 'coat', 'category' => 'coats', 'name' => 'Coat',
            'color' => 'Brown', 'audience' => 'women', 'image' => 'item.png',
            'price' => null, 'stock' => 0, 'publish' => false,
        ];
        $products = [$row];
        if ($missing) {
            $products[] = array_replace($row, ['code' => 'missing', 'image' => 'missing.png']);
        }
        file_put_contents($this->source . '/manifest.json', json_encode([
            'version' => 1, 'categories' => ['coats' => 'Coats'], 'products' => $products,
            'models' => [[
                'code' => 'sizen-women', 'label' => 'Female', 'audience' => 'women',
                'base' => null, 'looks' => [[
                    'product' => 'coat', 'slot' => 'top', 'label' => 'Coat preview', 'image' => 'item.png',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));
    }

    public function test_import_creates_editable_records_and_preserves_edits_and_deletions(): void
    {
        $this->fixture();
        $seeder = new InitialCatalogSeeder();
        $seeder->importFrom($this->source);

        $product = Product::query()->where('slug', 'sizen-coat')->sole();
        $variant = $product->variants()->sole();
        $this->assertFalse($product->is_active);
        $this->assertSame(0, $variant->stock);
        $this->assertCount(3, Media::query()->get());
        foreach (Media::query()->get() as $media) {
            Storage::disk('public')->assertExists($media->path);
        }
        $model = FittingRoomMannequin::query()->sole();
        $look = FittingRoomLook::query()->sole();
        $this->assertFalse($model->is_active);
        $this->assertNull($model->base_media_id);
        $this->assertFalse($look->is_active);
        $this->assertSame($variant->id, $look->items()->sole()->product_variant_id);

        $product->update(['name' => 'Edited']);
        $seeder->importFrom($this->source);
        $this->assertSame('Edited', $product->fresh()->name);
        $product->delete();
        $look->delete();
        $seeder->importFrom($this->source);
        $this->assertSame(0, Product::query()->where('slug', 'sizen-coat')->count());
        $this->assertSame(0, FittingRoomLook::query()->count());
        $this->assertSame(1, SiteSetting::query()->where('key', 'imports.sizen.initial-catalog.v1')->count());
    }

    public function test_invalid_asset_rolls_back_database_and_written_files(): void
    {
        $this->fixture(true);
        try {
            (new InitialCatalogSeeder())->importFrom($this->source);
            $this->fail('Expected missing asset to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('missing.png', $exception->getMessage());
        }
        $this->assertSame(0, Product::query()->where('slug', 'sizen-coat')->count());
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, SiteSetting::query()->where('key', 'imports.sizen.initial-catalog.v1')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_imported_catalog_is_visible_and_draft_model_preview_requires_admin(): void
    {
        $this->withoutVite();
        $this->fixture();
        $manifest = json_decode(file_get_contents($this->source . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['products'][0]['price'] = 1290000;
        $manifest['products'][0]['price_status'] = 'proposed';
        $manifest['products'][0]['publish'] = true;
        $manifest['products'][0]['hero'] = true;
        $manifest['models'][0]['base'] = 'item.png';
        $manifest['category_images'] = ['coats' => 'item.png'];
        file_put_contents($this->source . '/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        (new InitialCatalogSeeder())->importFrom($this->source);

        $variant = Product::query()->where('slug', 'sizen-coat')->sole()->variants()->sole();
        $model = FittingRoomMannequin::query()->sole();
        $this->assertFalse($model->is_active);
        $this->assertSame(0, $variant->stock);
        $this->assertNotNull($variant->product->category->coverMedia);
        $preview = ['preview_model' => $model->code];

        $this->get('/')->assertOk()->assertSee($variant->primaryGalleryMedia->url, false);
        $this->get(route('shop.index', $preview))->assertOk()->assertDontSee('data-t="' . $variant->id . '"', false);
        $this->getJson(route('shop.studio', $preview))->assertNotFound();

        $admin = \App\Models\User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('shop.index', $preview))
            ->assertOk()->assertSee('data-t="' . $variant->id . '"', false)
            ->assertDontSee('data-c="' . $variant->id . '"', false);
        $this->getJson(route('shop.studio', $preview))->assertOk()
            ->assertJsonPath('base', $model->baseMedia->url)
            ->assertJsonPath('garments.0.id', $variant->id)
            ->assertJsonPath('garments.0.stock', 0);

        $this->getJson(route('shop.studio'))->assertNotFound();
    }

    public function test_admin_can_preview_unpublished_products_without_purchase_or_public_leak(): void
    {
        $this->withoutVite();
        $this->fixture();
        $manifest = json_decode(file_get_contents($this->source . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['models'][0]['base'] = 'item.png';
        file_put_contents($this->source . '/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        (new InitialCatalogSeeder())->importFrom($this->source);

        $product = Product::query()->where('slug', 'sizen-coat')->sole();
        $variant = $product->variants()->sole();
        $variant->update(['is_active' => false, 'stock' => 5, 'price' => 1290000]);
        $model = FittingRoomMannequin::query()->sole();
        $preview = ['preview_model' => $model->code];
        $card = 'data-product="' . $variant->id . '"';

        $this->get(route('shop.index', $preview))->assertOk()->assertDontSee($card, false);
        $this->getJson(route('shop.studio', $preview))->assertNotFound();
        $customer = \App\Models\User::factory()->create();
        $this->actingAs($customer)->get(route('shop.index', $preview))->assertOk()->assertDontSee($card, false);

        $admin = \App\Models\User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('shop.index'))->assertOk()->assertDontSee($card, false);
        $response = $this->get(route('shop.index', $preview))->assertOk()
            ->assertSee($card, false)
            ->assertSee('data-t="' . $variant->id . '"', false)
            ->assertDontSee('data-c="' . $variant->id . '"', false)
            ->assertSee('پیش‌نویس');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->getJson(route('shop.studio', $preview))->assertOk()
            ->assertJsonPath('garments.0.id', $variant->id)
            ->assertJsonPath('garments.0.published', false);
        $this->assertFalse($product->fresh()->is_active);
        $this->assertFalse($variant->fresh()->is_active);
        $this->assertSame(5, $variant->fresh()->stock);
    }
}
