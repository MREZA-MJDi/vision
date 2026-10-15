<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Rm\VirtualTryOn\Models\TryOnAsset;
use Rm\VirtualTryOn\Models\TryOnMannequin;
use Rm\VirtualTryOn\Services\CompositionBuilder;
use Rm\VirtualTryOn\Support\VirtualTryOnConfig;

class VirtualTryOnController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(VirtualTryOnConfig::AUDIENCES)],
        ]);

        $mannequinModel = (string) config('virtual-try-on.models.mannequin', TryOnMannequin::class);
        $mannequins = $mannequinModel::query()
            ->active()
            ->where('audience', $validated['audience'])
            ->with('baseMedia')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (TryOnMannequin $mannequin): array => $this->mannequinPayload($mannequin))
            ->values();

        return response()->json([
            'data' => [
                'audience' => $validated['audience'],
                'slots' => VirtualTryOnConfig::slots(),
                'mannequins' => $mannequins,
            ],
            'meta' => [
                'ready' => $mannequins->isNotEmpty(),
                'asset_contract' => [
                    'format' => 'transparent PNG or WebP',
                    'canvas_width' => 1024,
                    'canvas_height' => 1536,
                    'alignment' => 'Every layer shares one mannequin canvas and pose.',
                ],
            ],
        ]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(VirtualTryOnConfig::AUDIENCES)],
            'mannequin_id' => ['required', 'integer', 'min:1'],
            'slot' => ['required', Rule::in(array_keys(VirtualTryOnConfig::SLOT_ORDER))],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $mannequin = $this->activeMannequin((int) $validated['mannequin_id'], $validated['audience']);

        $assetModel = (string) config('virtual-try-on.models.asset', TryOnAsset::class);
        $assets = $assetModel::query()
            ->active()
            ->where('try_on_mannequin_id', $mannequin->getKey())
            ->where('slot', $validated['slot'])
            ->whereHas('media', function ($query): void {
                $query->where('collection', 'try-on-layer')
                    ->whereNotNull('path')
                    ->whereRaw("TRIM(path) <> ''");
            })
            ->whereHas('variant', function ($variantQuery) use ($validated): void {
                $variantQuery->where('is_active', true)
                    ->whereHas('product', fn ($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->where('audience', $validated['audience']));
            })
            ->with([
                'media',
                'variant.primaryGalleryMedia',
                'variant.product.primaryGalleryMedia',
                'variant.product.category',
            ])
            ->orderBy('id')
            ->paginate((int) config('virtual-try-on.page_size', 24))
            ->through(fn (TryOnAsset $asset): array => $this->catalogItemPayload($asset));

        return response()->json([
            'data' => $assets->items(),
            'links' => ['next_page' => $assets->hasMorePages() ? $assets->currentPage() + 1 : null],
            'meta' => [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    public function preview(Request $request, CompositionBuilder $builder): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(VirtualTryOnConfig::AUDIENCES)],
            'mannequin_id' => ['required', 'integer', 'min:1'],
            'revision' => ['sometimes', 'integer', 'min:0'],
            'selections' => ['present', 'array:top,outerwear,bottom,shoes,accessory'],
            'selections.top' => ['nullable', 'integer', 'min:1'],
            'selections.outerwear' => ['nullable', 'integer', 'min:1'],
            'selections.bottom' => ['nullable', 'integer', 'min:1'],
            'selections.shoes' => ['nullable', 'integer', 'min:1'],
            'selections.accessory' => ['nullable', 'integer', 'min:1'],
        ]);

        $audience = $validated['audience'];
        $mannequin = $this->activeMannequin((int) $validated['mannequin_id'], $audience);
        $assetModel = (string) config('virtual-try-on.models.asset', TryOnAsset::class);
        $requested = array_filter(
            $validated['selections'],
            static fn ($variantId): bool => $variantId !== null && $variantId !== ''
        );

        $items = [];
        foreach ($requested as $slot => $variantId) {
            $asset = $assetModel::query()
                ->active()
                ->where('try_on_mannequin_id', $mannequin->getKey())
                ->where('slot', $slot)
                ->whereHas('media', function ($query): void {
                    $query->where('collection', 'try-on-layer')
                        ->whereNotNull('path')
                        ->whereRaw("TRIM(path) <> ''");
                })
                ->whereHas('variant', function ($variantQuery) use ($variantId, $audience): void {
                    $variantQuery->whereKey((int) $variantId)
                        ->where('is_active', true)
                        ->whereHas('product', fn ($productQuery) => $productQuery
                            ->where('is_active', true)
                            ->where('audience', $audience));
                })
                ->with(['media', 'variant.product'])
                ->first();

            if (! $asset || ! $asset->media?->url) {
                throw ValidationException::withMessages([
                    'selections.' . $slot => 'این گزینه برای مانکن انتخاب‌شده تصویر لایه‌ای معتبر ندارد.',
                ]);
            }

            /** @var object $variant */
            $variant = $asset->variant;
            $product = $variant->product;

            $items[] = [
                'slot' => $slot,
                'audience' => $product->audience,
                'variant_id' => $variant->getKey(),
                'product_name' => $product->name,
                'product_url' => route((string) config('virtual-try-on.routes.product_detail', 'products.show'), ['product' => $product->slug]),
                'label' => $variant->display_name,
                'layer_url' => $asset->media->url,
                'price' => (int) round((float) $variant->effective_price),
                'stock' => (int) $variant->stock,
            ];
        }

        $layers = $builder->build($audience, $items);
        $totalPrice = array_sum(array_map(static fn (array $layer): int => $layer['price'], $layers));

        return response()->json([
            'data' => [
                'revision' => (int) ($validated['revision'] ?? 0),
                'audience' => $audience,
                'mannequin' => $this->mannequinPayload($mannequin),
                'selections' => array_column($layers, 'variant_id', 'slot'),
                'layers' => $layers,
                'summary' => [
                    'items_count' => count($layers),
                    'total_price' => $totalPrice,
                    'all_in_stock' => ! collect($layers)->contains(fn (array $layer): bool => $layer['stock'] < 1),
                    'currency' => (string) config('virtual-try-on.currency_code', 'IRT'),
                ],
            ],
        ]);
    }

    private function activeMannequin(int $id, string $audience)
    {
        $mannequinModel = (string) config('virtual-try-on.models.mannequin', TryOnMannequin::class);
        return $mannequinModel::query()
            ->active()
            ->whereKey($id)
            ->where('audience', $audience)
            ->with('baseMedia')
            ->firstOrFail();
    }

    private function mannequinPayload($mannequin): array
    {
        return [
            'id' => $mannequin->getKey(),
            'code' => $mannequin->code,
            'label' => $mannequin->label,
            'audience' => $mannequin->audience,
            'pose_key' => $mannequin->pose_key,
            'canvas_width' => (int) $mannequin->canvas_width,
            'canvas_height' => (int) $mannequin->canvas_height,
            'base_image_url' => $mannequin->baseMedia?->url,
        ];
    }

    private function catalogItemPayload($asset): array
    {
        $variant = $asset->variant;
        $product = $variant->product;
        $thumbnail = $variant->primaryGalleryMedia?->url
            ?? $product->primaryGalleryMedia?->url;
        $colorCode = (string) $variant->color_code;

        return [
            'asset_id' => $asset->getKey(),
            'variant_id' => $variant->getKey(),
            'slot' => $asset->slot,
            'name' => $product->name,
            'category' => $product->category?->name,
            'label' => $variant->display_name,
            'color' => $variant->color,
            'color_code' => preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $colorCode) ? $colorCode : '#b8aea2',
            'price' => (int) round((float) $variant->effective_price),
            'stock' => (int) $variant->stock,
            'thumbnail_url' => $thumbnail,
            'layer_url' => $asset->media?->url,
            'product_url' => route((string) config('virtual-try-on.routes.product_detail', 'products.show'), ['product' => $product->slug]),
        ];
    }
}
