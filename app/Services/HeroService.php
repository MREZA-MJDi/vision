<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class HeroService
{
    public const MAX_SLIDES = 30;

    public const MAX_HOME_PRODUCTS = 7;

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

    public function storefrontSlides(Collection $products): Collection
    {
        return $products->flatMap(function (Product $product): Collection {
            $primary = $product->activeVariants->first();

            return $product->activeVariants
                ->groupBy(function (ProductVariant $variant): string {
                    $color = mb_strtolower(trim((string) $variant->color));
                    $code = mb_strtolower((string) $variant->color_code);

                    return $color !== '' || $code !== ''
                        ? "color:{$color}:{$code}"
                        : 'option:' . trim((string) $variant->size);
                })
                ->map(function (Collection $variants) use ($product, $primary): array {
                    $first = $variants->first();
                    $variant = $first === $primary
                        ? $first
                        : ($variants->first(fn (ProductVariant $item) => $item->stock > 0 && $item->primaryGalleryMedia?->url)
                            ?? $variants->first(fn (ProductVariant $item) => $item->stock > 0)
                            ?? $first);
                    $color = $variant->color_code;

                    return [
                        'productId' => $product->getKey(),
                        'title' => $product->name,
                        'description' => Str::limit($product->short_description ?: $product->description ?: 'انتخابی از فروشگاه VISION.', 220),
                        'image' => $variant->primaryGalleryMedia?->url ?: $product->primaryGalleryMedia?->url,
                        'alt' => trim(implode(' ', array_filter([$product->name, $variant->color, $variant->size]))),
                        'theme' => preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/D', (string) $color) ? $color : '#9a5527',
                        'url' => route('products.show', ['product' => $product->slug, 'variant_id' => $variant->getKey()]),
                        'price' => Money::format($variant->effective_price),
                        'originalPrice' => $variant->is_on_sale ? Money::format($variant->price) : '',
                        'stock' => $variant->stock > 0 ? Money::number($variant->stock) . ' عدد موجود' : 'فعلاً ناموجود',
                        'label' => $variant->color ?: ($variant->size ?: 'محصول منتخب'),
                    ];
                })
                ->values();
        })->values();
    }

    public function toggle(Product $product): bool
    {
        $isHero = Cache::lock('store:home:hero:toggle', 10)->block(
            5,
            function () use ($product): bool {
                return DB::transaction(function () use ($product): bool {
                    $current = Product::query()
                        ->whereKey($product->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $current->is_hero) {
                        $heroCount = Product::query()
                            ->where('is_hero', true)
                            ->lockForUpdate()
                            ->count();

                        if ($heroCount >= self::MAX_SLIDES) {
                            throw new RuntimeException('hero_limit');
                        }
                    }

                    $current->update([
                        'is_hero' => ! $current->is_hero,
                    ]);

                    return (bool) $current->is_hero;
                });
            }
        );

        $this->invalidate();

        return $isHero;
    }

    public function invalidate(): void
    {
        Cache::put(
            self::VERSION_KEY,
            (string) Str::uuid(),
            now()->addYear()
        );
    }

    private function loadSlides(): array
    {
        return Product::query()
            ->select([
                'id',
                'brand_id',
                'category_id',
                'name',
                'slug',
                'short_description',
                'description',
                'sort_order',
            ])
            ->where('is_active', true)
            ->where('is_hero', true)
            ->with([
                'brand:id,name',
                'brand.logoMedia',
                'category:id,name',
                'primaryGalleryMedia',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::MAX_SLIDES)
            ->get()
            ->values()
            ->map(function (Product $product, int $index): array {
                $description = trim((string) (
                    $product->short_description ?: $product->description
                ));

                if ($description === '') {
                    $description = $product->category?->name
                        ? 'منتخبی از دسته ' . $product->category->name . ' در جانان.'
                        : 'منتخبی از کالکشن جانان.';
                }

                return [
                    'id' => $product->getKey(),
                    'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'image' => $product->primaryGalleryMedia?->url
                        ?: $product->brand?->logoMedia?->url,
                    'title' => $product->name,
                    'description' => Str::limit($description, 220),
                    'brand' => $product->brand?->name ?? 'JANAN',
                    'url' => route('products.show', $product),
                ];
            })
            ->all();
    }
}
