@extends('commerce-admin::layout')

@section('title', request('panel') === 'payments' ? 'Payment Dashboard' : 'Analytics')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">E-Commerce <span>/</span> {{ request('panel') === 'payments' ? 'Payments' : 'Analytics' }}</div><h1>{{ request('panel') === 'payments' ? 'Payment dashboard' : 'Store analytics' }}</h1><p>Reports calculated from orders, customer destinations and stock records.</p></div>
        <div class="ak-heading-actions"><span class="ak-date-chip">◷ Last 8 months</span><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.orders.index') }}">Explore orders ↗</a></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-four">
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">$</span><span class="ak-stat-kicker">PAID REVENUE</span></div><div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $revenue, 2) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Settled orders recorded</span><span class="ak-mini-badge">Live</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">▤</span><span class="ak-stat-kicker">TOTAL ORDERS</span></div><div class="ak-stat-value">{{ number_format($allTimeOrders) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">All saved order records</span><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index') }}">View</a></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">✓</span><span class="ak-stat-kicker">PAID ORDERS</span></div><div class="ak-stat-value">{{ number_format($paidCount) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Marked paid in database</span><span class="ak-mini-badge">Verified records</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-red">↘</span><span class="ak-stat-kicker">CANCELLED / RETURNED</span></div><div class="ak-stat-value">{{ number_format($cancelledCount) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Terminal order states</span><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index', ['status' => 'cancelled']) }}">Review</a></div></article>
    </section>

    <section class="ak-content-grid ak-content-grid-main">
        <article class="ak-panel ak-revenue-panel">
            <div class="ak-panel-heading"><div><h2>Revenue by month</h2><p>Paid orders only · cancelled and returned orders excluded</p></div><span class="ak-icon-chip">↗</span></div>
            <div class="ak-chart-summary"><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $revenue, 2) }}</strong><span class="ak-stat-note">Recorded paid revenue</span></div>
            @php $maxValue = max(array_column($series, 'value')) ?: 1; @endphp
            <div class="ak-bar-chart ak-bar-chart-tall" role="img" aria-label="Paid revenue by month">
                @foreach ($series as $point)
                    <div class="ak-bar-column"><span class="ak-bar-tooltip">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format($point['value'], 2) }}</span><div class="ak-bar-track"><span style="height:{{ max(2, ($point['value'] / $maxValue) * 100) }}%"></span></div><small>{{ $point['label'] }}</small></div>
                @endforeach
            </div>
            <div class="ak-chart-legend"><span><i class="ak-legend-dot"></i> Paid revenue</span><span>8 months</span></div>
        </article>
        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Payment status</h2><p>Distribution of order payment records</p></div></div>
            @php $paymentTotal = max(1, $paymentBreakdown->sum('aggregate')); $palette = ['paid'=>'#26b878','pending'=>'#e7ad47','failed'=>'#e15b5b','refunded'=>'#8592a3']; $offset=0; $segments=[]; @endphp
            @foreach ($paymentBreakdown as $statusRow)
                @php $pct = ($statusRow->aggregate / $paymentTotal) * 100; $segments[] = ($palette[$statusRow->payment_status] ?? '#8993a4') . ' ' . $offset . '% ' . ($offset + $pct) . '%'; $offset += $pct; @endphp
            @endforeach
            <div class="ak-donut-wrap"><div class="ak-donut" style="background:conic-gradient({{ implode(', ', $segments) ?: '#edf0f4 0% 100%' }})"><div><strong>{{ number_format($allTimeOrders) }}</strong><small>Orders</small></div></div>
                <div class="ak-donut-legend">
                    @forelse ($paymentBreakdown as $row)<span><i style="background:{{ $palette[$row->payment_status] ?? '#8993a4' }}"></i>{{ Str::headline($row->payment_status ?: 'Unknown') }} <strong>{{ number_format($row->aggregate) }}</strong></span>@empty<span class="ak-muted">No payment records yet.</span>@endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="ak-content-grid ak-content-grid-three">
        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Sales by location</h2><p>Based on shipping city in each order</p></div></div>
            @php $cityMax = max(1, (float) $byCity->max('revenue_total')); @endphp
            @forelse ($byCity as $city)
                <div class="ak-location-row"><div class="ak-location-label"><span>{{ $city->shipping_city }}</span><strong>{{ number_format($city->orders_count) }} orders</strong></div><div class="ak-location-track"><span style="width:{{ min(100, (float) $city->revenue_total / $cityMax * 100) }}%"></span></div><small>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $city->revenue_total, 2) }}</small></div>
            @empty
                <div class="ak-inline-empty"><strong>No location data</strong><p>Shipping city will appear when it is saved on an order.</p></div>
            @endforelse
        </article>
        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Top-selling products</h2><p>Units recorded in order items</p></div><a class="ak-text-link" href="{{ route('commerce-admin.products.index') }}">Catalog ↗</a></div>
            @forelse ($topProducts as $row)
                <div class="ak-ranking-row"><span class="ak-ranking-index">{{ $loop->iteration }}</span><span class="ak-ranking-name">{{ $row->product_name ?: 'Product removed from catalog' }}<small>{{ number_format((int) $row->units_sold) }} units</small></span><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $row->gross_sales, 2) }}</strong></div>
            @empty
                <div class="ak-inline-empty"><strong>No sales yet</strong><p>Order line items are used here; no sample sales are injected.</p></div>
            @endforelse
        </article>
        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Low stock</h2><p>Active variants at or below threshold</p></div><a class="ak-text-link" href="{{ route('commerce-admin.products.index', ['status'=>'low_stock']) }}">View all ↗</a></div>
            @forelse ($lowStock as $variant)
                <a class="ak-stock-row" href="{{ route('commerce-admin.products.show', $variant->product_id) }}"><span class="ak-stock-product">{{ $variant->product?->name ?: 'Product unavailable' }}<small>{{ $variant->sku ?: $variant->display_name }}</small></span><span class="ak-stock-count {{ $variant->stock <= 0 ? 'is-out' : 'is-low' }}">{{ (int) $variant->stock }} left</span></a>
            @empty
                <div class="ak-inline-empty"><strong>Stock looks good</strong><p>No active variants are currently below their configured threshold.</p></div>
            @endforelse
        </article>
    </section>
@endsection
