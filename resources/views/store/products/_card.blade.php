@php
    $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
    $image = $product->primaryGalleryMedia?->url ?? $variant?->primaryGalleryMedia?->url;
    $price = $variant?->effective_price;
    $isSale = $variant?->is_on_sale;
@endphp
<article class="product-card">
    <a class="product-card__image" href="{{ route('products.show', ['product' => $product->slug]) }}" aria-label="مشاهدهٔ {{ $product->name }}">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @else
            <span class="product-card__no-image"><span>V.</span><small>تصویر به‌زودی</small></span>
        @endif
        <span class="product-card__tag">{{ $product->is_featured ? 'انتخاب VISION' : 'مجموعهٔ VISION' }}</span>
        @if($isSale)<span class="product-card__sale">قیمت ویژه</span>@endif
        <span class="product-card__quick">مشاهدهٔ محصول ↗</span>
    </a>
    <div class="product-card__meta">
        <div><span>{{ $product->brand?->name ?? $product->category?->name ?? 'VISION STUDIO' }}</span><h3><a href="{{ route('products.show', ['product' => $product->slug]) }}">{{ $product->name }}</a></h3></div>
        <div class="product-price">
            @if($price !== null)
                @if($isSale)<del>@money($variant->price)</del>@endif
                <strong>@money($price)</strong>
            @else
                <strong>مشاهدهٔ محصولات</strong>
            @endif
        </div>
    </div>
    @if($variant && $variant->stock < 1)
        <p class="product-card__stock">فعلاً ناموجود</p>
    @endif
</article>
