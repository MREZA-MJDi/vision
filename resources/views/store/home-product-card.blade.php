@php
    $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
    $image = $variant?->primaryGalleryMedia?->url ?: $product->primaryGalleryMedia?->url;
@endphp
<a class="home-product-card" href="{{ route('products.show', ['product' => $product->slug, 'variant_id' => $variant?->getKey()]) }}">
    <div class="home-product-card__image">
        @if($image)
            <img src="{{ $image }}" alt="" loading="lazy" decoding="async">
        @else
            <span>بدون تصویر</span>
        @endif
    </div>
    <div class="home-product-card__body">
        <strong>{{ $product->name }}</strong>
        @if($variant)
            <span>{{ \App\Support\Money::format($variant->effective_price) }}</span>
        @endif
    </div>
</a>
