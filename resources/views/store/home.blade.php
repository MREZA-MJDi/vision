@extends('layouts.store')

@section('title', 'VISION — پوشاک برای نگاه متفاوت')
@section('meta-description', 'محصولات فروشگاه VISION را ببینید و استایل خود را در اتاق پرو استودیویی انتخاب کنید.')
@section('canonical', route('storefront.index'))
@section('og-image', $heroSlides->first()['image'] ?? '')

@section('content')
@php($featured = $heroSlides->first())

<main class="store-main store-home">
    <section class="home-hero" aria-labelledby="home-hero-title" data-home-hero
             style="--home-hero-base: {{ $featured['theme'] ?? '#9a5527' }}">
        <div class="home-hero__frame">
            <header class="home-hero__header">
                <a class="home-hero__brand" href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی VISION">VISION</a>
                <nav class="home-hero__nav" aria-label="ناوبری اصلی">
                    <a href="{{ route('storefront.index') }}" aria-current="page">خانه</a>
                    <a href="{{ route('shop.index') }}">فروشگاه</a>
                    <a href="{{ route('shop.index', ['sort' => 'newest']) }}">تازه‌ها</a>
                    <a href="{{ route('shop.index', ['sale' => 1]) }}">تخفیف‌ها</a>
                </nav>
                <div class="home-hero__tools">
                    <a href="{{ route('account.show') }}" aria-label="حساب کاربری">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.3"></circle><path d="M5.3 20c.5-3.4 2.8-5.3 6.7-5.3s6.2 1.9 6.7 5.3"></path></svg>
                    </a>
                    <a href="{{ route('cart.index') }}" aria-label="مشاهدهٔ سبد خرید">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path></svg>
                        <span data-cart-count>0</span>
                    </a>
                </div>
            </header>

            <div class="home-hero__content">
                <div class="home-hero__copy">
                    <h1 id="home-hero-title" class="home-hero__title">استایل تو؛<br>امضای تو.</h1>
                    <p data-home-hero-description>{{ $featured['description'] ?? 'پوشاک روزمره و انتخاب‌های تازهٔ VISION را در فروشگاه ببینید.' }}</p>
                    <a class="home-hero__cta" href="{{ route('shop.index') }}">مشاهدهٔ فروشگاه</a>
                    @include('store.home-support')
                </div>

                @if($featured)
                    <div class="home-hero__image-stage">
                        <img data-home-hero-image @if($featured['image']) src="{{ $featured['image'] }}" @else hidden @endif
                             alt="{{ $featured['alt'] }}" fetchpriority="high" decoding="async">
                        <span class="home-hero__image-missing" data-home-hero-image-missing @if($featured['image']) hidden @endif>
                            تصویر محصول در دسترس نیست
                        </span>
                    </div>

                    <div class="home-hero__price-panel">
                        <div class="home-hero__product" aria-live="polite" aria-atomic="true">
                            <span data-home-hero-caption>{{ $featured['label'] }}</span>
                            <h2 data-home-hero-title>{{ $featured['title'] }}</h2>
                            <strong data-home-hero-price>{{ $featured['price'] }}</strong>
                            <del data-home-hero-original @if(!$featured['originalPrice']) hidden @endif>{{ $featured['originalPrice'] }}</del>
                            <span data-home-hero-stock>{{ $featured['stock'] }}</span>
                        </div>
                        <a class="home-hero__cta home-hero__cta--secondary" href="{{ $featured['url'] }}" data-home-hero-product-link>جزئیات محصول</a>
                    </div>
                @endif
            </div>

            @if($heroSlides->count() > 1)
                <div class="home-hero__picker">
                    <p id="hero-picker-label">انتخاب محصول و رنگ</p>
                    <div class="home-hero__variants" role="group" aria-labelledby="hero-picker-label">
                        @foreach($heroSlides as $slide)
                            <button type="button" class="home-hero__variant"
                                    data-home-hero-slide
                                    data-image="{{ $slide['image'] }}"
                                    data-image-alt="{{ $slide['alt'] }}"
                                    data-theme="{{ $slide['theme'] }}"
                                    data-title="{{ $slide['title'] }}"
                                    data-description="{{ $slide['description'] }}"
                                    data-url="{{ $slide['url'] }}"
                                    data-price="{{ $slide['price'] }}"
                                    data-original-price="{{ $slide['originalPrice'] }}"
                                    data-stock="{{ $slide['stock'] }}"
                                    data-label="{{ $slide['label'] }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                <span class="home-hero__swatch" style="--home-hero-swatch: {{ $slide['theme'] }}" aria-hidden="true"></span>
                                <span>{{ $slide['title'] }} · {{ $slide['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if($newProducts->isNotEmpty())
        <section class="home-content-section" aria-labelledby="home-new-title">
            <div class="home-content-heading">
                <h2 id="home-new-title">تازه‌های فروشگاه</h2>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog">همهٔ تازه‌ها</a>
            </div>
            <div class="home-product-grid">
                @foreach($newProducts as $product)
                    @include('store.home-product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if($homeCategories->isNotEmpty())
        <section class="home-content-section" aria-labelledby="home-categories-title">
            <div class="home-content-heading">
                <h2 id="home-categories-title">خرید بر اساس دسته‌بندی</h2>
            </div>
            <div class="home-category-list">
                @foreach($homeCategories as $category)
                    <a class="home-category-tile" href="{{ route('shop.index', ['category' => $category->slug]) }}#catalog">
                        @if($category->coverMedia?->url)
                            <img src="{{ $category->coverMedia->url }}" loading="lazy" decoding="async" alt="">
                        @endif
                        <strong>{{ $category->name }}</strong>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="vision-shop-story" aria-labelledby="home-fitting-title">
        <div class="home-fitting__content">
            <h2 id="home-fitting-title">قبل از خرید، استایلت را ببین.</h2>
            <p>لباس‌های دارای نشان چوب‌لباسی را در فروشگاه انتخاب کنید و ترکیب آن‌ها را روی مانکن استودیویی ببینید.</p>
            <a class="home-hero__cta" href="{{ route('shop.index') }}#catalog">رفتن به اتاق پرو</a>
        </div>
        <div class="vision-home-benefits" aria-label="مراحل استفاده از اتاق پرو">
            <ol>
                <li>لباس را از کاتالوگ انتخاب کنید.</li>
                <li>ترکیب لباس‌ها را در اتاق پرو ببینید.</li>
                <li>محصولات انتخابی را به سبد اضافه کنید.</li>
            </ol>
        </div>
    </section>

    @if($heroProducts->isNotEmpty())
        <section class="home-content-section vision-shop-collections" aria-labelledby="home-collections-title">
            <div class="home-content-heading">
                <h2 id="home-collections-title">منتخب VISION</h2>
                <a href="{{ route('shop.index') }}#catalog">همهٔ محصولات</a>
            </div>
            <div class="home-product-grid">
                @foreach($heroProducts->take(4) as $product)
                    @include('store.home-product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection
