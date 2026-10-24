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
            ->with([
                'coverMedia',
                // Load at most one active, photo-ready product per category.
                // The bounded eager load provides a useful fallback when a category
                // has no dedicated cover image, without introducing an N+1 query.
                'products' => fn ($query) => $query
                    ->active()
                    ->whereHas('activeVariants')
                    ->with([
                        'primaryGalleryMedia',
                        'primaryAvailableVariant.primaryGalleryMedia',
                        'primaryActiveVariant.primaryGalleryMedia',
                    ])
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();

        // The hero is a small, already eager-loaded playlist. It is rendered in
        // the initial document so changing slides never adds a request.
        $heroProducts = $this->catalogProducts()
            ->where('is_hero', true)
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $heroProduct = $heroProducts->first();

        if (!$heroProduct) {
            $heroProduct = $this->catalogProducts()
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            if ($heroProduct) {
                $heroProducts = collect([$heroProduct]);
            }
        }

        // The hero picker uses real active variants and each variant's own
        // photo. This is a single bounded eager load for the highlighted product.
        $heroProduct?->loadMissing(['activeVariants.primaryGalleryMedia']);

        return view('store.home', compact(
            'categories',
            'heroProduct',
            'heroProducts',
        ));
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
