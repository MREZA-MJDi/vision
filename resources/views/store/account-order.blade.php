@extends('layouts.store')

@section('title', 'Order ' . $model->order_number . ' — VISION')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">ORDER DETAILS</p>
            <h1>{{ $model->order_number }}<span>.</span></h1>
            <p>Placed {{ optional($model->placed_at ?: $model->created_at)->format('M d, Y H:i') }}</p>
        </div>
        <a class="commerce-text-link" href="{{ route('account.show') }}">← My account</a>
    </div>

    <div class="commerce-layout">
        <section class="commerce-panel commerce-cart-items">
            <div class="commerce-panel-heading"><h2>Items</h2><span>{{ $model->items->sum('quantity') }} units</span></div>
            @foreach($model->items as $item)
                <article class="commerce-cart-row">
                    @if($item->product_image)
                        <img class="commerce-cart-row__image" src="{{ $item->product_image }}" alt="{{ $item->product_name }}" loading="lazy">
                    @else
                        <div class="commerce-cart-row__image commerce-cart-row__image--empty" aria-hidden="true">V.</div>
                    @endif
                    <div class="commerce-cart-row__details">
                        <div class="commerce-cart-row__title">
                            <div><h3>{{ $item->product_name }}</h3><p>{{ $item->variant_name ?: $item->sku ?: 'Standard option' }} · Qty {{ (int) $item->quantity }}</p></div>
                            <strong>@money($item->line_total)</strong>
                        </div>
                        <p class="commerce-muted">@money($item->unit_price) per item</p>
                    </div>
                </article>
            @endforeach
        </section>

        <aside class="commerce-panel commerce-summary">
            <div class="commerce-panel-heading"><h2>Order summary</h2><span>{{ \Illuminate\Support\Str::headline($model->status) }}</span></div>
            <div class="commerce-summary__lines">
                <div><span>Payment status</span><strong>{{ \Illuminate\Support\Str::headline($model->payment_status) }}</strong></div>
                <div><span>Subtotal</span><strong>@money($model->subtotal)</strong></div>
                <div><span>Discount</span><strong>−@money($model->discount)</strong></div>
                <div><span>Tax</span><strong>@money($model->tax)</strong></div>
                <div><span>Shipping</span><strong>@money($model->shipping_cost)</strong></div>
                <div class="commerce-summary__total"><span>Total</span><strong>@money($model->total)</strong></div>
            </div>
            <p class="commerce-muted">Delivery address: {{ $model->shipping_address }}@if($model->shipping_city), {{ $model->shipping_city }}@endif</p>
        </aside>
    </div>
</main>
@endsection
