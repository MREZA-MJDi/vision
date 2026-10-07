import { initMoneyInputs } from './money-input.js';

initMoneyInputs();

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const menu = document.querySelector('[data-admin-menu]');
    const backdrop = document.querySelector('[data-admin-sidebar-backdrop]');

    if (!sidebar || !menu) return;

    const setOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        menu.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('admin-nav-open', open && window.innerWidth <= 900);

        if (open) {
            sidebar.querySelector('a, button')?.focus({ preventScroll: true });
        }
    };

    menu.addEventListener('click', () => {
        setOpen(!sidebar.classList.contains('is-open'));
    });

    backdrop?.addEventListener('click', () => setOpen(false));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            setOpen(false);
            menu.focus({ preventScroll: true });
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) setOpen(false);
    });
});


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-picker]').forEach((field) => {
        const input = field.querySelector('[data-media-input]');
        const editor = field.querySelector('[data-media-editor]');
        const canvas = field.querySelector('[data-media-canvas]');
        const zoom = field.querySelector('[data-media-zoom]');
        const apply = field.querySelector('[data-media-apply]');
        const cancel = field.querySelector('[data-media-cancel]');
        const preview = field.querySelector('[data-media-preview]');
        const fileName = field.querySelector('[data-media-file-name]');
        const ctx = canvas?.getContext('2d');
        const ratioValue = field.dataset.mediaRatio || '1:1';
        const [ratioW, ratioH] = ratioValue.split(':').map(Number);
        const ratio = ratioW > 0 && ratioH > 0 ? ratioW / ratioH : 1;
        const fitButton = field.querySelector('[data-media-fit]');

        if (!input || !editor || !canvas || !zoom || !apply || !ctx) return;

        let image = null;
        let offsetX = 0;
        let offsetY = 0;
        let scale = 1;
        let dragging = false;
        let dragStartX = 0;
        let dragStartY = 0;

        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

        const resizeCanvas = () => {
            const max = 720;
            if (ratio >= 1) { canvas.width = max; canvas.height = Math.round(max / ratio); }
            else { canvas.height = max; canvas.width = Math.round(max * ratio); }
        };
        resizeCanvas();

        const draw = () => {
            if (!image) return;

            const baseScale = Math.max(
                canvas.width / image.width,
                canvas.height / image.height
            );

            const renderedWidth = image.width * baseScale * scale;
            const renderedHeight = image.height * baseScale * scale;

            const maxX = Math.max(0, (renderedWidth - canvas.width) / 2);
            const maxY = Math.max(0, (renderedHeight - canvas.height) / 2);

            offsetX = clamp(offsetX, -maxX, maxX);
            offsetY = clamp(offsetY, -maxY, maxY);

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#201a1d';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const x = (canvas.width - renderedWidth) / 2 + offsetX;
            const y = (canvas.height - renderedHeight) / 2 + offsetY;

            ctx.drawImage(image, x, y, renderedWidth, renderedHeight);
        };

        const openEditor = (src, name) => {
            image = new Image();

            image.onload = () => {
                offsetX = 0;
                offsetY = 0;
                scale = 1;
                zoom.value = '1';
                draw();
                editor.hidden = false;
                field.classList.add('is-cropping');
                fileName.textContent = name;
            };

            image.src = src;
        };

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = () => openEditor(String(reader.result), file.name);
            reader.readAsDataURL(file);
        });

        zoom.addEventListener('input', () => {
            scale = Number(zoom.value) || 1;
            draw();
        });

        const pointFromEvent = (event) =>
            event.touches?.[0] ?? event.changedTouches?.[0] ?? event;

        const startDrag = (event) => {
            if (!image) return;

            const point = pointFromEvent(event);
            dragging = true;
            dragStartX = point.clientX - offsetX;
            dragStartY = point.clientY - offsetY;

            event.preventDefault?.();
        };

        const moveDrag = (event) => {
            if (!dragging) return;

            const point = pointFromEvent(event);
            offsetX = point.clientX - dragStartX;
            offsetY = point.clientY - dragStartY;
            draw();

            event.preventDefault?.();
        };

        const endDrag = () => {
            dragging = false;
        };

        canvas.addEventListener('mousedown', startDrag);
        window.addEventListener('mousemove', moveDrag);
        window.addEventListener('mouseup', endDrag);
        canvas.addEventListener('touchstart', startDrag, { passive: false });
        window.addEventListener('touchmove', moveDrag, { passive: false });
        window.addEventListener('touchend', endDrag);

        const replaceInputWithCrop = () => new Promise((resolve) => {
            if (!image || !input.files?.length) {
                resolve(false);
                return;
            }

            canvas.toBlob((blob) => {
                if (!blob) {
                    resolve(false);
                    return;
                }

                const originalName = input.files[0].name || 'image';
                const baseName = originalName.replace(/\\.[^/.]+$/, '') || 'image';
                const croppedFile = new File(
                    [blob],
                    baseName + '.webp',
                    { type: 'image/webp', lastModified: Date.now() }
                );

                const transfer = new DataTransfer();
                transfer.items.add(croppedFile);
                input.files = transfer.files;

                // Never post the canvas as a base64 hidden field.
                resolve(true);
            }, 'image/webp', 0.88);
        });

        fitButton?.addEventListener('click', () => { offsetX = 0; offsetY = 0; scale = 1; zoom.value = '1'; draw(); });

        const saveCropToPreview = async () => {
            if (!image) return;

            const saved = await replaceInputWithCrop();
            if (!saved) return;

            const previewImage = document.createElement('img');
            previewImage.setAttribute('data-media-preview-image', '');
            previewImage.alt = input.files[0].name || 'پیش‌نمایش تصویر';
            previewImage.src = URL.createObjectURL(input.files[0]);
            preview.replaceChildren(previewImage);
            fileName.textContent = input.files[0].name;
            field.classList.remove('is-cropping');
            editor.hidden = true;
        };

        apply.addEventListener('click', saveCropToPreview);

        cancel?.addEventListener('click', () => {
            editor.hidden = true;
            field.classList.remove('is-cropping');
            input.value = '';
        });

        field.closest('form')?.addEventListener('submit', async (event) => {
            if (image && input.files?.length && editor.hidden === false) {
                event.preventDefault();
                const saved = await replaceInputWithCrop();
                if (saved) {
                    editor.hidden = true;
                    field.classList.remove('is-cropping');
                    field.closest('form').requestSubmit();
                }
            }
        });
    });
});


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-attributes-editor]').forEach((editor) => {
        const rows = editor.querySelector('[data-attribute-rows]');
        const addButton = editor.querySelector('[data-add-attribute]');
        const output = editor.querySelector('[data-attributes-json]');

        if (!rows || !addButton || !output) return;

        let initial = [];

        try {
            initial = JSON.parse(editor.dataset.initialAttributes || '[]');
        } catch {
            initial = [];
        }

        const addRow = (key = '', value = '') => {
            const row = document.createElement('div');
            row.className = 'admin-attribute-row';

            row.innerHTML = `
                <input type="text" data-attribute-key placeholder="نام ویژگی" value="${escapeHtml(key)}">
                <input type="text" data-attribute-value placeholder="مقدار؛ چند مورد را با ، جدا کن" value="${escapeHtml(value)}">
                <button type="button" class="admin-attribute-row__remove" aria-label="حذف ویژگی">×</button>
            `;

            row.querySelector('.admin-attribute-row__remove')?.addEventListener('click', () => {
                row.remove();
                sync();
            });

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', sync);
            });

            rows.appendChild(row);
        };

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const sync = () => {
            const result = {};

            rows.querySelectorAll('.admin-attribute-row').forEach((row) => {
                const key = row.querySelector('[data-attribute-key]')?.value.trim();
                const rawValue = row.querySelector('[data-attribute-value]')?.value.trim();

                if (!key || !rawValue) return;

                const values = rawValue
                    .split(/[,،]/)
                    .map((item) => item.trim())
                    .filter(Boolean);

                result[key] = values.length > 1 ? values : (values[0] ?? '');
            });

            output.value = Object.keys(result).length
                ? JSON.stringify(result)
                : '';
        };

        initial.forEach((item) => addRow(item.key, item.value));

        if (!initial.length) {
            addRow();
        }

        addButton.addEventListener('click', () => addRow());
        sync();
    });
});


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-index]').forEach((page) => {
        const dialog = page.querySelector('[data-product-preview-modal]');
        const image = dialog?.querySelector('[data-preview-image]');
        const title = dialog?.querySelector('[data-preview-name]');
        const closeButton = dialog?.querySelector('[data-preview-close]');

        if (!dialog || !image || !title) return;

        const close = () => {
            if (dialog.open) dialog.close();
            image.removeAttribute('src');
            image.alt = '';
            title.textContent = '';
        };

        page.querySelectorAll('[data-preview-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const url = trigger.dataset.previewUrl;
                const name = trigger.dataset.previewName || '';

                if (!url) return;

                image.src = url;
                image.alt = name;
                title.textContent = name;

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });

        closeButton?.addEventListener('click', close);

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                close();
            }
        });

        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            close();
        });

        dialog.addEventListener('close', () => {
            image.removeAttribute('src');
            image.alt = '';
            title.textContent = '';
        });
    });
});

