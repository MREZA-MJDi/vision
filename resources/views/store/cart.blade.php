@extends('layouts.store')

@section('title', 'Your Bag — VISION')
@section('meta-description', 'Review your selected VISION pieces and continue to secure checkout.')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">YOUR SELECTION</p>
            <h1>Your bag<span>.</span></h1>
            <p>Prices and stock are rechecked by the server before the order is placed.</p>
        </div>
        <a class="commerce-text-link" href="{{ route('products.index') }}">Continue shopping ↗</a>
    </div>

    @if(session('status'))
        <p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>
    @endif
    @if(session('error'))
        <p class="commerce-alert commerce-alert--error" role="alert">{{ session('error') }}</p>
    @endif
    @if($errors->any())
        <div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if($items->isEmpty())
        <section class="commerce-empty">
            <span class="commerce-empty__mark">V.</span>
            <h2>Nothing in your bag yet.</h2>
            <p>Explore the edit and add a piece when something feels right.</p>
            <a class="commerce-button commerce-button--primary" href="{{ route('products.index') }}">Explore the edit ↗</a>
        </section>
    @else
        <div class="commerce-layout">
            <section class="commerce-panel commerce-cart-items" aria-label="Items in your bag">
                <div class="commerce-panel-heading"><h2>Selected pieces</h2><span>{{ $summary['itemCount'] }} {{ $summary['itemCount'] === 1 ? 'item' : 'items' }}</span></div>

                @foreach($items as $item)
                    @php
                        $variant = $item->productVariant;
                        $product = $variant?->product;
                        $image = $variant?->primaryGalleryMedia?->url ?? $product?->primaryGalleryMedia?->url;
                    @endphp
                    <article class="commerce-cart-row">
                        @if($image)
                            <img class="commerce-cart-row__image" src="{{ $image }}" alt="{{ $product?->name ?? 'Product image' }}" loading="lazy">
                        @else
                            <div class="commerce-cart-row__image commerce-cart-row__image--empty" aria-hidden="true">V.</div>
                        @endif
                        <div class="commerce-cart-row__details">
                            <div class="commerce-cart-row__title">
                                <div>
                                    <h3>{{ $product?->name ?? 'Unavailable product' }}</h3>
                                    <p>{{ $variant?->display_name ?? 'This variant is no longer available' }}</p>
                                </div>
                                <strong>{{ '$' }}{{ number_format((float) $item->display_line_total, 2) }}</strong>
                            </div>
                            @if(!$item->checkout_available)
                                <p class="commerce-inline-error">This item is unavailable or the requested quantity exceeds current stock. Remove it or update the quantity.</p>
                            @else
                                <p class="commerce-muted">{{ '$' }}{{ number_format((float) $item->display_unit_price, 2) }} each · {{ (int) $variant->stock }} available</p>
                            @endif
                            <div class="commerce-cart-row__actions">
                                @if($variant && $product)
                                    <form method="POST" action="{{ route('cart.items.update', $item->getKey()) }}" class="commerce-quantity-form">
                                        @csrf @method('PATCH')
                                        <label for="cart-quantity-{{ $item->getKey() }}">Qty</label>
                                        <input id="cart-quantity-{{ $item->getKey() }}" type="number" name="quantity" min="1" max="100" value="{{ old('quantity', $item->quantity) }}" required>
                                        <button class="commerce-button commerce-button--quiet" type="submit">Update</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('cart.items.destroy', $item->getKey()) }}">
                                    @csrf @method('DELETE')
                                    <button class="commerce-text-button" type="submit">Remove</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="commerce-panel commerce-summary">
                <div class="commerce-panel-heading"><h2>Order summary</h2><span>USD</span></div>
                <div class="commerce-summary__lines">
                    <div><span>Subtotal</span><strong>{{ '$' }}{{ number_format((float) $summary['subtotal'], 2) }}</strong></div>
                    <div><span>Tax</span><strong>{{ '$' }}{{ number_format((float) $summary['tax'], 2) }}</strong></div>
                    <div><span>Shipping</span><strong>{{ '$' }}{{ number_format((float) $summary['shipping'], 2) }}</strong></div>
                    <div class="commerce-summary__total"><span>Total</span><strong>{{ '$' }}{{ number_format((float) $summary['total'], 2) }}</strong></div>
                </div>
                @if($summary['allAvailable'])
                    <a class="commerce-button commerce-button--primary commerce-button--wide" href="{{ route('checkout.index') }}">Continue to checkout ↗</a>
                @else
                    <button class="commerce-button commerce-button--disabled commerce-button--wide" type="button" disabled>Resolve unavailable items</button>
                @endif
                <p class="commerce-muted">Final prices, taxes and available quantities are verified again when you place your order.</p>
            </aside>
        </div>
    @endif
</main>
@endsection
