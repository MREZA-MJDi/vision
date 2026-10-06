@props([
    'name',
    'id' => null,
    'label' => 'انتخاب تصویر',
    'help' => 'تصویر را انتخاب کن، بعد کادر نمایش را با کشیدن و زوم تنظیم کن.',
    'currentUrl' => null,
    'currentAlt' => '',
    'ratio' => '1:1',
])

@php($inputId = $id ?: str_replace(['[', ']'], ['-', ''], $name))

<div
    class="admin-media-picker"
    data-media-picker
    data-media-ratio="{{ $ratio }}"
    style="--media-ratio: {{ str_replace(':', ' / ', $ratio) }};"
>
    <div class="admin-media-picker__stage">
        <div class="admin-media-picker__preview" data-media-preview>
            @if($currentUrl)
                <img
                    src="{{ $currentUrl }}"
                    alt="{{ $currentAlt }}"
                    data-media-current-preview
                >
            @else
                <div class="admin-media-picker__placeholder">
                    <span aria-hidden="true">＋</span>
                    <strong>هنوز تصویری انتخاب نشده</strong>
                    <small>برای شروع روی انتخاب تصویر بزن</small>
                </div>
            @endif
        </div>

        <div class="admin-media-picker__meta">
            <strong data-media-file-name>{{ $currentUrl ? 'تصویر فعلی' : 'آماده انتخاب تصویر' }}</strong>
            <small>{{ $help }}</small>
        </div>
    </div>

    <div class="admin-field">
        <label for="{{ $inputId }}">{{ $label }}</label>
        <input
            id="{{ $inputId }}"
            type="file"
            name="{{ $name }}"
            accept="image/jpeg,image/png,image/webp,image/avif"
            data-media-input
        >
        <small class="admin-help">
            JPG، PNG، WebP یا AVIF — حداکثر 5MB
        </small>
    </div>

    <div class="admin-media-picker__editor" data-media-editor hidden>
        <div class="admin-media-picker__editor-head">
            <div>
                <strong>کادر نمایش را تنظیم کن</strong>
                <small>عکس را بکش و با زوم، دقیقاً همان بخشی را که می‌خواهی در فروشگاه دیده شود انتخاب کن.</small>
            </div>
            <span class="admin-badge admin-badge--info" data-media-ratio-label>{{ $ratio }}</span>
        </div>

        <canvas
            width="720"
            height="720"
            data-media-canvas
            aria-label="ویرایش کادر تصویر"
        ></canvas>

        <div class="admin-media-picker__controls">
            <label class="admin-field">
                <span>زوم</span>
                <input type="range" min="1" max="3" step="0.01" value="1" data-media-zoom>
            </label>

            <button type="button" class="admin-btn admin-btn--ghost" data-media-fit>
                فیت کردن
            </button>

            <button type="button" class="admin-btn admin-btn--secondary" data-media-apply>
                اعمال کادر
            </button>

            <button type="button" class="admin-btn admin-btn--ghost" data-media-cancel>
                انصراف
            </button>
        </div>
    </div>
</div>
