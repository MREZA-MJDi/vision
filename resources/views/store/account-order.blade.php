@extends('layouts.store')

@section('title', 'سفارش ' . $model->order_number . ' — VISION')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">جزئیات سفارش</p>
            <h1>{{ $model->order_number }}<span>.</span></h1>
            <p>تاریخ ثبت: {{ \App\Support\JalaliDate::format($model->placed_at ?: $model->created_at, 'j F Y - H:i') }}</p>
        </div>
        <a class="commerce-text-link" href="{{ route('account.show') }}">← حساب کاربری</a>
    </div>

    <div class="commerce-layout">
        <section class="commerce-panel commerce-cart-items">
            <div class="commerce-panel-heading"><h2>کالاهای سفارش</h2><span>{{ number_format($model->items->sum('quantity')) }} عدد</span></div>
            @foreach($model->items as $item)
                <article class="commerce-cart-row">
                    @if($item->product_image)
                        <img class="commerce-cart-row__image" src="{{ $item->product_image }}" alt="{{ $item->product_name }}" loading="lazy">
                    @else
                        <div class="commerce-cart-row__image commerce-cart-row__image--empty" aria-hidden="true">V.</div>
                    @endif
                    <div class="commerce-cart-row__details">
                        <div class="commerce-cart-row__title">
                            <div><h3>{{ $item->product_name }}</h3><p>{{ $item->variant_name ?: $item->sku ?: 'گزینهٔ استاندارد' }} · تعداد {{ number_format((int) $item->quantity) }}</p></div>
                            <strong>@money($item->line_total)</strong>
                        </div>
                        <p class="commerce-muted">@money($item->unit_price) برای هر عدد</p>
                    </div>
                </article>
            @endforeach
        </section>

        <aside class="commerce-panel commerce-summary">
            <div class="commerce-panel-heading"><h2>خلاصهٔ سفارش</h2><span>{{ __('commerce.order_status.' . $model->status) }}</span></div>
            <div class="commerce-summary__lines">
                <div><span>وضعیت پرداخت</span><strong>{{ __('commerce.payment_status.' . $model->payment_status) }}</strong></div>
                <div><span>جمع کالاها</span><strong>@money($model->subtotal)</strong></div>
                <div><span>تخفیف</span><strong>−@money($model->discount)</strong></div>
                <div><span>مالیات</span><strong>@money($model->tax)</strong></div>
                <div><span>هزینهٔ ارسال</span><strong>@money($model->shipping_cost)</strong></div>
                <div class="commerce-summary__total"><span>مبلغ نهایی</span><strong>@money($model->total)</strong></div>
            </div>
            <p class="commerce-muted">نشانی تحویل: {{ $model->shipping_address }}@if($model->shipping_city), {{ $model->shipping_city }}@endif</p>
        </aside>
    </div>
</main>
@endsection
