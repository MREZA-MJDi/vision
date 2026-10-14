@extends('commerce-admin::layout')

@section('title', 'Classic Dashboard')

@section('content')
    @php
        $currency = config('commerce-admin.currency_symbol', '$');
        $revenueMax = max(1, (float) collect($series)->max('value'));
        $ordersMax = max(1, (int) collect($series)->max('orders_count'));
        $revenuePoints = collect($series)->values()->map(fn ($point, $index) =>
            round(10 + ($index * 340 / max(1, count($series) - 1)), 1).','.round(85 - (((float) $point['value'] / $revenueMax) * 66), 1)
        )->implode(' ');
        $revenueMiniPoints = collect($series)->values()->map(fn ($point, $index) =>
            round(3 + ($index * 354 / max(1, count($series) - 1)), 1).','.round(52 - (((float) $point['value'] / $revenueMax) * 42), 1)
        )->implode(' ');
        $ordersPoints = collect($series)->values()->map(fn ($point, $index) =>
            round(10 + ($index * 340 / max(1, count($series) - 1)), 1).','.round(85 - (((int) $point['orders_count'] / $ordersMax) * 58), 1)
        )->implode(' ');
    @endphp

    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb">Dashboards <span>/</span> Classic Dashboard</div>
            <h1>Dashboard</h1>
            <p>Your store at a glance, with live figures from the commerce database.</p>
        </div>
        <div class="ak-heading-actions">
            <span class="ak-date-chip"><span>◷</span> {{ now()->startOfMonth()->format('M d') }} – {{ now()->format('M d, Y') }}</span>
            <a class="ak-button ak-button-dark" href="{{ route('commerce-admin.analytics') }}">View analytics <span>↗</span></a>
        </div>
    </div>

    <section class="ak-classic-top-grid">
        <article class="ak-panel ak-team-panel">
            <div class="ak-panel-heading">
                <div><h2>Customers</h2><p>Recent customer accounts</p></div>
                <span class="ak-count-chip">{{ number_format((int) $customersCount) }} total</span>
            </div>
            @forelse($recentCustomers as $customer)
                <div class="ak-team-row">
                    <span class="ak-avatar ak-team-avatar">{{ strtoupper(mb_substr($customer->name ?: $customer->email ?: 'C', 0, 1)) }}</span>
                    <div class="ak-team-identity">
                        <strong>{{ $customer->name ?: 'Customer' }}</strong>
                        <small>{{ $customer->email ?: 'No email saved' }}</small>
                    </div>
                    <a class="ak-team-action" href="{{ route('commerce-admin.orders.index', ['q' => $customer->email ?: $customer->name]) }}">Orders</a>
                </div>
            @empty
                <div class="ak-compact-empty">Registered customers will appear here when they join your store.</div>
            @endforelse
            <div class="ak-team-foot"><span>{{ number_format((int) $customersCount) }} customer accounts</span><a href="{{ route('commerce-admin.orders.index') }}">View orders ↗</a></div>
        </article>

        <article class="ak-panel ak-subscription-panel">
            <div class="ak-panel-heading">
                <div><h2>Orders</h2><p>Order volume over the last 12 months</p></div>
                <a class="ak-icon-button" href="{{ route('commerce-admin.orders.index') }}" aria-label="View orders">↗</a>
            </div>
            <div class="ak-classic-metric">{{ number_format((int) $orderCount) }}</div>
            <div class="ak-classic-change"><span class="ak-live-indicator"></span>{{ number_format((int) $pendingOrders) }} currently in progress</div>
            <div class="ak-subscription-bars" role="img" aria-label="Monthly order counts">
                @foreach($series as $point)
                    <div class="ak-subscription-bar-column" title="{{ $point['full_label'] }}: {{ number_format((int) $point['orders_count']) }} orders">
                        <span style="height:{{ max(4, ((int) $point['orders_count'] / $ordersMax) * 100) }}%"></span>
                        <small>{{ $point['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="ak-panel ak-total-revenue-panel">
            <div class="ak-panel-heading">
                <div><h2>Total Revenue</h2><p>Paid orders only</p></div>
                <a class="ak-icon-button" href="{{ route('commerce-admin.analytics') }}" aria-label="View revenue analytics">↗</a>
            </div>
            <div class="ak-classic-metric">{{ $currency }}{{ number_format((float) $revenue, 2) }}</div>
            <div class="ak-classic-change"><span class="ak-live-indicator"></span>Excluding cancelled and returned orders</div>
            <div class="ak-mini-line-chart" role="img" aria-label="Monthly revenue trend">
                <svg viewBox="0 0 360 62" preserveAspectRatio="none" aria-hidden="true">
                    <line x1="2" y1="54" x2="358" y2="54"></line>
                    <polyline points="{{ $revenueMiniPoints }}"></polyline>
                </svg>
            </div>
        </article>
    </section>

    <section class="ak-classic-lower-grid">
        <article class="ak-panel ak-activity-panel">
            <div class="ak-panel-heading">
                <div><h2>Store Activity</h2><p>Monthly revenue and order volume, based on saved orders</p></div>
                <a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.analytics.export') }}">Export CSV ↓</a>
            </div>
            <div class="ak-activity-summary">
                <div><small>Total revenue</small><strong>{{ $currency }}{{ number_format((float) $revenue, 2) }}</strong></div>
                <div><small>Total orders</small><strong>{{ number_format((int) $orderCount) }}</strong></div>
                <div><small>Pending / in progress</small><strong>{{ number_format((int) $pendingOrders) }}</strong></div>
            </div>
            <div class="ak-classic-line-chart" role="img" aria-label="Revenue and order activity over the last 12 months">
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
                <span><i class="ak-legend-dot"></i> Revenue</span>
                <span><i class="ak-legend-dot ak-legend-dot-light"></i> Orders</span>
                <a href="{{ route('commerce-admin.analytics') }}">Full report →</a>
            </div>
        </article>

        <article class="ak-panel ak-latest-orders-panel">
            <div class="ak-panel-heading">
                <div><h2>Latest Orders</h2><p>Recent transactions from your store</p></div>
                <a class="ak-text-link" href="{{ route('commerce-admin.orders.index') }}">View all ↗</a>
            </div>
            @forelse($recentOrders as $order)
                <a class="ak-latest-order-row" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}">
                    <span class="ak-latest-order-avatar">{{ strtoupper(mb_substr($order->customer_name ?: $order->user?->name ?: 'C', 0, 1)) }}</span>
                    <span class="ak-latest-order-identity">
                        <strong>{{ $order->customer_name ?: $order->user?->name ?: 'Guest customer' }}</strong>
                        <small>#{{ $order->order_number }} · {{ optional($order->placed_at ?: $order->created_at)->format('M d') }}</small>
                    </span>
                    <span class="ak-latest-order-total">{{ $currency }}{{ number_format((float) $order->total, 2) }}<small class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($order->status) }}">{{ \Illuminate\Support\Str::headline($order->status) }}</small></span>
                </a>
            @empty
                <div class="ak-compact-empty">New orders will be listed here as soon as they are saved.</div>
            @endforelse
            <div class="ak-latest-orders-foot">
                <div><span class="ak-mini-status-dot"></span><span>{{ number_format((int) $lowStockCount) }} variants need stock attention</span></div>
                <a href="{{ route('commerce-admin.products.index', ['status' => 'low_stock']) }}">Check inventory →</a>
            </div>
        </article>
    </section>
@endsection
