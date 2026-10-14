@extends('layouts.store')

@section('title', 'Order Confirmed — VISION')
@section('meta-description', 'Your VISION order has been recorded.')

@section('content')
<main class="store-main commerce-page">
    <section class="commerce-complete commerce-panel">
        <span class="commerce-complete__mark" aria-hidden="true">✓</span>
        <p class="store-eyebrow">ORDER RECEIVED</p>
        <h1>Thank you<span>.</span></h1>
        <p>Your order has been recorded. Inventory was updated and your order is now pending confirmation.</p>
        <div class="commerce-complete__number"><span>ORDER NUMBER</span><strong>{{ $order->order_number }}</strong></div>
        <div class="commerce-summary__lines">
            <div><span>Order status</span><strong>{{ \Illuminate\Support\Str::headline($order->status) }}</strong></div>
            <div><span>Payment</span><strong>{{ \Illuminate\Support\Str::headline($order->payment_status) }}</strong></div>
            <div><span>Subtotal</span><strong>@money($order->subtotal)</strong></div>
            <div><span>Tax</span><strong>@money($order->tax)</strong></div>
            <div><span>Shipping</span><strong>@money($order->shipping_cost)</strong></div>
            <div class="commerce-summary__total"><span>Total</span><strong>@money($order->total)</strong></div>
        </div>
        <div class="commerce-complete__items">
            @foreach($order->items as $item)
                <div><span>{{ $item->product_name }} × {{ (int) $item->quantity }}</span><strong>@money($item->line_total)</strong></div>
            @endforeach
        </div>
        @if($order->payment_method === 'bank_transfer')
            <p class="commerce-muted">The store must provide bank-transfer instructions separately. No online payment was taken.</p>
        @endif
        <div class="commerce-complete__actions">
            @auth
                @if(!auth()->user()->is_admin)
                    <a class="commerce-button commerce-button--primary" href="{{ route('account.show') }}">View my orders</a>
                @endif
            @endauth
            <a class="commerce-button commerce-button--quiet" href="{{ route('storefront.index') }}">Continue shopping</a>
        </div>
    </section>
</main>
@endsection
