@extends('layouts.store')

@section('title', 'VISION — فروشگاه پوشاک')
@section('meta-description', 'مجموعه‌ای منتخب از پوشاک و اکسسوری‌های VISION؛ محصولات واقعی، قیمت‌های به‌روز و خرید امن.')
@section('canonical', route('storefront.index'))

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
