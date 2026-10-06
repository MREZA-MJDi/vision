@extends('layouts.admin')

@section('title', 'محصولات')
@section('page-title', 'محصولات')

@section('content')

    <div data-product-index>

        {{-- =====================================================
            PAGE HEADER
        ====================================================== --}}

        <div class="admin-page-head">

            <div>

                <h1 class="admin-page-head__title">
                    محصولات
                </h1>

                <p class="admin-page-head__text">
                    مدیریت محصولات، قیمت، موجودی و وضعیت انتشار
                </p>

            </div>


            <a
                href="{{ route('admin.products.create') }}"
                class="admin-btn admin-btn--secondary"
            >
                + ایجاد محصول
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
                        محصول را با نام، SKU، دسته‌بندی یا وضعیت پیدا کنید.
                    </p>

                </div>

            </div>


            <form
                method="GET"
                action="{{ route('admin.products.index') }}"
            >

                <div class="admin-filter-grid">

                    <div class="admin-field">

                        <label for="q">
                            جستجو
                        </label>

                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="نام محصول یا کد کالا..."
                        >

                    </div>


                    <div class="admin-field">

                        <label for="filter_category_id">
                            دسته‌بندی
                        </label>

                        <select
                            id="filter_category_id"
                            name="category_id"
                        >

                            <option value="">
                                همه دسته‌بندی‌ها
                            </option>

                            @foreach($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                    (string) request('category_id')
                                ===
                                (string) $category->id
                                )
                                >
                                {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="admin-field">

                        <label for="filter_brand_id">
                            برند
                        </label>

                        <select
                            id="filter_brand_id"
                            name="brand_id"
                        >

                            <option value="">
                                همه برندها
                            </option>

                            @foreach($brands as $brand)

                                <option
                                    value="{{ $brand->id }}"
                                    @selected(
                                    (string) request('brand_id')
                                ===
                                (string) $brand->id
                                )
                                >
                                {{ $brand->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="admin-field">

                        <label for="status">
                            وضعیت
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option value="">
                                همه وضعیت‌ها
                            </option>

                            <option
                                value="active"
                                @selected(request('status') === 'active')
                            >
                            فعال
                            </option>

                            <option
                                value="inactive"
                                @selected(request('status') === 'inactive')
                            >
                            غیرفعال
                            </option>

                            <option
                                value="featured"
                                @selected(request('status') === 'featured')
                            >
                            ویژه
                            </option>

                        </select>

                    </div>


                    <div class="admin-field">

                        <label for="stock">
                            موجودی
                        </label>

                        <select
                            id="stock"
                            name="stock"
                        >

                            <option value="">
                                همه
                            </option>

                            <option
                                value="low"
                                @selected(request('stock') === 'low')
                            >
                            موجودی کم
                            </option>

                        </select>

                    </div>

                    <div class="admin-field">
                        <label for="quality">آمادگی کاتالوگ و SEO</label>
                        <select id="quality" name="quality">
                            <option value="">همه محصولات</option>
                            <option value="missing-seo" @selected(($quality ?? null) === 'missing-seo')>بدون عنوان یا توضیح SEO</option>
                            <option value="missing-image" @selected(($quality ?? null) === 'missing-image')>بدون تصویر محصول</option>
                            <option value="missing-variant" @selected(($quality ?? null) === 'missing-variant')>بدون واریانت فعال</option>
                        </select>
                    </div>


                    <div class="admin-filter-actions">

                        <button
                            type="submit"
                            class="admin-btn admin-btn--secondary"
                        >
                            اعمال فیلتر
                        </button>

                        <a
                            href="{{ route('admin.products.index') }}"
                            class="admin-btn admin-btn--ghost"
                        >
                            پاک کردن
                        </a>

                    </div>

                </div>

            </form>

        </div>


        {{-- =====================================================
            PRODUCTS
        ====================================================== --}}

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h2 class="admin-card-title">
                        فهرست محصولات
                    </h2>

                    <p class="admin-card-description">
                        {{ number_format($products->total()) }}
                        محصول
                    </p>

                </div>

            </div>


            @if($products->count())

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>

                        <tr>

                            <th>
                                محصول
                            </th>

                            <th>
                                برند
                            </th>

                            <th>
                                دسته‌بندی
                            </th>

                            <th>
                                واریانت / قیمت عمده
                            </th>

                            <th>
                                قیمت
                            </th>

                            <th>
                                موجودی
                            </th>

                            <th>
                                Hero
                            </th>

                            <th>
                                وضعیت
                            </th>

                            <th>
                                بروزرسانی
                            </th>

                            <th>
                                عملیات
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        @foreach($products as $product)

                            @php

                                $variant =
                                    $product->primaryActiveVariant;

                                $galleryImage =
                                    $product->primaryGalleryMedia;

                                $stock =
                                    (int) ($variant?->stock ?? 0);

                                $lowStockThreshold =
                                    (int) (
                                        $variant?->low_stock_threshold
                                        ?? 5
                                    );

                                $price =
                                    $variant?->price;

                                $salePrice =
                                    $variant?->sale_price;

                                $effectivePrice =
                                    $salePrice ?? $price;

                                $isLowStock =
                                    $stock <= $lowStockThreshold;

                            @endphp


                            <tr>

                                {{-- Product --}}

                                <td>

                                    <div class="admin-product-cell">

                                        {{-- IMAGE --}}

                                        @if($galleryImage)

                                            <button
                                                type="button"
                                                class="admin-product-thumb"
                                                style="
                                                        padding:0;
                                                        overflow:hidden;
                                                        cursor:pointer;
                                                    "
                                                data-preview-open
                                                data-preview-url="{{ $galleryImage->url }}"
                                                data-preview-name="{{ $product->name }}"
                                                title="مشاهده تصویر"
                                            >

                                                <img
                                                    src="{{ $galleryImage->url }}"
                                                    alt="{{ $product->name }}"
                                                    loading="lazy"
                                                    style="
                                                            width:100%;
                                                            height:100%;
                                                            object-fit:cover;
                                                        "
                                                    onerror="this.style.display='none';"
                                                >

                                            </button>

                                        @else

                                            <div class="admin-product-thumb-empty">

                                                    <span>
                                                        بدون تصویر
                                                    </span>

                                            </div>

                                        @endif


                                        {{-- PRODUCT INFO --}}

                                        <div>

                                            <div class="admin-product-name">
                                                {{ $product->name }}
                                            </div>

                                            <div class="admin-product-meta">

                                                @if($variant?->sku)

                                                    SKU:
                                                    <span dir="ltr">
                                                            {{ $variant->sku }}
                                                        </span>

                                                @else

                                                    بدون کد کالا

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- BRAND --}}

                                <td>
                                    @if($product->brand)
                                        <span class="admin-product-name">{{ $product->brand->name }}</span>
                                    @else
                                        <span class="admin-muted">بدون برند</span>
                                    @endif
                                </td>


                                {{-- CATEGORY --}}

                                <td>

                                    @if($product->category)

                                        {{ $product->category->name }}

                                    @else

                                        <span class="admin-muted">
                                                بدون دسته‌بندی
                                            </span>

                                    @endif

                                </td>


                                {{-- VARIANTS / WHOLESALE PRICE --}}

                                <td>
                                    <div class="admin-price">
                                        {{ number_format($product->variants_count) }}
                                        <span class="admin-muted">واریانت</span>
                                    </div>
                                    <div class="admin-muted">
                                        {{ number_format($product->wholesale_variants_count) }} قیمت عمده
                                    </div>
                                </td>


                                {{-- PRICE --}}

                                <td>

                                    @if($effectivePrice !== null)

                                        <div class="admin-price">
                                            {{ number_format((float) $effectivePrice) }}
                                        </div>

                                        @if(
                                            $salePrice !== null &&
                                            $price !== null &&
                                            (float) $salePrice < (float) $price
                                        )

                                            <div class="admin-old-price">
                                                {{ number_format((float) $price) }}
                                            </div>

                                        @endif

                                        <div class="admin-muted">
                                            تومان
                                        </div>

                                    @else

                                        <span class="admin-muted">
                                                بدون قیمت
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
                                                {{ number_format($lowStockThreshold) }}
                                            </span>

                                    @endif

                                </td>


                                {{-- HERO --}}

                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.products.hero.toggle', $product) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <label
                                            class="admin-hero-toggle"
                                            title="{{ $product->is_hero ? 'حذف از Hero صفحه اصلی' : 'نمایش در Hero صفحه اصلی' }}"
                                        >
                                            <input
                                                type="checkbox"
                                                name="is_hero"
                                                value="1"
                                                @checked($product->is_hero)
                                                onchange="this.form.submit()"
                                            >
                                            <span aria-hidden="true"></span>
                                            <span class="admin-hero-toggle__text">
                                                {{ $product->is_hero ? 'در Hero' : 'نمایش در Hero' }}
                                            </span>
                                        </label>
                                    </form>
                                </td>

                                {{-- STATUS --}}

                                <td>

                                    <div class="admin-actions">

                                        @if($product->is_active)

                                            <span class="admin-badge admin-badge--success">
                                                    فعال
                                                </span>

                                        @else

                                            <span class="admin-badge admin-badge--neutral">
                                                    غیرفعال
                                                </span>

                                        @endif


                                        @if($product->is_featured)

                                            <span class="admin-badge admin-badge--info">
                                                    ویژه
                                                </span>

                                        @endif

                                    </div>

                                </td>


                                {{-- UPDATED --}}

                                <td>

                                        <span class="admin-muted">
                                            {{ $product->updated_at?->format('Y/m/d H:i') }}
                                        </span>

                                </td>


                                {{-- ACTIONS --}}

                                <td>

                                    <div class="admin-actions">

                                        <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            class="admin-btn admin-btn--ghost admin-btn--sm"
                                        >
                                            ویرایش
                                        </a>


                                        <form
                                            method="POST"
                                            action="{{ route('admin.products.destroy', $product) }}"
                                            onsubmit="
                                                    return confirm(
                                                        'آیا از حذف این محصول مطمئن هستید؟'
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

                @if($products->hasPages())

                    <div class="admin-pagination">
                        {{ $products->links() }}
                    </div>

                @endif


            @else

                <div class="admin-empty">

                    <div class="admin-empty__icon">
                        —
                    </div>

                    <h3 class="admin-empty__title">
                        محصولی پیدا نشد
                    </h3>

                    <p class="admin-empty__text">
                        با فیلترهای فعلی محصولی وجود ندارد.
                    </p>

                    <div style="margin-top:16px;">

                        <a
                            href="{{ route('admin.products.create') }}"
                            class="admin-btn admin-btn--secondary"
                        >
                            ایجاد محصول
                        </a>

                    </div>

                </div>

            @endif

        </div>


        {{-- =====================================================
            IMAGE PREVIEW DIALOG
        ====================================================== --}}

        <dialog
            class="admin-product-preview"
            data-product-preview-modal
            aria-labelledby="product-preview-name"
        >
            <div class="admin-product-preview__inner">

                <button
                    type="button"
                    data-preview-close
                    class="admin-btn admin-btn--ghost admin-btn--sm admin-product-preview__close"
                    aria-label="بستن"
                >
                    ×
                </button>

                <img
                    data-preview-image
                    src=""
                    alt=""
                >

                <div class="admin-product-preview__caption">
                    <strong
                        id="product-preview-name"
                        data-preview-name
                    ></strong>
                </div>

            </div>
        </dialog>
        </div>

    </div>

@endsection
