@extends('layouts.store')

@section('title', 'My Account — VISION')

@section('content')
<main class="store-main commerce-page">
    <div class="commerce-page-heading">
        <div>
            <p class="store-eyebrow">YOUR VISION</p>
            <h1>My account<span>.</span></h1>
            <p>Welcome back, {{ $customer->name }}. Your order history lives here.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="commerce-button commerce-button--quiet" type="submit">Sign out</button>
        </form>
    </div>

    @if(session('status'))<p class="commerce-alert commerce-alert--success" role="status">{{ session('status') }}</p>@endif

    <section class="commerce-panel commerce-account-panel">
        <div class="commerce-panel-heading"><h2>Your orders</h2><span>{{ $orders->total() }} total</span></div>
        @if($orders->isEmpty())
            <div class="commerce-empty commerce-empty--compact">
                <h2>No orders yet.</h2>
                <p>When you place an order while signed in, it will appear here.</p>
                <a class="commerce-button commerce-button--primary" href="{{ route('products.index') }}">Explore the edit ↗</a>
            </div>
        @else
            <div class="commerce-table-wrap">
                <table class="commerce-table">
                    <thead><tr><th>Order</th><th>Placed</th><th>Items</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ optional($order->placed_at ?: $order->created_at)->format('M d, Y') }}</td>
                            <td>{{ (int) $order->items_count }}</td>
                            <td><span class="commerce-status">{{ \Illuminate\Support\Str::headline($order->status) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::headline($order->payment_status) }}</td>
                            <td><strong>@money($order->total)</strong></td>
                            <td><a class="commerce-text-link" href="{{ route('account.orders.show', $order->getKey()) }}">Details ↗</a></td>
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
