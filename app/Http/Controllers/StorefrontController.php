<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\FittingRoomShopData;
use App\Services\HeroService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(HeroService $hero): View
    {
        $heroProducts = $this->catalogProducts()
            ->where('is_hero', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(HeroService::MAX_HOME_PRODUCTS)
            ->get();

        $heroProducts->loadMissing(['activeVariants.primaryGalleryMedia']);
        $heroSlides = $hero->storefrontSlides($heroProducts);

        $newProducts = $this->catalogProducts()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $homeCategories = Category::query()
            ->active()
            ->whereHas('products', fn (Builder $query) => $query->active()->whereHas('activeVariants'))
            ->with('coverMedia')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $homeBrands = Brand::query()->active()
            ->whereHas('products', fn (Builder $query) => $query->active()->whereHas('activeVariants'))
            ->with('logoMedia')->orderBy('name')->orderBy('id')->limit(8)->get();

        return view('store.home', compact('heroProducts', 'heroSlides', 'newProducts', 'homeCategories', 'homeBrands'));
    }

    public function index(Request $request): View
    {
        return view('store.index', app(FittingRoomShopData::class)->payload($request));
    }

    public function studio(FittingRoomShopData $shop): JsonResponse
    {
        return response()->json($shop->studio());
    }

    /**
     * Render a public product detail page from active catalog records only.
     */
    public function show(Request $request, Product $product): View
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

        $requestedVariantId = $request->integer('variant_id');
        $selectedVariantId = $product->activeVariants
            ->first(fn ($variant) => (int) $variant->getKey() === $requestedVariantId)
            ?->getKey();

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

        return view('store.products.show', compact('product', 'relatedProducts', 'selectedVariantId'));
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
