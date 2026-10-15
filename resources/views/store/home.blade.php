@extends('layouts.store')

@section('title', 'VISION — فروشگاه پوشاک')
@section('meta-description', 'مجموعه‌ای منتخب از پوشاک و اکسسوری‌های VISION؛ محصولات واقعی، قیمت‌های به‌روز و خرید امن.')
@section('canonical', route('storefront.index'))
@section('og-image', $heroProduct?->primaryAvailableVariant?->primaryGalleryMedia?->url ?: $heroProduct?->primaryGalleryMedia?->url)

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
    $heroVariant = $heroProduct?->primaryAvailableVariant ?? $heroProduct?->primaryActiveVariant;
    $heroImage = $heroVariant?->primaryGalleryMedia?->url ?? $heroProduct?->primaryGalleryMedia?->url;
    $heroVariants = $heroProduct?->activeVariants ?? collect();
    $heroSlides = ($heroProducts ?? collect())->map(function ($product) use ($formatPrice) {
        $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
        $image = $variant?->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url;
        $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $variant?->color_code)
            ? $variant->color_code
            : '#9a5527';

        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'description' => $product->short_description ?: $product->description ?: 'انتخابی دقیق برای کمدی که با تو حرکت می‌کند.',
            'url' => route('products.show', ['product' => $product->slug]),
            'image' => $image,
            'color' => $color,
            'variant_label' => $variant?->color ?: ($variant?->size ?: 'انتخاب VISION'),
            'price' => $variant ? $formatPrice($variant->effective_price) : 'قیمت نامشخص',
            'original_price' => $variant?->is_on_sale ? $formatPrice($variant->price) : null,
            'stock' => $variant && (int) $variant->stock > 0 ? number_format((int) $variant->stock) . ' عدد موجود' : 'فعلاً ناموجود',
        ];
    })->values();
    $heroColors = $heroVariants
        ->filter(fn ($variant) => filled($variant->color))
        ->unique(fn ($variant) => mb_strtolower(trim((string) $variant->color)))
        ->take(4)
        ->values();
    $heroSizes = $heroVariants
        ->filter(fn ($variant) => filled($variant->size))
        ->unique(fn ($variant) => mb_strtolower(trim((string) $variant->size)))
        ->take(4)
        ->values();
    $heroChoiceMode = $heroColors->count() > 1 ? 'color' : 'size';
    $heroVariantChoices = $heroChoiceMode === 'color'
        ? $heroColors
        : ($heroSizes->isNotEmpty() ? $heroSizes : $heroColors);
    $brandStoryVariant = $brandStoryProduct?->primaryAvailableVariant ?? $brandStoryProduct?->primaryActiveVariant;
    $brandStoryImage = $brandStoryVariant?->primaryGalleryMedia?->url ?? $brandStoryProduct?->primaryGalleryMedia?->url;
@endphp