/* =========================================================
   ADMIN / LOCALIZED FORM INPUTS
========================================================= */
(() => {
    const MONEY_NAMES = new Set([
        'price',
        'sale_price',
        'amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'amount_toman',
    ]);

    const faNumber = new Intl.NumberFormat('fa-IR');

    const normalizeDigits = (value) => String(value ?? '')
        .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
        .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));

    const rawMoney = (value) => normalizeDigits(value)
        .replace(/[٬,،\s]/g, '')
        .replace(/[^0-9.-]/g, '');

    const formatMoney = (value) => {
        const raw = rawMoney(value);
        if (!raw || raw === '-') return '';
        const number = Number(raw);
        if (!Number.isFinite(number)) return '';
        return faNumber.format(Math.max(0, Math.round(number)));
    };

    const isMoneyInput = (input) => {
        const name = input.getAttribute('name') || '';
        return input.hasAttribute('data-money-input') || MONEY_NAMES.has(name);
    };

    const enhanceMoneyInput = (input) => {
        if (input.dataset.moneyReady === '1') return;
        input.dataset.moneyReady = '1';

        const originalName = input.name;
        const wasRequired = input.required;

        input.type = 'hidden';
        input.required = false;

        const visible = document.createElement('input');
        visible.type = 'text';
        visible.className = 'admin-money-input';
        visible.inputMode = 'numeric';
        visible.autocomplete = 'off';
        visible.name = originalName + '_display';
        visible.value = formatMoney(input.value);
        visible.placeholder = 'مثلاً ۱٬۵۰۰٬۰۰۰ تومان';
        visible.dir = 'ltr';
        visible.required = wasRequired;

        input.parentNode.insertBefore(visible, input);

        const sync = () => {
            input.value = rawMoney(visible.value);
            visible.value = formatMoney(input.value);
        };

        visible.addEventListener('input', () => {
            input.value = rawMoney(visible.value);
        });

        visible.addEventListener('blur', sync);
        input.closest('form')?.addEventListener('submit', sync);
    };

    const gregorianToJalali = (gy, gm, gd) => {
        const gdm = [0,31,59,90,120,151,181,212,243,273,304,334];
        let jy;

        if (gy > 1600) {
            jy = 979;
            gy -= 1600;
        } else {
            jy = 0;
            gy -= 621;
        }

        const gy2 = gm > 2 ? gy + 1 : gy;
        let days =
            (365 * gy) +
            Math.floor((gy2 + 3) / 4) -
            Math.floor((gy2 + 99) / 100) +
            Math.floor((gy2 + 399) / 400) -
            80 +
            gd +
            gdm[gm - 1];

        jy += 33 * Math.floor(days / 12053);
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;

        if (days > 365) {
            jy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }

        const jm = days < 186
            ? 1 + Math.floor(days / 31)
            : 7 + Math.floor((days - 186) / 30);

        const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
        return [jy, jm, jd];
    };

    const jalaliToGregorian = (jy, jm, jd) => {
        jy += 1597;

        let days =
            -355668 +
            (365 * jy) +
            Math.floor(jy / 33) * 8 +
            Math.floor(((jy % 33) + 3) / 4) +
            jd +
            (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);

        let gy = 400 * Math.floor(days / 146097);
        days %= 146097;

        if (days > 36524) {
            gy += 100 * Math.floor(--days / 36524);
            days %= 36524;

            if (days >= 365) {
                days++;
            }
        }

        gy += 4 * Math.floor(days / 1461);
        days %= 1461;

        if (days > 365) {
            gy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }

        const gd = days + 1;
        const leap = (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0;
        const monthDays = [0,31,leap ? 29 : 28,31,30,31,30,31,31,30,31,30,31];

        let month = 1;
        let day = gd;

        while (day > monthDays[month]) {
            day -= monthDays[month];
            month++;
        }

        return [gy, month, day];
    };

    const formatJalali = (iso) => {
        if (!iso) return '';
        const match = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return '';

        const [jy, jm, jd] = gregorianToJalali(
            Number(match[1]),
            Number(match[2]),
            Number(match[3])
        );

        return jy + '/' + String(jm).padStart(2, '0') + '/' + String(jd).padStart(2, '0');
    };

    const jalaliToIso = (value) => {
        const normalized = normalizeDigits(value).replace(/-/g, '/');
        const match = normalized.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);

        if (!match) return '';

        const jy = Number(match[1]);
        const jm = Number(match[2]);
        const jd = Number(match[3]);

        if (jm < 1 || jm > 12 || jd < 1 || jd > 31) {
            return '';
        }

        const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);

        return gy + '-' + String(gm).padStart(2, '0') + '-' + String(gd).padStart(2, '0');
    };

    const enhanceDateInput = (input) => {
        if (input.dataset.jalaliReady === '1') return;
        input.dataset.jalaliReady = '1';

        const wasRequired = input.required;
        const originalValue = input.value;

        input.type = 'hidden';
        input.required = false;

        const visible = document.createElement('input');
        visible.type = 'text';
        visible.className = 'admin-jalali-input';
        visible.inputMode = 'numeric';
        visible.autocomplete = 'off';
        visible.name = input.name + '_jalali';
        visible.placeholder = '۱۴۰۵/۰۷/۰۶';
        visible.value = formatJalali(originalValue);
        visible.dir = 'ltr';
        visible.required = wasRequired;

        const hint = document.createElement('small');
        hint.className = 'admin-help';
        hint.textContent = 'تاریخ را شمسی وارد کن؛ سیستم قبل از ذخیره آن را به فرمت استاندارد تبدیل می‌کند.';

        input.parentNode.insertBefore(visible, input);
        visible.insertAdjacentElement('afterend', hint);

        const sync = () => {
            const iso = jalaliToIso(visible.value);

            if (iso) {
                input.value = iso;
                visible.value = formatJalali(iso);
                visible.setCustomValidity('');
            } else if (visible.value.trim() !== '') {
                visible.setCustomValidity('تاریخ شمسی معتبر وارد کنید.');
            }
        };

        visible.addEventListener('blur', sync);
        visible.addEventListener('change', sync);
        input.closest('form')?.addEventListener('submit', (event) => {
            sync();

            if (wasRequired && !input.value) {
                event.preventDefault();
                visible.setCustomValidity('تاریخ شمسی معتبر وارد کنید.');
                visible.reportValidity();
            }
        });
    };

    const formatLocalDates = () => {
        document.querySelectorAll('[data-admin-date]').forEach((element) => {
            const iso = element.dataset.adminDate;
            if (!iso) return;

            const date = new Date(iso);
            if (Number.isNaN(date.getTime())) return;

            const dateOnly = element.dataset.adminDateFormat === 'day';

            element.textContent = new Intl.DateTimeFormat(
                'fa-IR-u-ca-persian',
                dateOnly
                    ? {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                    }
                    : {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                    }
            ).format(date);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.admin-main input').forEach((input) => {
            if (input instanceof HTMLInputElement && isMoneyInput(input)) {
                enhanceMoneyInput(input);
            }
        });

        document.querySelectorAll('.admin-main input[type="date"]').forEach(enhanceDateInput);
        formatLocalDates();
    });
})();


