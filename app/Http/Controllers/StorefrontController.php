<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Services\FittingRoomShopData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        // The homepage is intentionally one focused editorial hero: one real,
        // purchasable product and no below-the-fold product/category grid.
        $heroProduct = $this->catalogProducts()
            ->where('is_hero', true)
            ->orderBy('sort_order')
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

        $heroProduct?->loadMissing(['activeVariants.primaryGalleryMedia']);

        return view('store.home', compact('heroProduct'));
    }

    public function index(Request $request): View
    {
        // The approved catalog interactions run client-side; all visible product
        // values and fitting-room mappings still originate from active DB records.
        $shop = app(FittingRoomShopData::class)->payload($request);

        return view('store.index', compact('shop'));
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
