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
        <strong><a href="{{ route('products.show', ['product' => $product->slug, 'variant_id' => $variant?->getKey()]) }}">{{ $product->name }}</a></strong>
        @if($variant)
            <span>{{ \App\Support\Money::format($variant->effective_price) }}</span>
            @if($variant->stock > 0)
                <button type="button" data-cart-add="{{ $variant->getKey() }}" aria-label="افزودن {{ $product->name }} به سبد" title="افزودن به سبد"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2M12 12v4m-2-2h4"/></svg></button>
            @else
                <span>ناموجود</span>
            @endif
        @endif
    </div>
</article>
