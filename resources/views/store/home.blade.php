@extends('layouts.store')

@section('title', 'VISION — پوشاک برای نگاه متفاوت')
@section('meta-description', 'یک انتخاب دقیق. یک استایل متمایز. محصولات واقعی VISION را کشف کن.')
@section('canonical', route('storefront.index'))
@section('og-image', $heroProduct?->primaryAvailableVariant?->primaryGalleryMedia?->url ?: $heroProduct?->primaryGalleryMedia?->url)

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
    $heroVariant = $heroProduct?->primaryAvailableVariant ?? $heroProduct?->primaryActiveVariant;
    $heroImage = $heroVariant?->primaryGalleryMedia?->url ?? $heroProduct?->primaryGalleryMedia?->url;
    $heroColor = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', (string) $heroVariant?->color_code)
        ? $heroVariant->color_code
        : '#9a5527';
    $heroProductUrl = $heroProduct
        ? route('products.show', ['product' => $heroProduct->slug, 'variant_id' => $heroVariant?->getKey()])
        : route('shop.index');
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
                <h1 id="home-hero-title" class="home-hero__title">
                    استایل تو؛<br>
                    امضای تو.
                </h1>
                <p class="home-hero__description">
                    برای دیده‌شدن لازم نیست بلند باشی. انتخاب‌هایی دقیق، طراحی‌شده برای زندگی واقعی.
                </p>
                <div class="home-hero__actions">
                    <a class="home-hero__cta" href="{{ route('shop.index') }}">
                        <span>کشف فروشگاه</span><span aria-hidden="true">↗</span>
                    </a>
                    @if($heroProduct)
                        <a class="home-hero__cta home-hero__cta--secondary" href="{{ $heroProductUrl }}">
                            <span>محصول منتخب</span><span aria-hidden="true">↗</span>
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
                @if($heroProduct && $heroImage)
                    <img data-home-hero-image
                         src="{{ $heroImage }}"
                         alt="{{ $heroProduct->name }}"
                         fetchpriority="high"
                         decoding="async">
                @else
                    <div class="home-hero__image-missing home-hero__image-missing--empty">
                        <span>
                            @if($heroProduct)
                                برای این محصول هنوز تصویر واقعی ثبت نشده است.
                            @else
                                محصول فعالی برای نمایش در Hero ثبت نشده است.
                            @endif
                        </span>
                    </div>
                @endif
                <span class="home-hero__image-aura" aria-hidden="true"></span>
            </div>

            @if($heroProduct)
                <aside class="home-hero__price-panel" aria-label="اطلاعات محصول منتخب">
                    <span class="home-hero__price-label">محصول منتخب / 01</span>
                    <strong class="home-hero__featured-name">{{ $heroProduct->name }}</strong>
                    @if($heroVariant)
                        <strong class="home-hero__price">{{ $formatPrice($heroVariant->effective_price) }}</strong>
                        @if($heroVariant->is_on_sale)
                            <del class="home-hero__original-price">{{ $formatPrice($heroVariant->price) }}</del>
                        @endif
                        <span class="home-hero__stock">
                            @if((int) $heroVariant->stock > 0)
                                {{ number_format((int) $heroVariant->stock) }} عدد موجود
                            @else
                                فعلاً ناموجود
                            @endif
                        </span>
                    @endif
                    <a class="home-hero__price-link" href="{{ $heroProductUrl }}">جزئیات محصول <span aria-hidden="true">↗</span></a>
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
