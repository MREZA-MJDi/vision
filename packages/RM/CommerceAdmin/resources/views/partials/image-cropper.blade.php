@php($cropTitle = $cropTitle ?? 'برش تصویر')
<div class="ak-crop-modal" data-crop-modal hidden role="dialog" aria-modal="true" aria-labelledby="akCropTitle">
    <div class="ak-crop-dialog">
        <div class="ak-crop-dialog-head">
            <div><h2 id="akCropTitle">{{ $cropTitle }}</h2><p data-crop-count>پیش‌نمایش قبل از ذخیره</p></div>
            <button class="ak-icon-button" type="button" data-crop-cancel aria-label="لغو انتخاب تصویرها">×</button>
        </div>
        <div class="ak-crop-preview-stage"><canvas data-crop-preview width="640" height="640"></canvas></div>
        <div class="ak-crop-controls">
            <label class="ak-field"><span>نسبت تصویر</span><select data-crop-ratio><option value="original">حفظ نسبت اصلی</option><option value="1:1">مربع (۱:۱)</option><option value="4:3">افقی (۴:۳)</option><option value="3:4">عمودی (۳:۴)</option></select></label>
            <label class="ak-field"><span>بزرگ‌نمایی <output data-crop-zoom-value>۱۰۰٪</output></span><input type="range" min="100" max="250" step="5" value="100" data-crop-zoom></label>
            <label class="ak-field"><span>جابجایی افقی</span><input type="range" min="0" max="100" value="50" data-crop-x></label>
            <label class="ak-field"><span>جابجایی عمودی</span><input type="range" min="0" max="100" value="50" data-crop-y></label>
        </div>
        <div class="ak-crop-dialog-actions">
            <button class="ak-button ak-button-light" type="button" data-crop-skip>استفاده بدون برش</button>
            <div><button class="ak-button ak-button-light" type="button" data-crop-cancel>لغو</button><button class="ak-button ak-button-dark" type="button" data-crop-apply>تأیید برش و ادامه</button></div>
        </div>
    </div>
</div>