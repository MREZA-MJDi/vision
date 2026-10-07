<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class HeroService
{
    public const MAX_SLIDES = 30;

    private const VERSION_KEY = 'store:home:hero:products:version';
    private const CACHE_PREFIX = 'store:home:hero:products:';
    private const CACHE_TTL_MINUTES = 30;

    public function slides(): array
    {
        $version = (string) Cache::remember(
            self::VERSION_KEY,
            now()->addYear(),
            static fn () => '1'
        );

        return Cache::remember(
            self::CACHE_PREFIX . $version,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->loadSlides()
        );
    }

    public function toggle(Product $product): bool
    {
        $isHero = Cache::lock('store:home:hero:toggle', 10)->block(
            5,
            function () use ($product): bool {
                return DB::transaction(function () use ($product): bool {
                    $current = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

                    if (! $current->is_hero) {
                        $heroCount = Product::query()->where('is_hero', true)->lockForUpdate()->count();

                        if ($heroCount >= self::MAX_SLIDES) {
                            throw new RuntimeException('hero_limit');
                        }
                    }

                    $current->update(['is_hero' => ! $current->is_hero]);

                    return (bool) $current->is_hero;
                });
            }
        );

        $this->invalidate();

        return $isHero;
    }

    public function invalidate(): void
    {
        Cache::put(self::VERSION_KEY, (string) Str::uuid(), now()->addYear());
    }

    private function loadSlides(): array
    {
        return Product::query()
            ->select(['id', 'brand_id', 'category_id', 'name', 'slug', 'short_description', 'description', 'sort_order'])
            ->where('is_active', true)
            ->where('is_hero', true)
            ->with(['brand:id,name', 'brand.logoMedia', 'category:id,name', 'primaryGalleryMedia'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::MAX_SLIDES)
            ->get()
            ->values()
            ->map(function (Product $product, int $index): array {
                $description = trim((string) ($product->short_description ?: $product->description));

                if ($description === '') {
                    $description = $product->category?->name
                        ? 'منتخبی از دسته ' . $product->category->name . ' در جانان.'
                        : 'منتخبی از کالکشن جانان.';
                }

                return [
                    'id' => $product->getKey(),
                    'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'image' => $product->primaryGalleryMedia?->url ?: $product->brand?->logoMedia?->url,
                    'title' => $product->name,
                    'description' => Str::limit($description, 220),
                    'brand' => $product->brand?->name ?? 'JANAN',
                    'url' => url('/products/' . $product->slug),
                ];
            })
            ->all();
    }
}
