<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FittingRoomMannequin;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class FittingRoomShopData
{
    private const PER_PAGE = 12;

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

        $mannequin = $mannequins->first();
        $looks = [];
        $itemMap = [];

        foreach ($mannequins as $mannequinRecord) {
            foreach ($mannequinRecord->looks as $look) {
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
                        $mannequinRecord->audience === 'all'
                        || $product->audience === $mannequinRecord->audience
                    );
                    if (!$variant || !$product || !$variant->is_active || !$product->is_active || !$audienceMatches) {
                        continue;
                    }

                    // A variant appears once in the fitting-room pool. If it is
                    // mapped more than once, the first curated look wins.
                    $itemMap[$variant->getKey()] ??= [
                        'look_id' => $look->getKey(),
                        'slot' => $item->slot,
                        'fit_y' => (float) $item->fit_y,
                        'look' => $look,
                        'variant' => $variant,
                        'product' => $product,
                    ];
                }
            }
        }

        $categoryNames = Category::query()
            ->active()
            ->whereHas('products', fn ($products) => $products->active()->whereHas('activeVariants'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        $requestedSort = $request?->query('sort');
        $initialSort = is_string($requestedSort) ? match ($requestedSort) {
            'lo', 'price_asc' => 'lo',
            'hi', 'price_desc' => 'hi',
            'rt', 'rating' => 'rt',
            default => 'new',
        } : 'new';
        $initialQuery = $request && is_string($request->query('q'))
            ? mb_substr(trim($request->query('q')), 0, 120)
            : '';
        $requestedCategory = $request && is_string($request->query('category'))
            ? trim($request->query('category'))
            : '';
        $initialCategory = in_array($requestedCategory, $categoryNames, true) ? $requestedCategory : '';
        $saleOnly = $request?->boolean('sale') ?? false;

        // Compute a stable global slider range separately from the paged query.
        $priceBounds = ProductVariant::query()
            ->active()
            ->whereHas('product', fn ($products) => $products->active())
            ->selectRaw('
                MIN(CASE WHEN sale_price IS NOT NULL AND sale_price >= 0 AND sale_price < price THEN sale_price ELSE price END) AS min_effective_price,
                MAX(CASE WHEN sale_price IS NOT NULL AND sale_price >= 0 AND sale_price < price THEN sale_price ELSE price END) AS max_effective_price
            ')
            ->first();

        $rawMin = max(0, (float) ($priceBounds?->min_effective_price ?? 0));
        $rawMax = max($rawMin, (float) ($priceBounds?->max_effective_price ?? 0));
        $min = (int) floor($rawMin / 100000) * 100000;
        $max = (int) ceil($rawMax / 100000) * 100000;
        if ($max <= $min) {
            $max = $min + 100000;
        }

        $requestedMax = $request?->query('max_price');
        $initialMax = is_numeric($requestedMax)
            ? max($min, min($max, (int) $requestedMax))
            : $max;

        // Paginate parent products in SQL before eager-loading their card media.
        // The UI displays 12 products per page rather than rendering the entire
        // catalogue into one enormous JSON payload and DOM tree.
        $catalogQuery = Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->with([
                'category',
                'primaryGalleryMedia',
                'activeVariants.primaryGalleryMedia',
                'primaryAvailableVariant.primaryGalleryMedia',
                'primaryActiveVariant.primaryGalleryMedia',
            ]);

        if ($initialQuery !== '') {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $initialQuery) . '%';
            $catalogQuery->where(function ($search) use ($term) {
                $search->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhereHas('activeVariants', fn ($variants) => $variants
                        ->where('color', 'like', $term)
                        ->orWhere('size', 'like', $term)
                        ->orWhere('sku', 'like', $term));
            });
        }

        if ($initialCategory !== '') {
            $catalogQuery->whereHas('category', fn ($category) => $category->where('name', $initialCategory));
        }

        if ($saleOnly || $initialMax < $max) {
            $catalogQuery->whereHas('activeVariants', function ($variants) use ($saleOnly, $initialMax, $max) {
                if ($saleOnly) {
                    $variants->whereNotNull('sale_price')
                        ->where('sale_price', '>=', 0)
                        ->whereColumn('sale_price', '<', 'price');
                }

                if ($initialMax < $max) {
                    $variants->whereRaw(
                        'CASE WHEN sale_price IS NOT NULL AND sale_price >= 0 AND sale_price < price THEN sale_price ELSE price END <= ?',
                        [$initialMax]
                    );
                }
            });
        }

        match ($initialSort) {
            'lo' => $catalogQuery->orderByEffectivePrice('asc'),
            'hi' => $catalogQuery->orderByEffectivePrice('desc'),
            'rt' => $catalogQuery->orderByDesc('approved_reviews_avg_rating')->orderByDesc('id'),
            default => $catalogQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $paginator = $catalogQuery->paginate(self::PER_PAGE)->withQueryString();

        // All filtering and sorting above apply to the full SQL catalogue. This
        // helper serializes only a single real variant into a lightweight card.
        $isFitReady = function (ProductVariant $variant, ?array $fit): bool {
            $look = $fit['look'] ?? null;
            $slot = $fit['slot'] ?? null;
            if (!$look || !in_array($slot, ['top', 'bottom'], true)) {
                return false;
            }

            $hasIndividualScene = ($slot === 'top' && (bool) $look->previewTopMedia?->url)
                || ($slot === 'bottom' && (bool) $look->previewBottomMedia?->url);
            $mappedSlotItem = $look->items->firstWhere('slot', $slot);
            $hasExactCombinedScene = (bool) $look->previewCombinedMedia?->url
                && (int) ($mappedSlotItem?->product_variant_id ?? 0) === (int) $variant->getKey();

            return $hasIndividualScene || $hasExactCombinedScene;
        };

        $serializeVariant = function (ProductVariant $variant, Product $product, ?array $fit) use ($isFitReady): array {
            $fitReady = $isFitReady($variant, $fit);
            $slot = $fitReady ? ($fit['slot'] ?? null) : null;
            $isOnSale = (bool) $variant->is_on_sale;
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
                'fy' => $fitReady ? (float) ($fit['fit_y'] ?? 0.38) : (($fit['slot'] ?? null) === 'bottom' ? 0.66 : 0.38),
                'slot' => $slot,
                'o' => $fitReady ? (int) $fit['look_id'] : null,
                'url' => route('products.show', ['product' => $product->slug, 'variant_id' => $variant->getKey()]),
            ];
        };

        $catalogProducts = $paginator->getCollection();
        $products = $catalogProducts->flatMap(function (Product $product) use ($itemMap, $isFitReady, $serializeVariant) {
            $fittedVariants = $product->activeVariants
                ->filter(fn (ProductVariant $variant) => $isFitReady($variant, $itemMap[$variant->getKey()] ?? null))
                ->values();

            $variants = $fittedVariants->isNotEmpty()
                ? $fittedVariants
                : collect([$product->primaryAvailableVariant ?? $product->primaryActiveVariant])->filter();

            return $variants->map(fn (ProductVariant $variant) =>
                $serializeVariant($variant, $product, $itemMap[$variant->getKey()] ?? null)
            );
        })->filter(function (array $product) use ($saleOnly, $initialMax, $max) {
            if ($initialMax < $max && (int) $product['price'] > $initialMax) {
                return false;
            }

            return !$saleOnly || ($product['old'] !== null && (int) $product['old'] > (int) $product['price']);
        })->values();

        // The catalog cards are paged; the fitting-room pool contains only
        // variants explicitly mapped to a real mannequin scene, so a suggestion
        // may still be found even when its card is on another catalog page.
        $fitProducts = collect($itemMap)
            ->filter(fn (array $fit) => $isFitReady($fit['variant'], $fit))
            ->map(fn (array $fit) => $serializeVariant($fit['variant'], $fit['product'], $fit))
            ->unique('id')
            ->values();

        return [
            'products' => $products->all(),
            'fitProducts' => $fitProducts->all(),
            'cats' => $categoryNames,
            'min' => $min,
            'max' => $max,
            'base' => $mannequin?->baseMedia?->url,
            'looks' => $looks,
            'favs' => array_values(array_unique(array_map('intval', (array) ($request?->session()->get('shop_favourites', []))))),
            'cart' => 0,
            'currency' => (string) config('commerce-admin.currency_symbol', 'تومان'),
            'routes' => [
                'shop' => route('shop.index'),
                'cart' => route('cart.items.store'),
                'cartMany' => route('cart.items.bulk'),
                'fav' => url('/shop/favourites'),
                'cartSummary' => route('cart.summary'),
            ],
            'initialQuery' => $initialQuery,
            'initialCategory' => $initialCategory,
            'initialMax' => $initialMax,
            'initialSaleOnly' => $saleOnly,
            'initialSort' => $initialSort,
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
