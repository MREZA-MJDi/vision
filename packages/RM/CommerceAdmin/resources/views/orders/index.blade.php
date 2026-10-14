@extends('commerce-admin::layout')

@section('title', 'سفارش‌ها')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">فروشگاه <span>/</span> فهرست سفارش‌ها</div><h1>سفارش‌ها</h1><p>سفارش‌های مشتری، مراحل ارسال و پرداخت‌ها را مدیریت کنید.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.orders.create') }}"><span>＋</span> ثبت سفارش</a></div>
    </div>

    <section class="ak-panel ak-orders-list-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>سفارش‌ها <span class="ak-count-chip">{{ $orders->total() }}</span></h2><p>همهٔ سفارش‌های ذخیره‌شده در دیتابیس فروشگاه</p></div>
            <div class="ak-toolbar"><button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ فیلترها</button><button class="ak-button ak-button-light ak-button-sm" type="button" data-columns-toggle>☷ ستون‌ها</button><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.orders.create') }}">＋ ثبت سفارش</a></div>
        </div>

        <div class="ak-order-tabs" role="tablist" aria-label="فیلتر سفارش‌ها بر اساس وضعیت">
            <a class="{{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index') }}">همه <span>{{ number_format($stats['all']) }}</span></a>
            <a class="{{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'pending']) }}">در انتظار <span>{{ number_format($stats['pending']) }}</span></a>
            <a class="{{ $status === 'shipped' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'shipped']) }}">ارسال‌شده <span>{{ number_format($stats['shipped']) }}</span></a>
            <a class="{{ $status === 'delivered' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'delivered']) }}">تحویل‌شده <span>{{ number_format($stats['delivered']) }}</span></a>
            <a class="{{ $status === 'cancelled' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'cancelled']) }}">لغوشده / مرجوع‌شده <span>{{ number_format($stats['cancelled']) }}</span></a>
        </div>

        <form class="ak-filter-bar {{ $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.orders.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>جست‌وجوی سفارش‌ها</span><input type="search" name="q" value="{{ $search }}" placeholder="شماره سفارش، نام مشتری، ایمیل یا تلفن"></label>
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">جست‌وجو</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.orders.index') }}">پاک‌کردن</a></div>
        </form>

        <div class="ak-column-popover" data-columns-panel hidden><label><input type="checkbox" data-column-check="product" checked> محصول</label><label><input type="checkbox" data-column-check="customer" checked> مشتری</label><label><input type="checkbox" data-column-check="type" checked> نوع</label><label><input type="checkbox" data-column-check="price" checked> مبلغ کل</label><label><input type="checkbox" data-column-check="date" checked> تاریخ</label><label><input type="checkbox" data-column-check="status" checked> وضعیت</label></div>

        @if($orders->isEmpty())
            <div class="ak-empty-state"><span>▤</span><strong>سفارشی پیدا نشد</strong><p>وضعیت یا عبارت جست‌وجو را تغییر دهید یا با موجودی قابل فروش سفارش جدیدی ثبت کنید.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.orders.create') }}">ثبت سفارش</a></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table ak-orders-table">
                <thead><tr><th>سفارش</th><th data-column="product">محصول</th><th data-column="customer">مشتری</th><th data-column="type">نوع</th><th data-column="price">قیمت</th><th data-column="date">تاریخ</th><th data-column="status">وضعیت</th><th></th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    @php $firstItem = $order->primaryItem; @endphp
                    <tr>
                        <td><a class="ak-table-primary" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}">#{{ $order->order_number }}</a><small class="ak-table-subtext">ID {{ $order->getKey() }}</small></td>
                        <td data-column="product"><div class="ak-product-cell">@if($firstItem?->product_image)<img src="{{ $firstItem->product_image }}" alt="{{ $firstItem->product_name }}">@else<span class="ak-product-thumb-placeholder">▦</span>@endif<div><strong>{{ $firstItem?->product_name ?: 'سفارش بدون اطلاعات اقلام' }}</strong><small>{{ number_format((int) ($order->admin_total_quantity ?? 0)) }} واحد در مجموع</small></div></div></td>
                        <td data-column="customer"><div class="ak-customer-cell"><span class="ak-avatar ak-avatar-tiny">{{ strtoupper(substr($order->customer_name ?: 'C', 0, 1)) }}</span><span><strong>{{ $order->customer_name ?: $order->user?->name ?: 'مشتری مهمان' }}</strong><small>{{ $order->customer_email ?: $order->customer_phone ?: 'اطلاعات تماس ثبت نشده' }}</small></span></div></td>
                        <td data-column="type"><span class="ak-type-label">{{ $order->status === 'returned' ? 'مرجوعی' : ($order->discount > 0 ? 'فروش با تخفیف' : 'فروش') }}</span></td>
                        <td data-column="price"><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)$order->total, 2) }}</strong><small class="ak-table-subtext">{{ config('commerce-admin.status_labels.' . ($order->payment_status ?: 'pending'), \\Illuminate\\Support\\Str::headline($order->payment_status ?: 'pending')) }}</small></td>
                        <td data-column="date">{{ optional($order->placed_at ?: $order->created_at)->locale('fa')->translatedFormat('j M Y') }}</td>
                        <td data-column="status"><span class="ak-status-pill status-{{ \\Illuminate\\Support\\Str::slug($order->status) }}">{{ config('commerce-admin.status_labels.' . $order->status, \\Illuminate\\Support\\Str::headline($order->status)) }}</span></td>
                        <td><a class="ak-row-action" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}" aria-label="بازکردن جزئیات سفارش">···</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders</span>{{ $orders->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
