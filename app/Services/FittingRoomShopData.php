<?php

namespace App\Services;

use App\Models\FittingRoomMannequin;
use App\Models\Category;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
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
                        'variant.product.primaryGalleryMedia',
                        'variant.product' => fn ($product) => $product->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating'),
                    ]),
                ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // The public studio has one canvas. Never mix looks from other
        // mannequins with the first mannequin's base photograph.
        $mannequin = $mannequins->first(fn ($record) => (bool) $record->baseMedia?->url);
        $mannequins = $mannequin ? collect([$mannequin]) : collect();
        $looks = [];
        $combinations = [];
        $suggestions = [];
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

                $eligible = static fn ($item) => $item?->variant?->is_active
                    && $item->variant->product?->is_active
                    && ($mannequinRecord->audience === 'all'
                        || $item->variant->product->audience === $mannequinRecord->audience);
                $looks[(string) $look->getKey()] = [
                    't' => $eligible($top) ? $top->variant->getKey() : null,
                    'b' => $eligible($bottom) ? $bottom->variant->getKey() : null,
                    's' => array_filter($previews, static fn ($url) => is_string($url) && $url !== ''),
                ];

                if ($eligible($top) && !empty($previews['T']) && !empty($previews['S'])) {
                    $suggestions[$top->variant->id] ??= $previews['S'];
                }
                if ($eligible($top) && $eligible($bottom) && !empty($previews['TB'])) {
                    $combinations[$top->variant->id][$bottom->variant->id] ??= $previews['TB'];
                }

                foreach ($look->items as $item) {
                    $preview = $item->slot === 'top' ? 'T' : 'B';
                    if (! $eligible($item) || empty($previews[$preview])) {
                        continue;
                    }

                    $variant = $item->variant;
                    $itemMap[$variant->getKey()] ??= $this->card($variant, [
                        'slot' => $item->slot,
                        'o' => (int) $look->getKey(),
                        'fy' => (float) $item->fit_y,
                    ]);
                }
            }
        }

        $catalog = ProductVariant::query()
            ->select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true)
            ->where(function (Builder $variants) use ($itemMap): void {
                $variants->whereIntegerInRaw('product_variants.id', array_keys($itemMap))
                    ->orWhere(function (Builder $primary) use ($itemMap): void {
                        $primary->whereIntegerNotInRaw('product_variants.product_id', array_unique(array_column($itemMap, 'product_id')))
                            ->where('product_variants.id', ProductVariant::query()
                                ->select('product_variants.id')
                                ->whereColumn('product_variants.product_id', 'products.id')
                                ->where('product_variants.is_active', true)
                                ->orderByRaw('CASE WHEN product_variants.stock > 0 THEN 0 ELSE 1 END')
                                ->orderBy('product_variants.sort_order')
                                ->orderBy('product_variants.id')
                                ->limit(1));
                    });
            });

        $price = 'CASE WHEN product_variants.sale_price >= 0 AND product_variants.sale_price < product_variants.price THEN product_variants.sale_price ELSE product_variants.price END';
        $priceRange = (clone $catalog)->selectRaw("MAX({$price}) AS highest")->toBase()->first();
        $step = 100000;
        $max = max($step, (int) ceil(($priceRange->highest ?? $step) / $step) * $step);

        $categories = Category::query()
            ->whereIn('id', (clone $catalog)->select('products.category_id')->distinct()->toBase())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);
        $read = static fn (string $key): string => is_string($value = $request?->query($key)) ? trim($value) : '';
        $search = mb_substr($read('q'), 0, 120);
        $category = $read('category');
        $categoryId = $categories->firstWhere('slug', $category)?->id;
        $saleOnly = $request?->boolean('sale') ?? false;
        $priceMax = filter_var($read('price_max'), FILTER_VALIDATE_INT);
        $sort = match ($read('sort')) {
            'lo', 'price_asc' => 'price_asc',
            'hi', 'price_desc' => 'price_desc',
            'rt', 'rating' => 'rating',
            default => 'newest',
        };

        if ($search !== '') {
            $pattern = '%' . $search . '%';
            $catalog->where(fn (Builder $query) => $query
                ->whereLike('products.name', $pattern)
                ->orWhereLike('product_variants.color', $pattern)
                ->orWhereLike('product_variants.size', $pattern));
        }
        if ($categoryId !== null) {
            $catalog->where('products.category_id', $categoryId);
        }
        if ($saleOnly) {
            $catalog->where('product_variants.sale_price', '>=', 0)
                ->whereColumn('product_variants.sale_price', '<', 'product_variants.price');
        }
        if ($priceMax !== false) {
            $catalog->whereRaw("({$price}) <= ?", [max(0, $priceMax)]);
        }

        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $catalog->orderByRaw("({$price}) " . ($sort === 'price_asc' ? 'asc' : 'desc'));
        } elseif ($sort === 'rating') {
            $catalog->orderByDesc(ProductReview::query()->selectRaw('COALESCE(AVG(rating), 0)')
                ->whereColumn('product_id', 'products.id')->where('status', 'approved'));
        }

        $products = $catalog
            ->with([
                'primaryGalleryMedia',
                'product.primaryGalleryMedia',
                'product' => fn ($product) => $product->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating'),
            ])
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id')
            ->orderBy('product_variants.sort_order')
            ->orderBy('product_variants.id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ProductVariant $variant) => $itemMap[$variant->id] ?? $this->card($variant));

        return [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'q' => $search,
                'category' => $categoryId === null ? '' : $category,
                'sale' => $saleOnly,
                'sort' => $sort,
                'priceMax' => $priceMax === false ? $max : min($max, max(0, $priceMax)),
                'max' => $max,
            ],
            'studio' => [
                'garments' => array_values($itemMap),
                'base' => $mannequin?->baseMedia?->url,
                'looks' => $looks,
                'combinations' => $combinations,
                'suggestions' => $suggestions,
                'favs' => array_values(array_unique(array_map('intval', (array) ($request?->session()->get('shop_favourites', []))))),
                'currency' => (string) config('commerce-admin.currency_symbol', 'تومان'),
                'routes' => [
                    'cart' => route('cart.items.store'),
                    'cartMany' => route('cart.items.bulk'),
                    'fav' => url('/shop/favourites'),
                    'cartSummary' => route('cart.summary'),
                ],
            ],
        ];
    }

    private function card(ProductVariant $variant, array $fit = []): array
    {
        $product = $variant->product;
        $color = (string) $variant->color_code;

        return [
            'id' => (int) $variant->getKey(),
            'product_id' => (int) $product->getKey(),
            'name' => $product->name . (($variant->color || $variant->size) ? ' · ' . $variant->display_name : ''),
            'price' => $variant->effective_price,
            'old' => $variant->is_on_sale ? (float) $variant->price : null,
            'rate' => is_numeric($product->approved_reviews_avg_rating) ? round((float) $product->approved_reviews_avg_rating, 1) : null,
            'img' => $variant->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url,
            'color' => trim((string) $variant->color),
            'colorCode' => preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $color) ? $color : '#8b8490',
            'stock' => $variant->stock,
            'slot' => $fit['slot'] ?? null,
            'o' => $fit['o'] ?? null,
            'fy' => $fit['fy'] ?? null,
            'url' => route('products.show', ['product' => $product->slug, 'variant_id' => $variant->id]),
        ];
    }
}
