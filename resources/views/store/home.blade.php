@extends('layouts.store')

@section('title', 'VISION — پوشاک برای نگاه متفاوت')
@section('meta-description', 'یک انتخاب دقیق. یک استایل متمایز. محصولات واقعی VISION را کشف کن.')
@section('canonical', route('storefront.index'))
@section('og-image', $heroProducts->first()?->primaryAvailableVariant?->primaryGalleryMedia?->url ?: $heroProducts->first()?->primaryGalleryMedia?->url)

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
    $heroSlides = $heroProducts->flatMap(function ($product) {
        return $product->activeVariants
            ->groupBy(function ($variant) {
                $color = mb_strtolower(trim((string) $variant->color));

                return $color !== '' ? 'color:' . $color : 'option:' . trim((string) $variant->size);
            })
            ->map(function ($variants) use ($product) {
                $variant = $variants->first(fn ($item) => (int) $item->stock > 0 && ($item->primaryGalleryMedia?->url || $product->primaryGalleryMedia?->url))
                    ?? $variants->first(fn ($item) => (int) $item->stock > 0)
                    ?? $variants->first();

                return ['product' => $product, 'variant' => $variant];
            });
    })->filter(fn ($slide) => $slide['variant'] !== null)->take(8)->values();

    $activeHeroSlide = $heroSlides->first(fn ($slide) => (int) $slide['variant']->stock > 0
        && ($slide['variant']->primaryGalleryMedia?->url || $slide['product']->primaryGalleryMedia?->url))
        ?? $heroSlides->first();

    $heroProduct = $activeHeroSlide['product'] ?? null;
    $heroVariant = $activeHeroSlide['variant'] ?? null;
    $heroImage = $heroVariant?->primaryGalleryMedia?->url ?? $heroProduct?->primaryGalleryMedia?->url;
    $heroColor = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', (string) $heroVariant?->color_code)
        ? $heroVariant->color_code
        : '#9a5527';
    $heroProductUrl = $heroProduct
        ? route('products.show', ['product' => $heroProduct->slug, 'variant_id' => $heroVariant?->getKey()])
        : route('shop.index');
    $heroDescription = $heroProduct?->short_description ?: $heroProduct?->description ?: 'انتخاب‌هایی دقیق، طراحی‌شده برای زندگی واقعی.';
@endphp

