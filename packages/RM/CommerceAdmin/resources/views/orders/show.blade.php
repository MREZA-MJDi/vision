@extends('commerce-admin::layout')

@section('title', 'سفارش ' . $model->order_number)

@section('content')
    @php
        $statusSteps = [
            'pending' => 0,
            'confirmed' => 0,
            'preparing' => 0,
            'shipped' => 1,
            'out_for_delivery' => 1,
            'delivered' => 2,
            'cancelled' => -1,
            'returned' => -1,
        ];
        $step = $statusSteps[$model->status] ?? 0;
        $isTerminal = in_array($model->status, ['cancelled', 'returned'], true);
        $currency = config('commerce-admin.currency_symbol', '$');
    @endphp

    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb"><a href="{{ route('commerce-admin.orders.index') }}">سفارش‌ها</a> <span>/</span> جزئیات سفارش</div>
            <h1>Order #{{ $model->order_number }}</h1>
            <p>Placed on {{ optional($model->placed_at ?: $model->created_at)->format('M d, Y \a\t H:i') }}</p>
        </div>
        <div class="ak-heading-actions">
            <a class="ak-button ak-button-light" href="{{ route('commerce-admin.orders.index') }}">← همهٔ سفارش‌ها</a>
            <button class="ak-button ak-button-dark" type="button" onclick="window.print()">چاپ سفارش <span>⌑</span></button>
        </div>
    </div>

    <div class="ak-order-detail-grid">
        <div class="ak-form-main">
            <section class="ak-panel ak-order-info-panel">
                <div class="ak-panel-heading">
                    <div><h2>اطلاعات مشتری</h2><p>اطلاعات تماس ذخیره‌شده در سفارش.</p></div>
                    <span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($model->status) }}">{{ config('commerce-admin.status_labels.' . $model->status, \Illuminate\Support\Str::headline($model->status)) }}</span>
                </div>
                <div class="ak-customer-detail-card">
                    <span class="ak-avatar ak-avatar-large">{{ strtoupper(substr($model->customer_name ?: 'C', 0, 1)) }}</span>
                    <div>
                        <strong>{{ $model->customer_name ?: $model->user?->name ?: 'Guest customer' }}</strong>
                        <span>{{ $model->customer_email ?: 'No email saved' }}</span>
                        <span>{{ $model->customer_phone ?: 'No phone saved' }}</span>
                    </div>
                </div>
                <div class="ak-two-column-info">
                    <div><small>نشانی ارسال</small><strong>{{ $model->shipping_address ?: 'No address saved' }}</strong><span>{{ collect([$model->shipping_city, $model->shipping_province, $model->postal_code])->filter()->implode(', ') }}</span></div>
                    <div><small>حساب مشتری</small><strong>{{ $model->user?->name ?: ($model->user_id ? 'Linked account' : 'Guest order') }}</strong><span>{{ $model->user?->email ?: 'No linked account' }}</span></div>
                </div>
                @if($model->customer_note)
                    <div class="ak-help-note"><strong>یادداشت مشتری</strong><p>{{ $model->customer_note }}</p></div>
                @endif
            </section>

            <section class="ak-panel ak-order-items-panel">
                <div class="ak-panel-heading">
                    <div><h2>اقلام سفارش</h2><p>{{ number_format((int) $items->sum('quantity')) }} units across {{ $items->count() }} line items</p></div>
                </div>
                @if($items->isEmpty())
                    <div class="ak-empty-state"><strong>قلمی ثبت نشده است</strong><p>برای این سفارش اطلاعات اقلام ثبت نشده است.</p></div>
                @else
                    <div class="ak-table-scroll">
                        <table class="ak-table">
                            <thead><tr><th>Product</th><th>شناسه SKU / تنوع</th><th>تعداد</th><th>قیمت واحد</th><th>Total</th></tr></thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <div class="ak-product-cell">
                                            @if($item->product_image)
                                                <img src="{{ $item->product_image }}" alt="{{ $item->product_name ?: 'Product image' }}" loading="lazy">
                                            @else
                                                <span class="ak-product-thumb-placeholder" aria-hidden="true">▦</span>
                                            @endif
                                            <strong>{{ $item->product_name ?: 'Product item' }}</strong>
                                        </div>
                                    </td>
                                    <td><span class="ak-mono">{{ $item->sku ?: '—' }}</span><small class="ak-table-subtext">{{ $item->variant_name ?: 'Default variant' }}</small></td>
                                    <td>{{ number_format((int) $item->quantity) }}</td>
                                    <td>{{ $currency }}{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td><strong>{{ $currency }}{{ number_format((float) $item->line_total, 2) }}</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="ak-panel ak-delivery-panel">
                <div class="ak-panel-heading"><div><h2>وضعیت ارسال</h2><p>تغییر وضعیت اعتبارسنجی می‌شود و زمان آن در سفارش ثبت می‌شود.</p></div></div>
                @if($isTerminal)
                    <div class="ak-terminal-notice">
                        <strong>{{ config('commerce-admin.status_labels.' . $model->status, \Illuminate\Support\Str::headline($model->status)) }}</strong>
                        <p>بازگرداندن موجودی برای این وضعیت نهایی قبلاً ثبت شده است. این سفارش دوباره باز نمی‌شود.</p>
                    </div>
                @else
                    <div class="ak-delivery-steps">
                        <div class="ak-delivery-step is-complete"><span>✓</span><div><strong>ثبت سفارش</strong><small>{{ optional($model->placed_at ?: $model->created_at)->format('M d, Y · H:i') }}</small></div></div>
                        <div class="ak-delivery-connector {{ $step >= 1 ? 'is-complete' : '' }}"></div>
                        <div class="ak-delivery-step {{ $step >= 1 ? 'is-complete' : '' }}"><span>{{ $step >= 1 ? '✓' : '2' }}</span><div><strong>Shipped</strong><small>{{ $model->shipped_at?->format('M d, Y · H:i') ?: 'Not shipped yet' }}</small></div></div>
                        <div class="ak-delivery-connector {{ $step >= 2 ? 'is-complete' : '' }}"></div>
                        <div class="ak-delivery-step {{ $step >= 2 ? 'is-complete' : '' }}"><span>{{ $step >= 2 ? '✓' : '3' }}</span><div><strong>Delivered</strong><small>{{ $model->delivered_at?->format('M d, Y · H:i') ?: 'Awaiting delivery' }}</small></div></div>
                    </div>
                    <form method="POST" action="{{ route('commerce-admin.orders.status', $model->getKey()) }}" class="ak-inline-status-form">
                        @csrf @method('PATCH')
                        <label class="ak-inline-field">
                            <span>Update order status</span>
                            <select name="status">
                                @foreach($availableStatuses as $status)
                                    <option value="{{ $status }}" @selected($model->status === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="ak-button ak-button-dark ak-button-sm" type="submit">ذخیرهٔ وضعیت</button>
                    </form>
                @endif
            </section>
        </div>

        <aside class="ak-form-aside">
            <section class="ak-panel ak-aside-panel">
                <div class="ak-panel-heading"><div><h2>خلاصهٔ سفارش</h2><p>مبالغ ذخیره‌شده در رکورد سفارش.</p></div></div>
                <div class="ak-summary-lines">
                    <div><span>جمع جزء</span><strong>{{ $currency }}{{ number_format((float) $model->subtotal, 2) }}</strong></div>
                    <div><span>تخفیف</span><strong class="{{ (float) $model->discount > 0 ? 'ak-negative-value' : '' }}">−{{ $currency }}{{ number_format((float) $model->discount, 2) }}</strong></div>
                    <div><span>Shipping</span><strong>{{ $currency }}{{ number_format((float) $model->shipping_cost, 2) }}</strong></div>
                    <div><span>Tax</span><strong>{{ $currency }}{{ number_format((float) $model->tax, 2) }}</strong></div>
                    <div class="ak-summary-total"><span>Total</span><strong>{{ $currency }}{{ number_format((float) $model->total, 2) }}</strong></div>
                </div>
                <div class="ak-payment-detail">
                    <span class="ak-payment-icon" aria-hidden="true">▤</span>
                    <div><strong>روش پرداخت</strong><small>{{ \Illuminate\Support\Str::headline($model->payment_method ?: 'Not recorded') }}</small></div>
                    <span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($model->payment_status) }}">{{ config('commerce-admin.status_labels.' . $model->payment_status, \Illuminate\Support\Str::headline($model->payment_status)) }}</span>
                </div>
                @if($model->paid_at)<p class="ak-help-note">Marked paid on {{ $model->paid_at->format('M d, Y H:i') }}</p>@endif
                <form method="POST" action="{{ route('commerce-admin.orders.payment', $model->getKey()) }}" class="ak-payment-form">
                    @csrf @method('PATCH')
                    <label class="ak-field">
                        <span>وضعیت پرداخت</span>
                        <select name="payment_status">
                            @foreach($availablePaymentStatuses as $paymentStatus)
                                <option value="{{ $paymentStatus }}" @selected($model->payment_status === $paymentStatus)>{{ \Illuminate\Support\Str::headline($paymentStatus) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="ak-button ak-button-light ak-button-wide" type="submit">به‌روزرسانی وضعیت پرداخت</button>
                    <small class="ak-help-note">This records a manual payment status; it does not call a payment gateway.</small>
                </form>
                @if($model->payments->isNotEmpty())
                    <div class="ak-detail-list">
                        <span>سوابق پرداخت<strong>{{ $model->payments->count() }}</strong></span>
                        @foreach($model->payments as $payment)
                            <span>{{ \Illuminate\Support\Str::headline($payment->gateway) }} · {{ \Illuminate\Support\Str::headline($payment->status) }}<strong>{{ $currency }}{{ number_format((float) $payment->amount, 2) }}</strong></span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="ak-panel ak-aside-panel">
                <div class="ak-panel-heading"><div><h2>اطلاعات تکمیلی سفارش</h2></div></div>
                <div class="ak-detail-list">
                    <span>Order ID<strong>#{{ $model->getKey() }}</strong></span>
                    <span>ثبت شده<strong>{{ optional($model->placed_at ?: $model->created_at)->format('M d, Y') }}</strong></span>
                    <span>به‌روزرسانی<strong>{{ $model->updated_at?->diffForHumans() ?: '—' }}</strong></span>
                    <span>کد رهگیری<strong>{{ $model->tracking_code ?: 'Not assigned' }}</strong></span>
                </div>
            </section>
        </aside>
    </div>
@endsection
