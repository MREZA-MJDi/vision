<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

final class HomepageContent
{
    public const TRUST_SLOTS = [
        'selection' => 'انتخاب آگاهانه',
        'tracking' => 'پیگیری سفارش',
        'support' => 'پشتیبانی',
    ];

    public function data(HeroService $hero): array
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
            ->limit(8)
            ->get();

        $editorialProducts = $this->catalogProducts()
            ->where('is_featured', true)
            ->whereHas('primaryAvailableVariant')
            ->orderBy('sort_order')->orderByDesc('id')->limit(4)->get();

        $saleProducts = $this->catalogProducts()
            ->whereHas('primaryAvailableVariant', fn (Builder $query) => $query
                ->where('sale_price', '>=', 0)->whereColumn('sale_price', '<', 'price'))
            ->orderByDesc('created_at')->orderByDesc('id')->limit(4)->get();

        $homeCategories = Category::query()
            ->active()
            ->with('coverMedia')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $homeBrands = Brand::query()->active()
            ->with('logoMedia')->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get();

        $trustImages = $this->trustImages();

        return compact('heroProducts', 'heroSlides', 'newProducts', 'homeCategories', 'homeBrands', 'editorialProducts', 'saleProducts', 'trustImages');
    }


    public function trustImages(): array
    {
        $disk = Storage::disk('public');
        $images = [];
        foreach (self::TRUST_SLOTS as $slot => $label) {
            $path = "homepage/trust/{$slot}.webp";
            $images[$slot] = [
                'label' => $label,
                'url' => $disk->exists($path) ? $disk->url($path) . '?v=' . $disk->lastModified($path) : asset("images/home/trust/{$slot}.svg"),
                'custom' => $disk->exists($path),
            ];
        }
        return $images;
    }

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
