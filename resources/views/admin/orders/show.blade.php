@extends('layouts.admin')

@section('title', 'جزئیات سفارش')
@section('page-title', 'جزئیات سفارش')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    margin-bottom:8px;
                    font-size:13px;
                "
            >

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="admin-muted"
                    style="text-decoration:none;"
                >
                    سفارش‌ها
                </a>

                <span class="admin-muted">
                    /
                </span>

                <span class="admin-muted">
                    سفارش #{{ $order->order_number ?? $order->id }}
                </span>

            </div>


            <h1 class="admin-page-head__title">
                سفارش #{{ $order->order_number ?? $order->id }}
            </h1>


            <p class="admin-page-head__text">
                جزئیات سفارش، مشتری، اقلام و وضعیت پرداخت
            </p>

        </div>


        <a
            href="{{ route('admin.orders.index') }}"
            class="admin-btn admin-btn--ghost"
        >
            بازگشت به سفارش‌ها
        </a>

    </div>


    {{-- =====================================================
        ORDER SUMMARY
    ====================================================== --}}

    <div class="admin-dashboard-stats">

        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                مبلغ سفارش
            </div>

            <div class="admin-stat-card__value">
                {{ number_format((float) $order->total) }}
            </div>

            <div class="admin-stat-card__meta">
                تومان
            </div>

        </div>


        @php

            $orderStatusClass = match ($order->status) {
                'pending' => 'warning',
                'confirmed' => 'info',
                'preparing' => 'info',
                'shipped' => 'info',
                'delivered' => 'success',
                'cancelled' => 'danger',
                'returned' => 'neutral',
                default => 'neutral',
            };

            $paymentStatusClass = match ($order->payment_status) {
                'paid' => 'success',
                'pending' => 'warning',
                'failed' => 'danger',
                'refunded' => 'neutral',
                default => 'neutral',
            };

            $placedAt = $order->placed_at
                ?? $order->created_at;

        @endphp


        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                نوع سفارش
            </div>

            <div style="margin-top:10px;"></div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                وضعیت سفارش
            </div>

            <div style="margin-top:10px;">

                <span
                    class="admin-badge admin-badge--{{ $orderStatusClass }}"
                >
                    {{ $statusNames[$order->status] ?? $order->status }}
                </span>

            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                وضعیت پرداخت
            </div>

            <div style="margin-top:10px;">

                <span
                    class="admin-badge admin-badge--{{ $paymentStatusClass }}"
                >
                    {{
                        $paymentStatusNames[$order->payment_status]
                        ?? $order->payment_status
                    }}
                </span>

            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                تاریخ ثبت
            </div>

            <div class="admin-stat-card__value" style="font-size:18px;">

                @if($placedAt)
                    {{ $placedAt->format('Y/m/d H:i') }}
                @else
                    —
                @endif

            </div>

        </div>

    </div>


    {{-- =====================================================
        MAIN CONTENT
    ====================================================== --}}

    <div class="admin-dashboard-grid admin-dashboard-grid--equal">


        {{-- =================================================
            ORDER ITEMS
        ================================================== --}}

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h2 class="admin-card-title">
                        اقلام سفارش
                    </h2>

                    <p class="admin-card-description">
                        {{ number_format($order->items->count()) }}
                        قلم
                    </p>

                </div>

            </div>


            @if($order->items->count())

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>

                        <tr>

                            <th>
                                محصول
                            </th>

                            <th>
                                واریانت
                            </th>

                            <th>
                                تعداد
                            </th>

                            <th>
                                قیمت واحد
                            </th>

                            <th>
                                مبلغ
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        @foreach($order->items as $item)

                            @php

                                $productName =
                                    $item->product_name
                                    ?? $item->product?->name
                                    ?? 'محصول حذف‌شده';

                                $variantName =
                                    $item->variant_name
                                    ?? $item->productVariant?->size
                                    ?? $item->productVariant?->color
                                    ?? $item->productVariant?->sku
                                    ?? '—';

                                $lineTotal =
                                    $item->line_total
                                    ?? $item->total
                                    ?? (
                                        (float) $item->unit_price
                                        * (int) $item->quantity
                                    );

                            @endphp


                            <tr>

                                <td>

                                    <div class="admin-product-name">
                                        {{ $productName }}
                                    </div>

                                </td>


                                <td>

                                    @if($variantName !== '—')

                                        <span class="admin-muted">
                                            {{ $variantName }}
                                        </span>

                                    @else

                                        <span class="admin-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <span class="admin-price">
                                        {{ number_format((int) $item->quantity) }}
                                    </span>

                                </td>


                                <td>

                                    <div class="admin-price">

                                        {{ number_format(
                                            (float) $item->unit_price
                                        ) }}

                                    </div>

                                    <div class="admin-muted">
                                        تومان
                                    </div>

                                </td>


                                <td>

                                    <div class="admin-price">

                                        {{ number_format(
                                            (float) $lineTotal
                                        ) }}

                                    </div>

                                    <div class="admin-muted">
                                        تومان
                                    </div>

                                </td>

                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="admin-empty">

                    <div class="admin-empty__icon">
                        —
                    </div>

                    <h3 class="admin-empty__title">
                        آیتمی برای این سفارش وجود ندارد
                    </h3>

                </div>

            @endif

        </div>


        {{-- =================================================
            CUSTOMER + ORDER MANAGEMENT
        ================================================== --}}

        <div>


            {{-- CUSTOMER --}}

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h2 class="admin-card-title">
                            اطلاعات مشتری
                        </h2>

                        <p class="admin-card-description">
                            اطلاعات ثبت‌شده برای این سفارش
                        </p>

                    </div>

                </div>


                <div class="admin-card-body">

                    @php

                        $customerName =
                            $order->user?->name
                            ?? $order->customer_name
                            ?? 'مهمان';

                        $customerEmail =
                            $order->user?->email
                            ?? $order->customer_email
                            ?? null;

                        $customerPhone =
                            $order->user?->phone
                            ?? $order->customer_phone
                            ?? null;

                    @endphp


                    <div class="admin-status-list">

                        <div class="admin-status-row">

                            <div>
                                <div class="admin-status-row__label">
                                    نام
                                </div>
                            </div>

                            <div class="admin-status-row__value">
                                {{ $customerName }}
                            </div>

                        </div>


                        @if($customerPhone)

                            <div class="admin-status-row">

                                <div>
                                    <div class="admin-status-row__label">
                                        شماره تماس
                                    </div>
                                </div>

                                <div
                                    class="admin-status-row__value"
                                    dir="ltr"
                                >
                                    {{ $customerPhone }}
                                </div>

                            </div>

                        @endif


                        @if($customerEmail)

                            <div class="admin-status-row">

                                <div>
                                    <div class="admin-status-row__label">
                                        ایمیل
                                    </div>
                                </div>

                                <div
                                    class="admin-status-row__value"
                                    dir="ltr"
                                    style="max-width:220px; overflow-wrap:anywhere;"
                                >
                                    {{ $customerEmail }}
                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- ORDER MANAGEMENT --}}

            <div class="admin-card admin-form-section">

                <div class="admin-card-header">

                    <div>

                        <h2 class="admin-card-title">
                            مدیریت سفارش
                        </h2>

                        <p class="admin-card-description">
                            وضعیت سفارش و اطلاعات ارسال را به‌روزرسانی کنید.
                        </p>

                    </div>

                </div>


                <div class="admin-card-body">

                    <form
                        method="POST"
                        action="{{ route('admin.orders.update', $order) }}"
                    >

                        @csrf
                        @method('PUT')


                        {{-- ORDER STATUS --}}

                        <div class="admin-field">

                            <label for="status">
                                وضعیت سفارش
                            </label>

                            <select
                                id="status"
                                name="status"
                                required
                            >

                                @foreach($statusNames as $status => $label)

                                    <option
                                        value="{{ $status }}"
                                        @selected(
                                        old(
                                    'status',
                                    $order->status
                                    ) === $status
                                    )
                                    >
                                    {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- PAYMENT STATUS --}}

                        <div class="admin-field">

                            <label for="payment_status">
                                وضعیت پرداخت
                            </label>

                            <select
                                id="payment_status"
                                name="payment_status"
                                required
                            >

                                @foreach($paymentStatusNames as $paymentStatus => $label)

                                    <option
                                        value="{{ $paymentStatus }}"
                                        @selected(
                                        old(
                                    'payment_status',
                                    $order->payment_status
                                    ) === $paymentStatus
                                    )
                                    >
                                    {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- TRACKING CODE --}}

                        <div class="admin-field">

                            <label for="tracking_code">
                                کد رهگیری ارسال
                            </label>

                            <input
                                id="tracking_code"
                                type="text"
                                name="tracking_code"
                                value="{{ old(
                                    'tracking_code',
                                    $order->tracking_code ?? ''
                                ) }}"
                                dir="ltr"
                                placeholder="اختیاری"
                            >

                            <small class="admin-help">
                                در صورت ارسال سفارش، کد رهگیری مرسوله را وارد کنید.
                            </small>

                            @error('tracking_code')

                            <small
                                class="admin-help"
                                style="color:var(--admin-danger);"
                            >
                                {{ $message }}
                            </small>

                            @enderror

                        </div>


                        {{-- CUSTOMER / ORDER NOTE --}}

                        <div class="admin-field">

                            <label for="customer_note">
                                یادداشت سفارش
                            </label>

                            <textarea
                                id="customer_note"
                                name="customer_note"
                                rows="4"
                                placeholder="یادداشت یا توضیح مرتبط با سفارش..."
                            >{{ old(
                                'customer_note',
                                $order->customer_note ?? ''
                            ) }}</textarea>

                            <small class="admin-help">
                                این بخش برای ثبت توضیحات مرتبط با سفارش است.
                            </small>

                            @error('customer_note')

                            <small
                                class="admin-help"
                                style="color:var(--admin-danger);"
                            >
                                {{ $message }}
                            </small>

                            @enderror

                        </div>


                        <div class="admin-form-actions">

                            <button
                                type="submit"
                                class="admin-btn admin-btn--secondary"
                            >
                                ذخیره تغییرات
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
        SHIPPING ADDRESS
    ====================================================== --}}

    @php

        $shippingAddress =
            $order->shipping_address
            ?? $order->address?->address
            ?? null;

    @endphp


    @if($shippingAddress)

        <div class="admin-card admin-form-section">

            <div class="admin-card-header">

                <div>

                    <h2 class="admin-card-title">
                        آدرس ارسال
                    </h2>

                    <p class="admin-card-description">
                        آدرس ثبت‌شده برای تحویل سفارش
                    </p>

                </div>

            </div>


            <div class="admin-card-body">

                <div
                    style="
                        padding:16px;
                        border:1px solid var(--admin-border);
                        border-radius:var(--admin-radius-md);
                        background:var(--admin-surface-soft);
                        line-height:2;
                    "
                >
                    {!! nl2br(e($shippingAddress)) !!}
                </div>

            </div>

        </div>

    @endif

@endsection
