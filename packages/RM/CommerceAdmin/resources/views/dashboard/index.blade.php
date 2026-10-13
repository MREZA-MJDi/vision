@extends('commerce-admin::layout')

@section('title', 'Dashboard')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">Dashboards <span>/</span> Overview</div><h1>Dashboard</h1><p>Live snapshot of your store, catalog, inventory and orders.</p></div>
        <div class="ak-heading-actions">
            <span class="ak-date-chip"><span>◷</span> {{ now()->startOfMonth()->format('M d') }} – {{ now()->format('M d, Y') }}</span>
            <a class="ak-button ak-button-dark" href="{{ route('commerce-admin.analytics') }}">View analytics <span>↗</span></a>
        </div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-three">
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">♙</span><span class="ak-stat-kicker">CUSTOMERS</span><span class="ak-stat-menu">···</span></div>
            <div class="ak-stat-value">{{ number_format($customersCount) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Real customer accounts</span><span class="ak-mini-badge">Live</span></div>
            <div class="ak-stat-spark"><span style="height:28%"></span><span style="height:40%"></span><span style="height:34%"></span><span style="height:55%"></span><span style="height:45%"></span><span style="height:68%"></span><span style="height:61%"></span><span style="height:82%"></span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">▦</span><span class="ak-stat-kicker">PRODUCTS</span><span class="ak-stat-menu">···</span></div>
            <div class="ak-stat-value">{{ number_format($productsCount) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">{{ number_format($activeProducts) }} currently active</span><span class="ak-mini-badge">{{ number_format($lowStockCount) }} low stock</span></div>
            <div class="ak-stat-progress"><span style="width:{{ $productsCount ? min(100, $activeProducts / $productsCount * 100) : 0 }}%"></span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">$</span><span class="ak-stat-kicker">PAID REVENUE</span><span class="ak-stat-menu">···</span></div>
            <div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $revenue, 2) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Paid orders, excluding cancelled/returned</span><a class="ak-stat-link" href="{{ route('commerce-admin.analytics') }}">Details ↗</a></div>
            <div class="ak-stat-line"><svg viewBox="0 0 260 48" preserveAspectRatio="none" role="img" aria-label="Monthly paid revenue trend"><polyline points="@php $maxValue = max(array_column($series, 'value')) ?: 1; $pts=[]; foreach ($series as $i=>$point) { $pts[] = round($i * (260 / max(1, count($series)-1)), 1).','.round(42 - ($point['value']/$maxValue)*34, 1); } echo e(implode(' ', $pts)); @endphp" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        </article>
    </section>

    <section class="ak-content-grid ak-content-grid-main">
        <article class="ak-panel ak-revenue-panel">
            <div class="ak-panel-heading"><div><h2>Revenue overview</h2><p>Paid order totals by month</p></div><a class="ak-text-link" href="{{ route('commerce-admin.analytics') }}">Full report ↗</a></div>
            <div class="ak-chart-summary"><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $revenue, 2) }}</strong><span class="ak-stat-note">All-time paid revenue</span></div>
            @php $maxValue = max(array_column($series, 'value')) ?: 1; @endphp
            <div class="ak-bar-chart" role="img" aria-label="Monthly paid revenue bar chart">
                @foreach ($series as $point)
                    <div class="ak-bar-column">
                        <span class="ak-bar-tooltip">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format($point['value'], 2) }}</span>
                        <div class="ak-bar-track"><span style="height:{{ max(2, ($point['value'] / $maxValue) * 100) }}%"></span></div>
                        <small>{{ $point['label'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="ak-chart-legend"><span><i class="ak-legend-dot"></i> Paid revenue</span><span>Last 8 months · {{ config('commerce-admin.currency', 'USD') }}</span></div>
        </article>

        <article class="ak-panel ak-orders-panel">
            <div class="ak-panel-heading"><div><h2>Order activity</h2><p>Current workload</p></div><a class="ak-icon-button" href="{{ route('commerce-admin.orders.index') }}" aria-label="View all orders">↗</a></div>
            <div class="ak-order-ring-wrap">
                <div class="ak-ring" style="--ring-pct:{{ $orderCount ? min(100, ($pendingOrders / $orderCount) * 100) : 0 }}%"><div><strong>{{ number_format($orderCount) }}</strong><small>Total orders</small></div></div>
                <div class="ak-ring-key"><span><i class="ak-dot ak-dot-blue"></i> In progress <strong>{{ number_format($pendingOrders) }}</strong></span><span><i class="ak-dot ak-dot-gray"></i> Other statuses <strong>{{ number_format(max(0, $orderCount - $pendingOrders)) }}</strong></span></div>
            </div>
            <div class="ak-panel-bottom-link"><a href="{{ route('commerce-admin.orders.index', ['status' => 'pending']) }}">Review pending orders <span>→</span></a></div>
        </article>
    </section>

    <section class="ak-panel ak-table-panel">
        <div class="ak-panel-heading"><div><h2>Recent orders</h2><p>Latest orders recorded in your database</p></div><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.orders.index') }}">View all orders <span>↗</span></a></div>
        @if ($recentOrders->isEmpty())
            <div class="ak-empty-state"><span>▤</span><strong>No orders yet</strong><p>New orders will appear here once they are saved in the store.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.orders.create') }}">Create an order</a></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($recentOrders as $order)
                    <tr>
                        <td><a class="ak-table-primary" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}">#{{ $order->order_number }}</a></td>
                        <td><div class="ak-customer-cell"><span class="ak-avatar ak-avatar-tiny">{{ strtoupper(substr($order->customer_name ?: 'C', 0, 1)) }}</span><span><strong>{{ $order->customer_name ?: $order->user?->name ?: 'Guest customer' }}</strong><small>{{ $order->customer_email ?: 'No email saved' }}</small></span></div></td>
                        <td>{{ optional($order->placed_at ?: $order->created_at)->format('M d, Y') }}</td>
                        <td>{{ $order->items->sum('quantity') }}</td>
                        <td class="ak-table-primary">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $order->total, 2) }}</td>
                        <td><span class="ak-status-pill status-{{ Str::slug($order->status) }}">{{ Str::headline($order->status) }}</span></td>
                        <td><a class="ak-row-action" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}" aria-label="Open order">···</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="ak-panel ak-table-panel">
        <div class="ak-panel-heading"><div><h2>Recently added products</h2><p>Latest catalog changes</p></div><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.products.index') }}">Product list <span>↗</span></a></div>
        @if ($recentProducts->isEmpty())
            <div class="ak-empty-state"><span>▦</span><strong>Your catalog is empty</strong><p>Add a product to start managing prices, variants and stock.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.products.create') }}">Add product</a></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>Product</th><th>Category</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody>
            @foreach ($recentProducts as $item)
                @php $variant = $item->variants->firstWhere('is_active', true) ?: $item->variants->first(); $asset = $item->galleryMedia->first(); @endphp
                <tr>
                    <td><div class="ak-product-cell">@if($asset?->url)<img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $item->name }}">@else<span class="ak-product-thumb-placeholder">▦</span>@endif<a class="ak-table-primary" href="{{ route('commerce-admin.products.show', $item->getKey()) }}">{{ $item->name }}</a></div></td>
                    <td>{{ $item->category?->name ?: 'Uncategorized' }}</td><td>{{ $variant?->sku ?: '—' }}</td>
                    <td>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) ($variant?->sale_price ?? $variant?->price ?? 0), 2) }}</td>
                    <td>{{ number_format((int) $item->variants->sum('stock')) }}</td>
                    <td><span class="ak-status-pill {{ $item->is_active ? 'status-active' : 'status-draft' }}">{{ $item->is_active ? 'Active' : 'Draft' }}</span></td>
                    <td><a class="ak-row-action" href="{{ route('commerce-admin.products.show', $item->getKey()) }}">···</a></td>
                </tr>
            @endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
