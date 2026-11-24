@php
    $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
    $image = $variant?->primaryGalleryMedia?->url ?: $product->primaryGalleryMedia?->url;
@endphp
<article class="home-product-card">
    <a class="home-product-card__image store-media" data-home-cart-source href="{{ route('products.show', ['product' => $product->slug, 'variant_id' => $variant?->getKey()]) }}">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @else
            <span>{{ $product->name }}</span>
        @endif
    </a>
    <div class="home-product-card__body">
        <strong>{{ $product->name }}</strong>
        @if($variant)
            <span>{{ \App\Support\Money::format($variant->effective_price) }}</span>
            @if($variant->stock > 0)
                <button type="button" data-cart-add="{{ $variant->getKey() }}" aria-label="افزودن {{ $product->name }} به سبد">افزودن به سبد</button>
            @else
                <span>ناموجود</span>
            @endif
        @endif
    </div>
</article>