<main class="store-main store-home store-home--minimal">
    <section class="home-hero" aria-labelledby="home-hero-title" data-home-hero
             style="--home-hero-base: {{ $heroColor }}">
        <div class="home-hero__frame">
            <div class="home-hero__frame-brand">
                <a href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی VISION">
                    <strong>VISION</strong>
                    <small>FORM FOLLOWS FEELING</small>
                </a>
            </div>

            <nav class="home-hero__nav" aria-label="ناوبری اصلی">
                <a class="home-hero__nav-link home-hero__nav-link--current" href="{{ route('storefront.index') }}" aria-current="page">خانه</a>
                <a class="home-hero__nav-link" href="{{ route('shop.index') }}">فروشگاه</a>
                <a class="home-hero__nav-link" href="{{ route('shop.index', ['sort' => 'newest']) }}">تازه‌ها</a>
                <a class="home-hero__nav-link" href="{{ route('shop.index', ['sale' => 1]) }}">حراج</a>
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
                <p class="home-hero__eyebrow">VISION / انتخاب محدود، اثر ماندگار</p>
                <h1 id="home-hero-title" class="home-hero__title" data-home-hero-title>{{ $heroProduct?->name ?? 'استایل تو؛ امضای تو.' }}</h1>
                <p class="home-hero__description" data-home-hero-description>{{ $heroDescription }}</p>
                <div class="home-hero__actions">
                    <a class="home-hero__cta" href="{{ route('shop.index') }}">
                        <span>کشف فروشگاه</span><span aria-hidden="true">↗</span>
                    </a>
                    @if($heroProduct)
                        <a class="home-hero__cta home-hero__cta--secondary" href="{{ $heroProductUrl }}" data-home-hero-cta>
                            <span>مشاهدهٔ محصول</span><span aria-hidden="true">↗</span>
                        </a>
                    @endif
                </div>
                <div class="home-hero__signature" aria-hidden="true">
                    <span>CURATED PIECES</span>
                    <span class="home-hero__signature-line"></span>
                    <span>01 / VISION</span>
                </div>
            </div>

            <div class="home-hero__image-stage" data-home-hero-stage>
                <img data-home-hero-image
                     @if($heroImage) src="{{ $heroImage }}" @else hidden @endif
                     alt="{{ trim(implode(' ', array_filter([$heroProduct?->name, $heroVariant?->color, $heroVariant?->size]))) ?: 'محصول منتخب VISION' }}"
                     fetchpriority="high"
                     decoding="async">
                <div class="home-hero__image-missing home-hero__image-missing--empty" data-home-hero-image-missing @if($heroImage) hidden @endif>
                    <span>
                        @if($heroProduct)
                            برای این محصول هنوز تصویر واقعی ثبت نشده است.
                        @else
                            محصول فعالی برای نمایش در Hero ثبت نشده است.
                        @endif
                    </span>
                </div>
                <span class="home-hero__image-aura" aria-hidden="true"></span>
            </div>

            @if($heroProduct && $heroVariant)
                <aside class="home-hero__price-panel" aria-label="اطلاعات محصول منتخب">
                    <span class="home-hero__price-label">محصول منتخب / VISION</span>
                    <strong class="home-hero__featured-name" data-home-hero-caption>{{ $heroProduct->name }}</strong>
                    <strong class="home-hero__price" data-home-hero-price>{{ $formatPrice($heroVariant->effective_price) }}</strong>
                    <del class="home-hero__original-price" data-home-hero-original @if(!$heroVariant->is_on_sale) hidden @endif>{{ $heroVariant->is_on_sale ? $formatPrice($heroVariant->price) : '' }}</del>
                    <span class="home-hero__stock" data-home-hero-stock>
                        @if((int) $heroVariant->stock > 0)
                            {{ number_format((int) $heroVariant->stock) }} عدد موجود
                        @else
                            فعلاً ناموجود
                        @endif
                    </span>
                    <a class="home-hero__price-link" href="{{ $heroProductUrl }}" data-home-hero-product-link>جزئیات محصول <span aria-hidden="true">↗</span></a>

                    @if($heroSlides->isNotEmpty())
                        <div class="home-hero__variant-picker" aria-label="انتخاب رنگ و محصول">
                            <span class="home-hero__variant-label">انتخاب رنگ</span>
                            <div class="home-hero__variants" role="group" aria-label="رنگ‌های منتخب VISION">
                                @foreach($heroSlides as $slide)
                                    @php
                                        $slideProduct = $slide['product'];
                                        $slideVariant = $slide['variant'];
                                        $slideColor = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', (string) $slideVariant->color_code)
                                            ? $slideVariant->color_code
                                            : '#8b8490';
                                        $slideImage = $slideVariant->primaryGalleryMedia?->url ?? $slideProduct->primaryGalleryMedia?->url;
                                        $slideUrl = route('products.show', ['product' => $slideProduct->slug, 'variant_id' => $slideVariant->getKey()]);
                                        $slideLabel = trim(implode(' · ', array_filter([$slideProduct->name, $slideVariant->color])));
                                        $slideAlt = trim(implode(' ', array_filter([$slideProduct->name, $slideVariant->color, $slideVariant->size])));
                                        $slideSelected = (int) $slideProduct->getKey() === (int) $heroProduct->getKey()
                                            && (int) $slideVariant->getKey() === (int) $heroVariant->getKey();
                                    @endphp
                                    <button type="button"
                                            class="home-hero__variant {{ $slideSelected ? 'is-selected' : '' }}"
                                            data-home-hero-slide
                                            data-image="{{ $slideImage }}"
                                            data-theme="{{ $slideColor }}"
                                            data-title="{{ $slideProduct->name }}"
                                            data-description="{{ $slideProduct->short_description ?: $slideProduct->description ?: 'انتخابی دقیق از کالکشن VISION.' }}"
                                            data-url="{{ $slideUrl }}"
                                            data-price="{{ $formatPrice($slideVariant->effective_price) }}"
                                            data-original-price="{{ $slideVariant->is_on_sale ? $formatPrice($slideVariant->price) : '' }}"
                                            data-stock="{{ (int) $slideVariant->stock > 0 ? number_format((int) $slideVariant->stock) . ' عدد موجود' : 'فعلاً ناموجود' }}"
                                            data-label="{{ $slideProduct->name }}"
                                            data-image-alt="{{ $slideAlt }}"
                                            aria-label="نمایش {{ $slideLabel }}"
                                            aria-pressed="{{ $slideSelected ? 'true' : 'false' }}"
                                            title="{{ $slideLabel }}">
                                        <span class="home-hero__variant-dot" style="--home-hero-swatch: {{ $slideColor }}" aria-hidden="true"></span>
                                        <span class="home-hero__variant-initial">{{ $slideVariant->color ?: $slideVariant->size ?: $loop->iteration }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            @else
                <aside class="home-hero__price-panel">
                    <span class="home-hero__price-label">VISION / ONLINE STORE</span>
                    <strong class="home-hero__featured-name">جدیدترین انتخاب‌ها</strong>
                    <a class="home-hero__price-link" href="{{ route('shop.index') }}">ورود به فروشگاه <span aria-hidden="true">↗</span></a>
                </aside>
            @endif

            <div class="home-hero__bottom" aria-label="ناوبری تکمیلی">
                <a href="{{ route('shop.index') }}">SHOP ALL</a>
                <span aria-hidden="true">—</span>
                <span>DESIGNED FOR EVERYDAY</span>
            </div>
        </div>
        <span class="home-hero__index" aria-hidden="true">V / 001</span>
    </section>
</main>
@endsection
