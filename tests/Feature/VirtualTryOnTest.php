<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;
use Tests\TestCase;

class VirtualTryOnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_page_is_public_persian_rtl_and_has_both_audience_controls(): void
    {
        $this->withoutVite();
        $this->get('/try-on')
            ->assertOk()
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('اتاق پرو هوشمند')
            ->assertSee('data-virtual-try-on', false)
            ->assertSee('data-vto-stage', false)
            ->assertSee('data-vto-audience="women"', false)
            ->assertSee('data-vto-audience="men"', false)
            ->assertSee('/api/virtual-try-on/bootstrap', false);
    }

    public function test_bootstrap_accepts_only_women_or_men_audiences(): void
    {
        $this->getJson('/api/virtual-try-on/bootstrap?audience=women')
            ->assertOk()
            ->assertJsonPath('data.audience', 'women')
            ->assertJsonPath('data.slots.0.key', 'shoes');

        $this->getJson('/api/virtual-try-on/bootstrap?audience=men')
            ->assertOk()
            ->assertJsonPath('data.audience', 'men');

        $this->getJson('/api/virtual-try-on/bootstrap?audience=unisex')
            ->assertUnprocessable();
    }

    public function test_catalog_returns_only_active_layer_assets_matching_audience_and_slot(): void
    {
        $womenMannequin = $this->mannequin('women');
        $menMannequin = $this->mannequin('men');
        $womenVariant = $this->variant('women', 'vto-women-top');
        $menVariant = $this->variant('men', 'vto-men-top');
        $this->asset($womenVariant, $womenMannequin, 'top', 'women/top.webp');
        $this->asset($menVariant, $menMannequin, 'top', 'men/top.webp');

        $this->getJson('/api/virtual-try-on/catalog?audience=women&mannequin_id='.$womenMannequin->id.'&slot=top')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.variant_id', $womenVariant->id)
            ->assertJsonPath('data.0.slot', 'top')
            ->assertJsonPath('meta.per_page', 24);
    }

    public function test_catalog_excludes_layer_assets_with_wrong_format_or_canvas_size(): void
    {
        $mannequin = $this->mannequin('women');
        $variant = $this->variant('women', 'vto-invalid-layer');
        $asset = $this->asset($variant, $mannequin, 'top', 'women/invalid.webp');
        $asset->media()->update(['mime_type' => 'image/jpeg', 'width' => 500, 'height' => 500]);

        $this->getJson('/api/virtual-try-on/catalog?audience=women&mannequin_id='.$mannequin->id.'&slot=top')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_preview_layers_are_ordered_by_server_and_price_is_resolved_from_database(): void
    {
        $mannequin = $this->mannequin('women');
        $top = $this->variant('women', 'vto-women-shirt', 120000);
        $shoes = $this->variant('women', 'vto-women-shoes', 250000);
        $this->asset($top, $mannequin, 'top', 'women/shirt.webp');
        $this->asset($shoes, $mannequin, 'shoes', 'women/shoes.webp');

        $this->postJson('/api/virtual-try-on/preview', [
            'audience' => 'women',
            'mannequin_id' => $mannequin->id,
            'revision' => 7,
            'selections' => ['top' => $top->id, 'shoes' => $shoes->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.revision', 7)
            ->assertJsonPath('data.layers.0.slot', 'shoes')
            ->assertJsonPath('data.layers.1.slot', 'top')
            ->assertJsonPath('data.summary.total_price', 370000)
            ->assertJsonPath('data.selections.top', $top->id)
            ->assertJsonPath('data.selections.shoes', $shoes->id);
    }

    public function test_preview_accepts_an_empty_selection_map_without_writing_asset_rows(): void
    {
        $mannequin = $this->mannequin('women');
        $before = TryOnAsset::query()->count();

        $this->postJson('/api/virtual-try-on/preview', [
            'audience' => 'women',
            'mannequin_id' => $mannequin->id,
            'selections' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.layers', [])
            ->assertJsonPath('data.summary.total_price', 0)
            ->assertJsonPath('data.summary.items_count', 0);

        $this->assertSame($before, TryOnAsset::query()->count());
    }

    public function test_preview_rejects_a_variant_from_another_audience_or_without_a_valid_layer(): void
    {
        $womenMannequin = $this->mannequin('women');
        $menMannequin = $this->mannequin('men');
        $menVariant = $this->variant('men', 'vto-men-jacket');
        $this->asset($menVariant, $menMannequin, 'outerwear', 'men/jacket.webp');

        $this->postJson('/api/virtual-try-on/preview', [
            'audience' => 'women',
            'mannequin_id' => $womenMannequin->id,
            'selections' => ['outerwear' => $menVariant->id],
        ])->assertUnprocessable();

        $womenVariant = $this->variant('women', 'vto-women-no-layer');
        $this->postJson('/api/virtual-try-on/preview', [
            'audience' => 'women',
            'mannequin_id' => $womenMannequin->id,
            'selections' => ['top' => $womenVariant->id],
        ])->assertUnprocessable();
    }

    public function test_catalog_query_count_stays_bounded_when_more_assets_are_added(): void
    {
        $mannequin = $this->mannequin('women');
        $this->asset($this->variant('women', 'vto-query-baseline'), $mannequin, 'top', 'women/base.webp');

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $beforeSmall = $queryCount;
        $this->getJson('/api/virtual-try-on/catalog?audience=women&mannequin_id='.$mannequin->id.'&slot=top')->assertOk();
        $smallQueries = $queryCount - $beforeSmall;

        for ($index = 1; $index <= 15; $index++) {
            $this->asset(
                $this->variant('women', 'vto-query-growth-'.$index),
                $mannequin,
                'top',
                'women/growth-'.$index.'.webp'
            );
        }

        // Do not count fixture inserts as part of the subsequent request.
        $queryCount = 0;
        $beforeLarge = $queryCount;
        $this->getJson('/api/virtual-try-on/catalog?audience=women&mannequin_id='.$mannequin->id.'&slot=top')->assertOk();
        $largeQueries = $queryCount - $beforeLarge;

        $this->assertLessThanOrEqual(
            $smallQueries + 2,
            $largeQueries,
            'Catalog query count must remain bounded as try-on asset count grows.'
        );
    }

    private function mannequin(string $audience): TryOnMannequin
    {
        return TryOnMannequin::query()->create([
            'code' => $audience.'-test-'.uniqid(),
            'label' => $audience === 'women' ? 'مانکن زنانه آزمایشی' : 'مانکن مردانه آزمایشی',
            'audience' => $audience,
            'pose_key' => 'front-neutral',
            'canvas_width' => 1024,
            'canvas_height' => 1536,
            'is_active' => true,
        ]);
    }

    private function variant(string $audience, string $slug, int $price = 100000): ProductVariant
    {
        $product = Product::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'audience' => $audience,
            'is_active' => true,
            'is_featured' => false,
            'is_hero' => false,
            'is_taxable' => false,
            'sort_order' => 0,
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => strtoupper($slug),
            'size' => 'M',
            'color' => 'کرم',
            'color_code' => '#c8bdaa',
            'price' => $price,
            'sale_price' => null,
            'stock' => 6,
            'low_stock_threshold' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function asset(ProductVariant $variant, TryOnMannequin $mannequin, string $slot, string $path): TryOnAsset
    {
        Storage::disk('public')->put($path, 'test-image');
        $media = $variant->media()->create([
            'collection' => 'try-on-layer',
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'image/webp',
            'size' => 10,
            'width' => 1024,
            'height' => 1536,
        ]);

        return TryOnAsset::query()->create([
            'product_variant_id' => $variant->id,
            'try_on_mannequin_id' => $mannequin->id,
            'media_id' => $media->id,
            'slot' => $slot,
            'is_active' => true,
        ]);
    }
}
