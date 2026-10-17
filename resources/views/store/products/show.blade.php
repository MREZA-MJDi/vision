@extends('layouts.store')

@section('title', $product->meta_title ?: $product->name . ' — VISION')
@section('meta-description', \App\Support\SeoDefaults::description($product->name, $product->meta_description, [$product->short_description, $product->description]))

@section('canonical', route('products.show', ['product' => $product->slug]))
@section('og-type', 'product')
@section('og-image', $product->activeVariants->first(fn ($variant) => (int) $variant->stock > 0)?->primaryGalleryMedia?->url ?: $product->activeVariants->first()?->primaryGalleryMedia?->url ?: $product->primaryGalleryMedia?->url ?: $product->galleryMedia->first()?->url)

@section('content')
@php
    $defaultVariant = $product->activeVariants->first(fn ($variant) => (int) $variant->stock > 0) ?? $product->activeVariants->first();
    $baseImage = $product->primaryGalleryMedia?->url ?? $product->galleryMedia->first()?->url;
    $primaryImage = $defaultVariant?->primaryGalleryMedia?->url ?? $baseImage;
    $colorVariants = $product->activeVariants
        ->filter(fn ($variant) => trim((string) $variant->color) !== '')
        ->groupBy(fn ($variant) => mb_strtolower(trim((string) $variant->color)))
        ->map(fn ($variants) => $variants->first(fn ($variant) => (int) $variant->stock > 0) ?? $variants->first())
        ->values();
    $selectedColor = mb_strtolower(trim((string) $defaultVariant?->color));
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
@endphp

