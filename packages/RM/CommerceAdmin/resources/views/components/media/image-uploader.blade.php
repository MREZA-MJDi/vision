@php
    $imageUrl = $media?->url;
    $altText = old($prefix . '_alt', $media?->alt_text ?? '');
@endphp
<section class="ak-panel ak-form-panel">
    <div class="ak-panel-heading"><h2>{{ $title }}</h2></div>
    <div class="rm-uploader{{ !empty($categoryPreview) ? ' rm-uploader--category' : '' }}" data-rm-uploader data-existing-url="{{ $imageUrl ?? '' }}" data-aspect="{{ $aspect ?? 1 }}">
        <input type="file" name="{{ $prefix }}_image" accept="image/jpeg,image/png,image/webp" data-rm-input hidden>
        <input type="hidden" name="remove_{{ $prefix }}" value="{{ old('remove_' . $prefix, 0) ? 1 : 0 }}" data-rm-remove-input>
        <button type="button" class="rm-uploader__drop" data-rm-drop aria-label="انتخاب یا تغییر تصویر">
            <img data-rm-preview @if($imageUrl) src="{{ $imageUrl }}" @else hidden @endif alt="{{ $altText ?: $title }}">
            <span data-rm-placeholder @if($imageUrl) hidden @endif>
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 16V4m-5 5 5-5 5 5M4 16v4h16v-4"/></svg>
                <strong>انتخاب یا رها کردن تصویر</strong><small>JPG، PNG یا WebP</small>
            </span>
            @if(!empty($categoryPreview))
                <span class="vision-category-card__caption" data-rm-card-caption @if(!$imageUrl) hidden @endif>
                    <small>کالکشن VISION</small>
                    <strong data-rm-card-name>{{ $previewName ?: 'نام دسته‌بندی' }}</strong>
                    <span>مشاهده مجموعه ←</span>
                </span>
            @endif
        </button>
        @if(!empty($categoryPreview))
            <div class="rm-category-preview" data-rm-secondary-preview @if(!$imageUrl) hidden @endif>
                <p>پیش‌نمایش کارت کوچک در فروشگاه (۱:۱)</p>
                <div class="rm-category-preview__square">
                    <img data-rm-secondary-image @if($imageUrl) src="{{ $imageUrl }}" @endif alt="">
                    <span class="vision-category-card__caption">
                        <small>کالکشن VISION</small>
                        <strong data-rm-card-name>{{ $previewName ?: 'نام دسته‌بندی' }}</strong>
                        <span>مشاهده مجموعه ←</span>
                    </span>
                </div>
            </div>
            <small>قاب اصلی ۳:۴ است. کارت کوچک از مرکز همین تصویر برش می‌خورد.</small>
        @endif
        <div class="rm-uploader__actions">
            <button type="button" data-rm-crop @disabled(!$imageUrl)>تنظیم تصویر</button>
            <button type="button" data-rm-remove @disabled(!$imageUrl)>حذف</button>
            <button type="button" data-rm-restore hidden>بازگردانی</button>
        </div>
        <small data-rm-info aria-live="polite">حداکثر حجم خروجی ۱۰ مگابایت</small>
        <p data-rm-error class="rm-uploader__error" role="alert" hidden></p>
        @error($prefix . '_image')<p class="rm-uploader__error" role="alert">{{ $message }}</p>@enderror
        <label class="ak-field"><span>متن جایگزین تصویر</span><input name="{{ $prefix }}_alt" maxlength="255" value="{{ $altText }}">@error($prefix . '_alt')<small role="alert">{{ $message }}</small>@enderror</label>
        @include('commerce-admin::components.media.image-cropper')
    </div>
</section>
