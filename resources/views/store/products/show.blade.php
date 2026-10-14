@extends('layouts.store')

@section('title', $product->meta_title ?: $product->name . ' — VISION')
@section('description', \App\Support\SeoDefaults::description($product->name, $product->meta_description, [$product->short_description, $product->description]))

@section('canonical', route('products.show', ['product' => $product->slug]))

@section('content')
@php
    $defaultVariant = $product->activeVariants->first(fn ($variant) => (int) $variant->stock > 0) ?? $product->activeVariants->first();
    $primaryImage = $defaultVariant?->primaryGalleryMedia?->url
        ?? $product->primaryGalleryMedia?->url;
    $variantColors = $product->activeVariants
        ->filter(fn ($variant) => trim((string) $variant->color) !== '')
        ->groupBy(fn ($variant) => mb_strtolower(trim((string) $variant->color)))
        ->map(fn ($variants) => $variants->first(fn ($variant) => (int) $variant->stock > 0) ?? $variants->first())
        ->values();
    $selectedColor = mb_strtolower(trim((string) $defaultVariant?->color));
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
@endphp

<main class="store-main commerce-page">
    <nav class="commerce-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('storefront.index') }}">HOME</a><span>/</span>
        <a href="{{ route('products.index') }}">SHOP</a><span>/</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="store-product-detail">
        <div class="store-product-detail__gallery">
            @forelse($product->galleryMedia as $media)
                <div class="store-product-detail__image">
                    <img src="{{ $loop->first ? ($defaultVariant?->primaryGalleryMedia?->url ?? $media->url) : $media->url }}" alt="{{ $media->alt_text ?: $product->name }}" @if($loop->first) data-product-main-image data-fallback-src="{{ $product->primaryGalleryMedia?->url ?? $media->url }}" @endif loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                </div>
            @empty
                @if($primaryImage)
                    <div class="store-product-detail__image">
                        <img data-product-main-image data-fallback-src="{{ $product->primaryGalleryMedia?->url ?? '' }}" src="{{ $primaryImage }}" alt="{{ $product->name }}" loading="eager">
                    </div>
                @else
                    <div class="store-product-detail__image">
                        <img data-product-main-image data-fallback-src="" src="" alt="{{ $product->name }}" loading="eager" hidden>
                        <div class="store-product-detail__image--empty" data-product-image-placeholder><span>VISION<br>STUDIO</span></div>
                    </div>
                @endif
            @endforelse
        </div>

        <div class="store-product-detail__info">
            <p class="store-eyebrow">{{ $product->brand?->name ?? 'VISION STUDIO' }} / THE EDIT</p>
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
                <p class="store-product-detail__category">CATEGORY <span>{{ $product->category->name }}</span></p>
            @endif

            <form class="store-product-detail__buy" action="{{ route('cart.items.store') }}" method="POST">
                @csrf
                @if($product->activeVariants->isNotEmpty())
                    <label class="commerce-field">Select size / colour
                        <select name="variant_id" data-variant-select required>
                            @foreach($product->activeVariants as $variant)
                                <option
                                    value="{{ $variant->getKey() }}"
                                    data-price="{{ $variant->effective_price }}"
                                    data-original-price="{{ $variant->price }}"
                                    data-image="{{ $variant->primaryGalleryMedia?->url ?? $product->primaryGalleryMedia?->url ?? $defaultVariant?->primaryGalleryMedia?->url ?? '' }}"
                                    data-sale="{{ $variant->is_on_sale ? '1' : '0' }}"
                                    data-stock="{{ $variant->stock }}"
                                    data-color="{{ trim((string) $variant->color) }}"
                                    data-size="{{ trim((string) $variant->size) }}"
                                    @selected($variant->getKey() === $defaultVariant?->getKey())
                                    @disabled((int) $variant->stock < 1)
                                >
                                    {{ implode(' / ', array_filter([$variant->size, $variant->color])) ?: ($variant->sku ?: 'Standard') }}
                                    · {{ $formatPrice($variant->effective_price) }}
                                    {{ (int) $variant->stock < 1 ? ' — SOLD OUT' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if($variantColors->isNotEmpty())
                        <div class="store-variant-colors" data-variant-colors>
                            <span class="store-variant-colors__label">رنگ</span>
                            <div class="store-variant-colors__options" role="group" aria-label="انتخاب رنگ">
                                @foreach($variantColors as $colorVariant)
                                    @php $isSelectedColor = mb_strtolower(trim((string) $colorVariant->color)) === $selectedColor; @endphp
                                    <button
                                        type="button"
                                        class="store-variant-color {{ $isSelectedColor ? 'is-selected' : '' }}"
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
                    <label class="commerce-field">Quantity
                        <input type="number" name="quantity" value="1" min="1" max="{{ min(100, max(1, (int) $defaultVariant->stock)) }}" data-variant-quantity required>
                    </label>
                    <p class="commerce-stock-note" data-variant-stock>
                        {{ (int) $defaultVariant->stock > 0 ? ((int) $defaultVariant->stock . ' available') : 'Currently unavailable' }}
                    </p>
                    <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit" data-product-add @disabled((int) $defaultVariant->stock < 1)>Add to bag ↗</button>
                    <p class="commerce-muted">The server rechecks the selected option, price and stock when you add it to your bag and again at checkout.</p>
                @else
                    <p class="commerce-alert commerce-alert--error">This piece is not available for purchase right now.</p>
                @endif
            </form>

            <div class="store-product-detail__accordions">
                <details open><summary>توضیحات محصول</summary><div>{{ $product->description ?: $product->short_description ?: 'A considered piece from the VISION selection.' }}</div></details>
                <details><summary>Shipping & returns</summary><div>Shipping is calculated using the store's configured flat rate. Contact the store for return arrangements.</div></details>
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
            <a class="commerce-text-link" href="{{ route('products.index') }}">← Back to the edit</a>
        </div>
    </section>

    @if($relatedProducts->isNotEmpty())
        <section class="commerce-related">
            <div class="commerce-page-heading"><div><p class="store-eyebrow">YOU MAY ALSO LIKE</p><h2>More to love<span>.</span></h2></div><a class="commerce-text-link" href="{{ route('products.index') }}">Shop all ↗</a></div>
            <div class="commerce-related-grid">
                @foreach($relatedProducts as $related)
                    @php
                        $relatedVariant = $related->primaryActiveVariant;
                        $relatedImage = $related->primaryGalleryMedia?->url ?? $relatedVariant?->primaryGalleryMedia?->url;
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
