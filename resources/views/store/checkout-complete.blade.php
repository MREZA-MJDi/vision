@extends('layouts.store')

@section('title', 'تأیید سفارش — VISION')
@section('meta-description', 'سفارش شما در فروشگاه VISION ثبت شده است.')

@section('content')
<main class="store-main commerce-page">
    <section class="commerce-complete commerce-panel">
        <span class="commerce-complete__mark" aria-hidden="true">✓</span>
        <p class="store-eyebrow">سفارش دریافت شد</p>
        <h1>سپاس از خرید شما<span>.</span></h1>
        <p>سفارش شما ثبت شده، موجودی به‌روزرسانی شده و سفارش در انتظار تأیید است.</p>
        <div class="commerce-complete__number"><span>شمارهٔ سفارش</span><strong>{{ $order->order_number }}</strong></div>
        <div class="commerce-summary__lines">
            <div><span>وضعیت سفارش</span><strong>{{ __('commerce.order_status.' . $order->status) }}</strong></div>
            <div><span>پرداخت</span><strong>{{ __('commerce.payment_status.' . $order->payment_status) }}</strong></div>
            <div><span>جمع کالاها</span><strong>@money($order->subtotal)</strong></div>
            <div><span>مالیات</span><strong>@money($order->tax)</strong></div>
            <div><span>هزینهٔ ارسال</span><strong>@money($order->shipping_cost)</strong></div>
            <div class="commerce-summary__total"><span>مبلغ نهایی</span><strong>@money($order->total)</strong></div>
        </div>
        <div class="commerce-complete__items">
            @foreach($order->items as $item)
                <div><span>{{ $item->product_name }} × {{ (int) $item->quantity }}</span><strong>@money($item->line_total)</strong></div>
            @endforeach
        </div>
        @if($order->payment_method === 'bank_transfer')
            <p class="commerce-muted">اطلاعات واریز بانکی باید جداگانه از طرف فروشگاه اعلام شود. پرداخت آنلاین انجام نشده است.</p>
        @endif
        <div class="commerce-complete__actions">
            @auth
                @if(!auth()->user()->is_admin)
                    <a class="commerce-button commerce-button--primary" href="{{ route('account.show') }}">مشاهدهٔ سفارش‌ها</a>
                @endif
            @endauth
            <a class="commerce-button commerce-button--quiet" href="{{ route('storefront.index') }}">ادامهٔ خرید</a>
        </div>
    </section>
</main>
@endsection
