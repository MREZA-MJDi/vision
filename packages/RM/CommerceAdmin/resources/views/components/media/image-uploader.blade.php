
@php
    $imageUrl = $media?->url;

    $altText = old(
        $prefix . '_alt',
        $media?->alt_text ?? ''
    );
@endphp

<section class="ak-panel ak-form-panel">
    <div class="ak-panel-heading">
        <div>
            <h2>{{ $title }}</h2>
            <p>مدیریت تصویر قابل نمایش در فروشگاه</p>
        </div>
    </div>

    <div
        class="rm-uploader"
        data-rm-uploader
        data-existing-url="{{ $imageUrl ?? '' }}"
        data-aspect="{{ $aspect ?? '1' }}"
    >
        {{-- Original file input --}}
        <input
            type="file"
            class="rm-uploader__file"
            name="{{ $prefix }}_image"
            accept="image/jpeg,image/png,image/webp"
            data-rm-input
            hidden
        >

        {{-- Removal state --}}
        <input
            type="hidden"
            name="remove_{{ $prefix }}"
            value="{{ old('remove_' . $prefix, 0) ? 1 : 0 }}"
            data-rm-remove-input
        >

        {{-- Upload and preview area --}}
        <div class="rm-uploader__drop" data-rm-drop>

            <img
                data-rm-preview

                @if($imageUrl)
                    src="{{ $imageUrl }}"
                @else
                    hidden
                @endif

                alt="{{ $altText ?: $title }}"
            >

            <div
                data-rm-placeholder
                @if($imageUrl) hidden @endif
            >
                <svg
                    viewBox="0 0 24 24"
                    width="42"
                    height="42"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    aria-hidden="true"
                >
                    <rect
                        x="3"
                        y="3"
                        width="18"
                        height="18"
                        rx="3"
                    />

                    <circle cx="8" cy="8" r="2"/>

                    <path d="m3 17 5-5 4 4 4-5 5 6"/>
                </svg>

                <strong>
                    تصویر را اینجا رها کنید
                </strong>

                <small>
                    JPG، PNG یا WebP
                </small>
            </div>

            <button
                type="button"
                class="ak-button ak-button-light"
                data-rm-browse
            >
                انتخاب تصویر
            </button>

        </div>

        {{-- Image management actions --}}
        <div class="rm-uploader__actions">
            <button
                type="button"
                class="ak-button ak-button-light"
                data-rm-change
            >
                تغییر تصویر
            </button>

            <button
                type="button"
                class="ak-button ak-button-light"
                data-rm-crop
                @disabled(! $imageUrl)
            >
                برش مجدد
            </button>

            <button
                type="button"
                class="rm-uploader__danger"
                data-rm-remove
                @disabled(! $imageUrl)
            >
                حذف تصویر
            </button>

            <button
                type="button"
                class="ak-button ak-button-light"
                data-rm-restore
                hidden
            >
                بازگردانی تصویر قبلی
            </button>
        </div>

        {{-- Upload information --}}
        <p
            class="rm-uploader__info"
            data-rm-info
            aria-live="polite"
        >
            حداکثر حجم خروجی: ۴ مگابایت
        </p>

        {{-- Client-side errors --}}
        <p
            class="rm-uploader__error"
            data-rm-error
            role="alert"
            hidden
        ></p>

        {{-- Server-side errors --}}
        @error($prefix . '_image')
            <p class="rm-uploader__error" role="alert">
                {{ $message }}
            </p>
        @enderror

        {{-- Image accessibility and SEO --}}
        <label class="ak-field">
            <span>متن جایگزین تصویر (Alt)</span>

            <input
                type="text"
                name="{{ $prefix }}_alt"
                maxlength="255"
                value="{{ $altText }}"
                placeholder="توضیح کوتاه و مرتبط با تصویر"
            >

            <small>
                برای دسترس‌پذیری و بهینه‌سازی تصاویر استفاده می‌شود.
            </small>

            @error($prefix . '_alt')
                <small role="alert">{{ $message }}</small>
            @enderror
        </label>

        {{-- Shared cropper --}}
        @include('commerce-admin::components.media.image-cropper')
    </div>
</section>
