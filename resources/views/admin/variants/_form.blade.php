@php
    $variant = $variant ?? new \App\Models\ProductVariant();

    $isEdit = $variant->exists;

    $currentStock = (int) old(
        'stock',
        $variant->stock ?? 0
    );

    $lowStockThreshold = old(
        'low_stock_threshold',
        $variant->low_stock_threshold ?? 5
    );

    $sortOrder = old(
        'sort_order',
        $variant->sort_order ?? 0
    );

    $colorCode = old(
        'color_code',
        $variant->color_code ?? '#000000'
    );

    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $colorCode)) {
        $colorCode = '#000000';
    }
@endphp


{{-- =========================================================
    VARIANT INFORMATION
========================================================= --}}

<div class="admin-card">

    <div class="admin-card-header">

        <div>

            <h2 class="admin-card-title">
                مشخصات واریانت
            </h2>

            <p class="admin-card-description">
                اطلاعاتی مثل سایز، رنگ و قیمت این گزینه محصول را وارد کنید.
            </p>

        </div>

    </div>


    <div class="admin-card-body">

        <div class="admin-form-grid">

            {{-- SKU --}}

            <div class="admin-field">

                <label for="sku">
                    کد کالا
                </label>

                <div style="display:flex; gap:8px; align-items:stretch;">

                    <input
                        id="sku"
                        type="text"
                        name="sku"
                        value="{{ old('sku', $variant->sku) }}"
                        dir="ltr"
                        placeholder="خودکار ساخته می‌شود"
                        autocomplete="off"
                        style="flex:1;"
                    >

                    <button
                        type="button"
                        id="generate-variant-sku"
                        class="admin-btn admin-btn--ghost"
                        style="white-space:nowrap;"
                    >
                        خودکار
                    </button>

                </div>

                <small class="admin-help">
                    می‌توانید خالی بگذارید تا سیستم یک کد کالا بسازد.
                </small>

                @error('sku')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- SIZE --}}

            <div class="admin-field">

                <label for="size">
                    سایز
                </label>

                <input
                    id="size"
                    type="text"
                    name="size"
                    value="{{ old('size', $variant->size) }}"
                    placeholder="مثلاً S، M، L یا 75B"
                >

                @error('size')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- COLOR NAME --}}

            <div class="admin-field">

                <label for="color">
                    رنگ
                </label>

                <input
                    id="color"
                    type="text"
                    name="color"
                    value="{{ old('color', $variant->color) }}"
                    placeholder="مثلاً مشکی"
                >

                @error('color')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- COLOR PICKER --}}

            <div class="admin-field">

                <label for="color_code">
                    رنگ نمایش
                </label>

                <div style="display:flex; gap:10px; align-items:center;">

                    <input
                        id="color_code"
                        type="color"
                        name="color_code"
                        value="{{ $colorCode }}"
                        style="
                            width:52px;
                            height:44px;
                            padding:4px;
                            cursor:pointer;
                        "
                        aria-label="انتخاب رنگ"
                    >

                    <span
                        id="color-preview-name"
                        class="admin-muted"
                    >
                        رنگ انتخاب‌شده
                    </span>

                </div>

                <small class="admin-help">
                    برای تعیین رنگ ظاهری محصول استفاده می‌شود.
                </small>

                @error('color_code')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- PRICE --}}

            <div class="admin-field">

                <label for="price">
                    قیمت اصلی *
                </label>

                <input
                    id="price"
                    type="text"
                    name="price"
                    value="{{ old('price', $variant->price) }}"
                    inputmode="numeric"
                    data-money-input
                    placeholder="مثلاً ۱۲۹۰۰۰۰"
                    required
                >

                <small class="admin-help">
                    مبلغ به تومان وارد شود.
                </small>

                @error('price')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- WHOLESALE PRICE --}}

            <div class="admin-field">

                <label for="wholesale_price">
                    قیمت عمده
                </label>

                <input
                    id="wholesale_price"
                    type="text"
                    name="wholesale_price"
                    value="{{ old('wholesale_price', $variant->wholesale_price) }}"
                    inputmode="numeric"
                    data-money-input
                    placeholder="اختیاری"
                >

                <small class="admin-help">
                    فقط برای مشتریان عمده تأییدشده استفاده می‌شود.
                </small>

                @error('wholesale_price')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- SALE PRICE --}}

            <div class="admin-field">

                <label for="sale_price">
                    قیمت فروش ویژه
                </label>

                <input
                    id="sale_price"
                    type="text"
                    name="sale_price"
                    value="{{ old('sale_price', $variant->sale_price) }}"
                    inputmode="numeric"
                    data-money-input
                    placeholder="اختیاری"
                >

                <small class="admin-help">
                    در صورت تخفیف، قیمت نهایی فروش را وارد کنید.
                </small>

                @error('sale_price')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- STOCK --}}

            <div class="admin-field">

                <label for="stock">
                    موجودی
                    @if(!$isEdit)
                        *
                    @endif
                </label>

                <input
                    id="stock"
                    type="number"
                    name="stock"
                    min="0"
                    value="{{ $currentStock }}"
                    inputmode="numeric"
                    @required(!$isEdit)
                    @readonly($isEdit)
                >

                @if($isEdit)

                    <small class="admin-help">
                        موجودی فعلی است. برای افزایش یا کاهش موجودی از بخش انبار استفاده کنید.
                    </small>

                @else

                    <small class="admin-help">
                        موجودی اولیه این واریانت را وارد کنید.
                    </small>

                @endif

                @error('stock')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- LOW STOCK THRESHOLD --}}

            <div class="admin-field">

                <label for="low_stock_threshold">
                    حد هشدار موجودی
                </label>

                <input
                    id="low_stock_threshold"
                    type="number"
                    name="low_stock_threshold"
                    min="0"
                    value="{{ $lowStockThreshold }}"
                    inputmode="numeric"
                >

                <small class="admin-help">
                    وقتی موجودی به این عدد برسد، هشدار کمبود نمایش داده می‌شود.
                </small>

                @error('low_stock_threshold')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- SORT ORDER --}}

            <div class="admin-field">

                <label for="sort_order">
                    ترتیب نمایش
                </label>

                <input
                    id="sort_order"
                    type="number"
                    name="sort_order"
                    min="0"
                    value="{{ $sortOrder }}"
                    inputmode="numeric"
                >

                <small class="admin-help">
                    عدد کمتر، زودتر نمایش داده می‌شود.
                </small>

                @error('sort_order')
                <small
                    class="admin-help"
                    style="color:var(--admin-danger);"
                >
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- ACTIVE --}}

            <div class="admin-field admin-field-full">

                <label class="admin-checkbox">

                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(
                        old(
                    'is_active',
                    $variant->exists
                    ? $variant->is_active
                    : true
                    )
                    )
                    >

                    <span>
                        این واریانت فعال و قابل خرید باشد
                    </span>

                </label>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    PARENT PRODUCT
========================================================= --}}

<div class="admin-card admin-form-section">

    <div class="admin-card-header">

        <div>

            <h2 class="admin-card-title">
                محصول والد
            </h2>

            <p class="admin-card-description">
                این واریانت متعلق به محصول زیر است.
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

                    @if($product->category)
                        {{ $product->category->name }}
                    @endif

                    @if($product->brand)

                        @if($product->category)
                            ·
                        @endif

                        {{ $product->brand->name }}

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    FORM ACTIONS
========================================================= --}}

<div class="admin-form-actions">

    <a
        href="{{ route('admin.products.variants.index', $product) }}"
        class="admin-btn admin-btn--ghost"
    >
        انصراف
    </a>

    <button
        type="submit"
        class="admin-btn admin-btn--secondary"
    >
        {{ $variant->exists ? 'ذخیره تغییرات' : 'ایجاد واریانت' }}
    </button>

</div>


{{-- =========================================================
    VARIANT FORM JS
========================================================= --}}
