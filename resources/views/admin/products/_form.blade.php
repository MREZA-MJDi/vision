@php
    $product = $product ?? null;
    $variant = $variant ?? null;

    $existingAttributes = is_array($product?->attributes)
        ? $product->attributes
        : [];

    if (old('attributes_json') !== null) {
        $decodedOldAttributes = json_decode(
            old('attributes_json'),
            true
        );

        if (is_array($decodedOldAttributes)) {
            $existingAttributes = $decodedOldAttributes;
        }
    }

    if (empty($existingAttributes)) {
        $existingAttributes = [
            '' => '',
        ];
    }

    $galleryImage = $product?->galleryMedia?->first();

    $existingColorCode = $variant?->color_code;

    $defaultCategoryId = old(
        'category_id',
        $product?->category_id ?? $categories->first()?->id
    );

    $imagePreviewUrl = $galleryImage?->url;
@endphp


<div class="admin-form-grid">

    {{-- =========================================================
        MAIN INFORMATION
    ========================================================== --}}

    <section class="admin-card admin-form-section admin-form-section-full">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    اطلاعات اصلی
                </h2>

                <p class="admin-card-description">
                    فقط اطلاعات ساده محصول را وارد کنید؛ بخش‌های فنی خودکار مدیریت می‌شوند.
                </p>

            </div>

        </div>


        <div class="admin-form-grid">

            {{-- Name --}}

            <div class="admin-field">

                <label for="name">
                    نام محصول
                    <span class="required">*</span>
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $product?->name) }}"
                    maxlength="180"
                    placeholder="مثلاً لباس خواب ساتن"
                    required
                >

            </div>


            {{-- Slug --}}

            <div class="admin-field">

                <label for="slug">
                    آدرس محصول
                </label>

                <div style="display:flex;gap:8px;">

                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $product?->slug) }}"
                        dir="ltr"
                        maxlength="200"
                        placeholder="خودکار ساخته می‌شود"
                    >

                    <button
                        type="button"
                        id="generate-slug"
                        class="admin-btn admin-btn--ghost admin-btn--sm"
                    >
                        خودکار
                    </button>

                </div>

                <small class="admin-help">
                    لازم نیست خودتان چیزی وارد کنید.
                </small>

            </div>


            {{-- Category --}}

            <div class="admin-field">

                <label for="category_id">
                    دسته‌بندی
                    <span class="required">*</span>
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        انتخاب دسته‌بندی
                    </option>

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                            (string) $defaultCategoryId
                            ===
                            (string) $category->id
                            )
                            >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Brand --}}

            <div class="admin-field">

                <label for="brand_id">
                    برند
                </label>

                <select
                    id="brand_id"
                    name="brand_id"
                >

                    <option value="">
                        بدون برند
                    </option>

                    @foreach($brands as $brand)

                        <option
                            value="{{ $brand->id }}"
                            @selected(
                            (string) old(
                        'brand_id',
                        $product?->brand_id
                        )
                        ===
                        (string) $brand->id
                        )
                        >
                        {{ $brand->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Short description --}}

            <div class="admin-field admin-field-full">

                <label for="short_description">
                    توضیح کوتاه
                </label>

                <textarea
                    id="short_description"
                    name="short_description"
                    rows="3"
                    maxlength="1000"
                    placeholder="یک توضیح کوتاه درباره محصول..."
                >{{ old('short_description', $product?->short_description) }}</textarea>

            </div>


            {{-- Description --}}

            <div class="admin-field admin-field-full">

                <label for="description">
                    توضیحات محصول
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="7"
                    maxlength="10000"
                    placeholder="توضیحات کامل محصول..."
                >{{ old('description', $product?->description) }}</textarea>

            </div>

        </div>

    </section>


    {{-- =========================================================
        SEO
    ========================================================== --}}

    @php
        $defaultMetaTitle = old(
            'meta_title',
            $product?->meta_title ?: (
                filled($product?->name)
                    ? $product->name . ' | Janan'
                    : ''
            )
        );

        $defaultMetaDescription = old(
            'meta_description',
            $product?->meta_description ?: (
                $product?->short_description
                    ?: $product?->description
                    ?: 'معرفی و خرید ' . ($product?->name ?: 'محصول') . ' از فروشگاه جانان.'
            )
        );
    @endphp

    <section class="admin-card admin-form-section admin-form-section-full">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">نمایش در گوگل و اشتراک‌گذاری</h2>
                <p class="admin-card-description">
                    این بخش لازم نیست پیچیده باشد؛ اگر خالی بماند، جانان مقدار پیش‌فرض مناسبی بر اساس نام و توضیحات محصول می‌سازد.
                </p>
            </div>
        </div>

        <div class="admin-form-grid">
            <div class="admin-field admin-field-full">
                <label for="meta_title">عنوان SEO</label>
                <input
                    id="meta_title"
                    type="text"
                    name="meta_title"
                    maxlength="180"
                    value="{{ $defaultMetaTitle }}"
                    placeholder="مثلاً کرم مرطوب‌کننده آبرسان | Janan"
                >
                <small class="admin-help">
                    این متن عنوان صفحه در نتایج جستجو و عنوان مرورگر است. حالت پیشنهادی: نام محصول + نام فروشگاه.
                </small>
            </div>

            <div class="admin-field admin-field-full">
                <label for="meta_description">توضیحات SEO</label>
                <textarea
                    id="meta_description"
                    name="meta_description"
                    rows="4"
                    maxlength="320"
                    placeholder="یک توضیح کوتاه و روشن درباره محصول..."
                >{{ $defaultMetaDescription }}</textarea>
                <small class="admin-help">
                    توضیح کوتاهی که کاربر قبل از ورود به صفحه می‌تواند در نتایج جستجو ببیند. حدود ۱۲۰ تا ۱۶۰ کاراکتر معمولاً خواناتر است.
                </small>
            </div>

            <div class="admin-seo-preview admin-field-full">
                <span>پیش‌نمایش تقریبی</span>
                <strong data-seo-preview-title>{{ $defaultMetaTitle ?: 'عنوان محصول' }}</strong>
                <small data-seo-preview-url>janan.local/products/{{ $product?->slug ?: 'product-slug' }}</small>
                <p data-seo-preview-description>{{ $defaultMetaDescription ?: 'توضیحات کوتاه محصول در این قسمت دیده می‌شود.' }}</p>
            </div>
        </div>
    </section>

    {{-- =========================================================
        STATUS
    ========================================================== --}}

    <section class="admin-card admin-form-section">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    وضعیت
                </h2>

                <p class="admin-card-description">
                    وضعیت نمایش محصول در فروشگاه
                </p>

            </div>

        </div>


        <div class="admin-checkbox-list">

            <label class="admin-checkbox">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(
                    old(
                'is_active',
                $product?->is_active ?? true
                )
                )
                >

                <span>
                    محصول فعال باشد
                </span>

            </label>


            <label class="admin-checkbox">

                <input
                    type="checkbox"
                    name="is_featured"
                    value="1"
                    @checked(
                    old(
                'is_featured',
                $product?->is_featured ?? false
                )
                )
                >

                <span>
                    محصول ویژه باشد
                </span>

            </label>

        </div>

    </section>


    {{-- =========================================================
        SORT
    ========================================================== --}}

    <section class="admin-card admin-form-section">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    ترتیب نمایش
                </h2>

                <p class="admin-card-description">
                    اختیاری
                </p>

            </div>

        </div>


        <div class="admin-field">

            <label for="sort_order">
                ترتیب
            </label>

            <input
                id="sort_order"
                type="number"
                name="sort_order"
                min="0"
                max="9999"
                value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
            >

        </div>

    </section>


    {{-- =========================================================
        VARIANT
    ========================================================== --}}

    <section class="admin-card admin-form-section admin-form-section-full">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    قیمت و موجودی
                </h2>

                <p class="admin-card-description">
                    این بخش یک گزینهٔ اولیه برای شروع فروش می‌سازد. برای رنگ یا سایزهای دیگر، پس از ذخیره محصول «مدیریت واریانت‌ها» را باز کن؛ هر ترکیب رنگ و سایز یک ردیف جداست و رنگ می‌تواند تکرار شود.
                </p>

            </div>

        </div>


        <div class="admin-form-grid">

            {{-- SKU --}}

            <div class="admin-field">

                <label for="sku">
                    کد کالا
                </label>

                <div style="display:flex;gap:8px;">

                    <input
                        id="sku"
                        type="text"
                        name="sku"
                        value="{{ old('sku', $variant?->sku) }}"
                        dir="ltr"
                        maxlength="120"
                        placeholder="خودکار ساخته می‌شود"
                    >

                    <button
                        type="button"
                        id="generate-sku"
                        class="admin-btn admin-btn--ghost admin-btn--sm"
                    >
                        خودکار
                    </button>

                </div>

                <small class="admin-help">
                    لازم نیست کاربر کد کالا بلد باشد.
                </small>

            </div>


            {{-- Size --}}

            <div class="admin-field">

                <label for="size">
                    سایز
                </label>

                <input
                    id="size"
                    type="text"
                    name="size"
                    value="{{ old('size', $variant?->size) }}"
                    maxlength="80"
                    placeholder="مثلاً M یا 38"
                >

            </div>


            {{-- Color --}}

            <div class="admin-field">

                <label for="color">
                    رنگ
                </label>

                <input
                    id="color"
                    type="text"
                    name="color"
                    value="{{ old('color', $variant?->color) }}"
                    maxlength="80"
                    placeholder="مثلاً مشکی"
                >

            </div>


            {{-- Color picker --}}

            <div class="admin-field">

                <label for="color_picker">
                    انتخاب رنگ
                </label>

                <div style="display:flex;align-items:center;gap:10px;">

                    <input
                        id="color_picker"
                        type="color"
                        value="{{ $existingColorCode ?: '#000000' }}"
                        style="
                            width:54px;
                            min-height:44px;
                            padding:4px;
                            cursor:pointer;
                        "
                    >

                    <input
                        id="color_code"
                        type="text"
                        name="color_code"
                        value="{{ old('color_code', $existingColorCode) }}"
                        dir="ltr"
                        maxlength="20"
                        placeholder="اختیاری"
                    >

                </div>

                <small class="admin-help">
                    لازم نیست کد رنگ را بدانید؛ فقط رنگ را انتخاب کنید.
                </small>

            </div>


            {{-- Price --}}

            <div class="admin-field">

                <label for="price">
                    قیمت اصلی
                    <span class="required">*</span>
                </label>

                <input
                    id="price"
                    type="text"
                    name="price"
                    value="{{ old('price', $variant?->price ?? 0) }}"
                    inputmode="numeric"
                    data-money-input
                    required
                >

                <small class="admin-help">
                    مبلغ به تومان وارد شود.
                </small>

            </div>


            {{-- Sale price --}}

            <div class="admin-field">

                <label for="sale_price">
                    قیمت تخفیفی
                </label>

                <input
                    id="sale_price"
                    type="text"
                    name="sale_price"
                    value="{{ old('sale_price', $variant?->sale_price) }}"
                    inputmode="numeric"
                    data-money-input
                    placeholder="اختیاری"
                >

                <small class="admin-help">
                    اگر تخفیف ندارید خالی بگذارید. اگر بیشتر از قیمت اصلی باشد، نادیده گرفته می‌شود.
                </small>

            </div>


            {{-- Wholesale price --}}

            <div class="admin-field">

                <label for="wholesale_price">
                    قیمت عمده
                </label>

                <input
                    id="wholesale_price"
                    type="text"
                    name="wholesale_price"
                    value="{{ old('wholesale_price', $variant?->wholesale_price) }}"
                    inputmode="numeric"
                    data-money-input
                    placeholder="اختیاری"
                >

                <small class="admin-help">
                    فقط برای مشتریانی که دسترسی خرید عمده آن‌ها تأیید شده است.
                </small>

            </div>


            {{-- Stock --}}

            <div class="admin-field">

                <label for="stock">
                    موجودی
                    <span class="required">*</span>
                </label>

                <input
                    id="stock"
                    type="number"
                    name="stock"
                    min="0"
                    value="{{ old('stock', $variant?->stock ?? 0) }}"
                    required
                >

            </div>


            {{-- Low stock --}}

            <div class="admin-field">

                <label for="low_stock_threshold">
                    هشدار موجودی
                </label>

                <input
                    id="low_stock_threshold"
                    type="number"
                    name="low_stock_threshold"
                    min="0"
                    value="{{ old(
                        'low_stock_threshold',
                        $variant?->low_stock_threshold ?? 5
                    ) }}"
                    required
                >

                <small class="admin-help">
                    وقتی موجودی به این عدد برسد، هشدار داده می‌شود.
                </small>

            </div>

        </div>

    </section>


    {{-- =========================================================
        ATTRIBUTES
    ========================================================== --}}

    <section class="admin-card admin-form-section admin-form-section-full">

        <div class="admin-card-header">

            <div>

                <h2 class="admin-card-title">
                    ویژگی‌های محصول
                </h2>

                <p class="admin-card-description">
                    ویژگی‌ها را مثل «جنس / ساتن» وارد کنید. نیازی به JSON نیست.
                </p>

            </div>


            <button
                type="button"
                id="add-attribute"
                class="admin-btn admin-btn--ghost admin-btn--sm"
            >
                + افزودن ویژگی
            </button>

        </div>


        <div
            id="attributes-list"
            class="admin-card-body"
        >

            @foreach($existingAttributes as $key => $value)

                <div
                    class="attribute-row"
                    style="
                        display:grid;
                        grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;
                        gap:10px;
                        margin-bottom:10px;
                    "
                >

                    <input
                        type="text"
                        class="attribute-key"
                        value="{{ $key }}"
                        placeholder="ویژگی، مثلاً جنس"
                    >

                    <input
                        type="text"
                        class="attribute-value"
                        value="{{ $value }}"
                        placeholder="مقدار، مثلاً ساتن"
                    >

                    <button
                        type="button"
                        class="admin-btn admin-btn--danger admin-btn--sm remove-attribute"
                    >
                        حذف
                    </button>

                </div>

            @endforeach

        </div>

        <input
            type="hidden"
            name="attributes_json"
            id="attributes_json"
            value="{{ old('attributes_json') }}"
        >

    </section>


    {{-- =========================================================
        IMAGE
    ========================================================== --}}

    <section class="admin-card admin-form-section admin-form-section-full">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">تصویر اصلی محصول</h2>
                <p class="admin-card-description">تصویر را انتخاب کن و قبل از ذخیره کادر دقیق نمایش محصول را مشخص کن.</p>
            </div>
        </div>
        <div style="padding:20px 22px 22px;">
            @include('admin.components.media-picker', [
                'name' => 'image_file',
                'id' => 'image_file',
                'label' => 'انتخاب تصویر اصلی',
                'help' => 'عکس را بکش و زوم کن تا دقیقاً همان قسمت موردنظر در کارت و صفحه محصول دیده شود.',
                'currentUrl' => $imagePreviewUrl,
                'currentAlt' => $product?->name ?: 'محصول',
                'ratio' => '4:5',
            ])
        </div>
    </section>

</div>
