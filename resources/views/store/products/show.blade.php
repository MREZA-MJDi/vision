@extends('layouts.store')

@section('title', $product->meta_title ?: $product->name . ' — VISION')
@section('meta-description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: 'Discover the latest VISION edit.'), 155))

@section('content')
@php
    $defaultVariant = $product->activeVariants->first(fn ($variant) => (int) $variant->stock > 0) ?? $product->activeVariants->first();
    $fallbackImage = $product->primaryGalleryMedia?->url ?? $product->galleryMedia->first()?->url;
    $primaryImage = $defaultVariant?->primaryGalleryMedia?->url ?? $fallbackImage;
    $colorVariants = $product->activeVariants
        ->filter(fn ($variant) => trim((string) $variant->color) !== '')
        ->unique('color')
        ->values();
    $formatPrice = static fn ($amount) => number_format((float) $amount, 0, '.', ',') . ' ' . config('commerce-admin.currency_symbol', 'تومان');
@endphp

<main class="store-main commerce-page">
    <nav class="commerce-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('storefront.index') }}">HOME</a><span>/</span>
        <a href="{{ route('products.index') }}">SHOP</a><span>/</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="store-product-detail">
        <div class="store-product-detail__gallery" data-product-gallery>
            <div class="store-product-detail__image" data-product-main-stage data-base-image="{{ $fallbackImage ?? '' }}">
                <img data-product-main-image
                     @if($primaryImage) src="{{ $primaryImage }}" @else hidden @endif
                     alt="{{ $product->name }}{{ $defaultVariant?->color ? ' — ' . $defaultVariant->color : '' }}"
                     loading="eager" decoding="async">
                <div class="store-product-detail__image--empty" data-product-image-placeholder @if($primaryImage) hidden @endif><span>VISION<br>STUDIO</span></div>
            </div>
            @if($product->galleryMedia->isNotEmpty())
                <div class="store-product-detail__thumbnails" data-product-gallery-thumbs aria-label="Product images">
                    @foreach($product->galleryMedia as $media)
                        @if($media->url)
                            <button type="button" class="store-product-detail__thumb" data-gallery-image="{{ $media->url }}" data-gallery-alt="{{ $media->alt_text ?: $product->name }}" aria-label="Show product image {{ $loop->iteration }}">
                                <img src="{{ $media->url }}" alt="" loading="lazy" decoding="async">
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
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
                                    data-sale="{{ $variant->is_on_sale ? '1' : '0' }}"
                                    data-stock="{{ $variant->stock }}"
                                    data-color="{{ $variant->color ?? '' }}"
                                    data-size="{{ $variant->size ?? '' }}"
                                    data-image="{{ $variant->primaryGalleryMedia?->url ?? '' }}"
                                    data-image-alt="{{ trim(implode(' ', array_filter([$product->name, $variant->color, $variant->size]))) }}"
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
                    @if($colorVariants->isNotEmpty())
                        <div class="store-variant-colors">
                            <span class="store-variant-colors__label">Colour</span>
                            <div class="store-variant-colors__options" role="group" aria-label="Choose a colour">
                                @foreach($colorVariants as $colorVariant)
                                    <button type="button"
                                            class="store-variant-color {{ $colorVariant->color === $defaultVariant?->color ? 'is-selected' : '' }}"
                                            data-variant-pick="{{ $colorVariant->getKey() }}"
                                            data-color="{{ $colorVariant->color }}"
                                            aria-pressed="{{ $colorVariant->color === $defaultVariant?->color ? 'true' : 'false' }}"
                                            title="{{ $colorVariant->color }}">
                                        <span class="store-variant-color__swatch" style="--variant-color: {{ $colorVariant->color_code ?: '#888888' }}"></span>
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
                <details open><summary>The story</summary><div>{{ $product->description ?: $product->short_description ?: 'A considered piece from the VISION selection.' }}</div></details>
                <details><summary>Shipping & returns</summary><div>Shipping is calculated using the store's configured flat rate. Contact the store for return arrangements.</div></details>
                <details><summary>Product details</summary><div>Prices and stock shown here are indicative until the order is submitted; final availability is checked server-side.</div></details>
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
