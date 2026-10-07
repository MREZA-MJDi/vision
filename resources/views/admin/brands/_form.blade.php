@php
    $brand = $brand ?? new \App\Models\Brand();
    $logo = $brand->logoMedia;
@endphp

{{-- =========================================================
    BASIC INFORMATION
========================================================= --}}

<div class="admin-card">

    <div class="admin-card-header">

        <div>

            <h2 class="admin-card-title">
                اطلاعات برند
            </h2>

            <p class="admin-card-description">
                نام و اطلاعات اصلی برند را وارد کنید.
            </p>

        </div>

    </div>


    <div class="admin-card-body">

        <div class="admin-form-grid">

            {{-- NAME --}}

            <div class="admin-field">

                <label for="name">
                    نام برند *
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $brand->name) }}"
                    placeholder="مثلاً نایک"
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
                    شناسه برند
                </label>

                <div style="display:flex; gap:8px; align-items:stretch;">

                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $brand->slug) }}"
                        dir="ltr"
                        placeholder="خودکار ساخته می‌شود"
                        autocomplete="off"
                        style="flex:1;"
                    >

                    <button
                        type="button"
                        id="generate-brand-slug"
                        class="admin-btn admin-btn--ghost"
                        style="white-space:nowrap;"
                    >
                        خودکار
                    </button>

                </div>

                <small class="admin-help">
                    برای برند جدید می‌توانید این بخش را خالی بگذارید.
                </small>

                @error('slug')
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
                    placeholder="توضیح کوتاهی درباره برند بنویسید..."
                >{{ old('description', $brand->description) }}</textarea>

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
                    $brand->exists
                    ? $brand->is_active
                    : true
                    )
                    )
                    >

                    <span>
                        برند فعال باشد
                    </span>

                </label>

                <small class="admin-help">
                    برند فعال در بخش‌های مربوط به فروشگاه نمایش داده می‌شود.
                </small>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    SEO METADATA
========================================================= --}}

<div class="admin-card admin-form-section">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">نمایش در موتورهای جستجو</h2>
            <p class="admin-card-description">عنوان و توضیح نتایج جستجوی صفحه برند؛ در صورت خالی ماندن، مقدار پیش‌فرض نمایش داده می‌شود.</p>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="admin-form-grid">
            <div class="admin-field admin-field-full">
                <label for="meta_title">عنوان SEO</label>
                <input id="meta_title" name="meta_title" type="text" maxlength="180" value="{{ old('meta_title', $brand->meta_title) }}">
                @error('meta_title')<small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>@enderror
            </div>
            <div class="admin-field admin-field-full">
                <label for="meta_description">توضیحات SEO</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320">{{ old('meta_description', $brand->meta_description) }}</textarea>
                @error('meta_description')<small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>@enderror
            </div>
        </div>
    </div>
</div>


{{-- =========================================================
    LOGO
========================================================= --}}

<div class="admin-card admin-form-section">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">لوگوی برند</h2>
            <p class="admin-card-description">لوگو را انتخاب کن و کادر دقیق نمایش آن را قبل از ذخیره تنظیم کن.</p>
        </div>
    </div>
    <div class="admin-card-body">
        @include('admin.components.media-picker', [
            'name' => 'logo_file',
            'id' => 'logo_file',
            'label' => 'انتخاب لوگو',
            'help' => 'عکس را بکش و زوم کن تا دقیقاً همان قسمت موردنظر نمایش داده شود.',
            'currentUrl' => $logo?->url,
            'currentAlt' => $brand->name,
            'ratio' => '1:1',
        ])
        @error('logo_file')
            <small class="admin-help" style="color:var(--admin-danger);">{{ $message }}</small>
        @enderror
    </div>
</div>


{{-- =========================================================
    FORM ACTIONS
========================================================= --}}

<div class="admin-form-actions">

    <a
        href="{{ route('admin.brands.index') }}"
        class="admin-btn admin-btn--ghost"
    >
        انصراف
    </a>

    <button
        type="submit"
        class="admin-btn admin-btn--secondary"
    >
        {{ $brand->exists ? 'ذخیره تغییرات' : 'ایجاد برند' }}
    </button>

</div>


{{-- =========================================================
    BRAND FORM JS
========================================================= --}}

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const slugButton = document.getElementById('generate-brand-slug');

        const logoInput = document.getElementById('logo_file');
        const preview = document.getElementById('brand-logo-preview');
        const previewImage = document.getElementById('brand-logo-preview-image');


        /*
         * Persian / Arabic characters → Latin
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
            'ء': '',
            'ؤ': 'v'
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


        slugButton?.addEventListener('click', syncSlug);


        /*
         * Auto-generate slug only while the user has not manually
         * customized it.
         */

        nameInput?.addEventListener('input', () => {

            if (
                !slugInput.value ||
                slugInput.dataset.autoGenerated === 'true'
            ) {
                syncSlug();
                slugInput.dataset.autoGenerated = 'true';
            }

        });


        slugInput?.addEventListener('input', () => {

            slugInput.dataset.autoGenerated = 'false';

        });


        /*
         * Logo preview
         */

        logoInput?.addEventListener('change', () => {

            const file = logoInput.files?.[0];

            if (!file) {
                preview.hidden = true;
                previewImage.removeAttribute('src');
                return;
            }


            if (!file.type.startsWith('image/')) {

                logoInput.value = '';
                preview.hidden = true;
                previewImage.removeAttribute('src');

                return;

            }


            const reader = new FileReader();


            reader.onload = (event) => {

                previewImage.src = String(event.target?.result || '');
                preview.hidden = false;

            };


            reader.readAsDataURL(file);

        });


        /*
         * Initial slug state
         */

        if (
            slugInput?.value &&
            nameInput?.value
        ) {
            slugInput.dataset.autoGenerated = 'false';
        }

    });
</script>
