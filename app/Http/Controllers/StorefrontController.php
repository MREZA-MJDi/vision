<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'products as active_products_count' => fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereHas('activeVariants'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $heroProduct = $this->catalogProducts()
            ->where('is_hero', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$heroProduct) {
            $heroProduct = $this->catalogProducts()
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
        }

        $featuredProducts = $this->catalogProducts()
            ->when($heroProduct, fn (Builder $query) => $query->where('id', '!=', $heroProduct->getKey()))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('store.home', compact('categories', 'heroProduct', 'featuredProducts'));
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'products as active_products_count' => fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereHas('activeVariants'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->active()
            ->withCount([
                'products as active_products_count' => fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereHas('activeVariants'),
            ])
            ->orderBy('name')
            ->get();

        $search = is_string($request->input('q')) ? trim($request->input('q')) : '';
        $categoryId = filter_var($request->input('category_id'), FILTER_VALIDATE_INT) ?: null;
        $brandId = filter_var($request->input('brand_id'), FILTER_VALIDATE_INT) ?: null;
        $minPrice = is_numeric($request->input('price_min')) ? max(0, (float) $request->input('price_min')) : null;
        $maxPrice = is_numeric($request->input('price_max')) ? max(0, (float) $request->input('price_max')) : null;
        $sort = in_array($request->input('sort'), ['newest', 'featured', 'price_asc', 'price_desc'], true)
            ? $request->input('sort')
            : 'newest';
        $saleOnly = $request->boolean('sale');

        $productsQuery = $this->catalogProducts();

        if ($search !== '') {
            $productsQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($categoryId !== null) {
            $productsQuery->where('category_id', $categoryId);
        }

        if ($brandId !== null) {
            $productsQuery->where('brand_id', $brandId);
        }

        // Filters must use the exact variant the cards display: first in-stock
        // active variant, falling back to the first active variant when all are sold out.
        $displayedSaleFilter = static fn (Builder $variantQuery) => $variantQuery
            ->whereNotNull('sale_price')
            ->where('sale_price', '>=', 0)
            ->whereColumn('sale_price', '<', 'price');

        $displayedPriceFilter = static function (Builder $variantQuery) use ($minPrice, $maxPrice): void {
            if ($minPrice !== null) {
                $variantQuery->whereRaw(
                    'CASE WHEN sale_price IS NOT NULL AND sale_price >= 0 AND sale_price < price THEN sale_price ELSE price END >= ?',
                    [$minPrice]
                );
            }

            if ($maxPrice !== null) {
                $variantQuery->whereRaw(
                    'CASE WHEN sale_price IS NOT NULL AND sale_price >= 0 AND sale_price < price THEN sale_price ELSE price END <= ?',
                    [$maxPrice]
                );
            }
        };

        if ($saleOnly) {
            $productsQuery->where(function (Builder $query) use ($displayedSaleFilter): void {
                $query->whereHas('primaryAvailableVariant', $displayedSaleFilter)
                    ->orWhere(function (Builder $fallbackQuery) use ($displayedSaleFilter): void {
                        $fallbackQuery->whereDoesntHave('primaryAvailableVariant')
                            ->whereHas('primaryActiveVariant', $displayedSaleFilter);
                    });
            });
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $productsQuery->where(function (Builder $query) use ($displayedPriceFilter): void {
                $query->whereHas('primaryAvailableVariant', $displayedPriceFilter)
                    ->orWhere(function (Builder $fallbackQuery) use ($displayedPriceFilter): void {
                        $fallbackQuery->whereDoesntHave('primaryAvailableVariant')
                            ->whereHas('primaryActiveVariant', $displayedPriceFilter);
                    });
            });
        }

        match ($sort) {
            'featured' => $productsQuery->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('id'),
            'price_asc' => $productsQuery->orderByEffectivePrice('asc'),
            'price_desc' => $productsQuery->orderByEffectivePrice('desc'),
            default => $productsQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $products = $productsQuery->paginate(12)->withQueryString();

        return view('store.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'search' => $search,
            'selectedCategoryId' => $categoryId,
            'selectedBrandId' => $brandId,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'sort' => $sort,
            'saleOnly' => $saleOnly,
        ]);
    }

    /**
     * Render a public product detail page from active catalog records only.
     */
    public function show(Product $product): View
    {
        abort_unless((bool) $product->is_active, 404);

        $product->load([
            'category',
            'brand',
            'galleryMedia',
            'primaryGalleryMedia',
            'activeVariants.primaryGalleryMedia',
        ]);

        if ($product->activeVariants->isEmpty()) {
            abort(404);
        }

        $relatedQuery = Product::query()
            ->active()
            ->where('id', '!=', $product->getKey())
            ->whereHas('activeVariants')
            ->with([
                'category',
                'brand',
                'primaryGalleryMedia',
                'primaryAvailableVariant.primaryGalleryMedia',
                'primaryActiveVariant.primaryGalleryMedia',
            ])
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews');

        if ($product->category_id !== null) {
            $relatedQuery->where('category_id', $product->category_id);
        }

        $relatedProducts = $relatedQuery
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return view('store.products.show', compact('product', 'relatedProducts'));
    }

    /**
     * A bounded, eager-loaded base query shared by the homepage and catalog.
     * The card templates never need per-product follow-up queries.
     */
    private function catalogProducts(): Builder
    {
        return Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews')
            ->with([
                'category',
                'brand',
                'primaryGalleryMedia',
                'primaryAvailableVariant.primaryGalleryMedia',
                'primaryActiveVariant.primaryGalleryMedia',
            ]);
    }
}
