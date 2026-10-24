<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FittingRoomLook;
use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
use App\Models\Product;
use Illuminate\Http\Request;

class FittingRoomShopData
{
    public function payload(?Request $request = null): array
    {
        $mannequin = FittingRoomMannequin::query()
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
            ->first();

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
                    if (!$variant || !$product || !$variant->is_active || !$product->is_active) {
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

        $products = Product::query()
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
            ->get()
            ->map(function (Product $product) use ($itemMap): ?array {
                $variants = $product->activeVariants;
                $variant = $variants->first(fn ($candidate) => isset($itemMap[$candidate->getKey()]))
                    ?? $product->primaryAvailableVariant
                    ?? $product->primaryActiveVariant;

                if (!$variant || !$variant->is_active) {
                    return null;
                }

                $fit = $itemMap[$variant->getKey()] ?? null;
                $look = $fit['look'] ?? null;
                $slot = $fit['slot'] ?? null;
                $lookPreviews = $look ? [
                    'top' => $look->previewTopMedia?->url,
                    'bottom' => $look->previewBottomMedia?->url,
                    'combined' => $look->previewCombinedMedia?->url,
                ] : [];

                // Only display the hanger when the selected slot has a real
                // matching composite scene in the active fitting-room data.
                $fitReady = $fit && (
                    ($slot === 'top' && !empty($lookPreviews['top']))
                    || ($slot === 'bottom' && !empty($lookPreviews['bottom']))
                );

                $isOnSale = (bool) ($variant->is_on_sale ?? false);
                $image = $variant->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url;

                return [
                    'id' => (int) $variant->getKey(),
                    'product_id' => (int) $product->getKey(),
                    'name' => (string) $product->name,
                    'price' => (int) round((float) $variant->effective_price),
                    'old' => $isOnSale ? (int) round((float) $variant->price) : null,
                    'rate' => is_numeric($product->approved_reviews_avg_rating)
                        ? round((float) $product->approved_reviews_avg_rating, 1)
                        : null,
                    'cat' => (string) ($product->category?->name ?? 'سایر'),
                    'img' => $image,
                    'fy' => $fitReady ? (float) $fit['fit_y'] : ($slot === 'bottom' ? 0.66 : 0.38),
                    'slot' => $fitReady ? $slot : null,
                    'o' => $fitReady ? (int) $fit['look_id'] : null,
                    'url' => route('products.show', ['product' => $product->slug]),
                ];
            })
            ->filter()
            ->values();

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
            'favs' => [],
            'cart' => 0,
            'currency' => (string) config('commerce-admin.currency_symbol', 'تومان'),
            'routes' => [
                'cart' => route('cart.items.store'),
                'cartMany' => route('cart.items.bulk'),
                'cartSummary' => route('cart.summary'),
            ],
            'initialQuery' => $request && is_string($request->query('q'))
                ? mb_substr(trim($request->query('q')), 0, 120)
                : '',
        ];
    }
}
