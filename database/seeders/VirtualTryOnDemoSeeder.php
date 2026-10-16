<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;

/**
 * Creates a small, real try-on catalog from the transparent demo assets shipped
 * with Vision. It never overwrites an existing product, variant, mannequin base,
 * or admin-managed try-on mapping.
 */
class VirtualTryOnDemoSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('public');
        $mannequin = TryOnMannequin::query()->where('code', 'women-front')->first();

        if (! $mannequin) {
            $this->command?->warn('Virtual Try-On demo data was skipped: run VirtualTryOnMannequinSeeder first.');

            return;
        }

        $basePath = 'virtual-try-on/demo/mannequin-neutral.webp';
        if ($disk->exists($basePath) && ! $mannequin->base_media_id) {
            $baseMedia = Media::query()->firstOrCreate([
                'disk' => 'public',
                'path' => $basePath,
            ], [
                'mediable_type' => $mannequin->getMorphClass(),
                'mediable_id' => $mannequin->getKey(),
                'collection' => 'try-on-base',
                'original_name' => basename($basePath),
                'mime_type' => 'image/webp',
                'size' => $disk->size($basePath),
                'width' => 320,
                'height' => 480,
                'alt_text' => $mannequin->label,
                'sort_order' => 0,
                'uploaded_by' => null,
            ]);

            $mannequin->update(['base_media_id' => $baseMedia->getKey()]);
        }

        $vectorLayers = [
            'sweater-cream.svg' => $this->demoLayerSvg('sweater-cream'),
            'shirt-white.svg' => $this->demoLayerSvg('shirt-white'),
            'trousers-black.svg' => $this->demoLayerSvg('trousers-black'),
            'trousers-cream.svg' => $this->demoLayerSvg('trousers-cream'),
        ];
        foreach ($vectorLayers as $file => $svg) {
            $path = 'virtual-try-on/demo/' . $file;
            if (! $disk->exists($path)) {
                $disk->put($path, $svg);
            }
        }

        $definitions = [
            [
                'product_slug' => 'vto-demo-coat',
                'product_name' => 'پالتو کلاسیک',
                'description' => 'پالتو با لایهٔ شفاف آماده برای پیش‌نمایش روی مانکن.',
                'sku' => 'VTO-DEMO-COAT-CHOCOLATE',
                'color' => 'قهوه‌ای',
                'color_code' => '#704231',
                'price' => 6490000,
                'path' => 'virtual-try-on/demo/coat-chocolate.webp',
                'slot' => 'outerwear',
                'is_default' => true,
                'sort_order' => 10,
            ],
            [
                'product_slug' => 'vto-demo-coat',
                'product_name' => 'پالتو کلاسیک',
                'description' => 'پالتو با لایهٔ شفاف آماده برای پیش‌نمایش روی مانکن.',
                'sku' => 'VTO-DEMO-COAT-CREAM',
                'color' => 'کرم',
                'color_code' => '#E3D7C6',
                'price' => 6590000,
                'path' => 'virtual-try-on/demo/coat-cream.webp',
                'slot' => 'outerwear',
                'is_default' => false,
                'sort_order' => 11,
            ],
            [
                'product_slug' => 'vto-demo-puffer',
                'product_name' => 'کاپشن پافر',
                'description' => 'کاپشن آمادهٔ پرو برای تست تعویض لباس.',
                'sku' => 'VTO-DEMO-PUFFER-BLACK',
                'color' => 'مشکی',
                'color_code' => '#26252A',
                'price' => 4890000,
                'path' => 'virtual-try-on/demo/puffer-black.webp',
                'slot' => 'outerwear',
                'is_default' => false,
                'sort_order' => 20,
            ],
            [
                'product_slug' => 'vto-demo-sweater',
                'product_name' => 'پلیور یقه‌اسکی',
                'description' => 'پلیور روشن با لایهٔ آمادهٔ مانکن.',
                'sku' => 'VTO-DEMO-SWEATER-CREAM',
                'color' => 'کرم',
                'color_code' => '#D9C6B2',
                'price' => 2390000,
                'path' => 'virtual-try-on/demo/sweater-cream.svg',
                'slot' => 'top',
                'is_default' => true,
                'sort_order' => 30,
            ],
            [
                'product_slug' => 'vto-demo-shirt',
                'product_name' => 'پیراهن ساده',
                'description' => 'پیراهن سفید با لایهٔ آمادهٔ پرو.',
                'sku' => 'VTO-DEMO-SHIRT-WHITE',
                'color' => 'سفید',
                'color_code' => '#F4F0E6',
                'price' => 1790000,
                'path' => 'virtual-try-on/demo/shirt-white.svg',
                'slot' => 'top',
                'is_default' => false,
                'sort_order' => 40,
            ],
            [
                'product_slug' => 'vto-demo-trousers',
                'product_name' => 'شلوار پارچه‌ای',
                'description' => 'شلوار با لایهٔ شفاف هم‌تراز با مانکن.',
                'sku' => 'VTO-DEMO-TROUSERS-BLACK',
                'color' => 'مشکی',
                'color_code' => '#242329',
                'price' => 2990000,
                'path' => 'virtual-try-on/demo/trousers-black.svg',
                'slot' => 'bottom',
                'is_default' => true,
                'sort_order' => 50,
            ],
            [
                'product_slug' => 'vto-demo-trousers',
                'product_name' => 'شلوار پارچه‌ای',
                'description' => 'شلوار با لایهٔ شفاف هم‌تراز با مانکن.',
                'sku' => 'VTO-DEMO-TROUSERS-CREAM',
                'color' => 'کرم',
                'color_code' => '#D6C7B4',
                'price' => 3090000,
                'path' => 'virtual-try-on/demo/trousers-cream.svg',
                'slot' => 'bottom',
                'is_default' => false,
                'sort_order' => 51,
            ],
        ];

        DB::transaction(function () use ($definitions, $mannequin, $disk): void {
            foreach ($definitions as $definition) {
                $path = $definition['path'];
                if (! $disk->exists($path)) {
                    $this->command?->warn("Virtual Try-On layer [{$path}] is missing; its demo mapping was skipped.");

                    continue;
                }

                $product = Product::withTrashed()->firstOrNew(['slug' => $definition['product_slug']]);
                if ($product->exists && $product->trashed()) {
                    continue;
                }

                if (! $product->exists) {
                    $product->fill([
                        'name' => $definition['product_name'],
                        'slug' => $definition['product_slug'],
                        'audience' => 'women',
                        'short_description' => $definition['description'],
                        'description' => $definition['description'],
                        'meta_title' => $definition['product_name'] . ' | VISION',
                        'meta_description' => $definition['description'],
                        'is_active' => true,
                        'is_featured' => false,
                        'is_hero' => false,
                        'is_taxable' => false,
                        'sort_order' => $definition['sort_order'],
                    ])->save();
                }

                $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $definition['sku']]);
                if ($variant->exists && $variant->trashed()) {
                    continue;
                }

                if ($variant->exists && (int) $variant->product_id !== (int) $product->getKey()) {
                    $this->command?->warn("Virtual Try-On demo SKU [{$definition['sku']}] belongs to another product; it was left unchanged.");

                    continue;
                }

                if (! $variant->exists) {
                    $variant->fill([
                        'product_id' => $product->getKey(),
                        'sku' => $definition['sku'],
                        'size' => 'M',
                        'color' => $definition['color'],
                        'color_code' => $definition['color_code'],
                        'price' => $definition['price'],
                        'sale_price' => null,
                        'stock' => 10,
                        'low_stock_threshold' => 2,
                        'is_active' => true,
                        'sort_order' => $definition['sort_order'],
                    ])->save();
                }

                // An existing mapping is admin-owned. Do not replace its pose,
                // slot, image or default flag when the seeder runs again.
                if (TryOnAsset::query()->where('product_variant_id', $variant->getKey())->exists()) {
                    continue;
                }

                $media = Media::query()->firstOrCreate([
                    'disk' => 'public',
                    'path' => $path,
                ], [
                    'mediable_type' => $variant->getMorphClass(),
                    'mediable_id' => $variant->getKey(),
                    'collection' => 'try-on-layer',
                    'original_name' => basename($path),
                    'mime_type' => str_ends_with($path, '.svg') ? 'image/svg+xml' : 'image/webp',
                    'size' => $disk->size($path),
                    'width' => 320,
                    'height' => 480,
                    'alt_text' => $definition['product_name'] . ' — ' . $definition['color'],
                    'sort_order' => 0,
                    'metadata' => [
                        'source' => str_ends_with($path, '.svg') ? 'VISION generated neutral vector layer' : 'VISION supplied demo collage',
                        'optimized_canvas' => '320x480 (2:3)',
                    ],
                    'uploaded_by' => null,
                ]);

                TryOnAsset::query()->create([
                    'product_variant_id' => $variant->getKey(),
                    'try_on_mannequin_id' => $mannequin->getKey(),
                    'media_id' => $media->getKey(),
                    'slot' => $definition['slot'],
                    'is_active' => true,
                    'is_default' => $definition['is_default'],
                ]);
            }
        });

        $this->command?->info('Virtual Try-On demo mannequin and seven transparent clothing layers are ready.');
    }

    /**
     * Small, clean 2:3 transparent vector layers keep the demo usable without
     * inventing AI output. Admins can replace them with aligned photo assets.
     */
    private function demoLayerSvg(string $kind): string
    {
        return match ($kind) {
            'sweater-cream' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480">
  <defs>
    <linearGradient id="knit" x1="0" y1="0" x2="1" y2=".2"><stop offset="0" stop-color="#b9a990"/><stop offset=".28" stop-color="#e7dece"/><stop offset=".62" stop-color="#d6c8b4"/><stop offset="1" stop-color="#aa9b86"/></linearGradient>
    <linearGradient id="knitShade" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#8f816e" stop-opacity=".6"/><stop offset=".45" stop-color="#fff" stop-opacity=".15"/><stop offset="1" stop-color="#7e705f" stop-opacity=".48"/></linearGradient>
  </defs>
  <path d="M124 67 143 59Q160 75 177 59L196 67 216 84 236 107 225 128 207 116 205 224Q160 237 115 224L113 116 95 128 84 107 104 84Z" fill="url(#knit)" stroke="#a99a85" stroke-width="1.2" stroke-linejoin="round"/>
  <path d="M124 68 111 87 100 108 112 116 120 103 120 221 136 225 136 77Z" fill="url(#knitShade)" opacity=".5"/>
  <path d="M196 68 209 87 220 108 208 116 200 103 200 221 184 225 184 77Z" fill="url(#knitShade)" opacity=".48"/>
  <path d="M143 60Q160 81 177 60L173 74Q160 87 147 74Z" fill="#b1a18c" stroke="#8d806e" stroke-width="1"/>
  <path d="M115 211Q160 222 205 211L205 225Q160 237 115 225Z" fill="#b5a58e"/>
  <path d="M97 118 110 125M223 118 210 125" stroke="#92836f" stroke-width="1.4" stroke-linecap="round"/>
  <path d="M121 89 121 205M199 89 199 205" stroke="#f5ecdc" stroke-opacity=".4" stroke-width="1.4"/>
  <path d="M119 228 201 228" stroke="#8c7d6b" stroke-opacity=".6" stroke-width="1.2"/>
</svg>
SVG,
            'shirt-white' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480">
  <defs>
    <linearGradient id="shirt" x1="0" y1="0" x2="1" y2=".15"><stop stop-color="#c9c4ba"/><stop offset=".25" stop-color="#fffdf7"/><stop offset=".65" stop-color="#eee9df"/><stop offset="1" stop-color="#c7c0b5"/></linearGradient>
    <linearGradient id="shirtShadow" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#968f84" stop-opacity=".45"/><stop offset=".5" stop-color="#fff" stop-opacity=".08"/><stop offset="1" stop-color="#a29a8e" stop-opacity=".35"/></linearGradient>
  </defs>
  <path d="M132 66 146 60 160 70 174 60 188 66 211 83 233 105 222 126 206 115 204 224Q160 235 116 224L114 115 98 126 87 105 109 83Z" fill="url(#shirt)" stroke="#b8b1a6" stroke-width="1.2" stroke-linejoin="round"/>
  <path d="M146 60 160 70 174 60 168 86 160 96 152 86Z" fill="#d8d1c5" stroke="#aaa294" stroke-width="1"/>
  <path d="M151 83 160 96 169 83 167 111 160 119 153 111Z" fill="#f7f4ec" stroke="#d0c8bb" stroke-width=".8"/>
  <path d="M160 96 160 221" stroke="#b5ada0" stroke-width="1.1"/>
  <g fill="#aaa193"><circle cx="160" cy="129" r="1.7"/><circle cx="160" cy="151" r="1.7"/><circle cx="160" cy="173" r="1.7"/><circle cx="160" cy="195" r="1.7"/></g>
  <path d="M121 94 121 217M199 94 199 217" stroke="url(#shirtShadow)" stroke-width="9" opacity=".7"/>
  <path d="M115 211Q160 221 205 211L204 224Q160 235 116 224Z" fill="#d5cec1"/>
  <path d="M98 116 110 122M222 116 210 122" stroke="#b6ad9e" stroke-width="1.3" stroke-linecap="round"/>
</svg>
SVG,
            'trousers-black' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480">
  <defs>
    <linearGradient id="pants" x1="0" y1="0" x2="1" y2=".15"><stop stop-color="#17171b"/><stop offset=".25" stop-color="#29282d"/><stop offset=".58" stop-color="#202025"/><stop offset="1" stop-color="#111115"/></linearGradient>
    <linearGradient id="pantsFold" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#fff" stop-opacity=".04"/><stop offset=".5" stop-color="#aaa" stop-opacity=".15"/><stop offset="1" stop-color="#000" stop-opacity=".15"/></linearGradient>
  </defs>
  <path d="M117 220Q160 230 203 220L211 267 197 448 163 448 157 309 151 309 141 448 106 448 101 267Z" fill="url(#pants)" stroke="#111115" stroke-width="1.2" stroke-linejoin="round"/>
  <path d="M118 224Q160 232 202 224L203 244Q160 253 117 244Z" fill="#323137" stroke="#0c0c0f" stroke-width="1"/>
  <path d="M120 252 124 300 116 427M200 252 195 300 190 427" fill="none" stroke="url(#pantsFold)" stroke-width="9"/>
  <path d="M157 244 154 304 162 309" fill="none" stroke="#0c0c10" stroke-width="1.4"/>
  <path d="M115 262 140 265M180 265 203 262" stroke="#55545b" stroke-opacity=".55" stroke-width="1"/>
  <path d="M108 439 141 439M163 439 196 439" stroke="#08080a" stroke-width="3"/>
</svg>
SVG,
            'trousers-cream' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480">
  <defs>
    <linearGradient id="pantsCream" x1="0" y1="0" x2="1" y2=".15"><stop stop-color="#a99a83"/><stop offset=".25" stop-color="#e9dfcf"/><stop offset=".58" stop-color="#d5c5ad"/><stop offset="1" stop-color="#aa9a82"/></linearGradient>
    <linearGradient id="pantsCreamFold" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#fff" stop-opacity=".03"/><stop offset=".5" stop-color="#fff" stop-opacity=".45"/><stop offset="1" stop-color="#766954" stop-opacity=".12"/></linearGradient>
  </defs>
  <path d="M117 220Q160 230 203 220L211 267 197 448 163 448 157 309 151 309 141 448 106 448 101 267Z" fill="url(#pantsCream)" stroke="#b4a48c" stroke-width="1.1" stroke-linejoin="round"/>
  <path d="M118 224Q160 232 202 224L203 244Q160 253 117 244Z" fill="#d7c8b2" stroke="#a79780" stroke-width="1"/>
  <path d="M120 252 124 300 116 427M200 252 195 300 190 427" fill="none" stroke="url(#pantsCreamFold)" stroke-width="9"/>
  <path d="M157 244 154 304 162 309" fill="none" stroke="#9d8d75" stroke-width="1.2"/>
  <path d="M115 262 140 265M180 265 203 262" stroke="#b5a58c" stroke-opacity=".65" stroke-width="1"/>
  <path d="M108 439 141 439M163 439 196 439" stroke="#9c8b72" stroke-width="3"/>
</svg>
SVG,
            default => throw new \InvalidArgumentException('Unsupported demo vector layer.'),
        };
    }
}
