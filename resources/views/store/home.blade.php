@extends('layouts.store')

@section('title', 'VISION — پوشاک برای نگاه متفاوت')
@section('meta-description', 'محصولات، برندها و کالکشن‌های فروشگاه پوشاک VISION را ببینید.')
@section('canonical', route('storefront.index'))
@section('og-image', $heroSlides->first()['image'] ?? '')

@section('content')
@php
    $heroGroups = $heroSlides->groupBy('productId');
    $featured = $heroSlides->first();
@endphp

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
                    <a href="{{ route('contact.index') }}">تماس با ما</a>
                </nav>
                <div class="home-hero__tools">
                    <a href="{{ route('account.show') }}" aria-label="حساب کاربری">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.3"></circle><path d="M5.3 20c.5-3.4 2.8-5.3 6.7-5.3s6.2 1.9 6.7 5.3"></path></svg>
                    </a>
                    @include('store.home-cart-link', ['floating' => false])
                </div>
            </header>

            <div class="home-hero__content">
                <div class="home-hero__copy">
                    @if($heroGroups->count() > 1)
                        @include('store.home-carousel-controls', ['controls' => 'home-hero-stage', 'attribute' => 'data-home-hero-step'])
                    @endif
                    <h1 id="home-hero-title" class="home-hero__title">استایل تو؛<br>امضای تو.</h1>
                    <p data-home-hero-description>{{ $featured['description'] ?? 'پوشاک روزمره و انتخاب‌های تازهٔ VISION را در فروشگاه ببینید.' }}</p>
                    <a class="home-hero__cta" href="{{ route('shop.index') }}">مشاهدهٔ فروشگاه</a>
                    
                </div>

                @if($featured)
                    <div class="home-hero__image-stage" id="home-hero-stage">
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
                        @include('store.home.hero-products')
                        @if($heroSlides->count() > 1)
                            <div class="home-hero__variants" role="group" aria-label="رنگ‌های محصول">
                                @foreach($heroSlides as $slide)
                                    <button type="button" class="home-hero__variant"
                                            data-home-hero-slide
                                            data-product-id="{{ $slide['productId'] }}"
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
                                            aria-label="{{ $slide['label'] }}"
                                            title="{{ $slide['label'] }}"
                                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                            @if($slide['productId'] !== $featured['productId']) hidden @endif>
                                        @if($slide['image'])
                                            <img src="{{ $slide['image'] }}" alt="" width="40" height="40" loading="lazy" decoding="async">
                                        @else
                                            <span class="home-hero__swatch" style="--home-hero-swatch: {{ $slide['theme'] }}" aria-hidden="true"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>


        </div>
    </section>

    <div class="home-page-content">
    @include('store.home.brands')

    @include('store.home.identity')
    @include('store.home.categories')

    @include('store.home.product-section', [
        'collection' => $newProducts, 'sectionId' => 'new-arrivals',
        'eyebrow' => 'JUST IN', 'heading' => 'تازه‌های فروشگاه',
        'description' => 'انتخاب‌های تازه برای کمد لباس شما.',
        'destination' => route('shop.index', ['sort' => 'newest']),
    ])

    @include('store.home.editorial')

    @include('store.home.product-section', [
        'collection' => $saleProducts, 'sectionId' => 'special-offers',
        'eyebrow' => 'SPECIAL EDIT', 'heading' => 'فرصت‌های خرید',
        'description' => 'محصولات دارای تخفیف و موجود در فروشگاه.',
        'destination' => route('shop.index', ['sale' => 1]),
    ])

    @include('store.home.trust')

    @include('store.home.shop-finale')
    </div>
    @include('store.home-cart-link', ['floating' => true])
</main>
@endsection
