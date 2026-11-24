<dialog
    class="rm-cropper"
    data-rm-dialog
    aria-label="تنظیم تصویر"
>
    <header class="rm-cropper__header">
        <h2>تنظیم تصویر</h2>
    </header>

    <div class="rm-cropper__stage">
        <div class="rm-cropper__frame">
        <canvas
            data-rm-canvas
            tabindex="0"
            aria-label="جابجایی تصویر با ماوس، لمس یا کلیدهای جهت"
        ></canvas>
        @if(!empty($brandPreview))<span class="rm-cropper__brand-guide" aria-hidden="true"></span>@endif
        </div>
    </div>

    <p class="rm-cropper__hint">
        تصویر را جابه‌جا کنید.
        برای بزرگ‌نمایی از اسکرول یا کلیدهای + و - استفاده کنید.
    </p>

    <p
        class="rm-uploader__error"
        data-rm-crop-error
        role="alert"
        hidden
    ></p>

    <footer class="rm-cropper__footer">
        <button
            type="button"
            class="ak-button ak-button-light"
            data-rm-cancel
        >
            انصراف
        </button>

        <button
            type="button"
            class="ak-button ak-button-dark"
            data-rm-apply
        >
            تأیید تصویر
        </button>
    </footer>
</dialog>