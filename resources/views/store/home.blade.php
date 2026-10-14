@extends('layouts.store')

@section('title', 'VISION — فروشگاه پوشاک')
@section('meta-description', 'مجموعه‌ای منتخب از پوشاک و اکسسوری‌های VISION؛ محصولات واقعی، قیمت‌های به‌روز و خرید امن.')
@section('canonical', route('storefront.index'))

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
    $heroVariant = $heroProduct?->primaryAvailableVariant ?? $heroProduct?->primaryActiveVariant;
    $heroImage = $heroProduct?->primaryGalleryMedia?->url ?? $heroVariant?->primaryGalleryMedia?->url;
@endphp

<main class="store-main store-home">
    <section class="home-hero" aria-labelledby="home-hero-title">
        <div class="home-hero__copy">
            <p class="store-eyebrow"><span class="home-hero__live-dot" aria-hidden="true"></span> فروشگاه رسمی VISION</p>
            <h1 id="home-hero-title">استایل خودت را<br><span>انتخاب کن.</span></h1>
            <p class="home-hero__description">پوشاک و اکسسوری‌های منتخب؛ با اطلاعات محصول، قیمت و موجودی متصل به فروشگاه واقعی.</p>
            <div class="home-hero__actions">
                <a class="home-hero__primary" href="{{ route('shop.index') }}">مشاهدهٔ فروشگاه <span aria-hidden="true">←</span></a>
                @if($heroProduct)
                    <a class="home-hero__secondary" href="{{ route('products.show', ['product' => $heroProduct->slug]) }}">محصول منتخب</a>
                @endif
            </div>
            <div class="home-hero__meta">
                <span><strong>{{ number_format($categories->count()) }}</strong> دسته‌بندی فعال</span>
                <span><strong>{{ number_format($featuredProducts->count() + ($heroProduct ? 1 : 0)) }}</strong> محصول قابل مشاهده</span>
            </div>
        </div>

        <div class="home-hero__visual">
            @if($heroProduct && $heroImage)
                <a class="home-hero__image-link" href="{{ route('products.show', ['product' => $heroProduct->slug]) }}" aria-label="مشاهدهٔ {{ $heroProduct->name }}">
                    <img src="{{ $heroImage }}" alt="{{ $heroProduct->name }}" fetchpriority="high" decoding="async">
                </a>
                <div class="home-hero__product-label">
                    <span><small>محصول منتخب</small><strong>{{ $heroProduct->name }}</strong></span>
                    @if($heroVariant)
                        <b>{{ $formatPrice($heroVariant->effective_price) }}</b>
                    @endif
                </div>
            @elseif($heroProduct)
                <div class="home-hero__placeholder" aria-hidden="true"><span>V.</span><small>محصول منتخب</small></div>
                <div class="home-hero__product-label">
                    <span><small>محصول منتخب</small><strong>{{ $heroProduct->name }}</strong></span>
                    @if($heroVariant)
                        <b>{{ $formatPrice($heroVariant->effective_price) }}</b>
                    @endif
                </div>
            @else
                <div class="home-hero__placeholder" aria-hidden="true"><span>V.</span><small>VISION / SELECTED</small></div>
                <div class="home-hero__product-label"><span><small>کالکشن VISION</small><strong>انتخاب‌های تازه در راه‌اند</strong></span></div>
            @endif
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section class="home-section home-categories" aria-labelledby="home-categories-title">
            <div class="home-section__heading">
                <div><p class="store-eyebrow">دسته‌بندی‌ها</p><h2 id="home-categories-title">از کجا شروع کنیم؟</h2></div>
                <a class="home-section__link" href="{{ route('shop.index') }}">همهٔ محصولات <span aria-hidden="true">←</span></a>
            </div>
            <div class="home-category-grid">
                @foreach($categories as $category)
                    <a class="home-category-card" href="{{ route('shop.index', ['category_id' => $category->id]) }}">
                        <span class="home-category-card__index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="home-category-card__name">{{ $category->name }}</span>
                        <span class="home-category-card__meta">{{ number_format($category->active_products_count) }} محصول</span>
                        <span class="home-category-card__arrow" aria-hidden="true">↙</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="home-section home-featured" aria-labelledby="home-featured-title">
        <div class="home-section__heading">
            <div><p class="store-eyebrow">انتخاب VISION</p><h2 id="home-featured-title">محصولات منتخب</h2></div>
            <a class="home-section__link" href="{{ route('shop.index', ['sort' => 'featured']) }}">رفتن به فروشگاه <span aria-hidden="true">←</span></a>
        </div>

        @if($featuredProducts->isNotEmpty())
            <div class="home-product-grid">
                @foreach($featuredProducts as $product)
                    @php
                        $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
                        $image = $product->primaryGalleryMedia?->url ?? $variant?->primaryGalleryMedia?->url;
                        $isOnSale = $variant?->is_on_sale ?? false;
                    @endphp
                    <article class="home-product-card">
                        <a class="home-product-card__visual" href="{{ route('products.show', ['product' => $product->slug]) }}">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                            @else
                                <span class="home-product-card__placeholder" aria-hidden="true">V.</span>
                            @endif
                            @if($isOnSale)<span class="home-product-card__badge">تخفیف</span>@elseif($product->is_featured)<span class="home-product-card__badge">منتخب</span>@endif
                        </a>
                        <div class="home-product-card__details">
                            <span class="home-product-card__category">{{ $product->brand?->name ?? $product->category?->name ?? 'VISION' }}</span>
                            <h3><a href="{{ route('products.show', ['product' => $product->slug]) }}">{{ $product->name }}</a></h3>
                            <div class="home-product-card__price">
                                @if($variant && $isOnSale)
                                    <strong>{{ $formatPrice($variant->effective_price) }}</strong><del>{{ $formatPrice($variant->price) }}</del>
                                @elseif($variant)
                                    <strong>{{ $formatPrice($variant->effective_price) }}</strong>
                                @else
                                    <strong>استعلام قیمت</strong>
                                @endif
                                @if($variant)
                                    <button type="button" class="home-product-card__cart" data-cart-add="{{ $variant->id }}" aria-label="افزودن {{ $product->name }} به سبد خرید" title="افزودن به سبد خرید" @disabled((int) $variant->stock < 1)>+</button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="home-empty-state">
                <span aria-hidden="true">V.</span>
                <p>هنوز محصول فعالی برای نمایش منتشر نشده است.</p>
                <a href="{{ route('shop.index') }}">مشاهدهٔ فروشگاه</a>
            </div>
        @endif
    </section>

    <section class="home-shop-cta" aria-labelledby="home-shop-cta-title">
        <div><p class="store-eyebrow">VISION / COLLECTION</p><h2 id="home-shop-cta-title">انتخاب بعدی‌ات را پیدا کن.</h2><p>محصولات را بر اساس دسته‌بندی، برند، قیمت و موجودی بررسی کن.</p></div>
        <a href="{{ route('shop.index') }}">شروع خرید <span aria-hidden="true">←</span></a>
    </section>
</main>
@endsection
