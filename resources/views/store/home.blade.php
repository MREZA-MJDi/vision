@extends('layouts.store')

@section('title', 'VISION — فروشگاه پوشاک')
@section('meta-description', 'مجموعه‌ای منتخب از پوشاک و اکسسوری‌های VISION؛ محصولات واقعی، قیمت‌های به‌روز و خرید امن.')
@section('canonical', route('storefront.index'))

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
    $heroVariant = $heroProduct?->primaryAvailableVariant ?? $heroProduct?->primaryActiveVariant;
    $heroImage = $heroVariant?->primaryGalleryMedia?->url ?? $heroProduct?->primaryGalleryMedia?->url;
    $brandStoryVariant = $brandStoryProduct?->primaryAvailableVariant ?? $brandStoryProduct?->primaryActiveVariant;
    $brandStoryImage = $brandStoryVariant?->primaryGalleryMedia?->url ?? $brandStoryProduct?->primaryGalleryMedia?->url;
@endphp

<main class="store-main store-home">
    {{-- The split-color editorial hero follows the supplied visual reference. --}}
    <section class="home-hero" aria-labelledby="home-hero-title">
        <div class="home-hero__frame">
            <div class="home-hero__frame-brand" aria-hidden="true">
                <strong>VISION</strong>
                <small>EVERYDAY OBJECTS</small>
            </div>

            @if($heroProduct && $heroImage)
                <div class="home-hero__image-stage">
                    <img src="{{ $heroImage }}" alt="" fetchpriority="high" decoding="async">
                </div>
            @else
                <div class="home-hero__image-missing">
                    <span>تصویر واقعی محصول منتخب ثبت نشده است.</span>
                </div>
            @endif

            <div class="home-hero__shade" aria-hidden="true"></div>

            <div class="home-hero__center">
                <p class="home-hero__eyebrow">فروشگاه رسمی VISION</p>
                <h1 id="home-hero-title">VISION</h1>
                <p class="home-hero__tagline">انتخاب دقیق‌تر. شلوغی کمتر.</p>
            </div>

            @if($heroProduct)
                <a class="home-hero__product-link"
                   href="{{ route('products.show', ['product' => $heroProduct->slug]) }}"
                   aria-label="مشاهدهٔ محصول منتخب {{ $heroProduct->name }}">
                    <small>محصول منتخب</small>
                    <strong>{{ $heroProduct->name }}</strong>
                    @if($heroVariant)
                        <span>{{ $formatPrice($heroVariant->effective_price) }}</span>
                    @endif
                </a>
            @endif

            <div class="home-hero__bottom">
                <a class="home-hero__side-link" href="#home-featured">محصولات منتخب</a>
                <a class="home-hero__circle" href="#home-featured" aria-label="رفتن به محصولات منتخب">
                    <span aria-hidden="true">↓</span>
                </a>
                <a class="home-hero__side-link" href="{{ route('shop.index') }}">ورود به فروشگاه</a>
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
    <section class="home-section home-featured" id="home-featured" aria-labelledby="home-featured-title">
        <div class="home-section__inner">
            <div class="home-section__heading">
                <div>
                    <p class="store-eyebrow">انتخاب VISION</p>
                    <h2 id="home-featured-title">انتخاب‌های VISION</h2>
                    <p class="home-section__intro">ترکیبی از پوشاک و اکسسوری‌های قابل سفارش.</p>
                </div>
                <a class="home-section__link" href="{{ route('shop.index', ['sort' => 'featured']) }}">مشاهدهٔ همه <span aria-hidden="true">←</span></a>
            </div>

            @if($featuredProducts->isNotEmpty())
                <div class="home-product-grid">
                    @foreach($featuredProducts as $product)
                        @php
                            $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
                            $image = $variant?->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url;
                            $isOnSale = $variant?->is_on_sale ?? false;
                            $isOutOfStock = !$variant || (int) $variant->stock < 1;
                        @endphp
                        <article class="home-product-card">
                            <a class="home-product-card__visual" href="{{ route('products.show', ['product' => $product->slug]) }}" aria-label="مشاهدهٔ {{ $product->name }}">
                                @if($image)
                                    <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                @else
                                    <span class="home-product-card__placeholder">تصویر واقعی این محصول ثبت نشده است.</span>
                                @endif

                                @if($isOnSale)
                                    <span class="home-product-card__badge">تخفیف</span>
                                @elseif($isOutOfStock)
                                    <span class="home-product-card__badge home-product-card__badge--muted">ناموجود</span>
                                @elseif($product->is_featured)
                                    <span class="home-product-card__badge">منتخب</span>
                                @endif
                            </a>

                            <div class="home-product-card__details">
                                <span class="home-product-card__category">{{ $product->brand?->name ?? $product->category?->name ?? 'VISION' }}</span>
                                <h3><a href="{{ route('products.show', ['product' => $product->slug]) }}">{{ $product->name }}</a></h3>
                                <div class="home-product-card__price">
                                    <div class="home-product-card__price-copy">
                                        @if($variant && $isOnSale)
                                            <strong>{{ $formatPrice($variant->effective_price) }}</strong>
                                            <del>{{ $formatPrice($variant->price) }}</del>
                                        @elseif($variant)
                                            <strong>{{ $formatPrice($variant->effective_price) }}</strong>
                                        @else
                                            <strong>استعلام قیمت</strong>
                                        @endif
                                    </div>
                                    @if($variant)
                                        <button type="button"
                                                class="home-product-card__cart"
                                                data-cart-add="{{ $variant->id }}"
                                                aria-label="افزودن {{ $product->name }} به سبد خرید"
                                                title="افزودن به سبد خرید"
                                                @disabled((int) $variant->stock < 1)>+</button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="home-empty-state">
                    <p>هنوز محصول فعالی برای نمایش منتشر نشده است.</p>
                    <a href="{{ route('shop.index') }}">مشاهدهٔ فروشگاه <span aria-hidden="true">←</span></a>
                </div>
            @endif
        </div>
    </section>

    {{-- Section 3: an editorial brand/campaign banner backed by an actual catalog image. --}}
    <section class="home-brand-story" id="home-brand-story" aria-labelledby="home-brand-story-title">
        <div class="home-brand-story__visual">
            @if($brandStoryProduct && $brandStoryImage)
                <a href="{{ route('products.show', ['product' => $brandStoryProduct->slug]) }}"
                   aria-label="مشاهدهٔ {{ $brandStoryProduct->name }}">
                    <img src="{{ $brandStoryImage }}" alt="{{ $brandStoryProduct->name }}" loading="lazy" decoding="async">
                </a>
            @else
                <div class="home-brand-story__image-missing">برای این بخش هنوز تصویر واقعی ثبت نشده است.</div>
            @endif
        </div>
        <div class="home-brand-story__copy">
            <p class="store-eyebrow">نگاه VISION</p>
            <h2 id="home-brand-story-title">کمتر، دقیق‌تر، ماندگارتر.</h2>
            <p>انتخاب‌های روزمره باید ساده باشند؛ محصول را با تصویر، مشخصات، قیمت و موجودی واقعی بررسی کن و بعد تصمیم بگیر.</p>
            <a href="{{ route('shop.index', ['sort' => 'featured']) }}">دیدن انتخاب‌های ما <span aria-hidden="true">←</span></a>
        </div>
    </section>

    {{-- Section 4: only benefits supported by the current storefront implementation. --}}
    <section class="home-benefits" id="home-benefits" aria-label="مزیت‌های خرید از VISION">
        <div class="home-benefit">
            <span class="home-benefit__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3.5 14.3 5l2.8-.1 1.1 2.6 2.2 1.7-.8 2.7.8 2.7-2.2 1.7-1.1 2.6-2.8-.1L12 20.5l-2.3-1.5-2.8.1-1.1-2.6-2.2-1.7.8-2.7-.8-2.7 2.2-1.7 1.1-2.6 2.8.1L12 3.5Z"/><path d="m8.8 12.1 2.1 2.1 4.5-4.6"/></svg>
            </span>
            <strong>قیمت شفاف</strong>
            <p>قیمت و تخفیف از واریانت واقعی محصول نمایش داده می‌شود.</p>
        </div>
        <div class="home-benefit">
            <span class="home-benefit__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 6.5h11v11H3zM14 10h4l3 3.2v4.3h-7z"/><circle cx="7.5" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/></svg>
            </span>
            <strong>موجودی به‌روز</strong>
            <p>موجودی هنگام افزودن به سبد و ثبت سفارش سمت سرور بررسی می‌شود.</p>
        </div>
        <div class="home-benefit">
            <span class="home-benefit__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5.5h16v14H4zM8 5.5V4a4 4 0 0 1 8 0v1.5"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <strong>سبد خرید متصل</strong>
            <p>انتخاب محصول و ادامهٔ خرید به مسیر واقعی فروشگاه متصل است.</p>
        </div>
    </section>
</main>
@endsection
