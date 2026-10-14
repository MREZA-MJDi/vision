@extends('commerce-admin::layout')

@section('title', 'Create order')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.orders.index') }}">Orders</a> <span>/</span> Create order</div><h1>Create order</h1><p>Create a real order and reserve available stock with an inventory movement record.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.orders.index') }}">Cancel</a><button class="ak-button ak-button-dark" type="submit" form="orderCreateForm">Create order <span>✓</span></button></div>
    </div>

    <form id="orderCreateForm" method="POST" action="{{ route('commerce-admin.orders.store') }}">
        @csrf
        <div class="ak-form-layout ak-order-create-layout">
            <div class="ak-form-main">
                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>Customer information</h2><p>If the email matches an existing account, the order will be linked to that customer.</p></div></div>
                    <div class="ak-form-grid">
                        <label class="ak-field"><span>Customer name <b>*</b></span><input type="text" name="customer_name" required maxlength="255" value="{{ old('customer_name') }}" placeholder="Full name"></label>
                        <label class="ak-field"><span>Email address</span><input type="email" name="customer_email" maxlength="255" value="{{ old('customer_email') }}" placeholder="customer@example.com"></label>
                        <label class="ak-field"><span>Phone</span><input type="text" name="customer_phone" maxlength="30" value="{{ old('customer_phone') }}" placeholder="Phone number"></label>
                        <label class="ak-field"><span>Payment method <b>*</b></span><select name="payment_method" required><option value="cash_on_delivery" @selected(old('payment_method','cash_on_delivery')==='cash_on_delivery')>Cash on delivery</option><option value="bank_transfer" @selected(old('payment_method')==='bank_transfer')>Bank transfer</option><option value="manual" @selected(old('payment_method')==='manual')>Manual / external payment</option></select></label>
                    </div>
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>Delivery information</h2><p>Address fields are persisted on the order record.</p></div></div>
                    <div class="ak-form-grid">
                        <label class="ak-field ak-field-full"><span>Shipping address</span><textarea name="shipping_address" rows="3" maxlength="2000" placeholder="Street address, building, unit...">{{ old('shipping_address') }}</textarea></label>
                        <label class="ak-field"><span>City</span><input type="text" name="shipping_city" maxlength="100" value="{{ old('shipping_city') }}"></label>
                        <label class="ak-field"><span>Province / State</span><input type="text" name="shipping_province" maxlength="100" value="{{ old('shipping_province') }}"></label>
                        <label class="ak-field"><span>Postal code</span><input type="text" name="postal_code" maxlength="30" value="{{ old('postal_code') }}"></label>
                    </div>
                </section>

                <section class="ak-panel ak-form-panel">
                    <div class="ak-panel-heading"><div><h2>Order items</h2><p>Only active variants are shown. Quantity is checked and stock is locked before saving.</p></div><button class="ak-button ak-button-light ak-button-sm" type="button" data-add-order-item>＋ Add item</button></div>
                    @if($variants->isEmpty())
                        <div class="ak-inline-empty"><strong>No active inventory available</strong><p>Create a product and activate at least one variant before creating an order.</p><a class="ak-text-link" href="{{ route('commerce-admin.products.create') }}">Add a product →</a></div>
                    @endif
                    <div class="ak-order-item-list" data-order-items>
                        <div class="ak-order-item-row" data-order-item-row>
                            <div class="ak-order-item-field ak-order-product-select"><label class="ak-field"><span>Product variant <b>*</b></span><select name="items[0][variant_id]" required data-order-variant><option value="">Select product / option</option>@foreach($variants as $variant)<option value="{{ $variant->getKey() }}" data-price="{{ $variant->sale_price ?? $variant->price }}" data-stock="{{ $variant->stock }}">{{ $variant->product->name }} · {{ $variant->display_name }}{{ $variant->sku ? ' · '.$variant->sku : '' }} — {{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant->sale_price ?? $variant->price),2) }} ({{ $variant->stock }} available)</option>@endforeach</select></label></div>
                            <div class="ak-order-qty-field"><label class="ak-field"><span>Qty <b>*</b></span><input type="number" name="items[0][quantity]" value="1" min="1" max="1000" required data-order-quantity></label></div>
                            <div class="ak-order-line-total"><small>Line total</small><strong data-line-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div>
                            <button class="ak-icon-button ak-remove-order-item" type="button" data-remove-order-item aria-label="Remove item" disabled>×</button>
                        </div>
                    </div>
                    <template id="order-item-template"><div class="ak-order-item-row" data-order-item-row><div class="ak-order-item-field ak-order-product-select"><label class="ak-field"><span>Product variant <b>*</b></span><select name="items[__INDEX__][variant_id]" required data-order-variant><option value="">Select product / option</option>@foreach($variants as $variant)<option value="{{ $variant->getKey() }}" data-price="{{ $variant->sale_price ?? $variant->price }}" data-stock="{{ $variant->stock }}">{{ $variant->product->name }} · {{ $variant->display_name }}{{ $variant->sku ? ' · '.$variant->sku : '' }} — {{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant->sale_price ?? $variant->price),2) }} ({{ $variant->stock }} available)</option>@endforeach</select></label></div><div class="ak-order-qty-field"><label class="ak-field"><span>Qty <b>*</b></span><input type="number" name="items[__INDEX__][quantity]" value="1" min="1" max="1000" required data-order-quantity></label></div><div class="ak-order-line-total"><small>Line total</small><strong data-line-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div><button class="ak-icon-button ak-remove-order-item" type="button" data-remove-order-item aria-label="Remove item">×</button></div></template>
                </section>
            </div>

            <aside class="ak-form-aside">
                <section class="ak-panel ak-aside-panel"><div class="ak-panel-heading"><div><h2>Order summary</h2><p>Calculated from the selected live variant prices.</p></div></div>
                    <div class="ak-summary-lines"><div><span>Subtotal</span><strong data-order-subtotal>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div><label class="ak-field"><span>Shipping cost</span><input type="number" name="shipping_cost" value="{{ old('shipping_cost', 0) }}" min="0" step="0.01" required data-shipping-cost></label><div class="ak-summary-total"><span>Total</span><strong data-order-total>{{ config('commerce-admin.currency_symbol', '$') }}0.00</strong></div></div>
                    <div class="ak-info-box"><span>i</span><p>Stock is deducted inside the same database transaction as the order. If a selected variant cannot satisfy the quantity, nothing is saved.</p></div>
                    <button class="ak-button ak-button-dark ak-button-wide" type="submit" {{ $variants->isEmpty() ? 'disabled' : '' }}>Create order <span>✓</span></button>
                </section>
            </aside>
        </div>
    </form>
@endsection
