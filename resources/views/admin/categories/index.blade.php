@extends('layouts.admin')

@section('title', 'دسته‌بندی‌ها')
@section('page-title', 'دسته‌بندی‌ها')

@section('content')

    {{-- =====================================================
        PAGE HEADER
    ====================================================== --}}

    <div class="admin-page-head">

        <div>

            <h1 class="admin-page-head__title">
                دسته‌بندی‌ها
            </h1>

            <p class="admin-page-head__text">
                مدیریت ساختار دسته‌بندی محصولات فروشگاه
            </p>

        </div>

        <a
            href="{{ route('admin.categories.create') }}"
            class="admin-btn admin-btn--secondary"
        >
            + افزودن دسته‌بندی
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
                    دسته‌بندی را با نام، شناسه یا وضعیت پیدا کنید.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('admin.categories.index') }}"
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
                        placeholder="نام دسته‌بندی یا شناسه..."
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
                        href="{{ route('admin.categories.index') }}"
                        class="admin-btn admin-btn--ghost"
                    >
                        پاک کردن
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
        CATEGORIES
    ====================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    فهرست دسته‌بندی‌ها
                </h2>

                <p class="admin-card-description">
                    {{ number_format($categories->total()) }}
                    دسته‌بندی
                </p>

            </div>

        </div>


        @if($categories->count())

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>

                    <tr>

                        <th>
                            دسته‌بندی
                        </th>

                        <th>
                            والد
                        </th>

                        <th>
                            محصولات
                        </th>

                        <th>
                            ترتیب
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

                    @foreach($categories as $category)

                        @php
                            $cover = $category->coverMedia;
                        @endphp

                        <tr>

                            {{-- CATEGORY --}}

                            <td>

                                <div class="admin-product-cell">

                                    @if($cover?->url)

                                        <img
                                            src="{{ $cover->url }}"
                                            alt="{{ $category->name }}"
                                            class="admin-product-thumb"
                                            loading="lazy"
                                        >

                                    @else

                                        <div class="admin-product-thumb-empty">
                                            {{ mb_substr($category->name, 0, 1) }}
                                        </div>

                                    @endif


                                    <div>

                                        <div class="admin-product-name">
                                            {{ $category->name }}
                                        </div>

                                        <div class="admin-product-meta" dir="ltr">
                                            {{ $category->slug }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- PARENT --}}

                            <td>

                                @if($category->parent)

                                    {{ $category->parent->name }}

                                @else

                                    <span class="admin-muted">
                                        دسته اصلی
                                    </span>

                                @endif

                            </td>


                            {{-- PRODUCTS --}}

                            <td>

                                <span class="admin-price">
                                    {{ number_format($category->products_count ?? 0) }}
                                </span>

                            </td>


                            {{-- SORT --}}

                            <td>

                                <span class="admin-muted">
                                    {{ number_format($category->sort_order ?? 0) }}
                                </span>

                            </td>


                            {{-- STATUS --}}

                            <td>

                                @if($category->is_active)

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
                                        href="{{ route('admin.categories.edit', $category) }}"
                                        class="admin-btn admin-btn--ghost admin-btn--sm"
                                    >
                                        ویرایش
                                    </a>


                                    <form
                                        method="POST"
                                        action="{{ route('admin.categories.destroy', $category) }}"
                                        onsubmit="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟');"
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

            @if($categories->hasPages())

                <div class="admin-pagination">

                    {{ $categories->links() }}

                </div>

            @endif


        @else

            <div class="admin-empty">

                <div class="admin-empty__icon">
                    —
                </div>

                <h3 class="admin-empty__title">
                    دسته‌بندی‌ای پیدا نشد
                </h3>

                <p class="admin-empty__text">
                    با فیلترهای فعلی دسته‌بندی‌ای وجود ندارد.
                </p>

                <div style="margin-top:16px;">

                    <a
                        href="{{ route('admin.categories.create') }}"
                        class="admin-btn admin-btn--secondary"
                    >
                        افزودن دسته‌بندی
                    </a>

                </div>

            </div>

        @endif

    </div>

@endsection
