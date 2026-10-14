@extends('commerce-admin::layout')

@section('title', request('panel') === 'payments' ? 'Payment Dashboard' : 'E-Commerce Dashboard')

@section('content')
    @php
        $currency = config('commerce-admin.currency_symbol', '$');
        $maxRevenue = max(1, (float) collect($series)->max('value'));
        $maxOrders = max(1, (int) collect($series)->max('orders_count'));
        $revenuePoints = collect($series)->values()->map(fn ($point, $index) =>
            round(12 + ($index * 336 / max(1, count($series) - 1)), 1).','.round(148 - (((float) $point['value'] / $maxRevenue) * 124), 1)
        )->implode(' ');
        $ordersPoints = collect($series)->values()->map(fn ($point, $index) =>
            round(12 + ($index * 336 / max(1, count($series) - 1)), 1).','.round(148 - (((int) $point['orders_count'] / $maxOrders) * 105) - 7, 1)
        )->implode(' ');
        $paymentTotal = max(1, (int) $paymentBreakdown->sum('aggregate'));
        $palette = ['paid' => '#202228', 'pending' => '#9da2ab', 'failed' => '#d86b71', 'refunded' => '#d9dce2'];
        $offset = 0;
        $segments = [];
        foreach ($paymentBreakdown as $statusRow) {
            $pct = ((int) $statusRow->aggregate / $paymentTotal) * 100;
            $segments[] = ($palette[$statusRow->payment_status] ?? '#b7bbc3').' '.$offset.'% '.($offset + $pct).'%';
            $offset += $pct;
        }
        $cityMax = max(1, (float) $byCity->max('revenue_total'));
        $reviewMax = max(1, (int) max(array_values($reviewDistribution ?: [1])));
    @endphp

    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb">Dashboard <span>/</span> {{ request('panel') === 'payments' ? 'Payment Dashboard' : 'E-Commerce' }}</div>
            <h1>{{ request('panel') === 'payments' ? 'Payment Dashboard' : 'E-Commerce Dashboard' }}</h1>
            <p>A live view of revenue, customer retention, payment records and product reviews.</p>
        </div>
        <div class="ak-heading-actions">
            <span class="ak-date-chip">◷ Last 12 months</span>
            <a class="ak-button ak-button-dark" href="{{ route('commerce-admin.analytics.export') }}">Export CSV <span>↓</span></a>
        </div>
    </div>

    <section class="ak-content-grid ak-analytics-top">
        <article class="ak-panel ak-analytics-revenue-panel">
            <div class="ak-panel-heading">
                <div><h2>Total Revenue</h2><p>Paid order totals by month</p></div>
                <span class="ak-period-label">{{ config('commerce-admin.currency', 'USD') }}</span>
            </div>
            <div class="ak-analytics-main-value">
                <strong>{{ $currency }}{{ number_format((float) $revenue, 2) }}</strong>
                <span>All recorded paid orders</span>
            </div>
            <div class="ak-bar-chart ak-bar-chart-tall" role="img" aria-label="Paid revenue by month">
                @foreach ($series as $point)
                    <div class="ak-bar-column">
                        <span class="ak-bar-tooltip">{{ $currency }}{{ number_format((float) $point['value'], 0) }}</span>
                        <div class="ak-bar-track"><span style="height:{{ max(2, (((float) $point['value']) / $maxRevenue) * 100) }}%"></span></div>
                        <small>{{ $point['label'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="ak-chart-legend">
                <span><i class="ak-legend-dot"></i> Paid revenue</span>
                <span>Updated from the order database</span>
            </div>
        </article>

        <article class="ak-panel ak-retention-panel">
            <div class="ak-panel-heading">
                <div><h2>Returning Rate</h2><p>Identified customers with more than one order</p></div>
                <span class="ak-icon-chip">↗</span>
            </div>
            <div class="ak-analytics-main-value ak-retention-value">
                <strong>{{ number_format((float) $returningRate, 1) }}%</strong>
                <span>{{ number_format((int) $identifiedCustomers) }} identified customers</span>
            </div>
            <div class="ak-line-chart" role="img" aria-label="Monthly revenue and order trend">
                <svg viewBox="0 0 360 180" preserveAspectRatio="none" aria-hidden="true">
                    <line x1="10" y1="27" x2="350" y2="27"></line>
                    <line x1="10" y1="67" x2="350" y2="67"></line>
                    <line x1="10" y1="107" x2="350" y2="107"></line>
                    <line x1="10" y1="147" x2="350" y2="147"></line>
                    <polyline class="ak-line-revenue" points="{{ $revenuePoints }}"></polyline>
                    <polyline class="ak-line-orders" points="{{ $ordersPoints }}"></polyline>
                </svg>
            </div>
            <div class="ak-chart-legend">
                <span><i class="ak-legend-dot"></i> Revenue trend</span>
                <span><i class="ak-legend-dot ak-legend-dot-light"></i> Order volume</span>
            </div>
        </article>
    </section>

    <section class="ak-content-grid ak-content-grid-three ak-analytics-bottom">
        <article class="ak-panel">
            <div class="ak-panel-heading">
                <div><h2>Sales by Location</h2><p>Revenue grouped by shipping city</p></div>
            </div>
            @forelse ($byCity as $city)
                <div class="ak-location-row">
                    <div class="ak-location-label"><span>{{ $city->shipping_city }}</span><strong>{{ $currency }}{{ number_format((float) $city->revenue_total, 0) }}</strong></div>
                    <div class="ak-location-track"><span style="width:{{ min(100, ((float) $city->revenue_total / $cityMax) * 100) }}%"></span></div>
                    <div class="ak-location-meta"><span>{{ number_format((int) $city->orders_count) }} orders</span><span>{{ $cityMax > 0 ? number_format(((float) $city->revenue_total / $cityMax) * 100, 0) : 0 }}%</span></div>
                </div>
            @empty
                <div class="ak-compact-empty">Shipping-city data will appear when orders include a destination.</div>
            @endforelse
        </article>

        <article class="ak-panel">
            <div class="ak-panel-heading">
                <div><h2>Sales by Source</h2><p>Payment status across saved orders</p></div>
            </div>
            <div class="ak-source-chart">
                <div class="ak-donut" style="background:conic-gradient({{ implode(', ', $segments) ?: '#edf0f4 0% 100%' }})">
                    <div><strong>{{ number_format((int) $allTimeOrders) }}</strong><small>Orders</small></div>
                </div>
                <div class="ak-donut-legend">
                    @forelse ($paymentBreakdown as $row)
                        <span><i style="background:{{ $palette[$row->payment_status] ?? '#b7bbc3' }}"></i>{{ \Illuminate\Support\Str::headline($row->payment_status ?: 'Unknown') }} <strong>{{ number_format((int) $row->aggregate) }}</strong></span>
                    @empty
                        <span class="ak-muted">No payment records yet.</span>
                    @endforelse
                </div>
            </div>
            <div class="ak-source-footer"><span>Paid orders</span><strong>{{ number_format((int) $paidCount) }}</strong></div>
        </article>

        <article class="ak-panel ak-review-panel">
            <div class="ak-panel-heading">
                <div><h2>Customer Reviews</h2><p>Approved product ratings</p></div>
                <a class="ak-text-link" href="{{ route('commerce-admin.reviews.index') }}">View all <span>↗</span></a>
            </div>
            <div class="ak-review-summary">
                <strong>{{ number_format((float) $reviewAverage, 1) }}</strong>
                <div><div class="ak-review-stars" aria-label="{{ number_format((float) $reviewAverage, 1) }} out of 5 stars">★★★★★</div><small>{{ number_format((int) $reviewsCount) }} approved reviews</small></div>
            </div>
            @for ($rating = 5; $rating >= 1; $rating--)
                @php $ratingCount = (int) ($reviewDistribution[$rating] ?? 0); @endphp
                <div class="ak-rating-row">
                    <span>{{ $rating }} <span class="ak-star-mini">★</span></span>
                    <div><span style="width:{{ min(100, ($ratingCount / $reviewMax) * 100) }}%"></span></div>
                    <small>{{ number_format($ratingCount) }}</small>
                </div>
            @endfor
            @if ($pendingReviews > 0)
                <a class="ak-pending-review-link" href="{{ route('commerce-admin.reviews.index', ['status' => 'pending']) }}">{{ number_format((int) $pendingReviews) }} reviews waiting for moderation <span>→</span></a>
            @endif
        </article>
    </section>

    <section class="ak-content-grid ak-content-grid-main ak-analytics-details">
        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Top Products</h2><p>Best-selling catalog items by units</p></div><a class="ak-text-link" href="{{ route('commerce-admin.products.index') }}">Product list ↗</a></div>
            @if ($topProducts->isEmpty())
                <div class="ak-compact-empty">Product sales will appear after a saved order contains an item.</div>
            @else
                <div class="ak-table-scroll"><table class="ak-table">
                    <thead><tr><th>Product</th><th>Units sold</th><th>Gross sales</th></tr></thead>
                    <tbody>
                    @foreach ($topProducts as $item)
                        <tr><td class="ak-table-primary">{{ $item->product_name }}</td><td>{{ number_format((int) $item->units_sold) }}</td><td>{{ $currency }}{{ number_format((float) $item->gross_sales, 2) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </article>

        <article class="ak-panel">
            <div class="ak-panel-heading"><div><h2>Inventory Watch</h2><p>Active variants at or below their stock threshold</p></div><a class="ak-text-link" href="{{ route('commerce-admin.products.index', ['status' => 'low_stock']) }}">Review stock ↗</a></div>
            @forelse ($lowStock as $variant)
                <div class="ak-stock-row">
                    <div><strong>{{ $variant->product?->name ?: 'Product variant' }}</strong><small>{{ $variant->sku ?: 'No SKU' }}</small></div>
                    <span class="{{ (int) $variant->stock <= 0 ? 'is-out' : 'is-low' }}">{{ number_format((int) $variant->stock) }} left</span>
                </div>
            @empty
                <div class="ak-compact-empty">No active variants are currently below their stock thresholds.</div>
            @endforelse
        </article>
    </section>
@endsection
