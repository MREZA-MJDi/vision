<?php

namespace App\Services;

use App\Models\FittingRoomMannequin;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class FittingRoomShopData
{
    public function payload(?Request $request = null): array
    {
        $mannequins = FittingRoomMannequin::query()
            ->active()
            ->with([
                'baseMedia',
                'looks' => fn ($query) => $query->active()->with([
                    'previewTopMedia',
                    'previewBottomMedia',
                    'previewCombinedMedia',
                    'previewSuggestedMedia',
                    'items' => fn ($items) => $items->active()->with([
                        'variant.primaryGalleryMedia',
                        'variant.product.category',
                        'variant.product.primaryGalleryMedia',
                        'variant.product' => fn ($product) => $product->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating'),
                    ]),
                ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mannequin = $mannequins->first(fn (FittingRoomMannequin $record) => filled($record->baseMedia?->url));
        $looks = [];
        $itemMap = [];

        if ($mannequin) {
            foreach ($mannequin->looks as $look) {
                $top = $look->items->firstWhere('slot', 'top');
                $bottom = $look->items->firstWhere('slot', 'bottom');

                $previews = [
                    'T' => $look->previewTopMedia?->url,
                    'B' => $look->previewBottomMedia?->url,
                    'TB' => $look->previewCombinedMedia?->url,
                    'S' => $look->previewSuggestedMedia?->url,
                ];

                $looks[(string) $look->getKey()] = [
                    't' => $top?->variant?->getKey(),
                    'b' => $bottom?->variant?->getKey(),
                    's' => array_filter($previews, static fn ($url) => is_string($url) && $url !== ''),
                ];

                foreach ($look->items as $item) {
                    $variant = $item->variant;
                    $product = $variant?->product;
                    $audienceMatches = $product && (
                        $mannequin->audience === 'all'
                        || $product->audience === $mannequin->audience
                    );
                    if (!$variant || !$product || !$variant->is_active || !$product->is_active || !$audienceMatches) {
                        continue;
                    }

                    // Each variant owns one storefront card. If it is assigned
                    // to more than one curated look, the lowest-order look wins.
                    $itemMap[$variant->getKey()] ??= [
                        'look_id' => $look->getKey(),
                        'slot' => $item->slot,
                        'fit_y' => (float) $item->fit_y,
                        'look' => $look,
                    ];
                }
            }
        }

        // Keep the catalogue product-first. Products without fitting-room
        // mapping appear once with their primary purchasable variant. If an
        // administrator maps several variants into curated looks, those
        // fit-ready variants appear as selectable garment cards.
        $catalog = Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->with([
                'category',
                'primaryGalleryMedia',
                'activeVariants.primaryGalleryMedia',
                'primaryAvailableVariant.primaryGalleryMedia',
                'primaryActiveVariant.primaryGalleryMedia',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $products = $catalog->flatMap(function (Product $product) use ($itemMap): array {
            $fittedVariants = $product->activeVariants
                ->filter(fn (ProductVariant $variant): bool => isset($itemMap[$variant->getKey()]))
                ->values();

            $primaryActiveVariant = $product->activeVariants->first(
                fn (ProductVariant $variant): bool => (int) $variant->stock > 0
            ) ?? $product->activeVariants->first();

            $variants = $fittedVariants->isNotEmpty()
                ? $fittedVariants
                : collect([$primaryActiveVariant])->filter();

            return $variants->map(function (ProductVariant $variant) use ($product, $itemMap): array {
                $fit = $itemMap[$variant->getKey()] ?? null;
                $look = $fit['look'] ?? null;
                $slot = $fit['slot'] ?? $this->inferSlot($product);
                $lookPreviews = $look ? [
                    'top' => $look->previewTopMedia?->url,
                    'bottom' => $look->previewBottomMedia?->url,
                ] : [];
                $sceneUrl = $lookPreviews[$slot] ?? null;
                $isOnSale = (bool) ($variant->is_on_sale ?? false);
                $variantLabel = ($variant->color || $variant->size) ? ' · ' . $variant->display_name : '';
                $colorCode = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $variant->color_code)
                    ? (string) $variant->color_code
                    : '#8b8490';

                return [
                    'id' => (int) $variant->getKey(),
                    'product_id' => (int) $product->getKey(),
                    'name' => (string) $product->name . $variantLabel,
                    'price' => (int) round((float) $variant->effective_price),
                    'old' => $isOnSale ? (int) round((float) $variant->price) : null,
                    'rate' => is_numeric($product->approved_reviews_avg_rating)
                        ? round((float) $product->approved_reviews_avg_rating, 1)
                        : null,
                    'cat' => (string) ($product->category?->name ?? 'سایر'),
                    'img' => $variant->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url,
                    'color' => trim((string) $variant->color),
                    'colorCode' => $colorCode,
                    'stock' => (int) $variant->stock,
                    'fy' => $fit ? (float) $fit['fit_y'] : ($slot === 'bottom' ? 0.66 : 0.38),
                    'slot' => $slot,
                    'o' => $fit ? (int) $fit['look_id'] : null,
                    'scene' => $sceneUrl,
                    'url' => route('products.show', ['product' => $product->slug]),
                ];
            })->all();
        })->values();

        $requestedSort = $request?->query('sort');
        $initialSort = is_string($requestedSort) ? match ($requestedSort) {
            'new', 'newest' => 'new',
            'lo', 'price_asc' => 'lo',
            'hi', 'price_desc' => 'hi',
            'rt', 'rating' => 'rt',
            default => 'new',
        } : 'new';

        $categories = $products->pluck('cat')->filter()->unique()->values()->all();
        $prices = $products->pluck('price')->filter(fn ($price) => is_numeric($price) && $price >= 0);
        $min = $prices->isNotEmpty() ? (int) floor($prices->min() / 100000) * 100000 : 0;
        $max = $prices->isNotEmpty() ? (int) ceil($prices->max() / 100000) * 100000 : 4000000;
        if ($max <= $min) {
            $max = $min + 100000;
        }

        return [
            'products' => $products->all(),
            'cats' => $categories,
            'min' => $min,
            'max' => $max,
            'base' => $mannequin?->baseMedia?->url,
            'looks' => $looks,
            'favs' => array_values(array_unique(array_map('intval', (array) ($request?->session()->get('shop_favourites', []))))),
            'cart' => 0,
            'currency' => (string) config('commerce-admin.currency_symbol', 'تومان'),
            'routes' => [
                'cart' => route('cart.items.store'),
                'cartMany' => route('cart.items.bulk'),
                'fav' => url('/shop/favourites'),
                'cartSummary' => route('cart.summary'),
            ],
            'initialQuery' => $request && is_string($request->query('q'))
                ? mb_substr(trim($request->query('q')), 0, 120)
                : '',
            'initialSaleOnly' => $request?->boolean('sale') ?? false,
            'initialSort' => $initialSort,
        ];
    }

    /**
     * Infer a display slot for active catalogue products not yet assigned to
     * a curated studio look. Explicit LookItem mappings always take priority.
     *
     * @return 'top'|'bottom'
     */
    private function inferSlot(Product $product): string
    {
        $metadata = mb_strtolower(implode(' ', array_filter([
            $product->category?->slug,
            $product->category?->name,
            $product->name,
            json_encode($product->attributes ?? [], JSON_UNESCAPED_UNICODE) ?: '',
        ])));

        foreach ([
            'bottom', 'bottoms', 'trouser', 'pants', 'jeans', 'shorts', 'skirt',
            'شلوار', 'دامن', 'جین', 'لگ', 'کارگو', 'پایین تنه', 'پایین‌تنه',
        ] as $keyword) {
            if (str_contains($metadata, $keyword)) {
                return 'bottom';
            }
        }

        return 'top';
    }

}
