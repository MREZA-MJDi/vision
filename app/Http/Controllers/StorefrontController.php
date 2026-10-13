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

        $productsQuery = Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews')
            ->with([
                'category',
                'brand',
                'primaryGalleryMedia',
                'primaryActiveVariant.primaryGalleryMedia',
            ]);

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

        // Keep sale/price filters aligned with the same primary variant shown on each card.
        if ($saleOnly) {
            $productsQuery->whereHas('primaryActiveVariant', fn (Builder $variantQuery) => $variantQuery
                ->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price'));
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $productsQuery->whereHas('primaryActiveVariant', function (Builder $variantQuery) use ($minPrice, $maxPrice): void {
                if ($minPrice !== null) {
                    $variantQuery->whereRaw('COALESCE(sale_price, price) >= ?', [$minPrice]);
                }

                if ($maxPrice !== null) {
                    $variantQuery->whereRaw('COALESCE(sale_price, price) <= ?', [$maxPrice]);
                }
            });
        }

        match ($sort) {
            'featured' => $productsQuery->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('id'),
            'price_asc' => $productsQuery->orderByEffectivePrice('asc'),
            'price_desc' => $productsQuery->orderByEffectivePrice('desc'),
            default => $productsQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $initialProduct = $products->first();
        $initialImage = $initialProduct?->primaryGalleryMedia?->url
            ?? $initialProduct?->primaryActiveVariant?->primaryGalleryMedia?->url;

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
            'initialProduct' => $initialProduct,
            'initialImage' => $initialImage,
        ]);
    }
}
