@extends('layouts.store')

@section('title', 'VISION — پوشاک برای نگاه متفاوت')
@section('meta-description', 'محصولات فروشگاه VISION را ببینید و استایل خود را در اتاق پرو استودیویی انتخاب کنید.')
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
                    <a href="#home-contact">تماس با ما</a>
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
                    @include('store.home-support')
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
                                        <span class="home-hero__swatch" style="--home-hero-swatch: {{ $slide['theme'] }}" aria-hidden="true"></span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            @if($heroGroups->count() > 1)
                <div class="home-hero__products" role="group" aria-label="محصولات منتخب اسلایدر">
                    @foreach($heroGroups as $productId => $variants)
                        @php($preview = $variants->first())
                        <button type="button" class="home-hero__thumbnail store-media store-media--thumb" data-home-hero-product="{{ $productId }}"
                                title="{{ $preview['title'] }}" aria-label="{{ $preview['title'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            @if($preview['image'])
                                <img src="{{ $preview['image'] }}" alt="" width="54" height="54" decoding="async">
                            @else
                                <span>{{ $loop->iteration }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="home-content-section home-trust" aria-labelledby="home-trust-title">
        <div class="home-content-heading"><h2 id="home-trust-title">با اطمینان انتخاب کن</h2></div>
        <div class="home-trust-grid">
            <article><span>۰۱</span><h3>انتخاب آگاهانه</h3><p>تصاویر، مشخصات و قیمت محصولات را قبل از خرید بررسی کن.</p></article>
            <article><span>۰۲</span><h3>پیگیری سفارش</h3><p>وضعیت خریدهای خود را از <a href="{{ route('account.show') }}">حساب کاربری</a> دنبال کن.</p></article>
            <article><span>۰۳</span><h3>ارتباط مستقیم</h3><p>پرسش‌های خود را از طریق <a href="#home-contact">فرم تماس</a> با فروشگاه در میان بگذار.</p></article>
        </div>
    </section>

    @if($newProducts->isNotEmpty())
        <section class="home-content-section" aria-labelledby="home-new-title">
            <div class="home-content-heading">
                <h2 id="home-new-title">تازه‌های فروشگاه</h2>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog">همهٔ تازه‌ها</a>
                @include('store.home-carousel-controls', ['controls' => 'home-new-rail', 'attribute' => 'data-home-rail-step'])
            </div>
            <div class="home-product-grid home-rail" id="home-new-rail" role="group" aria-labelledby="home-new-title" tabindex="0">
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
                @include('store.home-carousel-controls', ['controls' => 'home-category-rail', 'attribute' => 'data-home-rail-step'])
            </div>
            <div class="home-category-list home-rail" id="home-category-rail" role="group" aria-labelledby="home-categories-title" tabindex="0">
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

    @if($homeBrands->isNotEmpty())
        <section class="home-content-section" aria-labelledby="home-brands-title">
            <div class="home-content-heading"><h2 id="home-brands-title">برندهای فروشگاه</h2></div>
            <div class="home-brand-grid">
                @foreach($homeBrands as $brand)
                    <a class="home-brand-tile" href="{{ route('shop.index', ['brand' => $brand->slug]) }}#catalog">
                        @if($brand->logoMedia?->url)<img src="{{ $brand->logoMedia->url }}" alt="" loading="lazy" decoding="async">@endif
                        <strong>{{ $brand->name }}</strong>
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
                @include('store.home-carousel-controls', ['controls' => 'home-curated-rail', 'attribute' => 'data-home-rail-step'])
            </div>
            <div class="home-product-grid home-rail" id="home-curated-rail" role="group" aria-labelledby="home-collections-title" tabindex="0">
                @foreach($heroProducts as $product)
                    @include('store.home-product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
    <section id="home-contact" class="home-content-section home-contact" aria-labelledby="home-contact-title">
        <div class="home-contact-intro">
            <h2 id="home-contact-title">با ما در ارتباط باش</h2>
            <p>برای پیگیری سفارش، پرسش درباره محصولات یا پیشنهاد همکاری پیام بفرست.</p>
            <a href="{{ route('shop.index') }}#catalog">مشاهدهٔ فروشگاه</a>
        </div>
        <div class="home-contact-form">@include('store.contact-form', ['onHome' => true])</div>
    </section>
    @include('store.home-cart-link', ['floating' => true])
</main>
@endsection
