<?php

namespace App\Services;

use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
use App\Models\Category;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FittingRoomShopData
{
    public function payload(?Request $request = null): array
    {
        $mannequin = $this->publicMannequin();
        $fitItems = $mannequin ? $this->fittingItems($mannequin) : null;

        $catalog = ProductVariant::query()
            ->select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true);

        $price = 'CASE WHEN product_variants.sale_price >= 0 AND product_variants.sale_price < product_variants.price THEN product_variants.sale_price ELSE product_variants.price END';
        $highest = (clone $catalog)->toBase()->max(DB::raw($price));
        $step = 100000;
        $max = max($step, (int) ceil(($highest ?? $step) / $step) * $step);

        $categories = Category::query()
            ->whereIn('id', (clone $catalog)->select('products.category_id')->distinct()->toBase())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);
        $primaryVariant = ProductVariant::query()
            ->select('product_variants.id')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->active()
            ->orderByRaw('CASE WHEN product_variants.stock > 0 THEN 0 ELSE 1 END')
            ->orderBy('product_variants.sort_order')
            ->orderBy('product_variants.id')
            ->limit(1);

        if ($fitItems) {
            $fitProducts = (clone $fitItems)
                ->join('product_variants as fit_variants', 'fit_variants.id', '=', 'fitting_room_look_items.product_variant_id')
                ->select('fit_variants.product_id');

            $catalog->where(fn (Builder $variants) => $variants
                ->whereIn('product_variants.id', (clone $fitItems)->select('product_variant_id'))
                ->orWhere(fn (Builder $primary) => $primary
                    ->whereNotIn('product_variants.product_id', $fitProducts)
                    ->where('product_variants.id', $primaryVariant)));
        } else {
            $catalog->where('product_variants.id', $primaryVariant);
        }

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
            ->withQueryString();

        $pageFitIds = $fitItems
            ? (clone $fitItems)->whereIntegerInRaw('product_variant_id', $products->getCollection()->modelKeys())
                ->distinct()->pluck('product_variant_id')->flip()
            : collect();
        $products->through(fn (ProductVariant $variant) => $this->card($variant) + ['fitReady' => $pageFitIds->has($variant->id)]);

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
                'favs' => array_values(array_unique(array_map('intval', (array) ($request?->session()->get('shop_favourites', []))))),
                'currency' => (string) config('commerce-admin.currency_symbol', 'تومان'),
                'routes' => [
                    'studio' => $mannequin ? route('shop.studio') : null,
                    'cart' => route('cart.items.store'),
                    'cartMany' => route('cart.items.bulk'),
                    'fav' => url('/shop/favourites'),
                    'cartSummary' => route('cart.summary'),
                ],
            ],
        ];
    }

    public function studio(): array
    {
        $mannequin = $this->publicMannequin();
        abort_unless($mannequin, 404);

        $items = $this->fittingItems($mannequin)
            ->with([
                'look.previewTopMedia',
                'look.previewBottomMedia',
                'look.previewCombinedMedia',
                'look.previewSuggestedMedia',
                'variant.primaryGalleryMedia',
                'variant.product.primaryGalleryMedia',
                'variant.product' => fn ($product) => $product->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating'),
            ])
            ->join('fitting_room_looks as ordered_looks', 'ordered_looks.id', '=', 'fitting_room_look_items.look_id')
            ->orderBy('ordered_looks.sort_order')
            ->orderBy('ordered_looks.id')
            ->orderBy('fitting_room_look_items.id')
            ->get(['fitting_room_look_items.*']);

        $garments = [];
        $combinations = [];
        $suggestions = [];
        foreach ($items->groupBy('look_id') as $lookItems) {
            $look = $lookItems->first()->look;
            $top = $lookItems->firstWhere('slot', 'top');
            $bottom = $lookItems->firstWhere('slot', 'bottom');
            $scenes = [
                'top' => $look->previewTopMedia?->url,
                'bottom' => $look->previewBottomMedia?->url,
            ];
            if ($top && $scenes['top'] && ($suggested = $look->previewSuggestedMedia?->url)) {
                $suggestions[$top->product_variant_id] ??= $suggested;
            }
            if ($top && $bottom && $scenes['top'] && $scenes['bottom'] && ($combined = $look->previewCombinedMedia?->url)) {
                $combinations[$top->product_variant_id][$bottom->product_variant_id] ??= $combined;
            }
            foreach ($lookItems as $item) {
                $scene = $scenes[$item->slot];
                if (! $scene || isset($garments[$item->product_variant_id])) {
                    continue;
                }
                $garments[$item->product_variant_id] = $this->productData($item->variant) + [
                    'slot' => $item->slot,
                    'fy' => $item->fit_y,
                    'scene' => $scene,
                ];
            }
        }

        return [
            'base' => $mannequin->baseMedia->url,
            'garments' => array_values($garments),
            'combinations' => $combinations,
            'suggestions' => $suggestions,
        ];
    }

    private function publicMannequin(): ?FittingRoomMannequin
    {
        return FittingRoomMannequin::query()->active()
            ->with('baseMedia')->whereHas('baseMedia', fn ($media) => $media->whereNotNull('path'))
            ->orderBy('sort_order')->orderBy('id')
            ->lazy(16)->first(fn (FittingRoomMannequin $mannequin) => (bool) $mannequin->baseMedia?->url);
    }

    private function fittingItems(FittingRoomMannequin $mannequin): Builder
    {
        $hasPhoto = static fn ($media) => $media->whereRaw("TRIM(path) <> ''");

        return FittingRoomLookItem::query()->where('fitting_room_look_items.is_active', true)
            ->whereHas('variant', fn ($variant) => $variant->active()->whereHas('product', fn ($product) => $product
                ->active()->when($mannequin->audience !== 'all', fn ($audience) => $audience->where('audience', $mannequin->audience))))
            ->whereHas('look', fn ($look) => $look->active()->where('mannequin_id', $mannequin->id))
            ->where(fn ($slot) => $slot
                ->where(fn ($top) => $top->where('slot', 'top')->whereHas('look.previewTopMedia', $hasPhoto))
                ->orWhere(fn ($bottom) => $bottom->where('slot', 'bottom')->whereHas('look.previewBottomMedia', $hasPhoto)));
    }

    private function productData(ProductVariant $variant): array
    {
        $product = $variant->product;

        return [
            'id' => (int) $variant->getKey(),
            'name' => $product->name . (($variant->color || $variant->size) ? ' · ' . $variant->display_name : ''),
            'price' => $variant->effective_price,
            'rate' => is_numeric($product->approved_reviews_avg_rating) ? round((float) $product->approved_reviews_avg_rating, 1) : null,
            'img' => $variant->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url,
            'stock' => $variant->stock,
        ];
    }

    private function card(ProductVariant $variant): array
    {
        $color = (string) $variant->color_code;

        return $this->productData($variant) + [
            'old' => $variant->is_on_sale ? (float) $variant->price : null,
            'color' => trim((string) $variant->color),
            'colorCode' => preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $color) ? $color : '#8b8490',
            'url' => route('products.show', ['product' => $variant->product->slug, 'variant_id' => $variant->id]),
        ];
    }
}
