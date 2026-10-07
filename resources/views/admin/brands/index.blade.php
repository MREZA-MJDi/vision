@extends('layouts.admin')

@section('title', 'برندها')
@section('page-title', 'برندها')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                برندها
            </h1>

            <p class="admin-page-head__text">
                مدیریت برندهای فروشگاه
            </p>

        </div>

        <a
            href="{{ route('admin.brands.create') }}"
            class="admin-btn admin-btn--secondary"
        >
            + افزودن برند
        </a>

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
                    برند را با نام، شناسه یا وضعیت پیدا کنید.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('admin.brands.index') }}"
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
                        placeholder="نام برند یا شناسه..."
                    >

                </div>


                {{-- STATUS --}}

                <div class="admin-field">

                    <label for="active">
                        وضعیت
                    </label>

                    <select
                        id="active"
                        name="active"
                    >

                        <option value="">
                            همه وضعیت‌ها
                        </option>

                        <option
                            value="1"
                            @selected(request('active') === '1')
                        >
                        فعال
                        </option>

                        <option
                            value="0"
                            @selected(request('active') === '0')
                        >
                        غیرفعال
                        </option>

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
                        href="{{ route('admin.brands.index') }}"
                        class="admin-btn admin-btn--ghost"
                    >
                        پاک کردن
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        BRANDS TABLE
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    فهرست برندها
                </h2>

                <p class="admin-card-description">
                    {{ number_format($brands->total()) }}
                    برند
                </p>

            </div>

        </div>


        @if($brands->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            برند
                        </th>

                        <th>
                            شناسه
                        </th>

                        <th>
                            محصولات
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

                    @foreach($brands as $brand)

                        @php
                            $logo = $brand->logoMedia;
                        @endphp

                        <tr>

                            {{-- BRAND --}}

                            <td>

                                <div class="admin-product-cell">

                                    @if($logo?->url)

                                        <img
                                            src="{{ $logo->url }}"
                                            alt="{{ $brand->name }}"
                                            class="admin-product-thumb"
                                            loading="lazy"
                                        >

                                    @else

                                        <div class="admin-product-thumb-empty">

                                            {{ mb_substr($brand->name, 0, 1) }}

                                        </div>

                                    @endif


                                    <div>

                                        <div class="admin-product-name">
                                            {{ $brand->name }}
                                        </div>

                                        @if($brand->description)

                                            <div class="admin-product-meta">

                                                {{ \Illuminate\Support\Str::limit(
                                                    $brand->description,
                                                    70
                                                ) }}

                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- SLUG --}}

                            <td dir="ltr">

                                @if($brand->slug)

                                    <span class="admin-muted">
                                        {{ $brand->slug }}
                                    </span>

                                @else

                                    <span class="admin-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- PRODUCTS COUNT --}}

                            <td>

                                <span class="admin-price">
                                    {{ number_format($brand->products_count ?? 0) }}
                                </span>

                            </td>


                            {{-- STATUS --}}

                            <td>

                                @if($brand->is_active)

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

                                <div class="admin-actions">

                                    <a
                                        href="{{ route('admin.brands.edit', $brand) }}"
                                        class="admin-btn admin-btn--ghost admin-btn--sm"
                                    >
                                        ویرایش
                                    </a>


                                    <form
                                        method="POST"
                                        action="{{ route('admin.brands.destroy', $brand) }}"
                                        onsubmit="return confirm('آیا از حذف این برند مطمئن هستید؟');"
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

            @if($brands->hasPages())

                <div class="admin-pagination">

                    {{ $brands->links() }}

                </div>

            @endif


        @else

            <div class="admin-empty">

                <div class="admin-empty__icon">
                    —
                </div>

                <h3 class="admin-empty__title">
                    برندی پیدا نشد
                </h3>

                <p class="admin-empty__text">
                    با فیلترهای فعلی برندی وجود ندارد.
                </p>

                <div style="margin-top:16px;">

                    <a
                        href="{{ route('admin.brands.create') }}"
                        class="admin-btn admin-btn--secondary"
                    >
                        افزودن برند
                    </a>

                </div>

            </div>

        @endif

    </div>

@endsection
