@extends('layouts.admin')

@section('title', 'سفارش‌ها')
@section('page-title', 'سفارش‌ها')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                سفارش‌ها
            </h1>

            <p class="admin-page-head__text">
                مشاهده و پیگیری سفارش‌های ثبت‌شده
            </p>

        </div>

    </div>


    {{-- =====================================================
        FILTERS
    ====================================================== --}}

    <div class="admin-card admin-filter-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    جستجو و فیلتر
                </h2>

                <p class="admin-card-description">
                    سفارش موردنظر را بر اساس شماره، مشتری، وضعیت یا پرداخت پیدا کنید.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('admin.orders.index') }}"
        >

            <div class="admin-filter-grid">

                {{-- SEARCH --}}

                <div class="admin-field">

                    <label for="q">
                        جستجو
                    </label>

                    <input
                        id="q"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="شماره سفارش، نام یا شماره تماس..."
                    >

                </div>


                {{-- ORDER STATUS --}}

                <div class="admin-field">

                    <label for="status">
                        وضعیت سفارش
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="">
                            همه وضعیت‌ها
                        </option>

                        @foreach($statusNames as $status => $label)

                            <option
                                value="{{ $status }}"
                                @selected(request('status') === $status)
                            >
                            {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ORDER TYPE --}}

{{-- PAYMENT STATUS --}}

                <div class="admin-field">

                    <label for="payment_status">
                        وضعیت پرداخت
                    </label>

                    <select
                        id="payment_status"
                        name="payment_status"
                    >

                        <option value="">
                            همه وضعیت‌ها
                        </option>

                        @foreach([
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                            'failed' => 'ناموفق',
                            'refunded' => 'بازپرداخت شده',
                        ] as $status => $label)

                            <option
                                value="{{ $status }}"
                                @selected(
                                request('payment_status') === $status
                            )
                            >
                            {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ACTIONS --}}

                <div class="admin-filter-actions">

                    <button
                        type="submit"
                        class="admin-btn admin-btn--secondary"
                    >
                        اعمال فیلتر
                    </button>

                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="admin-btn admin-btn--ghost"
                    >
                        پاک کردن
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        ORDERS TABLE
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    فهرست سفارش‌ها
                </h2>

                <p class="admin-card-description">
                    {{ number_format($orders->total()) }}
                    سفارش
                </p>

            </div>

        </div>


        @if($orders->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            سفارش
                        </th>

                        <th>
                            مشتری
                        </th>

<th>
                            مبلغ
                        </th>

                        <th>
                            وضعیت سفارش
                        </th>

                        <th>
                            پرداخت
                        </th>

                        <th>
                            تاریخ
                        </th>

                        <th>
                            عملیات
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    @foreach($orders as $order)

                        @php

                            $status = $order->status;

                            $statusClass = match ($status) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'preparing' => 'info',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                'returned' => 'neutral',
                                default => 'neutral',
                            };

                            $paymentStatus = $order->payment_status;

                            $paymentClass = match ($paymentStatus) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'refunded' => 'neutral',
                                default => 'neutral',
                            };

                            $customerName = $order->user?->name
                                ?? $order->customer_name
                                ?? 'مهمان';

                            $customerPhone = $order->user?->phone
                                ?? $order->customer_phone
                                ?? null;

                            $placedAt = $order->placed_at
                                ?? $order->created_at;

                        @endphp


                        <tr>

                            {{-- ORDER --}}

                            <td>

                                <div class="admin-product-name">
                                    #{{ $order->order_number ?? $order->id }}
                                </div>

                                <div class="admin-product-meta">
                                    {{ number_format($order->items_count ?? 0) }}
                                    قلم
                                </div>

                            </td>


                            {{-- CUSTOMER --}}

                            <td>

                                <div class="admin-product-name">
                                    {{ $customerName }}
                                </div>

                                @if($customerPhone)

                                    <div
                                        class="admin-product-meta"
                                        dir="ltr"
                                    >
                                        {{ $customerPhone }}
                                    </div>

                                @elseif($order->user?->email)

                                    <div
                                        class="admin-product-meta"
                                        dir="ltr"
                                    >
                                        {{ $order->user->email }}
                                    </div>

                                @endif

                            </td>


{{-- TOTAL --}}

                            <td>

                                <div class="admin-price">

                                    {{ number_format(
                                        (float) $order->total
                                    ) }}

                                </div>

                                <div class="admin-muted">
                                    تومان
                                </div>

                            </td>


                            {{-- ORDER STATUS --}}

                            <td>

                                <span
                                    class="admin-badge admin-badge--{{ $statusClass }}"
                                >
                                    {{ $statusNames[$status] ?? $status }}
                                </span>

                            </td>


                            {{-- PAYMENT STATUS --}}

                            <td>

                                <span
                                    class="admin-badge admin-badge--{{ $paymentClass }}"
                                >
                                    {{
                                        [
                                            'pending' => 'در انتظار',
                                            'paid' => 'پرداخت شده',
                                            'failed' => 'ناموفق',
                                            'refunded' => 'بازپرداخت شده',
                                        ][$paymentStatus]
                                        ?? $paymentStatus
                                    }}
                                </span>

                            </td>


                            {{-- DATE --}}

                            <td>

                                @if($placedAt)

                                    <span
                                        class="admin-muted admin-local-date"
                                        data-admin-date="{{ $placedAt?->toIso8601String() }}"
                                    >
                                        {{ $placedAt->format('Y/m/d H:i') }}
                                    </span>

                                @else

                                    <span class="admin-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- ACTIONS --}}

                            <td>

                                <a
                                    href="{{ route(
                                        'admin.orders.show',
                                        $order
                                    ) }}"
                                    class="admin-btn admin-btn--ghost admin-btn--sm"
                                >
                                    مشاهده
                                </a>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}

            @if($orders->hasPages())

                <div class="admin-pagination">

                    {{ $orders->links() }}

                </div>

            @endif


        @else

            <div class="admin-empty">

                <div class="admin-empty__icon">
                    —
                </div>

                <h3 class="admin-empty__title">
                    سفارشی پیدا نشد
                </h3>

                <p class="admin-empty__text">
                    با فیلترهای فعلی سفارشی برای نمایش وجود ندارد.
                </p>

            </div>

        @endif

    </div>

@endsection
