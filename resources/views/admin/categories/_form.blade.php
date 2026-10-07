@php
    $category = $category ?? new \App\Models\Category();
    $cover = $category->coverMedia;
@endphp


{{-- =========================================================
    BASIC INFORMATION
========================================================= --}}

<div class="admin-card">

    <div class="admin-card-header">

        <div>

            <h2 class="admin-card-title">
                اطلاعات دسته‌بندی
            </h2>

            <p class="admin-card-description">
                اطلاعات اصلی دسته‌بندی را وارد کنید.
            </p>

        </div>

    </div>


    <div class="admin-card-body">

        <div class="admin-form-grid">

            {{-- NAME --}}

            <div class="admin-field">

                <label for="name">
                    نام دسته‌بندی *
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $category->name) }}"
                    placeholder="مثلاً لباس زیر"
                    autocomplete="off"
                    required
                >

                @error('name')
                <small class="admin-help" style="color:var(--admin-danger);">
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- SLUG --}}

            <div class="admin-field">

                <label for="slug">
                    شناسه دسته‌بندی
                </label>

                <div style="display:flex; gap:8px; align-items:stretch;">

                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $category->slug) }}"
                        dir="ltr"
                        placeholder="خودکار ساخته می‌شود"
                        autocomplete="off"
                        style="flex:1;"
                    >

                    <button
                        type="button"
                        id="generate-category-slug"
                        class="admin-btn admin-btn--ghost"
                        style="white-space:nowrap;"
                    >
                        خودکار
                    </button>

                </div>

                <small class="admin-help">
                    در صورت خالی بودن، از نام دسته‌بندی ساخته می‌شود.
                </small>

                @error('slug')
                <small class="admin-help" style="color:var(--admin-danger);">
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- PARENT --}}

            <div class="admin-field">

                <label for="parent_id">
                    دسته‌بندی والد
                </label>

                <select id="parent_id" name="parent_id">

                    <option value="">
                        بدون والد — دسته اصلی
                    </option>

                    @foreach($parentCategories as $parent)

                        <option
                            value="{{ $parent->id }}"
                            @selected(
                            (string) old(
                        'parent_id',
                        $category->parent_id
                        ) === (string) $parent->id
                        )
                        >
                        {{ $parent->name }}
                        </option>

                    @endforeach

                </select>

                <small class="admin-help">
                    برای ساخت یک دسته اصلی، این گزینه را خالی بگذارید.
                </small>

                @error('parent_id')
                <small class="admin-help" style="color:var(--admin-danger);">
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
                    max="9999"
                    value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                    inputmode="numeric"
                >

                <small class="admin-help">
                    عدد کمتر، معمولاً زودتر نمایش داده می‌شود.
                </small>

                @error('sort_order')
                <small class="admin-help" style="color:var(--admin-danger);">
                    {{ $message }}
                </small>
                @enderror

            </div>


            {{-- DESCRIPTION --}}

            <div class="admin-field admin-field-full">

                <label for="description">
                    توضیحات
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="توضیح کوتاهی درباره این دسته‌بندی بنویسید..."
                >{{ old('description', $category->description) }}</textarea>

                <small class="admin-help">
                    این بخش اختیاری است.
                </small>

                @error('description')
                <small class="admin-help" style="color:var(--admin-danger);">
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
                    $category->exists
                    ? $category->is_active
                    : true
                    )
                    )
                    >

                    <span>
                        دسته‌بندی فعال باشد
                    </span>

                </label>

                <small class="admin-help">
                    دسته‌بندی فعال در بخش‌های مربوط به فروشگاه قابل نمایش خواهد بود.
                </small>

                @error('is_active')
                <small class="admin-help" style="color:var(--admin-danger);">
                    {{ $message }}
                </small>
                @enderror

            </div>

        </div>

    </div>

</div>


<div class="admin-card admin-form-section">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">نمایش در موتورهای جستجو</h2>
            <p class="admin-card-description">این عنوان و توضیح در نتایج جستجو استفاده می‌شود؛ در صورت خالی ماندن، مقدار پیش‌فرض ساخته می‌شود.</p>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="admin-form-grid">
            <div class="admin-field admin-field-full">
                <label for="meta_title">عنوان SEO</label>
                <input id="meta_title" name="meta_title" type="text" maxlength="180" value="{{ old('meta_title', $category->meta_title) }}">
                @error('meta_title')<small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>@enderror
            </div>
            <div class="admin-field admin-field-full">
                <label for="meta_description">توضیحات SEO</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320">{{ old('meta_description', $category->meta_description) }}</textarea>
                @error('meta_description')<small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>@enderror
            </div>
        </div>
    </div>
