@extends('commerce-admin::layout')

@section('title', 'Orders')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">E-Commerce <span>/</span> Order list</div><h1>Orders</h1><p>Review customer orders, fulfillment states and payment records.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.orders.create') }}"><span>＋</span> Create order</a></div>
    </div>

    <section class="ak-panel ak-orders-list-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>Orders <span class="ak-count-chip">{{ $orders->total() }}</span></h2><p>All saved orders from the commerce database</p></div>
            <div class="ak-toolbar"><button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ Filters</button><button class="ak-button ak-button-light ak-button-sm" type="button" data-columns-toggle>☷ Columns</button><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.orders.create') }}">＋ Create order</a></div>
        </div>

        <div class="ak-order-tabs" role="tablist" aria-label="Filter orders by status">
            <a class="{{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index') }}">All <span>{{ number_format($stats['all']) }}</span></a>
            <a class="{{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'pending']) }}">Pending <span>{{ number_format($stats['pending']) }}</span></a>
            <a class="{{ $status === 'shipped' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'shipped']) }}">Shipped <span>{{ number_format($stats['shipped']) }}</span></a>
            <a class="{{ $status === 'delivered' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'delivered']) }}">Delivered <span>{{ number_format($stats['delivered']) }}</span></a>
            <a class="{{ $status === 'cancelled' ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index', ['status'=>'cancelled']) }}">Cancelled / returned <span>{{ number_format($stats['cancelled']) }}</span></a>
        </div>

        <form class="ak-filter-bar {{ $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.orders.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>Search orders</span><input type="search" name="q" value="{{ $search }}" placeholder="Order number, customer, email or phone"></label>
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">Search</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.orders.index') }}">Reset</a></div>
        </form>

        <div class="ak-column-popover" data-columns-panel hidden><label><input type="checkbox" data-column-check="product" checked> Product</label><label><input type="checkbox" data-column-check="customer" checked> Customer</label><label><input type="checkbox" data-column-check="type" checked> Type</label><label><input type="checkbox" data-column-check="price" checked> Total</label><label><input type="checkbox" data-column-check="date" checked> Date</label><label><input type="checkbox" data-column-check="status" checked> Status</label></div>

        @if($orders->isEmpty())
            <div class="ak-empty-state"><span>▤</span><strong>No orders found</strong><p>Try another status or search term, or create a real order against available stock.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.orders.create') }}">Create order</a></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table ak-orders-table">
                <thead><tr><th>Order</th><th data-column="product">Product</th><th data-column="customer">Customer</th><th data-column="type">Type</th><th data-column="price">Price</th><th data-column="date">Date</th><th data-column="status">Status</th><th></th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    @php $firstItem = $order->admin_primary_item ?? $order->items->first(); @endphp
                    <tr>
                        <td><a class="ak-table-primary" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}">#{{ $order->order_number }}</a><small class="ak-table-subtext">ID {{ $order->getKey() }}</small></td>
                        <td data-column="product"><div class="ak-product-cell">@if($firstItem?->product_image)<img src="{{ $firstItem->product_image }}" alt="{{ $firstItem->product_name }}">@else<span class="ak-product-thumb-placeholder">▦</span>@endif<div><strong>{{ $firstItem?->product_name ?: 'Order without item snapshot' }}</strong><small>{{ number_format((int)($order->admin_item_count ?? $order->items->sum('quantity'))) }} total units</small></div></div></td>
                        <td data-column="customer"><div class="ak-customer-cell"><span class="ak-avatar ak-avatar-tiny">{{ strtoupper(substr($order->customer_name ?: 'C', 0, 1)) }}</span><span><strong>{{ $order->customer_name ?: $order->user?->name ?: 'Guest customer' }}</strong><small>{{ $order->customer_email ?: $order->customer_phone ?: 'No contact saved' }}</small></span></div></td>
                        <td data-column="type"><span class="ak-type-label">{{ $order->status === 'returned' ? 'Return' : ($order->discount > 0 ? 'Discounted sale' : 'Sale') }}</span></td>
                        <td data-column="price"><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)$order->total, 2) }}</strong><small class="ak-table-subtext">{{ \Illuminate\Support\Str::headline($order->payment_status ?: 'pending') }}</small></td>
                        <td data-column="date">{{ optional($order->placed_at ?: $order->created_at)->format('M d, Y') }}</td>
                        <td data-column="status"><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($order->status) }}">{{ \Illuminate\Support\Str::headline($order->status) }}</span></td>
                        <td><a class="ak-row-action" href="{{ route('commerce-admin.orders.show', $order->getKey()) }}" aria-label="Open order details">···</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders</span>{{ $orders->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
