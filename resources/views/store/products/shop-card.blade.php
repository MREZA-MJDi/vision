<article class="shop-product-card" data-product="{{ $product['id'] }}">
    <div class="shop-product-side">
        <button type="button" class="fav {{ $isFavourite ? 'on' : '' }}" data-f="{{ $product['id'] }}"
                aria-label="علاقه‌مندی {{ $product['name'] }}" aria-pressed="{{ $isFavourite ? 'true' : 'false' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"/></svg>
        </button>
        @if($product['slot'] && $product['stock'] > 0)
            <button type="button" class="try" data-t="{{ $product['id'] }}" aria-controls="fit"
                    aria-label="امتحان {{ $product['name'] }} روی مانکن" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5V5.8a2.3 2.3 0 1 0-2.3-2.3"/><path d="m12 8.5-8.3 4.7a1.6 1.6 0 0 0 .8 3h15a1.6 1.6 0 0 0 .8-3L12 8.5Z"/></svg>
            </button>
        @endif
    </div>
    @if($product['rate'] !== null)
        <span class="shop-rate">★ {{ \App\Support\Money::persianDigits(str_replace('.', '٫', (string) $product['rate'])) }}</span>
    @endif
    <a class="shop-pic store-media" href="{{ $product['url'] }}" aria-label="مشاهدهٔ {{ $product['name'] }}">
        @if($product['img'])
            <img src="{{ $product['img'] }}" alt="" loading="lazy" decoding="async">
        @else
            <span class="shop-pic-empty">بدون تصویر</span>
        @endif
    </a>
    <div class="shop-product-details">
        <a class="shop-product-name" href="{{ $product['url'] }}">{{ $product['name'] }}</a>
        @if($product['color'] !== '')
            <span class="shop-product-colour">
                <i style="--shop-swatch: {{ $product['colorCode'] }}" aria-hidden="true"></i>{{ $product['color'] }}
            </span>
        @endif
    </div>
    <div class="shop-product-actions">
        <div class="shop-product-price">
            @if($product['old'] !== null)<del>@money($product['old'])</del>@endif
            <strong>@money($product['price'])</strong>
        </div>
        <button type="button" class="shop-cart-add" data-c="{{ $product['id'] }}"
                aria-label="افزودن {{ $product['name'] }} به سبد خرید" @disabled($product['stock'] < 1)>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2M12 12v4m-2-2h4"/></svg>
        </button>
    </div>
    @if($product['stock'] < 1)<span class="shop-stock">ناموجود</span>@endif
</article>
