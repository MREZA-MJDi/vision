@extends('layouts.store')

@section('title', 'Checkout — VISION')
@section('meta-description', 'Complete your VISION order with server-verified prices and availability.')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">THE FINAL STEP</p>
            <h1>Checkout<span>.</span></h1>
            <p>You can order as a guest or sign in to save your order history.</p>
        </div>
        <a class="commerce-text-link" href="{{ route('cart.index') }}">← Back to bag</a>
    </div>

    @if($errors->any())
        <div class="commerce-alert commerce-alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if(!$summary['allAvailable'])
        <div class="commerce-alert commerce-alert--error" role="alert">One or more items are unavailable or exceed current stock. Return to your bag to fix them before placing the order.</div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="commerce-layout commerce-checkout-layout">
        @csrf
        <div class="commerce-form-stack">
            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>Contact details</h2><span>01</span></div>
                <div class="commerce-form-grid">
                    <label class="commerce-field commerce-field--wide">Full name <span>*</span>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" maxlength="255" autocomplete="name" required>
                        @error('customer_name')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">Email address
                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" maxlength="255" autocomplete="email">
                        @error('customer_email')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">Phone number <span>*</span>
                        <input type="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" maxlength="30" autocomplete="tel" required>
                        @error('customer_phone')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>Delivery address</h2><span>02</span></div>
                <div class="commerce-form-grid">
                    <label class="commerce-field commerce-field--wide">Street address <span>*</span>
                        <textarea name="shipping_address" rows="3" maxlength="2000" autocomplete="street-address" required>{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">City
                        <input type="text" name="shipping_city" value="{{ old('shipping_city') }}" maxlength="100" autocomplete="address-level2">
                        @error('shipping_city')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">State / Province
                        <input type="text" name="shipping_province" value="{{ old('shipping_province') }}" maxlength="100" autocomplete="address-level1">
                        @error('shipping_province')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field">Postal code
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" autocomplete="postal-code">
                        @error('postal_code')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="commerce-field commerce-field--wide">Order note
                        <textarea name="customer_note" rows="2" maxlength="2000" placeholder="Optional delivery instructions">{{ old('customer_note') }}</textarea>
                        @error('customer_note')<small class="commerce-field-error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>

            <section class="commerce-panel">
                <div class="commerce-panel-heading"><h2>Payment method</h2><span>03</span></div>
                <div class="commerce-payment-options">
                    <label><input type="radio" name="payment_method" value="cash_on_delivery" @checked(old('payment_method', 'cash_on_delivery') === 'cash_on_delivery') required><span><strong>Cash on delivery</strong><small>Pay when the order arrives.</small></span></label>
                    <label><input type="radio" name="payment_method" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer') required><span><strong>Bank transfer</strong><small>Payment instructions must be provided by the store after order placement.</small></span></label>
                </div>
                @error('payment_method')<small class="commerce-field-error">{{ $message }}</small>@enderror
                <p class="commerce-muted">Online card processing is not enabled; no card details are requested or stored here.</p>
            </section>
        </div>

        <aside class="commerce-panel commerce-summary">
            <div class="commerce-panel-heading"><h2>Your order</h2><span>{{ $summary['itemCount'] }} items</span></div>
            <div class="commerce-summary__products">
                @foreach($items as $item)
                    @php($variant = $item->productVariant)
                    <div><span>{{ $variant?->product?->name ?? 'Unavailable item' }} × {{ (int) $item->quantity }}</span><strong>@money($item->display_line_total)</strong></div>
                @endforeach
            </div>
            <div class="commerce-summary__lines">
                <div><span>Subtotal</span><strong>@money($summary['subtotal'])</strong></div>
                <div><span>Tax</span><strong>@money($summary['tax'])</strong></div>
                <div><span>Shipping</span><strong>@money($summary['shipping'])</strong></div>
                <div class="commerce-summary__total"><span>Total</span><strong>@money($summary['total'])</strong></div>
            </div>
            <button class="commerce-button commerce-button--primary commerce-button--wide" type="submit" @disabled(!$summary['allAvailable'])>Place order securely ↗</button>
            <p class="commerce-muted">Clicking once creates the order and reserves stock in one database transaction. Prices and stock are recalculated server-side.</p>
        </aside>
    </form>
</main>
@endsection