/* =========================================================
   PRODUCT MEDIA MANAGER
========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-media-manager]').forEach((manager) => {
        const input = manager.querySelector('[data-media-upload-input]');
        const dropzone = manager.querySelector('[data-media-dropzone]');
        const preview = manager.querySelector('[data-media-upload-preview]');
        const count = manager.querySelector('[data-media-upload-count]');
        const submit = manager.querySelector('[data-media-upload-submit]');
        const sortable = manager.querySelector('[data-media-sortable]');

        const renderFiles = async (files) => {
            if (!input || !preview || !submit) return;

            preview.innerHTML = '';
            const selected = [...files].slice(0, 12);
            const accepted = [];

            for (const file of selected) {
                if (file.type.startsWith('video/')) {
                    const url = URL.createObjectURL(file);
                    const video = document.createElement('video');
                    video.muted = true;
                    video.playsInline = true;
                    video.preload = 'metadata';
                    video.src = url;

                    await new Promise((resolve) => {
                        video.onloadedmetadata = () => {
                            if (video.duration <= 5.01) {
                                accepted.push(file);
                                video.controls = true;
                                video.className = 'admin-media-upload-preview__asset';
                                const item = document.createElement('div');
                                item.className = 'admin-media-upload-preview__item';
                                const name = document.createElement('span');
                                name.textContent = file.name + ' · ویدئو';
                                item.append(video, name);
                                preview.appendChild(item);
                            }
                            resolve();
                        };
                        video.onerror = () => resolve();
                    });
                    continue;
                }

                if (file.type.startsWith('image/')) {
                    accepted.push(file);
                    const item = document.createElement('div');
                    item.className = 'admin-media-upload-preview__item';

                    const image = document.createElement('img');
                    image.alt = file.name;
                    image.src = URL.createObjectURL(file);
                    image.className = 'admin-media-upload-preview__asset';

                    const name = document.createElement('span');
                    name.textContent = file.name + ' · تصویر';

                    item.append(image, name);
                    preview.appendChild(item);
                }
            }

            const transfer = new DataTransfer();
            accepted.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;

            if (count) {
                count.textContent = accepted.length
                    ? '\${accepted.length} رسانه انتخاب شده'
                    : 'رسانه معتبر انتخاب نشده است';
            }

            submit.disabled = accepted.length === 0;
        };

        input?.addEventListener('change', () => renderFiles(input.files));

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone?.addEventListener('drop', (event) => {
            const files = event.dataTransfer?.files;
            if (!files?.length) return;
            renderFiles(files);
        });

        let dragged = null;

        sortable?.querySelectorAll('[data-media-id]').forEach((item) => {
            item.addEventListener('dragstart', () => {
                dragged = item;
                item.classList.add('is-dragging');
            });

            item.addEventListener('dragend', async () => {
                item.classList.remove('is-dragging');
                if (!dragged || !sortable) return;

                const ids = [...sortable.querySelectorAll('[data-media-id]')]
                    .map((node) => Number(node.dataset.mediaId))
                    .filter(Boolean);

                try {
                    const response = await fetch(manager.dataset.reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ media: ids }),
                    });

                    if (!response.ok) throw new Error('reorder failed');
                } catch {
                    window.location.reload();
                }

                dragged = null;
            });

            item.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!dragged || dragged === item) return;

                const rect = item.getBoundingClientRect();
                const after = event.clientY > rect.top + rect.height / 2;

                if (after) item.after(dragged);
                else item.before(dragged);
            });
        });
    });
});

/* =========================================================
   ADMIN FORM INTERACTIONS
   Page forms keep behavior here; Blade remains markup-only.
========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    const initProductForm = () => {
        const slugInput = document.getElementById('slug');
        const nameInput = document.getElementById('name');
        const skuInput = document.getElementById('sku');
        const slugButton = document.getElementById('generate-slug');
        const skuButton = document.getElementById('generate-sku');
        const colorPicker = document.getElementById('color_picker');
        const colorCode = document.getElementById('color_code');
        const priceInput = document.getElementById('price');
        const salePriceInput = document.getElementById('sale_price');
        const imageInput = document.getElementById('image_file');
        const imagePreview = document.getElementById('image-preview');
        const imagePreviewWrap = document.getElementById('image-preview-wrap');
        const attributesList = document.getElementById('attributes-list');
        const attributesJson = document.getElementById('attributes_json');
        const addAttributeButton = document.getElementById('add-attribute');

        if (!slugInput && !skuInput && !attributesList && !imageInput) return;

        const transliterate = (value) => value
            .toLowerCase()
            .replace(/[إأآا]/g, 'a').replace(/ب/g, 'b').replace(/پ/g, 'p')
            .replace(/ت/g, 't').replace(/ث/g, 's').replace(/ج/g, 'j')
            .replace(/چ/g, 'ch').replace(/ح/g, 'h').replace(/خ/g, 'kh')
            .replace(/د/g, 'd').replace(/ذ/g, 'z').replace(/ر/g, 'r')
            .replace(/ز/g, 'z').replace(/ژ/g, 'zh').replace(/س/g, 's')
            .replace(/ش/g, 'sh').replace(/ص/g, 's').replace(/ض/g, 'z')
            .replace(/ط/g, 't').replace(/ظ/g, 'z').replace(/ع/g, 'a')
            .replace(/غ/g, 'gh').replace(/ف/g, 'f').replace(/ق/g, 'gh')
            .replace(/ک/g, 'k').replace(/گ/g, 'g').replace(/ل/g, 'l')
            .replace(/م/g, 'm').replace(/ن/g, 'n').replace(/و/g, 'v')
            .replace(/ه/g, 'h').replace(/ی/g, 'y').replace(/ء/g, '')
            .replace(/ۀ/g, 'e').replace(/ة/g, 'e')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

        const generateSlug = () => {
            if (!slugInput || !nameInput) return;
            const value = nameInput.value.trim();
            if (!value) return;
            slugInput.value = transliterate(value) || 'product';
        };

        const generateSku = () => {
            if (!skuInput) return;
            skuInput.value = 'JAN-' + Math.random().toString(36).slice(2, 8).toUpperCase();
        };

        slugButton?.addEventListener('click', generateSlug);
        skuButton?.addEventListener('click', generateSku);

        if (skuInput && !skuInput.value.trim()) generateSku();
        if (slugInput && nameInput && !slugInput.value.trim() && nameInput.value.trim()) {
            generateSlug();
        }

        if (colorPicker && colorCode) {
            colorPicker.addEventListener('input', () => {
                colorCode.value = colorPicker.value;
            });
            if (colorCode.value) colorPicker.value = colorCode.value;
        }

        const normalizeSalePrice = () => {
            if (!priceInput || !salePriceInput) return;
            const price = Number(priceInput.value);
            const sale = Number(salePriceInput.value);
            if (Number.isFinite(price) && Number.isFinite(sale) && sale >= price) {
                salePriceInput.value = '';
            }
        };

        priceInput?.addEventListener('input', normalizeSalePrice);
        salePriceInput?.addEventListener('input', normalizeSalePrice);

        imageInput?.addEventListener('change', () => {
            const file = imageInput.files?.[0];
            if (!file || !file.type.startsWith('image/') || !imagePreview || !imagePreviewWrap) return;

            const url = URL.createObjectURL(file);
            imagePreview.src = url;
            imagePreviewWrap.hidden = false;
            imagePreview.onload = () => URL.revokeObjectURL(url);
        });

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const createAttributeRow = (key = '', value = '') => {
            if (!attributesList) return;
            const row = document.createElement('div');
            row.className = 'attribute-row';
            row.innerHTML = `
                <input type="text" class="attribute-key" value="${escapeHtml(key)}" placeholder="ویژگی، مثلاً جنس">
                <input type="text" class="attribute-value" value="${escapeHtml(value)}" placeholder="مقدار، مثلاً ساتن">
                <button type="button" class="admin-btn admin-btn--danger admin-btn--sm remove-attribute">حذف</button>
            `;
            attributesList.appendChild(row);
        };

        const syncAttributes = () => {
            if (!attributesList || !attributesJson) return;
            const result = {};
            attributesList.querySelectorAll('.attribute-row').forEach((row) => {
                const key = row.querySelector('.attribute-key')?.value.trim();
                const value = row.querySelector('.attribute-value')?.value.trim();
                if (key) result[key] = value;
            });
            attributesJson.value = JSON.stringify(result);
        };

        addAttributeButton?.addEventListener('click', () => {
            createAttributeRow();
            syncAttributes();
        });

        attributesList?.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-attribute');
            if (!button) return;
            button.closest('.attribute-row')?.remove();
            syncAttributes();
        });

        attributesList?.addEventListener('input', syncAttributes);

        const metaTitle = document.getElementById('meta_title');
        const metaDescription = document.getElementById('meta_description');
        const seoTitlePreview = document.querySelector('[data-seo-preview-title]');
        const seoDescriptionPreview = document.querySelector('[data-seo-preview-description]');

        const syncSeoPreview = () => {
            if (seoTitlePreview && metaTitle) {
                seoTitlePreview.textContent = metaTitle.value.trim() || 'عنوان محصول';
            }
            if (seoDescriptionPreview && metaDescription) {
                seoDescriptionPreview.textContent =
                    metaDescription.value.trim() ||
                    'توضیحات کوتاه محصول در این قسمت دیده می‌شود.';
            }
        };

        metaTitle?.addEventListener('input', syncSeoPreview);
        metaDescription?.addEventListener('input', syncSeoPreview);
        syncSeoPreview();
        attributesJson?.closest('form')?.addEventListener('submit', () => {
            syncAttributes();
            normalizeSalePrice();
        });
        syncAttributes();
    };

    const initVariantForm = () => {
        const skuInput = document.getElementById('sku');
        const skuButton = document.getElementById('generate-variant-sku');
        const colorInput = document.getElementById('color_code');
        const colorLabel = document.getElementById('color-preview-name');
        const priceInput = document.getElementById('price');
        const salePriceInput = document.getElementById('sale_price');

        if (!skuButton && !colorInput && !priceInput) return;

        skuButton?.addEventListener('click', () => {
            if (!skuInput) return;
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let value = '';
            for (let i = 0; i < 8; i++) {
                value += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            skuInput.value = 'JAN-' + value;
            skuInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        const updateColorLabel = () => {
            if (colorLabel && colorInput) colorLabel.textContent = 'رنگ انتخاب‌شده';
        };
        colorInput?.addEventListener('input', updateColorLabel);
        updateColorLabel();

        const normalize = (value) => Number(
            String(value ?? '')
                .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
                .replace(/[,\u066C،\s]/g, '')
        );

        const syncSale = () => {
            if (!priceInput || !salePriceInput) return;
            const price = normalize(priceInput.value);
            const sale = normalize(salePriceInput.value);
            if (Number.isFinite(price) && price > 0 && Number.isFinite(sale) && sale >= price) {
                salePriceInput.value = '';
            }
        };

        priceInput?.addEventListener('input', syncSale);
        salePriceInput?.addEventListener('blur', syncSale);
    };

    const initInventoryForm = () => {
        const form = document.getElementById('inventory-movement-form');
        if (!form) return;

        const typeInput = document.getElementById('movement-type');
        const quantityAmountInput = document.getElementById('quantity_amount');
        const quantityInput = document.getElementById('quantity');
        const directionField = document.getElementById('adjustment-direction-field');
        const directionInput = document.getElementById('adjustment_direction');
        const variantSelect = document.querySelector('[data-inventory-variant]');
        const variantSearch = document.querySelector('[data-inventory-variant-search]');
        const variantNext = document.querySelector('[data-inventory-next]');
        const currentStock = document.querySelector('[data-current-stock]');
        const quantityHelp = document.getElementById('quantity-help');

        const updateCurrentStock = () => {
            if (!variantSelect || !currentStock) return;
            const option = variantSelect.options[variantSelect.selectedIndex];
            const stock = option?.dataset?.stock;
            currentStock.textContent = stock === undefined
                ? '—'
                : new Intl.NumberFormat('fa-IR').format(Number(stock)) + ' عدد';
        };

        let lookupPage = 1;
        let lookupTerm = '';
        let lookupNext = false;
        let lookupTimer;
        let lookupController;
        const loadVariants = async (append = false) => {
            if (!variantSearch || !variantSelect) return;
            lookupController?.abort();
            lookupController = new AbortController();
            const page = append ? lookupPage + 1 : 1;
            lookupTerm = variantSearch.value.trim();
            try {
                const url = new URL(variantSearch.dataset.lookupUrl, window.location.origin);
                url.searchParams.set('q', lookupTerm);
                url.searchParams.set('page', String(page));
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: lookupController.signal });
                if (!response.ok) return;
                const payload = await response.json();
                if (!append) {
                    const selected = variantSelect.selectedOptions[0];
                    const keep = selected?.value ? selected.cloneNode(true) : null;
                    variantSelect.replaceChildren(new Option('یک واریانت را انتخاب کن', ''));
                    if (keep) variantSelect.append(keep);
                }
                payload.data.forEach((variant) => {
                    const option = new Option(`${variant.name} — ${variant.display_name} — موجودی: ${new Intl.NumberFormat('fa-IR').format(variant.stock)}`, String(variant.id));
                    option.dataset.stock = String(variant.stock);
                    variantSelect.append(option);
                });
                lookupPage = page;
                lookupNext = Boolean(payload.next_page_url);
                if (variantNext) variantNext.hidden = !lookupNext;
            } catch (error) {
                if (error.name !== 'AbortError') console.error('Variant lookup failed', error);
            }
        };
        variantSearch?.addEventListener('input', () => {
            clearTimeout(lookupTimer);
            lookupTimer = setTimeout(() => loadVariants(false), 250);
        });
        variantNext?.addEventListener('click', () => { if (lookupNext) loadVariants(true); });

        const updateQuantity = () => {
            if (!typeInput || !quantityAmountInput || !quantityInput) return;
            const amount = Math.abs(Number(quantityAmountInput.value) || 0);
            const type = typeInput.value;
            let signed = amount;

            if (type === 'sale' || type === 'damage') signed = -amount;
            if (type === 'adjustment' && directionInput) {
                signed = directionInput.value === 'decrease' ? -amount : amount;
            }

            quantityInput.value = amount > 0 ? String(signed) : '';

            if (quantityHelp) {
                quantityHelp.textContent = {
                    purchase: 'این مقدار به موجودی اضافه می‌شود.',
                    return: 'این مقدار به موجودی اضافه می‌شود.',
                    sale: 'این مقدار از موجودی کم می‌شود.',
                    damage: 'این مقدار از موجودی کم می‌شود.',
                    adjustment: directionInput?.value === 'decrease'
                        ? 'این مقدار از موجودی کم می‌شود.'
                        : 'این مقدار به موجودی اضافه می‌شود.',
                }[type] || 'تعداد موردنظر را وارد کنید.';
            }
        };

        const updateDirectionVisibility = () => {
            if (!typeInput || !directionField) return;
            directionField.hidden = typeInput.value !== 'adjustment';
            updateQuantity();
        };

        typeInput?.addEventListener('change', updateDirectionVisibility);
        variantSelect?.addEventListener('change', updateCurrentStock);
        directionInput?.addEventListener('change', updateQuantity);
        quantityAmountInput?.addEventListener('input', updateQuantity);
        updateDirectionVisibility();
        updateCurrentStock();
        loadVariants(false);
        form.addEventListener('submit', updateQuantity);
    };

    initProductForm();
    initVariantForm();
    initInventoryForm();
});
