@extends('layouts.admin')

@section('title', 'واریانت‌های محصول')
@section('page-title', 'واریانت‌های محصول')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                واریانت‌های محصول
            </h1>

            <p class="admin-page-head__text">
                {{ $product->name }}
            </p>

        </div>


        <div class="admin-actions">

            <a
                href="{{ route('admin.products.edit', $product) }}"
                class="admin-btn admin-btn--ghost"
            >
                ویرایش محصول
            </a>

            <a
                href="{{ route('admin.products.variants.create', $product) }}"
                class="admin-btn admin-btn--secondary"
            >
                + افزودن واریانت
            </a>

        </div>

    </div>


    {{-- =====================================================
        PRODUCT SUMMARY
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    محصول
                </h2>

                <p class="admin-card-description">
                    واریانت‌های زیر متعلق به این محصول هستند.
                </p>

            </div>

        </div>


        <div class="admin-card-body">

            <div class="admin-product-cell">

                <div class="admin-product-thumb-empty">
                    {{ mb_substr($product->name, 0, 1) }}
                </div>

                <div>

                    <div class="admin-product-name">
                        {{ $product->name }}
                    </div>


                    <div class="admin-product-meta">

                        <span>
                            دسته‌بندی:
                            {{ $product->category?->name ?? '—' }}
                        </span>

                        @if($product->brand)

                            <span>
                                ·
                                برند:
                                {{ $product->brand->name }}
                            </span>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
        VARIANTS
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    فهرست واریانت‌ها
                </h2>

                <p class="admin-card-description">
                    {{ number_format($variants->total()) }}
                    واریانت
                </p>

            </div>

        </div>


        @if($variants->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            کد کالا
                        </th>

                        <th>
                            سایز
                        </th>

                        <th>
                            رنگ
                        </th>

                        <th>
                            قیمت
                        </th>

                        <th>
                            قیمت ویژه
                        </th>
                        <th>
                        </th>

                        <th>
                            موجودی
                        </th>

                        <th>
                            وضعیت
                        </th>

                        <th>
                            ترتیب
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

                            {{-- SKU --}}

                            <td dir="ltr">

                                <div class="admin-product-name">

                                    {{ $variant->sku ?: 'خودکار' }}

                                </div>

                            </td>


                            {{-- SIZE --}}

                            <td>

                                @if($variant->size)

                                    {{ $variant->size }}

                                @else

                                    <span class="admin-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- COLOR --}}

                            <td>

                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                    "
                                >

                                    @if($variant->color_code)

                                        <span
                                            aria-hidden="true"
                                            style="
                                                display:inline-block;
                                                width:20px;
                                                height:20px;
                                                border-radius:50%;
                                                border:1px solid var(--admin-border-strong);
                                                background:{{ $variant->color_code }};
                                                flex:0 0 20px;
                                                "
                                        ></span>

                                    @endif


                                    <span>

                                        {{ $variant->color ?: '—' }}

                                    </span>

                                </div>

                            </td>


                            {{-- PRICE --}}

                            <td>

                                <div class="admin-price">

                                    {{ number_format((float) $variant->price) }}

                                </div>

                                <div class="admin-muted">
                                    تومان
                                </div>

                            </td>


                            {{-- SALE PRICE --}}

                            <td>

                                @if($variant->sale_price !== null)

                                    <div class="admin-price">

                                        {{ number_format((float) $variant->sale_price) }}

                                    </div>

                                    <div class="admin-muted">
                                        تومان
                                    </div>

                                @else

                                    <span class="admin-muted">
                                        بدون تخفیف
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

                                        حد هشدار:
                                        {{ number_format($threshold) }}

                                    </span>

                                @endif

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


                            {{-- SORT --}}

                            <td>

                                <span class="admin-muted">

                                    {{ number_format(
                                        $variant->sort_order ?? 0
                                    ) }}

                                </span>

                            </td>


                            {{-- ACTIONS --}}

                            <td>

                                <div class="admin-actions">

                                    <a
                                        href="{{ route(
                                            'admin.products.variants.edit',
                                            [$product, $variant]
                                        ) }}"
                                        class="admin-btn admin-btn--ghost admin-btn--sm"
                                    >
                                        ویرایش
                                    </a>


                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.products.variants.destroy',
                                            [$product, $variant]
                                        ) }}"
                                        onsubmit="
                                            return confirm(
                                                'آیا از حذف این واریانت مطمئن هستید؟'
                                            );
                                        "
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="admin-btn admin-btn--danger admin-btn--sm"
                                        >
                                            حذف
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}

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
                    واریانتی ثبت نشده است
                </h3>

                <p class="admin-empty__text">
                    هنوز برای این محصول سایز، رنگ یا گزینه قابل فروش ثبت نشده است.
                </p>

                <div style="margin-top:16px;">

                    <a
                        href="{{ route(
                            'admin.products.variants.create',
                            $product
                        ) }}"
                        class="admin-btn admin-btn--secondary"
                    >
                        افزودن واریانت
                    </a>

                </div>

            </div>

        @endif

    </div>

@endsection
