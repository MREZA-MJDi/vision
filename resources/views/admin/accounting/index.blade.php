@extends('layouts.admin')

@section('title', 'حسابداری')
@section('page-title', 'حسابداری')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                حسابداری
            </h1>

            <p class="admin-page-head__text">
                ثبت، پیگیری و مشاهده تراکنش‌های مالی فروشگاه
            </p>

        </div>

    </div>


    {{-- =====================================================
        FINANCIAL SUMMARY
    ====================================================== --}}

    <div class="admin-dashboard-stats accounting-summary-stats">

        <div class="admin-stat-card admin-stat-card--success">

            <div class="admin-stat-card__label">
                مجموع درآمد
            </div>

            <div class="admin-stat-card__value">
                {{ number_format((float) ($income ?? 0)) }}
            </div>

            <div class="admin-stat-card__meta">
                تومان
            </div>

        </div>


        <div class="admin-stat-card admin-stat-card--danger">

            <div class="admin-stat-card__label">
                مجموع هزینه
            </div>

            <div class="admin-stat-card__value">
                {{ number_format((float) ($expense ?? 0)) }}
            </div>

            <div class="admin-stat-card__meta">
                تومان
            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-card__label">
                خالص
            </div>

            <div class="admin-stat-card__value">
                {{ number_format((float) ($net ?? 0)) }}
            </div>

            <div class="admin-stat-card__meta">
                درآمد منهای هزینه · تومان
            </div>

        </div>

    </div>


    {{-- =====================================================
        DOUBLE ENTRY FOUNDATION
    ====================================================== --}}

    <div class="admin-dashboard-grid admin-dashboard-grid--equal">

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">دفتر کل</h2>
                    <p class="admin-card-description">
                        مانده‌های ثبت‌شده در سیستم دوطرفه حسابداری
                    </p>
                </div>
            </div>

            @if(($ledgerSummary ?? collect())->isNotEmpty())
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>حساب</th>
                            <th>بدهکار</th>
                            <th>بستانکار</th>
                            <th>خالص</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($ledgerSummary as $account)
                            @php
                                $debit = (float) $account->debit;
                                $credit = (float) $account->credit;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $account->name }}</strong>
                                    <div class="admin-muted">{{ $account->code }}</div>
                                </td>
                                <td>{{ number_format($debit) }} تومان</td>
                                <td>{{ number_format($credit) }} تومان</td>
                                <td>{{ number_format($debit - $credit) }} تومان</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="admin-empty">
                    <h3 class="admin-empty__title">هنوز سند دوطرفه‌ای ثبت نشده است</h3>
                    <p class="admin-empty__text">
                        با ثبت پرداخت یا بازپرداخت، سند حسابداری در دفتر کل ظاهر می‌شود.
                    </p>
                </div>
            @endif
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">آخرین اسناد حسابداری</h2>
                    <p class="admin-card-description">سندهای ثبت‌شده از پرداخت‌ها و بازپرداخت‌ها</p>
                </div>
            </div>

            <div class="admin-card-body accounting-journal-list">
                @forelse(($journalEntries ?? collect()) as $entry)
                    <article class="accounting-journal-entry">
                        <header class="accounting-journal-entry__head">
                            <code dir="ltr">{{ $entry->entry_number }}</code>
                            <time>{{ optional($entry->entry_date)->format('Y/m/d') }}</time>
                        </header>
                        <p class="accounting-journal-entry__description">
                            {{ $entry->description ?: 'بدون شرح' }}
                        </p>
                        <div class="accounting-journal-entry__lines">
                            @foreach($entry->lines as $line)
                                <div>
                                    <span>{{ $line->account?->name ?? 'حساب' }}</span>
                                    <strong>
                                        @if((float) $line->debit > 0)
                                            بدهکار {{ number_format((float) $line->debit) }}
                                        @else
                                            بستانکار {{ number_format((float) $line->credit) }}
                                        @endif
                                        <small>تومان</small>
                                    </strong>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="admin-empty">
                        <h3 class="admin-empty__title">سندی وجود ندارد</h3>
                    </div>
                @endforelse
                <p class="accounting-journal-list__note">
                    تراکنش‌های دستی قدیمی در دفتر تراکنش مالی هستند؛ سندهای فروش و بازپرداخت از دفتر کل خوانده می‌شوند.
                </p>
            </div>
        </div>

    </div>


        {{-- =====================================================
        FILTERS
    ====================================================== --}}

    <div class="admin-card admin-filter-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    جستجو و فیلتر تراکنش‌ها
                </h2>

                <p class="admin-card-description">
                    تراکنش‌ها را بر اساس شرح، نوع یا بازه تاریخ پیدا کنید.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('admin.accounting.index') }}"
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
                        placeholder="دسته‌بندی یا شرح تراکنش..."
                    >

                </div>


                {{-- TYPE --}}

                <div class="admin-field">

                    <label for="accounting-filter-type">
                        نوع تراکنش
                    </label>

                    <select
                        id="accounting-filter-type"
                        name="type"
                    >

                        <option value="">
                            همه
                        </option>

                        <option
                            value="income"
                            @selected(request('type') === 'income')
                        >
                        درآمد
                        </option>

                        <option
                            value="expense"
                            @selected(request('type') === 'expense')
                        >
                        هزینه
                        </option>

                    </select>

                </div>


                {{-- FROM --}}

                <div class="admin-field">

                    <label for="from">
                        از تاریخ
                    </label>

                    <input
                        id="from"
                        type="date"
                        name="from"
                        value="{{ request('from') }}"
                    >

                </div>


                {{-- TO --}}

                <div class="admin-field">

                    <label for="to">
                        تا تاریخ
                    </label>

                    <input
                        id="to"
                        type="date"
                        name="to"
                        value="{{ request('to') }}"
                    >

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
                        href="{{ route('admin.accounting.index') }}"
                        class="admin-btn admin-btn--ghost"
                    >
                        پاک کردن
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        MAIN CONTENT
    ====================================================== --}}

    <div class="admin-dashboard-grid admin-dashboard-grid--equal">


        {{-- =================================================
            TRANSACTIONS
        ================================================== --}}

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h2 class="admin-card-title">
                        تراکنش‌ها
                    </h2>

                    <p class="admin-card-description">
                        {{ number_format($transactions->total()) }}
                        تراکنش
                    </p>

                </div>

            </div>


            @if($transactions->count())

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>

                        <tr>

                            <th>
                                تاریخ
                            </th>

                            <th>
                                نوع
                            </th>

                            <th>
                                دسته‌بندی
                            </th>

                            <th>
                                مبلغ
                            </th>

                            <th>
                                شرح
                            </th>

                            <th>
                                ثبت‌کننده
                            </th>

                            <th>
                                عملیات
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        @foreach($transactions as $transaction)

                            @php

                                $isIncome =
                                    $transaction->type === 'income';

                                $typeLabel = $isIncome
                                    ? 'درآمد'
                                    : 'هزینه';

                                $typeClass = $isIncome
                                    ? 'success'
                                    : 'danger';

                            @endphp


                            <tr>

                                {{-- DATE --}}

                                <td>

                                    @if($transaction->transaction_date)

                                        <span class="admin-muted">

                                            <span class="admin-local-date" data-admin-date="{{ $transaction->transaction_date?->toIso8601String() }}" data-admin-date-format="day">{{ $transaction->transaction_date->format('Y/m/d') }}</span>

                                        </span>

                                    @else

                                        <span class="admin-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- TYPE --}}

                                <td>

                                    <span
                                        class="admin-badge admin-badge--{{ $typeClass }}"
                                    >
                                        {{ $typeLabel }}
                                    </span>

                                </td>


                                {{-- CATEGORY --}}

                                <td>

                                    {{ $transaction->category ?: '—' }}

                                </td>


                                {{-- AMOUNT --}}

                                <td>

                                    <div class="admin-price">

                                        {{ number_format(
                                            (float) $transaction->amount
                                        ) }}

                                    </div>

                                    <div class="admin-muted">
                                        تومان
                                    </div>

                                </td>


                                {{-- DESCRIPTION --}}

                                <td>

                                    @if($transaction->description)

                                        <span>
                                            {{ \Illuminate\Support\Str::limit(
                                                $transaction->description,
                                                80
                                            ) }}
                                        </span>

                                    @else

                                        <span class="admin-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- CREATED BY --}}

                                <td>

                                    {{ $transaction->createdBy?->name ?? '—' }}

                                </td>


                                {{-- ACTION --}}

                                <td>

                                    <a
                                        href="{{ route(
                                            'admin.accounting.show',
                                            $transaction
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

                @if($transactions->hasPages())

                    <div class="admin-pagination">

                        {{ $transactions->links() }}

                    </div>

                @endif


            @else

                <div class="admin-empty">

                    <div class="admin-empty__icon">
                        —
                    </div>

                    <h3 class="admin-empty__title">
                        تراکنشی پیدا نشد
                    </h3>

                    <p class="admin-empty__text">
                        با فیلترهای فعلی تراکنشی برای نمایش وجود ندارد.
                    </p>

                </div>

            @endif

        </div>


        {{-- =================================================
            CREATE TRANSACTION
        ================================================== --}}

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h2 class="admin-card-title">
                        ثبت تراکنش
                    </h2>

                    <p class="admin-card-description">
                        درآمد یا هزینه جدید را ثبت کنید.
                    </p>

                </div>

            </div>


            <div class="admin-card-body">

                <form
                    method="POST"
                    action="{{ route('admin.accounting.store') }}"
                >

                    @csrf


                    {{-- TYPE --}}

                    <div class="admin-field">

                        <label for="transaction-type">
                            نوع تراکنش *
                        </label>

                        <select
                            id="transaction-type"
                            name="type"
                            required
                        >

                            <option value="">
                                انتخاب کنید
                            </option>

                            <option
                                value="income"
                                @selected(old('type', 'expense') === 'income')
                            >
                            درآمد
                            </option>

                            <option
                                value="expense"
                                @selected(old('type', 'expense') === 'expense')
                            >
                            هزینه
                            </option>

                        </select>


                        @error('type')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- CATEGORY --}}

                    <div class="admin-field">

                        <label for="category">
                            دسته‌بندی *
                        </label>

                        <input
                            id="category"
                            type="text"
                            name="category"
                            value="{{ old('category') }}"
                            maxlength="80"
                            autocomplete="off"
                            placeholder="مثلاً فروش، تبلیغات، خرید..."
                            required
                        >


                        <small class="admin-help">
                            یک عنوان کوتاه برای دسته‌بندی هزینه یا درآمد.
                        </small>


                        @error('category')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- AMOUNT --}}

                    <div class="admin-field">

                        <label for="amount">
                            مبلغ *
                        </label>

                        <input
                            id="amount"
                            type="text"
                            name="amount"
                            value="{{ old('amount') }}"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="مثلاً ۱٬۵۰۰٬۰۰۰"
                            data-money-input
                            required
                        >


                        <small class="admin-help">
                            مبلغ را به تومان وارد کنید؛ جداکننده هم می‌توانید استفاده کنید.
                        </small>


                        @error('amount')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- DATE --}}

                    <div class="admin-field">

                        <label for="transaction_date">
                            تاریخ تراکنش *
                        </label>

                        <input
                            id="transaction_date"
                            type="date"
                            name="transaction_date"
                            value="{{ old(
                                'transaction_date',
                                now()->toDateString()
                            ) }}"
                            required
                        >


                        @error('transaction_date')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- DESCRIPTION --}}

                    <div class="admin-field">

                        <label for="description">
                            شرح
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="500"
                            placeholder="مثلاً پرداخت هزینه تبلیغات اینستاگرام..."
                        >{{ old('description') }}</textarea>


                        @error('description')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- ACTIONS --}}

                    <div class="admin-form-actions">

                        <button
                            type="submit"
                            class="admin-btn admin-btn--secondary"
                        >
                            ثبت تراکنش
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
