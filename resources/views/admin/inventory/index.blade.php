@extends('layouts.admin')

@section('title', 'انبار')
@section('page-title', 'انبار')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                انبار
            </h1>

            <p class="admin-page-head__text">
                مشاهده موجودی و ثبت گردش‌های انبار
            </p>

        </div>

    </div>


    {{-- =====================================================
        SEARCH
    ====================================================== --}}

    <div class="admin-card admin-filter-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    جستجو
                </h2>

                <p class="admin-card-description">
                    محصول یا واریانت موردنظر را پیدا کنید.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('admin.inventory.index') }}"
        >

            <div class="admin-filter-grid">

                <div class="admin-field">

                    <label for="q">
                        جستجوی محصول
                    </label>

                    <input
                        id="q"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="نام محصول یا شناسه..."
                    >

                </div>


                <div class="admin-filter-actions">

                    <button
                        type="submit"
                        class="admin-btn admin-btn--secondary"
                    >
                        جستجو
                    </button>

                    <a
                        href="{{ route('admin.inventory.index') }}"
                        class="admin-btn admin-btn--ghost"
                    >
                        پاک کردن
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        INVENTORY
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    موجودی واریانت‌ها
                </h2>

                <p class="admin-card-description">
                    موجودی فعلی هر گزینه قابل فروش
                </p>

            </div>

        </div>


        @if($variants->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            محصول
                        </th>

                        <th>
                            کد کالا
                        </th>

                        <th>
                            ویژگی
                        </th>

                        <th>
                            موجودی
                        </th>

                        <th>
                            حد هشدار
                        </th>

                        <th>
                            وضعیت
                        </th>

                        <th>
                            عملیات
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    @foreach($variants as $variant)

                        @php

                            $stock = (int) (
                                $variant->stock ?? 0
                            );

                            $threshold = (int) (
                                $variant->low_stock_threshold ?? 5
                            );

                            $isLowStock = $stock <= $threshold;

                        @endphp


                        <tr>

                            {{-- PRODUCT --}}

                            <td>

                                <div class="admin-product-name">
                                    {{ $variant->product?->name ?? '—' }}
                                </div>

                                @if($variant->product?->category)

                                    <div class="admin-product-meta">
                                        {{ $variant->product->category->name }}
                                    </div>

                                @endif

                            </td>


                            {{-- SKU --}}

                            <td dir="ltr">

                                <span class="admin-muted">
                                    {{ $variant->sku ?: 'خودکار' }}
                                </span>

                            </td>


                            {{-- ATTRIBUTES --}}

                            <td>

                                @if($variant->size)

                                    <div>
                                        سایز:
                                        {{ $variant->size }}
                                    </div>

                                @endif


                                @if($variant->color)

                                    <div>
                                        رنگ:
                                        {{ $variant->color }}
                                    </div>

                                @endif


                                @if(!$variant->size && !$variant->color)

                                    <span class="admin-muted">
                                        بدون ویژگی
                                    </span>

                                @endif

                            </td>


                            {{-- STOCK --}}

                            <td>

                                <div class="admin-price">
                                    {{ number_format($stock) }}
                                </div>


                                @if($isLowStock)

                                    <span class="admin-badge admin-badge--warning">
                                        موجودی کم
                                    </span>

                                @else

                                    <span class="admin-muted">
                                        مناسب
                                    </span>

                                @endif

                            </td>


                            {{-- THRESHOLD --}}

                            <td>

                                <span class="admin-muted">

                                    {{ number_format($threshold) }}

                                </span>

                            </td>


                            {{-- STATUS --}}

                            <td>

                                @if($variant->is_active)

                                    <span class="admin-badge admin-badge--success">
                                        فعال
                                    </span>

                                @else

                                    <span class="admin-badge admin-badge--neutral">
                                        غیرفعال
                                    </span>

                                @endif

                            </td>


                            {{-- ACTIONS --}}

                            <td>

                                <a
                                    href="{{ route(
                                        'admin.products.variants.edit',
                                        [$variant->product, $variant]
                                    ) }}"
                                    class="admin-btn admin-btn--ghost admin-btn--sm"
                                >
                                    مدیریت
                                </a>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            @if($variants->hasPages())

                <div class="admin-pagination">
                    {{ $variants->links() }}
                </div>

            @endif


        @else

            <div class="admin-empty">

                <div class="admin-empty__icon">
                    —
                </div>

                <h3 class="admin-empty__title">
                    موجودی‌ای برای نمایش وجود ندارد
                </h3>

                <p class="admin-empty__text">
                    با جستجوی فعلی واریانتی پیدا نشد.
                </p>

            </div>

        @endif

    </div>


    {{-- =====================================================
        CREATE MOVEMENT
    ====================================================== --}}

    <div class="admin-card admin-form-section">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    ثبت گردش انبار
                </h2>

                <p class="admin-card-description">
                    ورود، خروج، خرابی یا اصلاح موجودی را ثبت کنید.
                </p>

            </div>

        </div>


        <div class="admin-card-body">

            <form
                method="POST"
                action="{{ route('admin.inventory.store') }}"
                id="inventory-movement-form"
            >

                @csrf


                <div class="admin-form-grid">

                    {{-- VARIANT --}}

                    <div class="admin-field admin-field-full">

                        <label for="product_variant_id">
                            محصول و واریانت *
                        </label>

                        <input type="search" id="inventory-variant-search" placeholder="نام محصول، برند، SKU، رنگ یا سایز را جستجو کن…" autocomplete="off" data-inventory-variant-search data-lookup-url="{{ route('admin.variant-lookup') }}">
                        <select id="product_variant_id" name="product_variant_id" required data-inventory-variant aria-label="نتیجه‌های جستجوی واریانت">
                            <option value="">اول جستجو کن و یک واریانت انتخاب کن</option>
                            @if($selectedVariant)
                                <option value="{{ $selectedVariant->id }}" data-stock="{{ (int) $selectedVariant->stock }}" selected>{{ $selectedVariant->product?->name ?? 'محصول' }} — {{ $selectedVariant->display_name }} — موجودی: {{ number_format((int) $selectedVariant->stock) }}</option>
                            @endif
                        </select>
                        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-inventory-next hidden>بارگذاری نتیجه‌های بعدی</button>
                        <small class="admin-help">نتایج به‌صورت صفحه‌ای بارگذاری می‌شوند تا فهرست بزرگ واریانت‌ها باعث کندی نشود.</small>


                        @error('product_variant_id')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                        <div class="inventory-current-stock" data-current-stock-panel>
                            <span>موجودی فعلی این واریانت</span>
                            <strong data-current-stock>—</strong>
                            <small>بعد از انتخاب محصول، این عدد را می‌بینی.</small>
                        </div>

                    </div>


                    {{-- TYPE --}}

                    <div class="admin-field">

                        <label for="movement-type">
                            نوع گردش *
                        </label>

                        <select
                            id="movement-type"
                            name="type"
                            required
                        >

                            <option value="">
                                انتخاب کنید
                            </option>

                            <option
                                value="purchase"
                                @selected(old('type') === 'purchase')
                            >
                            ورود خرید
                            </option>

                            <option
                                value="sale"
                                @selected(old('type') === 'sale')
                            >
                            خروج فروش
                            </option>

                            <option
                                value="adjustment"
                                @selected(old('type') === 'adjustment')
                            >
                            اصلاح موجودی
                            </option>

                            <option
                                value="damage"
                                @selected(old('type') === 'damage')
                            >
                            ضایعات / خرابی
                            </option>

                            <option
                                value="return"
                                @selected(old('type') === 'return')
                            >
                            برگشت کالا
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


                    {{-- DIRECTION FOR ADJUSTMENT --}}

                    <div
                        class="admin-field"
                        id="adjustment-direction-field"
                        style="display:none;"
                    >

                        <label for="adjustment_direction">
                            جهت اصلاح
                        </label>

                        <select
                            id="adjustment_direction"
                        >

                            <option value="increase">
                                افزایش موجودی
                            </option>

                            <option value="decrease">
                                کاهش موجودی
                            </option>

                        </select>

                        <small class="admin-help">
                            برای اصلاح موجودی مشخص کنید مقدار باید اضافه شود یا کم.
                        </small>

                    </div>


                    {{-- QUANTITY --}}

                    <div class="admin-field">

                        <label for="quantity_amount">
                            مقدار *
                        </label>

                        <input
                            id="quantity_amount"
                            type="number"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            value="{{ old('quantity')
                                ? abs((int) old('quantity'))
                                : '' }}"
                            required
                        >

                        <input
                            type="hidden"
                            name="quantity"
                            id="quantity"
                            value="{{ old('quantity') }}"
                        >


                        <small
                            class="admin-help"
                            id="quantity-help"
                        >
                            تعداد موردنظر را بدون علامت مثبت یا منفی وارد کنید.
                        </small>


                        @error('quantity')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>


                    {{-- NOTE --}}

                    <div class="admin-field admin-field-full">

                        <label for="note">
                            توضیحات
                        </label>

                        <textarea
                            id="note"
                            name="note"
                            rows="4"
                            maxlength="500"
                            placeholder="مثلاً خرید از تأمین‌کننده، اصلاح شمارش یا کالای آسیب‌دیده..."
                        >{{ old('note') }}</textarea>


                        <small class="admin-help">
                            توضیحات اختیاری است.
                        </small>


                        @error('note')

                        <small
                            class="admin-help"
                            style="color:var(--admin-danger);"
                        >
                            {{ $message }}
                        </small>

                        @enderror

                    </div>

                </div>


                <div class="admin-form-actions">

                    <button
                        type="submit"
                        class="admin-btn admin-btn--secondary"
                    >
                        ثبت گردش انبار
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =====================================================
        RECENT MOVEMENTS
    ====================================================== --}}

    <div class="admin-card admin-form-section">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    آخرین گردش‌ها
                </h2>

                <p class="admin-card-description">
                    آخرین تغییرات ثبت‌شده در موجودی
                </p>

            </div>

        </div>


        @if($movements->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            محصول
                        </th>

                        <th>
                            نوع
                        </th>

                        <th>
                            تغییر
                        </th>

                        <th>
                            موجودی پس از تغییر
                        </th>

                        <th>
                            ثبت‌کننده
                        </th>

                        <th>
                            تاریخ
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    @foreach($movements as $movement)

                        @php

                            $movementNames = [
                                'purchase' => 'ورود خرید',
                                'sale' => 'خروج فروش',
                                'adjustment' => 'اصلاح موجودی',
                                'damage' => 'ضایعات / خرابی',
                                'return' => 'برگشت کالا',
                            ];

                            $quantity = (int) $movement->quantity;

                            $quantityClass = $quantity > 0
                                ? 'success'
                                : 'danger';

                        @endphp


                        <tr>

                            <td>

                                <div class="admin-product-name">

                                    {{
                                        $movement->productVariant?->product?->name
                                        ?? '—'
                                    }}

                                </div>

                                @if($movement->productVariant?->sku)

                                    <div class="admin-product-meta" dir="ltr">

                                        {{ $movement->productVariant->sku }}

                                    </div>

                                @endif

                            </td>


                            <td>

                                <span class="admin-badge admin-badge--neutral">

                                    {{
                                        $movementNames[$movement->type]
                                        ?? $movement->type
                                    }}

                                </span>

                            </td>


                            <td>

                                <span class="admin-badge admin-badge--{{ $quantityClass }}">

                                    {{ $quantity > 0 ? '+' : '' }}
                                    {{ number_format($quantity) }}

                                </span>

                            </td>


                            <td>

                                <span class="admin-price">

                                    {{
                                        number_format(
                                            (int) $movement->stock_after
                                        )
                                    }}

                                </span>

                            </td>


                            <td>

                                {{ $movement->createdBy?->name ?? 'سیستم' }}

                            </td>


                            <td>

                                <span class="admin-muted">

                                    <span class="admin-local-date" data-admin-date="{{ $movement->created_at?->toIso8601String() }}">{{ $movement->created_at ? $movement->created_at->format('Y/m/d H:i') : '—' }}</span>

                                </span>

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
                    گردش انباری ثبت نشده است
                </h3>

                <p class="admin-empty__text">
                    اولین تغییر موجودی را از فرم بالا ثبت کنید.
                </p>

            </div>

        @endif

    </div>


    {{-- =====================================================
        INVENTORY FORM JS
    ====================================================== --}}

    

@endsection
