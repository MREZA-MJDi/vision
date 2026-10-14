@extends('commerce-admin::layout')

@section('title', 'داشبورد فروشگاه')

@section('content')
    @php
        $revenueMax = max(1, (float) collect($series)->max('value'));
        $ordersMax = max(1, (int) collect($series)->max('orders_count'));
        $revenuePoints = collect($series)->values()->map(fn ($point, $index) =>
            round(10 + ($index * 340 / max(1, count($series) - 1)), 1).','.round(85 - (((float) $point['value'] / $revenueMax) * 66), 1)
        )->implode(' ');
        $ordersPoints = collect($series)->values()->map(fn ($point, $index) =>
            round(10 + ($index * 340 / max(1, count($series) - 1)), 1).','.round(85 - (((int) $point['orders_count'] / $ordersMax) * 58), 1)
        )->implode(' ');
    @endphp

    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb">مدیریت <span>/</span> داشبورد فروشگاه</div>
            <h1>داشبورد فروشگاه</h1>
            <p>وضعیت فروش، سفارش‌ها و موجودی بر اساس اطلاعات واقعی فروشگاه.</p>
        </div>
        <div class="ak-heading-actions">
            <span class="ak-date-chip">◷ @commerceDate(now(), 'j F Y')</span>
            <a class="ak-button ak-button-dark" href="{{ route('commerce-admin.analytics') }}">گزارش کامل <span>↗</span></a>
        </div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-four ak-dashboard-kpis" aria-label="شاخص‌های اصلی فروشگاه">
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">＄</span><span class="ak-stat-kicker">درآمد پرداخت‌شده</span></div>
            <div class="ak-stat-value">@commerceMoney($revenue)</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">بدون سفارش لغو یا مرجوع‌شده</span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">▤</span><span class="ak-stat-kicker">کل سفارش‌ها</span></div>
            <div class="ak-stat-value">{{ number_format((int) $orderCount) }}</div>
            <div class="ak-stat-footer"><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index') }}">مشاهده سفارش‌ها ↗</a></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-yellow">◷</span><span class="ak-stat-kicker">در انتظار / در حال انجام</span></div>
            <div class="ak-stat-value">{{ number_format((int) $pendingOrders) }}</div>
            <div class="ak-stat-footer"><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index', ['status' => 'pending']) }}">پیگیری سفارش‌های باز ↗</a></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-red">▣</span><span class="ak-stat-kicker">تنوع با موجودی کم</span></div>
            <div class="ak-stat-value">{{ number_format((int) $lowStockCount) }}</div>
            <div class="ak-stat-footer"><a class="ak-stat-link" href="{{ route('commerce-admin.inventory.index', ['status' => 'low_stock']) }}">بررسی موجودی ↗</a></div>
        </article>
    </section>

    <section class="ak-classic-lower-grid">
        <article class="ak-panel ak-activity-panel">
            <div class="ak-panel-heading">
                <div><h2>روند فروشگاه</h2><p>درآمد پرداخت‌شده و تعداد سفارش در ۱۲ ماه اخیر</p></div>
                <a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.analytics.export') }}">خروجی CSV ↓</a>
            </div>
            <div class="ak-activity-summary">
                <div><small>درآمد پرداخت‌شده</small><strong>@commerceMoney($revenue)</strong></div>
                <div><small>کل سفارش‌ها</small><strong>{{ number_format((int) $orderCount) }}</strong></div>
                <div><small>سفارش‌های باز</small><strong>{{ number_format((int) $pendingOrders) }}</strong></div>
            </div>
            <div class="ak-classic-line-chart" role="img" aria-label="نمودار درآمد و سفارش در ۱۲ ماه اخیر">
                <div class="ak-line-chart-labels"><span>{{ $series[0]['label'] ?? '' }}</span><span>{{ $series[3]['label'] ?? '' }}</span><span>{{ $series[6]['label'] ?? '' }}</span><span>{{ $series[9]['label'] ?? '' }}</span><span>{{ $series[11]['label'] ?? '' }}</span></div>
                <svg viewBox="0 0 360 180" preserveAspectRatio="none" aria-hidden="true">
                    <line x1="8" y1="22" x2="352" y2="22"></line>
                    <line x1="8" y1="62" x2="352" y2="62"></line>
                    <line x1="8" y1="102" x2="352" y2="102"></line>
                    <line x1="8" y1="142" x2="352" y2="142"></line>
                    <polyline class="ak-line-revenue" points="{{ $revenuePoints }}"></polyline>
                    <polyline class="ak-line-orders" points="{{ $ordersPoints }}"></polyline>
                </svg>
            </div>
            <div class="ak-chart-legend">
                <span><i class="ak-legend-dot"></i> درآمد</span>
                <span><i class="ak-legend-dot ak-legend-dot-light"></i> سفارش‌ها</span>
                <a href="{{ route('commerce-admin.analytics') }}">گزارش تحلیلی ←</a>
            </div>
        </article>

        <article class="ak-panel ak-latest-orders-panel">
            <div class="ak-panel-heading">
                <div><h2>آخرین سفارش‌ها</h2><p>سفارش‌های ثبت‌شده در فروشگاه</p></div>
                <a class="ak-text-link" href="{{ route('commerce-admin.orders.index') }}">مشاهده همه ↗</a>
            </div>
            @forelse($recentOrders as $order)
                <a class="ak-latest-order-row" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}">
                    <span class="ak-latest-order-avatar">{{ mb_strtoupper(mb_substr($order->customer_name ?: $order->user?->name ?: 'م', 0, 1)) }}</span>
                    <span class="ak-latest-order-identity">
                        <strong>{{ $order->customer_name ?: $order->user?->name ?: 'مشتری مهمان' }}</strong>
                        <small>#{{ $order->order_number }} · @commerceDate($order->placed_at ?: $order->created_at, 'j M')</small>
                    </span>
                    <span class="ak-latest-order-total">@commerceMoney($order->total)<small class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($order->status) }}">{{ \Illuminate\Support\Str::headline($order->status) }}</small></span>
                </a>
            @empty
                <div class="ak-compact-empty">هنوز سفارشی ثبت نشده است.</div>
            @endforelse
            <div class="ak-latest-orders-foot">
                <div><span class="ak-mini-status-dot"></span><span>{{ number_format((int) $lowStockCount) }} تنوع نیازمند بررسی موجودی است</span></div>
                <a href="{{ route('commerce-admin.inventory.index', ['status' => 'low_stock']) }}">مدیریت موجودی ←</a>
            </div>
        </article>
    </section>

    <section class="ak-classic-customer-grid">
        <article class="ak-panel ak-team-panel">
            <div class="ak-panel-heading">
                <div><h2>مشتری‌های اخیر</h2><p>حساب‌های مشتری ثبت‌شده در دیتابیس</p></div>
                <span class="ak-count-chip">{{ number_format((int) $customersCount) }} مشتری</span>
            </div>
            @forelse($recentCustomers as $customer)
                <div class="ak-team-row">
                    <span class="ak-avatar ak-team-avatar">{{ mb_strtoupper(mb_substr($customer->name ?: $customer->email ?: 'م', 0, 1)) }}</span>
                    <div class="ak-team-identity">
                        <strong>{{ $customer->name ?: 'مشتری' }}</strong>
                        <small>{{ $customer->email ?: 'ایمیل ثبت نشده' }}</small>
                    </div>
                    <a class="ak-team-action" href="{{ route('commerce-admin.customers.show', $customer->getKey()) }}">جزئیات</a>
                </div>
            @empty
                <div class="ak-compact-empty">هنوز حساب مشتری ثبت نشده است.</div>
            @endforelse
            <div class="ak-team-foot"><span>{{ number_format((int) $customersCount) }} حساب مشتری</span><a href="{{ route('commerce-admin.customers.index') }}">فهرست مشتری‌ها ↗</a></div>
        </article>
    </section>
@endsection
