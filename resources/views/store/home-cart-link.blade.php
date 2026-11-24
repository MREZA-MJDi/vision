<a href="{{ route('cart.index') }}" aria-label="مشاهدهٔ سبد خرید"
   @if($floating) class="home-cart-floating" data-home-cart-floating hidden @else data-home-cart-target @endif>
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path></svg>
    <span data-cart-count>۰</span>
</a>