<main class="store-main store-home">
    {{-- The split-color editorial hero follows the supplied visual reference. --}}
    <section class="home-hero" aria-labelledby="home-hero-title" data-home-hero
             style="--home-hero-base: {{ data_get($heroSlides, '0.color', '#9a5527') }}">
        <div class="home-hero__frame">
            <div class="home-hero__frame-brand">
                <a href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی VISION">
                    <strong>VISION</strong>
                    <small>EVERYDAY OBJECTS</small>
                </a>
            </div>

            <nav class="home-hero__nav" aria-label="دسترسی سریع">
                @if($heroProduct)
                    <a class="home-hero__nav-link home-hero__nav-link--current"
                       href="{{ route('products.show', ['product' => $heroProduct->slug]) }}" data-home-hero-product-link>محصول ویژه</a>
                @endif
                <a class="home-hero__nav-link" href="{{ route('shop.index') }}">همهٔ محصولات</a>
                <a class="home-hero__nav-link" href="#home-brand-story">داستان ما</a>
                <a class="home-hero__nav-link" href="#home-benefits">مزایا</a>
            </nav>

            <div class="home-hero__top-actions">
                <a class="home-hero__icon-button" href="{{ route('account.show') }}" aria-label="حساب کاربری" title="حساب کاربری">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.3"></circle><path d="M5.3 20c.5-3.4 2.8-5.3 6.7-5.3s6.2 1.9 6.7 5.3"></path></svg>
                </a>
                <a class="home-hero__icon-button home-hero__cart" href="{{ route('cart.index') }}" aria-label="مشاهدهٔ سبد خرید" title="سبد خرید">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path></svg>
                    <span data-cart-count>0</span>
                </a>
            </div>

            <div class="home-hero__copy">
                <div class="home-hero__slider-controls" aria-label="تغییر گزینهٔ محصول">
                    <button type="button" data-home-hero-prev aria-label="گزینهٔ قبلی">‹</button>
                    <button type="button" data-home-hero-next aria-label="گزینهٔ بعدی">›</button>
                </div>
                <p class="home-hero__eyebrow">مجموعهٔ منتخب VISION</p>
                <h1 id="home-hero-title" class="home-hero__title" data-home-hero-title>متفاوت باش،<br>بدون تلاش اضافه</h1>
                <p class="home-hero__description" data-home-hero-description>انتخاب‌هایی برای هر روز؛ طراحی دقیق، جزئیات حساب‌شده و پوشاکی هماهنگ با سبک تو.</p>
                @if($heroProduct)
                    <a class="home-hero__cta" href="{{ route('products.show', ['product' => $heroProduct->slug]) }}" data-home-hero-cta>
                        <span>این محصول را ببین</span><span aria-hidden="true">↗</span>
                    </a>
                @else
                    <a class="home-hero__cta" href="{{ route('shop.index') }}">
                        <span>رفتن به فروشگاه</span><span aria-hidden="true">↗</span>
                    </a>
                @endif
            </div>

            @if($heroProduct)
                <div class="home-hero__image-stage"
                     data-home-hero-stage
                     data-base-image="{{ $heroProduct->primaryGalleryMedia?->url ?? '' }}">
                    <img data-home-hero-image
                         src="{{ $heroImage ?? '' }}"
                         alt="{{ $heroProduct->name }}"
                         fetchpriority="high"
                         decoding="async"
                         @if(!$heroImage) hidden @endif>
                    <div class="home-hero__image-missing" data-home-hero-image-missing @if($heroImage) hidden @endif>
                        <span>برای این محصول هنوز تصویر واقعی ثبت نشده است.</span>
                    </div>
                </div>
            @else
                <div class="home-hero__image-missing home-hero__image-missing--empty">
                    <span>برای نمایش محصول ویژه، ابتدا یک محصول فعال انتخاب کن.</span>
                </div>
            @endif

            <div class="home-hero__image-caption" aria-live="polite">
                <span>انتخاب VISION</span>
                <strong data-home-hero-caption>{{ $heroVariant?->color ?: ($heroVariant?->size ?: ($heroProduct?->name ?? 'کالکشن جدید')) }}</strong>
            </div>

            @if($heroProduct)
                <aside class="home-hero__price-panel" aria-label="قیمت و گزینه‌های محصول">
                    <span class="home-hero__price-label">قیمت محصول</span>
                    <strong class="home-hero__price" data-home-hero-price>{{ $heroVariant ? $formatPrice($heroVariant->effective_price) : 'قیمت نامشخص' }}</strong>
                    <del class="home-hero__original-price"
                         data-home-hero-original
                         @if(!($heroVariant?->is_on_sale ?? false)) hidden @endif>{{ $heroVariant && $heroVariant->is_on_sale ? $formatPrice($heroVariant->price) : '' }}</del>
                    <span class="home-hero__stock" data-home-hero-stock>
                        @if($heroVariant && (int) $heroVariant->stock > 0)
                            {{ number_format((int) $heroVariant->stock) }} عدد موجود
                        @else
                            فعلاً ناموجود
                        @endif
                    </span>

                    @if($heroSlides->count() > 1)
                        <div class="home-hero__variant-picker">
                            <span class="home-hero__variant-label">کالکشن را ورق بزن</span>
                            <div class="home-hero__variants" role="group" aria-label="انتخاب اسلاید محصول">
                                @foreach($heroSlides as $index => $slide)
                                    <button type="button"
                                            class="home-hero__variant{{ $index === 0 ? ' is-selected' : '' }}"
                                            data-home-hero-slide
                                            data-title="{{ $slide['name'] }}"
                                            data-description="{{ $slide['description'] }}"
                                            data-url="{{ $slide['url'] }}"
                                            data-price="{{ $slide['price'] }}"
                                            data-original-price="{{ $slide['original_price'] ?? '' }}"
                                            data-stock="{{ $slide['stock'] }}"
                                            data-image="{{ $slide['image'] ?? '' }}"
                                            data-image-alt="{{ $slide['name'] }}"
                                            data-label="{{ $slide['variant_label'] }}"
                                            data-theme="{{ $slide['color'] }}"
                                            style="--home-hero-swatch: {{ $slide['color'] }}"
                                            aria-label="نمایش {{ $slide['name'] }}"
                                            title="{{ $slide['name'] }}"
                                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                            <span class="home-hero__variant-dot" aria-hidden="true"></span>
                                            <span class="home-hero__variant-initial" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>

                <a class="home-hero__mini-product"
                   href="{{ route('products.show', ['product' => $heroProduct->slug]) }}"
                   aria-label="مشاهدهٔ {{ $heroProduct->name }}"
                   data-home-hero-thumbnail-link
                   @if(!$heroImage) hidden @endif>
                    <img src="{{ $heroImage ?? '' }}"
                         alt=""
                         loading="lazy"
                         decoding="async"
                         data-home-hero-thumbnail
                         @if(!$heroImage) hidden @endif>
                </a>
            @endif

            <div class="home-hero__social" aria-hidden="true">
                <span>◎</span><span>f</span><span>𝕏</span><span>◇</span>
            </div>

            <div class="home-hero__bottom">
                <a href="#home-categories">دسته‌بندی‌ها</a>
                <span aria-hidden="true">—</span>
                <a href="#home-featured">انتخاب‌های ویژه</a>
            </div>
        </div>
    </section>

    {{-- Section 1: real category covers, with a bounded product-image fallback. --}}
    @if($categories->isNotEmpty())
        <section class="home-section home-categories" id="home-categories" aria-labelledby="home-categories-title">
            <div class="home-section__inner">
                <div class="home-section__heading">
                    <div>
                        <p class="store-eyebrow">دسته‌بندی‌ها</p>
                        <h2 id="home-categories-title">انتخاب بر اساس استایل</h2>
                        <p class="home-section__intro">از دستهٔ موردعلاقه‌ات شروع کن.</p>
                    </div>
                    <a class="home-section__link" href="{{ route('shop.index') }}">همهٔ محصولات <span aria-hidden="true">←</span></a>
                </div>

                <div class="home-category-grid">
                    @foreach($categories as $category)
                        @php
                            $categoryImageProduct = $category->products->first();
                            $categoryImage = $category->coverMedia?->url
                                ?? $categoryImageProduct?->primaryGalleryMedia?->url
                                ?? $categoryImageProduct?->primaryAvailableVariant?->primaryGalleryMedia?->url
                                ?? $categoryImageProduct?->primaryActiveVariant?->primaryGalleryMedia?->url;
                        @endphp
                        <a class="home-category-card" href="{{ route('shop.index', ['category_id' => $category->id]) }}">
                            @if($categoryImage)
                                <img class="home-category-card__image" src="{{ $categoryImage }}" alt="" loading="lazy" decoding="async">
                            @endif
                            <span class="home-category-card__shade" aria-hidden="true"></span>
                            <span class="home-category-card__index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="home-category-card__content">
                                <strong class="home-category-card__name">{{ $category->name }}</strong>
                                <span class="home-category-card__meta">{{ number_format($category->active_products_count) }} محصول</span>
                            </span>
                            <span class="home-category-card__arrow" aria-hidden="true">↙</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Section 2: the same active, eager-loaded catalog data used by the public shop. --}}
</main>
@endsection
