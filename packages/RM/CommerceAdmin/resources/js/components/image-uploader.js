import '../../css/components/image-uploader.css';

const MAX_SIZE = 4 * 1024 * 1024;
const MAX_SOURCE = 20 * 1024 * 1024;
const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

const clamp = (value, min, max) =>
    Math.min(max, Math.max(min, value));

class ImageUploader {
    constructor(root) {
        this.root = root;

        const find = selector => root.querySelector(selector);

        this.input = find('[data-rm-input]');
        this.drop = find('[data-rm-drop]');
        this.preview = find('[data-rm-preview]');
        this.placeholder = find('[data-rm-placeholder]');
        this.removeInput = find('[data-rm-remove-input]');
        this.info = find('[data-rm-info]');
        this.error = find('[data-rm-error]');

        this.cropButton = find('[data-rm-crop]');
        this.removeButton = find('[data-rm-remove]');
        this.restoreButton = find('[data-rm-restore]');

        this.dialog = find('[data-rm-dialog]');
        this.canvas = find('[data-rm-canvas]');
        this.applyButton = find('[data-rm-apply]');
        this.cropError = find('[data-rm-crop-error]');

        this.aspect = Number(root.dataset.aspect) || 1;
        this.existingUrl = root.dataset.existingUrl || '';

        this.file = null;
        this.source = null;
        this.image = null;

        this.previewUrl = null;
        this.cropUrl = null;

        this.zoom = 1;
        this.x = 0.5;
        this.y = 0.5;

        this.busy = false;
        this.drag = null;
        this.removed = this.removeInput.value === '1';

        this.bind();
        this.render();
    }

