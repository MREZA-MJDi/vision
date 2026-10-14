@extends('layouts.store')

@section('title', 'حساب کاربری — VISION')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">حساب کاربری VISION</p>
            <h1>حساب کاربری<span>.</span></h1>
            <p>{{ $customer->name }} عزیز، خوش آمدی. سوابق سفارش‌هایت در این بخش قرار دارد.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="commerce-button commerce-button--quiet" type="submit">خروج از حساب</button>
        </form>
    </div>

    @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif

    <section class="commerce-panel commerce-account-panel">
        <div class="commerce-panel-heading"><h2>سفارش‌های شما</h2><span>{{ number_format($orders->total()) }} سفارش</span></div>
        @if($orders->isEmpty())
            <div class="commerce-empty commerce-empty--compact">
                <h2>هنوز سفارشی ثبت نکرده‌ای.</h2>
                <p>سفارش‌هایی که پس از ورود به حساب ثبت کنی، در اینجا نمایش داده می‌شوند.</p>
                <a class="commerce-button commerce-button--primary" href="{{ route('products.index') }}">مشاهدهٔ محصولات ↗</a>
            </div>
        @else
            <div class="commerce-table-wrap">
                <table class="commerce-table">
                    <thead><tr><th>شمارهٔ سفارش</th><th>تاریخ ثبت</th><th>تعداد کالا</th><th>وضعیت</th><th>پرداخت</th><th>مبلغ نهایی</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ \App\Support\JalaliDate::format($order->placed_at ?: $order->created_at) }}</td>
                            <td>{{ (int) $order->items_count }}</td>
                            <td><span class="commerce-status">{{ __('commerce.order_status.' . $order->status) }}</span></td>
                            <td>{{ __('commerce.payment_status.' . $order->payment_status) }}</td>
                            <td><strong>@money($order->total)</strong></td>
                            <td><a class="commerce-text-link" href="{{ route('account.orders.show', $order->getKey()) }}">جزئیات ↗</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="commerce-pagination">{{ $orders->links() }}</div>
        @endif
    </section>
</main>
@endsection
