<?php

namespace App\Http\Controllers;

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
        return view('store.home', app(\App\Services\HomepageContent::class)->data($hero));
    }

    public function index(Request $request): \Illuminate\Http\Response
    {
        $response = response()->view('store.index', app(FittingRoomShopData::class)->payload($request));
        if ($request->user()?->isAdmin()) {
            $response->header('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    public function studio(FittingRoomShopData $shop, Request $request): JsonResponse
    {
        $response = response()->json($shop->studio($request));
        return $response->header('Cache-Control', 'private, no-store');
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

}