    bind() {
        this.drop.addEventListener('click', () => {
            if (!this.busy) this.input.click();
        });

        this.input.addEventListener('change', () => {
            const file = this.input.files?.[0];

            this.syncInput();

            if (file) this.open(file);
        });

        ['dragenter', 'dragover'].forEach(event => {
            this.drop.addEventListener(event, e => {
                e.preventDefault();
                this.drop.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            this.drop.addEventListener(event, e => {
                e.preventDefault();
                this.drop.classList.remove('is-dragging');
            });
        });

        this.drop.addEventListener('drop', e => {
            const file = e.dataTransfer?.files?.[0];

            if (file) this.open(file);
        });

        this.cropButton.addEventListener('click', () => {
            this.recrop();
        });

        this.removeButton.addEventListener('click', () => {
            if (this.busy) return;

            this.file = null;
            this.source = null;
            this.removed = true;

            this.syncInput();
            this.render();
        });

        this.restoreButton.addEventListener('click', () => {
            if (this.busy) return;

            this.file = null;
            this.source = null;
            this.removed = false;

            this.syncInput();
            this.render();
        });

        this.canvas.addEventListener('pointerdown', e => {
            if (this.busy || !this.image) return;

            this.canvas.setPointerCapture(e.pointerId);

            this.drag = {
                id: e.pointerId,
                x: e.clientX,
                y: e.clientY,
            };
        });

        this.canvas.addEventListener('pointermove', e => {
            if (this.drag?.id !== e.pointerId) return;

            const rect = this.cropRect();
            const bounds = this.canvas.getBoundingClientRect();

            const dx = e.clientX - this.drag.x;
            const dy = e.clientY - this.drag.y;

            const spaceX = this.image.naturalWidth - rect.sw;
            const spaceY = this.image.naturalHeight - rect.sh;

            if (spaceX > 0) {
                this.x = clamp(
                    this.x - dx * rect.sw / bounds.width / spaceX,
                    0, 1
                );
            }

            if (spaceY > 0) {
                this.y = clamp(
                    this.y - dy * rect.sh / bounds.height / spaceY,
                    0, 1
                );
            }

            this.drag.x = e.clientX;
            this.drag.y = e.clientY;

            this.paint();
        });

        ['pointerup', 'pointercancel', 'lostpointercapture']
            .forEach(event => {
                this.canvas.addEventListener(event, () => {
                    this.drag = null;
                });
            });

        this.canvas.addEventListener('wheel', e => {
            e.preventDefault();

            if (this.busy) return;

            this.zoom = clamp(
                this.zoom * (e.deltaY < 0 ? 1.1 : 0.91),
                1, 4
            );

            this.paint();
        }, { passive: false });

        this.canvas.addEventListener('keydown', e => {
            const actions = {
                ArrowLeft: () => this.x -= 0.05,
                ArrowRight: () => this.x += 0.05,
                ArrowUp: () => this.y -= 0.05,
                ArrowDown: () => this.y += 0.05,
                '+': () => this.zoom *= 1.1,
                '=': () => this.zoom *= 1.1,
                '-': () => this.zoom /= 1.1,
            };

            if (!actions[e.key] || this.busy) return;

            e.preventDefault();

            actions[e.key]();

            this.x = clamp(this.x, 0, 1);
            this.y = clamp(this.y, 0, 1);
            this.zoom = clamp(this.zoom, 1, 4);

            this.paint();
        });

        this.root.querySelector('[data-rm-cancel]')
            .addEventListener('click', () => {
                if (!this.busy) this.close();
            });

        this.dialog.addEventListener('cancel', e => {
            e.preventDefault();

            if (!this.busy) this.close();
        });

        this.applyButton.addEventListener('click', () => {
            this.apply();
        });

        this.root.closest('form')?.addEventListener('submit', e => {
            if (this.busy || this.dialog.open) {
                e.preventDefault();
            }
        });
    }

    async open(file) {
        if (this.busy || this.dialog.open) return;

        this.busy = true;
        this.error.hidden = true;

        try {
            if (!TYPES.includes(file.type)) {
                throw new Error('فرمت تصویر معتبر نیست.');
            }

            if (file.size > MAX_SOURCE) {
                throw new Error('حجم تصویر اولیه بیشتر از ۲۰ مگابایت است.');
            }

            this.cropUrl = URL.createObjectURL(file);

            const image = new Image();
            image.src = this.cropUrl;

            await image.decode();

            if (
                !image.naturalWidth ||
                !image.naturalHeight ||
                image.naturalWidth * image.naturalHeight > 40_000_000
            ) {
                throw new Error('ابعاد تصویر معتبر نیست.');
            }

            this.image = image;
            this.pendingSource = file;

            this.zoom = 1;
            this.x = 0.5;
            this.y = 0.5;

            this.cropError.hidden = true;

            this.paint();
            this.dialog.showModal();
            this.canvas.focus();
        } catch (error) {
            this.releaseCrop();
            this.showError(error.message);
        } finally {
            this.busy = false;
        }
    }

    cropRect() {
        const w = this.image.naturalWidth;
        const h = this.image.naturalHeight;

        const sw = Math.min(w, h * this.aspect) / this.zoom;
        const sh = sw / this.aspect;

        return {
            sx: (w - sw) * this.x,
            sy: (h - sh) * this.y,
            sw,
            sh,
        };
    }

    draw(canvas, size) {
        const { sx, sy, sw, sh } = this.cropRect();
        const scale = Math.min(1, size / Math.max(sw, sh));

        canvas.width = Math.max(1, Math.round(sw * scale));
        canvas.height = Math.max(1, Math.round(sh * scale));

        const context = canvas.getContext('2d');

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        context.drawImage(
            this.image,
            sx, sy, sw, sh,
            0, 0,
            canvas.width, canvas.height
        );
    }

    paint() {
        if (this.image) this.draw(this.canvas, 640);
    }

    async apply() {
        if (this.busy || !this.image) return;

        this.busy = true;
        this.applyButton.disabled = true;
        this.cropError.hidden = true;

        try {
            const canvas = document.createElement('canvas');

            this.draw(canvas, 1600);

            let file = null;

            for (const quality of [0.9, 0.8, 0.7, 0.6]) {
                const blob = await new Promise(resolve => {
                    canvas.toBlob(resolve, 'image/webp', quality);
                });

                if (!blob || blob.size > MAX_SIZE) continue;

                if (!TYPES.includes(blob.type)) continue;

                const extension = {
                    'image/webp': 'webp',
                    'image/png': 'png',
                    'image/jpeg': 'jpg',
                }[blob.type];

                file = new File(
                    [blob],
                    `image-${Date.now()}.${extension}`,
                    { type: blob.type }
                );

                break;
            }

            if (!file) {
                throw new Error('آماده‌سازی تصویر انجام نشد.');
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.input.files = transfer.files;
            this.file = file;
            this.source = this.pendingSource;
            this.removed = false;
            this.removeInput.value = '0';
            this.render();
            this.close();
        } catch (error) {
            this.cropError.textContent = error.message;
            this.cropError.hidden = false;
        } finally {
            this.busy = false;
            this.applyButton.disabled = false;
        }
    }

    async recrop() {
        if (this.busy || this.removed) return;

        if (this.source || this.file) {
            return this.open(this.source || this.file);
        }

        if (!this.existingUrl) return;

        this.busy = true;

        let file = null;

        try {
            const response = await fetch(this.existingUrl);

            if (!response.ok) {
                throw new Error('دریافت تصویر انجام نشد.');
            }

            const blob = await response.blob();

            file = new File([blob], 'existing-image', {
                type: blob.type,
            });
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.busy = false;
        }

        if (file) await this.open(file);
    }

    syncInput() {
        const transfer = new DataTransfer();

        if (this.file) transfer.items.add(this.file);

        this.input.files = transfer.files;
        this.removeInput.value = this.removed ? '1' : '0';
    }

    render() {
        if (this.previewUrl) {
            URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
        }

        let url = this.removed ? '' : this.existingUrl;

        if (this.file && !this.removed) {
            this.previewUrl = URL.createObjectURL(this.file);
            url = this.previewUrl;
        }

        if (url) {
            this.preview.src = url;
        } else {
            this.preview.removeAttribute('src');
        }

        this.preview.hidden = !url;
        this.placeholder.hidden = Boolean(url);

        this.cropButton.disabled = !url;
        this.removeButton.disabled = !url;

        this.restoreButton.hidden = !(
            this.existingUrl &&
            (this.file || this.removed)
        );

        this.info.textContent = this.file
            ? 'تصویر آماده ذخیره است.'
            : this.removed
                ? 'تصویر پس از ذخیره حذف می‌شود.'
                : 'حداکثر حجم خروجی ۴ مگابایت';
    }

    releaseCrop() {
        if (this.cropUrl) {
            URL.revokeObjectURL(this.cropUrl);
            this.cropUrl = null;
        }

        this.image = null;
        this.pendingSource = null;
        this.drag = null;
    }

    close() {
        this.dialog.close();
        this.releaseCrop();

        this.canvas.width = 1;
        this.canvas.height = 1;
    }

    showError(message) {
        this.error.textContent = message;
        this.error.hidden = false;
    }
}

function initialize() {
    document.querySelectorAll('[data-rm-uploader]').forEach(root => {
        if (root.dataset.initialized) return;

        new ImageUploader(root);

        root.dataset.initialized = '1';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}