<main class="store-main commerce-page">
    <nav class="commerce-breadcrumbs" aria-label="مسیر صفحه">
        <a href="{{ route('storefront.index') }}">خانه</a><span>/</span>
        <a href="{{ route('shop.index') }}">فروشگاه</a><span>/</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="store-product-detail">
        <div class="store-product-detail__gallery">
            @forelse($product->galleryMedia as $media)
                <div class="store-product-detail__image" @if($loop->first) data-product-main-stage data-base-image="{{ $baseImage ?? '' }}" @endif>
                    <img src="{{ $loop->first ? ($defaultVariant?->primaryGalleryMedia?->url ?? $media->url) : $media->url }}" alt="{{ $media->alt_text ?: $product->name }}" @if($loop->first) data-product-main-image data-fallback-src="{{ $baseImage ?? '' }}" @endif loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    @if($loop->first)<div class="store-product-detail__image--empty" data-product-image-placeholder hidden><span>تصویر<br>محصول</span></div>@endif
                </div>
            @empty
                @if($primaryImage)
                    <div class="store-product-detail__image" data-product-main-stage data-base-image="{{ $baseImage ?? '' }}">
                        <img data-product-main-image data-fallback-src="{{ $baseImage ?? '' }}" src="{{ $primaryImage }}" alt="{{ $product->name }}" loading="eager">
                        <div class="store-product-detail__image--empty" data-product-image-placeholder hidden><span>VISION<br>STUDIO</span></div>
                    </div>
                @else
                    <div class="store-product-detail__image" data-product-main-stage data-base-image="">
                        <img data-product-main-image data-fallback-src="" src="" alt="{{ $product->name }}" loading="eager" hidden>
                        <div class="store-product-detail__image--empty" data-product-image-placeholder><span>VISION<br>STUDIO</span></div>
                    </div>
                @endif
            @endforelse

        </div>

        <div class="store-product-detail__info">
            <p class="store-eyebrow">{{ $product->brand?->name ?? 'VISION STUDIO' }} / محصولات منتخب</p>
            <h1>{{ $product->name }}</h1>
            @if($product->short_description)
                <p class="store-product-detail__lead">{{ $product->short_description }}</p>
            @endif

            <div class="store-product-detail__price" data-variant-price>
                @if($defaultVariant)
                    @if($defaultVariant->is_on_sale)<del>{{ $formatPrice($defaultVariant->price) }}</del>@endif
                    <strong>{{ $formatPrice($defaultVariant->effective_price) }}</strong>
                @endif
            </div>

            @if($product->category)
                <p class="store-product-detail__category">دسته‌بندی <span>{{ $product->category->name }}</span></p>
            @endif

            <form class="store-product-detail__buy" action="{{ route('cart.items.store') }}" method="POST">
                @csrf
                @if($product->activeVariants->isNotEmpty())
                    <label class="commerce-field">انتخاب سایز و رنگ
                        <select name="variant_id" data-variant-select required>
                            @foreach($product->activeVariants as $variant)
                                <option
                                    value="{{ $variant->getKey() }}"
                                    data-price="{{ $variant->effective_price }}"
                                    data-original-price="{{ $variant->price }}"
                                    data-image="{{ $variant->primaryGalleryMedia?->url ?? '' }}"
                                    data-image-alt="{{ trim(implode(' ', array_filter([$product->name, $variant->color, $variant->size]))) }}"
                                    data-sale="{{ $variant->is_on_sale ? '1' : '0' }}"
                                    data-stock="{{ $variant->stock }}"
                                    data-color="{{ trim((string) $variant->color) }}"
                                    data-size="{{ trim((string) $variant->size) }}"
                                    @selected($variant->getKey() === $defaultVariant?->getKey())
                                    @disabled((int) $variant->stock < 1)
                                >
                                    {{ implode(' / ', array_filter([$variant->size, $variant->color])) ?: ($variant->sku ?: 'معمولی') }}
                                    · {{ $formatPrice($variant->effective_price) }}
                                    {{ (int) $variant->stock < 1 ? ' — ناموجود' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if($colorVariants->isNotEmpty())
                        <div class="store-variant-colors" data-variant-colors>
                            <span class="store-variant-colors__label">رنگ</span>
                            <div class="store-variant-colors__options" role="group" aria-label="انتخاب رنگ">
                                @foreach($colorVariants as $colorVariant)
                                    @php $isSelectedColor = mb_strtolower(trim((string) $colorVariant->color)) === $selectedColor; @endphp
                                    <button
                                        type="button"
                                        class="store-variant-color {{ $isSelectedColor ? 'is-selected' : '' }}"
                                        data-variant-pick="{{ $colorVariant->getKey() }}"
                                        data-color="{{ $colorVariant->color }}"
                                        data-variant-color-pick="{{ $colorVariant->color }}"
                                        aria-pressed="{{ $isSelectedColor ? 'true' : 'false' }}"
                                        title="{{ $colorVariant->color }}"
                                        @disabled((int) $colorVariant->stock < 1)
                                    >
                                        <span class="store-variant-color__swatch" style="--variant-color: {{ $colorVariant->color_code ?: '#888888' }}" aria-hidden="true"></span>
                                        <span>{{ $colorVariant->color }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <label class="commerce-field">تعداد
                        <input type="number" name="quantity" value="1" min="1" max="{{ min(100, max(1, (int) $defaultVariant->stock)) }}" data-variant-quantity required>
                    </label>
                    <p class="commerce-stock-note" data-variant-stock>
                        {{ (int) $defaultVariant->stock > 0 ? ((int) $defaultVariant->stock . ' عدد موجود') : 'فعلاً موجود نیست' }}
                    </p>
                    <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit" data-product-add @disabled((int) $defaultVariant->stock < 1)>افزودن به سبد خرید ↗</button>
                    <p class="commerce-muted">قیمت، گزینهٔ انتخاب‌شده و موجودی هنگام افزودن به سبد و ثبت سفارش، در سرور دوباره بررسی می‌شوند.</p>
                @else
                    <p class="commerce-alert commerce-alert--error">این محصول در حال حاضر برای خرید در دسترس نیست.</p>
                @endif
            </form>

            <div class="store-product-detail__accordions">
                <details open><summary>توضیحات محصول</summary><div>{{ $product->description ?: $product->short_description ?: 'محصولی از مجموعهٔ منتخب VISION.' }}</div></details>
                <details><summary>ارسال و بازگشت کالا</summary><div>هزینهٔ ارسال بر اساس نرخ ثبت‌شده در تنظیمات فروشگاه محاسبه می‌شود. برای هماهنگی بازگشت کالا با فروشگاه تماس بگیرید.</div></details>
                <details>
                    <summary>مشخصات محصول</summary>
                    <div>قیمت و موجودی نهایی هنگام ثبت سفارش دوباره در سرور بررسی می‌شوند.</div>
                    @if(is_array($product->attributes) && count($product->attributes))
                        <dl class="store-product-attributes">
                            @foreach($product->attributes as $attributeName => $attributeValue)
                                @if(is_scalar($attributeValue) && trim((string) $attributeValue) !== '')
                                    <div><dt>{{ $attributeName }}</dt><dd>{{ $attributeValue }}</dd></div>
                                @endif
                            @endforeach
                        </dl>
                    @endif
                </details>
            </div>
            <a class="commerce-text-link" href="{{ route('shop.index') }}">بازگشت به فروشگاه ←</a>
        </div>
    </section>

    @if($relatedProducts->isNotEmpty())
        <section class="commerce-related">
            <div class="commerce-page-heading"><div><p class="store-eyebrow">پیشنهادهای دیگر</p><h2>شاید این‌ها را هم بپسندی<span>.</span></h2></div><a class="commerce-text-link" href="{{ route('shop.index') }}">مشاهدهٔ همه ↗</a></div>
            <div class="commerce-related-grid">
                @foreach($relatedProducts as $related)
                    @php
                        $relatedVariant = $related->primaryAvailableVariant ?? $related->primaryActiveVariant;
                        $relatedImage = $relatedVariant?->primaryGalleryMedia?->url ?? $related->primaryGalleryMedia?->url;
                    @endphp
                    <a class="commerce-related-card" href="{{ route('products.show', ['product' => $related->slug]) }}">
                        @if($relatedImage)<img src="{{ $relatedImage }}" alt="{{ $related->name }}" loading="lazy">@else<div class="commerce-related-card__empty">V.</div>@endif
                        <span>{{ $related->brand?->name ?? $related->category?->name ?? 'VISION STUDIO' }}</span>
                        <strong>{{ $related->name }}</strong>
                        @if($relatedVariant)<b>{{ $formatPrice($relatedVariant->effective_price) }}</b>@endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif


</main>
@endsection