</div>


{{-- =========================================================
    CATEGORY IMAGE
========================================================= --}}

<div class="admin-card admin-form-section">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">تصویر دسته‌بندی</h2>
            <p class="admin-card-description">تصویر را انتخاب کن و کادر دقیق نمایش آن را قبل از ذخیره تنظیم کن.</p>
        </div>
    </div>
    <div class="admin-card-body">
        @include('admin.components.media-picker', [
            'name' => 'image_file',
            'id' => 'image_file',
            'label' => 'انتخاب تصویر',
            'help' => 'عکس را بکش و زوم کن تا دقیقاً همان قسمت موردنظر در فروشگاه نمایش داده شود.',
            'currentUrl' => $cover?->url,
            'currentAlt' => $category->name,
            'ratio' => '4:5',
        ])
        @error('image_file')
            <small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>
        @enderror
    </div>
</div>


{{-- =========================================================
    FORM ACTIONS
========================================================= --}}

<div class="admin-form-actions">

    <a
        href="{{ route('admin.categories.index') }}"
        class="admin-btn admin-btn--ghost"
    >
        انصراف
    </a>

    <button
        type="submit"
        class="admin-btn admin-btn--secondary"
    >
        {{ $category->exists ? 'ذخیره تغییرات' : 'ایجاد دسته‌بندی' }}
    </button>

</div>


{{-- =========================================================
    CATEGORY FORM JS
========================================================= --}}

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const slugButton = document.getElementById('generate-category-slug');

        const imageInput = document.getElementById('image_file');
        const preview = document.getElementById('category-image-preview');
        const previewImage = document.getElementById('category-image-preview-image');


        /*
         * Persian / Arabic → Latin
         */

        const persianMap = {
            'ا': 'a',
            'آ': 'a',
            'ب': 'b',
            'پ': 'p',
            'ت': 't',
            'ث': 's',
            'ج': 'j',
            'چ': 'ch',
            'ح': 'h',
            'خ': 'kh',
            'د': 'd',
            'ذ': 'z',
            'ر': 'r',
            'ز': 'z',
            'ژ': 'zh',
            'س': 's',
            'ش': 'sh',
            'ص': 's',
            'ض': 'z',
            'ط': 't',
            'ظ': 'z',
            'ع': 'a',
            'غ': 'gh',
            'ف': 'f',
            'ق': 'gh',
            'ک': 'k',
            'گ': 'g',
            'ل': 'l',
            'م': 'm',
            'ن': 'n',
            'و': 'v',
            'ه': 'h',
            'ی': 'y',
            'ي': 'y',
            'ئ': 'y',
            'ة': 'h',
            'ؤ': 'v',
            'ء': ''
        };


        const generateSlug = (value) => {

            let text = String(value || '').trim().toLowerCase();

            text = text
                .split('')
                .map(char => persianMap[char] ?? char)
                .join('');

            return text
                .replace(/['’"`]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-+/g, '-');

        };


        const syncSlug = () => {

            if (!nameInput || !slugInput) {
                return;
            }

            slugInput.value = generateSlug(nameInput.value);

        };


        slugButton?.addEventListener('click', () => {

            syncSlug();

            if (slugInput) {
                slugInput.dataset.autoGenerated = 'true';
            }

        });


        nameInput?.addEventListener('input', () => {

            if (
                !slugInput?.value ||
                slugInput?.dataset.autoGenerated === 'true'
            ) {
                syncSlug();

                if (slugInput) {
                    slugInput.dataset.autoGenerated = 'true';
                }
            }

        });


        slugInput?.addEventListener('input', () => {

            slugInput.dataset.autoGenerated = 'false';

        });


        /*
         * Image preview
         */

        imageInput?.addEventListener('change', () => {

            const file = imageInput.files?.[0];

            if (!file) {

                preview.hidden = true;
                previewImage.removeAttribute('src');

                return;

            }


            if (!file.type.startsWith('image/')) {

                imageInput.value = '';
                preview.hidden = true;
                previewImage.removeAttribute('src');

                return;

            }


            const reader = new FileReader();


            reader.onload = (event) => {

                previewImage.src = String(
                    event.target?.result || ''
                );

                preview.hidden = false;

            };


            reader.readAsDataURL(file);

        });


        /*
         * Existing slug should not be overwritten automatically
         */

        if (
            slugInput?.value &&
            nameInput?.value
        ) {
            slugInput.dataset.autoGenerated = 'false';
        }

    });
</script>
