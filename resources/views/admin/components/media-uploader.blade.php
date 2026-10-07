@php
    $mediaItem = $mediaItem ?? null;
    $uploadType = $uploadType ?? 'variant';
    $uploadId = (int) ($uploadId ?? 0);
    $title = $title ?? 'رسانه';
    $description = $description ?? 'تصویر را بکشید و رها کنید یا انتخاب کنید.';
    $accept = $accept ?? 'image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm';
    $videoAllowed = $videoAllowed ?? true;
    $placeholder = $placeholder ?? 'برای آپلود کلیک کنید';
@endphp

<section class="admin-card admin-media-uploader" data-media-uploader data-video-allowed="{{ $videoAllowed ? '1' : '0' }}">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">{{ $title }}</h2>
            <p class="admin-card-description">{{ $description }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.media.store', ['type' => $uploadType, 'id' => $uploadId]) }}" enctype="multipart/form-data" data-media-form>
        @csrf

        <div class="admin-media-uploader__body">
            <label class="admin-media-dropzone" data-media-dropzone>
                <input type="file" name="media_file" accept="{{ $accept }}" data-media-input hidden>

                <div class="admin-media-placeholder" data-media-placeholder>
                    @if($mediaItem?->url)
                        @if(str_starts_with((string) $mediaItem->mime_type, 'video/'))
                            <video src="{{ $mediaItem->url }}" muted playsinline preload="metadata"></video>
                        @else
                            <img src="{{ $mediaItem->url }}" alt="{{ $mediaItem->alt_text ?: $title }}" loading="lazy">
                        @endif
                    @else
                        <span class="admin-media-placeholder__icon">＋</span>
                        <strong>{{ $placeholder }}</strong>
                        <small>JPG · PNG · WebP · AVIF @if($videoAllowed) · MP4 · WebM @endif</small>
                    @endif
                </div>

                <div class="admin-media-preview" data-media-preview hidden></div>

                <div class="admin-media-uploader__hint">
                    <span>تصویر را قبل از ذخیره می‌توانی مربع و تمیز crop کنی.</span>
                    @if($videoAllowed)
                        <span>ویدئوی کوتاه حداکثر ۵ ثانیه.</span>
                    @endif
                </div>
            </label>

            <div class="admin-media-crop" data-media-crop hidden>
                <div class="admin-media-crop__head">
                    <strong>برش تصویر</strong>
                    <div class="admin-media-crop__tools">
                        <select data-media-ratio aria-label="نسبت برش">
                            <option value="1">مربع ۱:۱</option>
                            <option value="0.8">پرتره ۴:۵</option>
                            <option value="1.7777778">افقی ۱۶:۹</option>
                        </select>
                        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-media-reset>بازنشانی</button>
                    </div>
                </div>
                <div class="admin-media-crop__stage">
                    <canvas data-media-canvas></canvas>
                </div>
                <div class="admin-media-crop__actions">
                    <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-media-fit>نمایش کامل</button>
                    <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-media-crop-apply>اعمال برش</button>
                </div>
            </div>

            <div class="admin-media-uploader__meta">
                <input type="text" name="alt_text" value="{{ old('alt_text', $mediaItem?->alt_text) }}" maxlength="180" placeholder="متن جایگزین تصویر (اختیاری)">
                <small data-media-status>فایل هنوز انتخاب نشده است.</small>
            </div>

            <button type="submit" class="admin-btn admin-btn--secondary">ذخیره رسانه</button>
        </div>
    </form>
</section>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-uploader]').forEach((root) => {
        const input = root.querySelector('[data-media-input]');
        const dropzone = root.querySelector('[data-media-dropzone]');
        const preview = root.querySelector('[data-media-preview]');
        const placeholder = root.querySelector('[data-media-placeholder]');
        const crop = root.querySelector('[data-media-crop]');
        const canvas = root.querySelector('[data-media-canvas]');
        const status = root.querySelector('[data-media-status]');
        const apply = root.querySelector('[data-media-crop-apply]');
        const reset = root.querySelector('[data-media-reset]');
        const fit = root.querySelector('[data-media-fit]');
        const ratioInput = root.querySelector('[data-media-ratio]');
        let sourceImage = null;
        let objectUrl = null;

        const draw = () => {
            if (!sourceImage) return;
            const max = Math.min(760, root.clientWidth - 48);
            const scale = Math.min(1, max / sourceImage.width);
            canvas.width = Math.max(1, Math.round(sourceImage.width * scale));
            canvas.height = Math.max(1, Math.round(sourceImage.height * scale));
            canvas.getContext('2d').drawImage(sourceImage, 0, 0, canvas.width, canvas.height);
        };

        const renderImage = (file) => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
                sourceImage = image;
                crop.hidden = false;
                draw();
            };
            image.src = objectUrl;
        };

        const showVideo = (file) => {
            const url = URL.createObjectURL(file);
            const video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.preload = 'metadata';
            video.src = url;
            video.onloadedmetadata = () => {
                if (video.duration > 5.01) {
                    input.value = '';
                    status.textContent = 'ویدئو باید حداکثر ۵ ثانیه باشد.';
                    URL.revokeObjectURL(url);
                    return;
                }
                preview.hidden = false;
                preview.innerHTML = '';
                video.controls = true;
                preview.appendChild(video);
                placeholder.hidden = true;
                crop.hidden = true;
                status.textContent = 'ویدئوی کوتاه آماده ذخیره است.';
            };
        };

        const handleFile = (file) => {
            if (!file) return;
            const isVideo = file.type.startsWith('video/');
            const isImage = file.type.startsWith('image/');
            if (!isImage && !isVideo) {
                status.textContent = 'فرمت فایل پشتیبانی نمی‌شود.';
                return;
            }

            status.textContent = file.name + ' آماده است.';
            preview.hidden = true;
            preview.innerHTML = '';

            if (isVideo) {
                showVideo(file);
            } else {
                placeholder.hidden = true;
                renderImage(file);
            }
        };

        input.addEventListener('change', () => handleFile(input.files?.[0]));
        ['dragenter', 'dragover'].forEach(event => dropzone.addEventListener(event, e => {
            e.preventDefault();
            dropzone.classList.add('is-dragging');
        }));
        ['dragleave', 'drop'].forEach(event => dropzone.addEventListener(event, e => {
            e.preventDefault();
            dropzone.classList.remove('is-dragging');
        }));
        dropzone.addEventListener('drop', e => handleFile(e.dataTransfer.files?.[0]));

        apply?.addEventListener('click', () => {
            if (!sourceImage) return;
            const ratio = Number(ratioInput?.value || 1);
            const sourceRatio = sourceImage.width / sourceImage.height;
            let cropWidth = sourceImage.width;
            let cropHeight = sourceImage.height;

            if (sourceRatio > ratio) {
                cropWidth = sourceImage.height * ratio;
            } else {
                cropHeight = sourceImage.width / ratio;
            }

            const output = document.createElement('canvas');
            output.width = Math.round(cropWidth);
            output.height = Math.round(cropHeight);
            output.getContext('2d').drawImage(
                sourceImage,
                (sourceImage.width - cropWidth) / 2,
                (sourceImage.height - cropHeight) / 2,
                cropWidth,
                cropHeight,
                0,
                0,
                output.width,
                output.height
            );
            output.toBlob((blob) => {
                if (!blob) return;
                const file = new File([blob], 'cropped-image.webp', {type: 'image/webp'});
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                crop.hidden = true;
                status.textContent = 'برش تصویر اعمال شد.';
            }, 'image/webp', 0.9);
        });

        reset?.addEventListener('click', () => {
            if (input.files?.[0]) renderImage(input.files[0]);
        });

        fit?.addEventListener('click', draw);
    });
});
</script>
@endpush
@endonce
