@extends('layouts.admin')

@section('title', 'جزئیات تراکنش')
@section('page-title', 'جزئیات تراکنش')

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
                    href="{{ route('admin.accounting.index') }}"
                    class="admin-muted"
                    style="text-decoration:none;"
                >
                    حسابداری
                </a>

                <span class="admin-muted">
                    /
                </span>

                <span class="admin-muted">
                    تراکنش #{{ $transaction->id }}
                </span>

            </div>


            <h1 class="admin-page-head__title">
                جزئیات تراکنش
            </h1>


            <p class="admin-page-head__text">
                اطلاعات کامل تراکنش مالی
            </p>

        </div>


        <a
            href="{{ route('admin.accounting.index') }}"
            class="admin-btn admin-btn--ghost"
        >
            بازگشت
        </a>

    </div>


    {{-- =====================================================
        TRANSACTION
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    تراکنش #{{ $transaction->id }}
                </h2>

                <p class="admin-card-description">

                    ثبت‌شده در

                    @if($transaction->created_at)
                        {{ $transaction->created_at->format('Y/m/d H:i') }}
                    @else
                        —
                    @endif

                </p>

            </div>

        </div>


        <div class="admin-card-body">

            @php
                $isIncome = $transaction->type === 'income';

                $typeLabel = $isIncome
                    ? 'درآمد'
                    : 'هزینه';

                $typeClass = $isIncome
                    ? 'success'
                    : 'danger';
            @endphp


            <div class="admin-status-list">

                {{-- TYPE --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            نوع تراکنش
                        </div>

                    </div>


                    <div class="admin-status-row__value">

                        <span
                            class="admin-badge admin-badge--{{ $typeClass }}"
                        >
                            {{ $typeLabel }}
                        </span>

                    </div>

                </div>


                {{-- CATEGORY --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            دسته‌بندی
                        </div>

                    </div>


                    <div class="admin-status-row__value">

                        {{ $transaction->category ?: '—' }}

                    </div>

                </div>


                {{-- AMOUNT --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            مبلغ
                        </div>

                    </div>


                    <div class="admin-status-row__value">

                        <span class="admin-price">

                            {{ number_format(
                                (float) $transaction->amount
                            ) }}

                        </span>

                        <span class="admin-muted">
                            تومان
                        </span>

                    </div>

                </div>


                {{-- TRANSACTION DATE --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            تاریخ تراکنش
                        </div>

                    </div>


                    <div class="admin-status-row__value">

                        @if($transaction->transaction_date)

                            {{ $transaction->transaction_date->format('Y/m/d') }}

                        @else

                            —

                        @endif

                    </div>

                </div>


                {{-- CREATED BY --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            ثبت‌کننده
                        </div>

                    </div>


                    <div class="admin-status-row__value">

                        {{ $transaction->createdBy?->name ?? '—' }}

                    </div>

                </div>


                {{-- REFERENCE --}}

                @if(
                    $transaction->reference_type &&
                    $transaction->reference_id
                )

                    <div class="admin-status-row">

                        <div>

                            <div class="admin-status-row__label">
                                مرجع
                            </div>

                        </div>


                        <div
                            class="admin-status-row__value"
                            dir="ltr"
                        >

                            {{ class_basename(
                                $transaction->reference_type
                            ) }}

                            #

                            {{ $transaction->reference_id }}

                        </div>

                    </div>

                @endif


                {{-- DESCRIPTION --}}

                <div class="admin-status-row">

                    <div>

                        <div class="admin-status-row__label">
                            شرح
                        </div>

                    </div>


                    <div
                        class="admin-status-row__value"
                        style="
                            max-width:70%;
                            line-height:2;
                            text-align:left;
                        "
                    >

                        {{ $transaction->description ?: 'بدون شرح' }}

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
