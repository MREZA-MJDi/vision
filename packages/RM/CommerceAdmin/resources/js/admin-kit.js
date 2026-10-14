(() => {
    'use strict';
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const symbol = document.body && document.body.dataset.currencySymbol ? document.body.dataset.currencySymbol : 'تومان';
    const money = (value) => Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' ' + symbol;
    const cleanMoneyInput = (value) => String(value ?? '')
        .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
        .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)))
        .replace(/[٬،,\s]/g, '')
        .replace(/٫/g, '.')
        .replace(/[^0-9.]/g, '');
    const formatMoneyInput = (value) => {
        const clean = cleanMoneyInput(value);
        if (!clean) return clean;
        const [whole, ...fractionParts] = clean.split('.');
        const fraction = fractionParts.join('');
        const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        // Preserve the decimal separator and trailing zeroes while the user edits (e.g. "12.").
        return fractionParts.length ? grouped + '.' + fraction : grouped;
    };
    const parseMoneyInput = (value) => Number(cleanMoneyInput(value) || 0);
    // Format every price field (including dynamically added variants) without
    // changing its underlying value until submit.
    $('[data-money-input]').forEach((input) => { input.value = formatMoneyInput(input.value); });
    document.addEventListener('focusin', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-money-input]')) {
            event.target.value = cleanMoneyInput(event.target.value);
        }
    });
    document.addEventListener('focusout', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-money-input]')) {
            event.target.value = formatMoneyInput(event.target.value);
        }
    });
    document.addEventListener('submit', (event) => {
        $('[data-money-input]', event.target).forEach((input) => { input.value = cleanMoneyInput(input.value); });
    }, true);

    // Sidebar interactions (desktop expandable navigation + mobile drawer).
    $$('[data-nav-toggle]').forEach((button) => button.addEventListener('click', () => {
        const open = button.classList.toggle('is-open');
        button.setAttribute('aria-expanded', String(open));
    }));
    $('[data-sidebar-open]')?.addEventListener('click', () => {
        $('#adminSidebar')?.classList.add('is-open');
        $('[data-sidebar-close]')?.classList.add('is-visible');
    });
    $('[data-sidebar-close]')?.addEventListener('click', () => {
        $('#adminSidebar')?.classList.remove('is-open');
        $('[data-sidebar-close]')?.classList.remove('is-visible');
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            $('#adminSidebar')?.classList.remove('is-open');
            $('[data-sidebar-close]')?.classList.remove('is-visible');
            $('[data-columns-panel]')?.setAttribute('hidden', '');
            $('[data-help-panel]')?.setAttribute('hidden', '');
        }
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            const search = $('[data-nav-search]');
            if (search) { event.preventDefault(); search.focus(); }
        }
    });
    $('[data-nav-search]')?.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        $$('.ak-nav-link, .ak-nav-group, .ak-nav-submenu a').forEach((link) => {
            link.hidden = !!term && !link.textContent.toLowerCase().includes(term);
        });
        if (term) $$('.ak-nav-group').forEach((group) => {
            group.classList.add('is-open');
            group.setAttribute('aria-expanded', 'true');
        });
    });

    // Table filters, column controls, and selection.
    $$('[data-toggle-filters]').forEach((button) => button.addEventListener('click', () => {
        const panel = $('[data-filter-panel]');
        if (panel) panel.classList.toggle('is-visible');
    }));
    $$('[data-columns-toggle]').forEach((button) => button.addEventListener('click', (event) => {
        const panel = $('[data-columns-panel]');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        event.stopPropagation();
    }));
    document.addEventListener('click', (event) => {
        const panel = $('[data-columns-panel]');
        if (panel && !panel.hidden && !event.target.closest('[data-columns-panel], [data-columns-toggle]')) panel.hidden = true;
    });
    $$('[data-column-check]').forEach((checkbox) => {
        const apply = () => {
            const selector = '[data-column="' + checkbox.dataset.columnCheck + '"]';
            $$(selector).forEach((cell) => { cell.hidden = !checkbox.checked; });
        };
        checkbox.addEventListener('change', apply);
        apply();
    });
    const rowCheckboxes = $('[data-row-select]');
    const bulkArchiveButton = $('[data-bulk-archive-button]');
    const updateBulkArchiveButton = () => {
        const selectedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
        if (!bulkArchiveButton) return;
        bulkArchiveButton.disabled = selectedCount === 0;
        bulkArchiveButton.textContent = selectedCount > 0
            ? `بایگانی ${selectedCount} محصول انتخاب‌شده`
            : 'بایگانی انتخاب‌شده‌ها';
    };
    rowCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateBulkArchiveButton));
    $('[data-select-all]')?.addEventListener('change', (event) => {
        rowCheckboxes.forEach((checkbox) => { checkbox.checked = event.target.checked; });
        updateBulkArchiveButton();
    });
    updateBulkArchiveButton();

    // Destructive actions always request confirmation.
    $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
    }));
    $$('[data-dismiss-flash]').forEach((button) => button.addEventListener('click', () => button.closest('.ak-flash')?.remove()));
    $('[data-help-open]')?.addEventListener('click', () => $('[data-help-panel]')?.removeAttribute('hidden'));
    $('[data-help-close]')?.addEventListener('click', () => $('[data-help-panel]')?.setAttribute('hidden', ''));

    // Select, crop and preview new product images before they reach Laravel.
    const imageInput = $('[data-image-input]');
    const previewBox = $('[data-image-preview]');
    let activeImageInput = imageInput;
    let activePreviewBox = previewBox;
    const cropModal = $('[data-crop-modal]');
    const cropCanvas = $('[data-crop-preview]');
    const cropContext = cropCanvas?.getContext('2d');
    const cropImage = new Image();
    const cropRatio = $('[data-crop-ratio]');
    const cropZoom = $('[data-crop-zoom]');
    const cropX = $('[data-crop-x]');
    const cropY = $('[data-crop-y]');
    const cropCount = $('[data-crop-count]');
    let cropQueue = [];
    let cropResults = [];
    let cropIndex = 0;
    let cropObjectUrl = null;
    let cropRect = null;
    const previewUrlsByBox = new WeakMap();

    const revokePreviewUrls = (box = activePreviewBox) => {
        if (!box) return;
        (previewUrlsByBox.get(box) || []).forEach((url) => URL.revokeObjectURL(url));
        previewUrlsByBox.delete(box);
    };
    const selectImageTarget = (input) => {
        activeImageInput = input || imageInput;
        activePreviewBox = activeImageInput?.matches('[data-variant-image-input]')
            ? activeImageInput.closest('[data-variant-row]')?.querySelector('[data-variant-image-preview]')
            : previewBox;
    };
    const renderImages = (files) => {
        if (!activePreviewBox) return;
        const box = activePreviewBox;
        revokePreviewUrls(box);
        box.replaceChildren();
        const localUrls = [];
        const isVariantUpload = activeImageInput?.matches('[data-variant-image-input]');
        const limit = isVariantUpload ? 4 : 8;
        Array.from(files || []).slice(0, limit).forEach((file) => {
            if (!file.type?.startsWith('image/')) return;
            const tile = document.createElement('div');
            tile.className = 'ak-new-image-tile';
            const image = document.createElement('img');
            image.alt = file.name;
            image.loading = 'lazy';
            image.src = URL.createObjectURL(file);
            localUrls.push(image.src);
            image.addEventListener('error', () => {
                image.alt = 'پیش‌نمایش این تصویر در مرورگر ممکن نیست';
                tile.classList.add('is-preview-error');
            }, { once: true });
            const name = document.createElement('small');
            name.textContent = file.name;
            tile.append(image, name);
            box.appendChild(tile);
        });
        previewUrlsByBox.set(box, localUrls);
    };
    const currentCropRatio = () => {
        if (cropRatio?.value === '1:1') return 1;
        if (cropRatio?.value === '4:3') return 4 / 3;
        if (cropRatio?.value === '3:4') return 3 / 4;
        return cropImage.naturalWidth / Math.max(1, cropImage.naturalHeight);
    };
    const calculateCropRect = () => {
        const width = cropImage.naturalWidth;
        const height = cropImage.naturalHeight;
        const ratio = currentCropRatio();
        let cropWidth = Math.min(width, height * ratio);
        let cropHeight = cropWidth / ratio;
        const zoom = Math.max(1, Number(cropZoom?.value || 100) / 100);
        cropWidth /= zoom;
        cropHeight /= zoom;
        const xSpace = Math.max(0, width - cropWidth);
        const ySpace = Math.max(0, height - cropHeight);
        return {
            sx: xSpace * (Number(cropX?.value || 50) / 100),
            sy: ySpace * (Number(cropY?.value || 50) / 100),
            sw: cropWidth,
            sh: cropHeight,
            ratio,
        };
    };
    const paintCropPreview = () => {
        if (!cropContext || !cropCanvas || !cropImage.complete || !cropImage.naturalWidth) return;
        cropRect = calculateCropRect();
        const width = Math.min(640, Math.round(640 * Math.min(1, cropRect.ratio)));
        const height = Math.max(1, Math.round(width / cropRect.ratio));
        cropCanvas.width = width;
        cropCanvas.height = height;
        cropContext.clearRect(0, 0, width, height);
        cropContext.drawImage(cropImage, cropRect.sx, cropRect.sy, cropRect.sw, cropRect.sh, 0, 0, width, height);
        const zoomLabel = $('[data-crop-zoom-value]');
        if (zoomLabel) zoomLabel.textContent = Number(cropZoom?.value || 100) + '٪';
    };
    const closeCropper = () => {
        cropModal?.setAttribute('hidden', '');
        if (cropObjectUrl) URL.revokeObjectURL(cropObjectUrl);
        cropObjectUrl = null;
    };
    const assignCroppedFiles = () => {
        if (!activeImageInput) return;
        const limit = activeImageInput.matches('[data-variant-image-input]') ? 4 : 8;
        const files = cropResults.slice(0, limit);
        try {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            activeImageInput.files = transfer.files;
        } catch (error) {
            window.alert('مرورگر نتوانست فایل‌های آماده‌شده را به فرم پیوند بدهد. لطفاً از نسخهٔ به‌روز Chrome یا Edge استفاده کنید.');
            closeCropper();
            return;
        }
        renderImages(activeImageInput.files);
        closeCropper();
    };
    const showCropAtIndex = () => {
        if (cropIndex >= cropQueue.length) {
            assignCroppedFiles();
            return;
        }
        const file = cropQueue[cropIndex];
        if (cropCount) cropCount.textContent = 'تصویر ' + (cropIndex + 1) + ' از ' + cropQueue.length + ' · ' + file.name;
        if (cropObjectUrl) URL.revokeObjectURL(cropObjectUrl);
        cropObjectUrl = URL.createObjectURL(file);
        cropImage.onload = () => paintCropPreview();
        cropImage.onerror = () => {
            window.alert('این تصویر قابل خواندن نیست و از انتخاب حذف شد.');
            cropIndex += 1;
            showCropAtIndex();
        };
        cropImage.src = cropObjectUrl;
        if (cropZoom) cropZoom.value = '100';
        if (cropX) cropX.value = '50';
        if (cropY) cropY.value = '50';
        cropModal?.removeAttribute('hidden');
    };
    const startImageCropping = (fileList, targetInput = imageInput) => {
        selectImageTarget(targetInput);
        const files = Array.from(fileList || []);
        if (!files.length) return;
        const maxImages = activeImageInput?.matches('[data-variant-image-input]') ? 4 : 8;
        if (files.length > maxImages) {
            window.alert(activeImageInput?.matches('[data-variant-image-input]')
                ? 'برای هر تنوع حداکثر ۴ تصویر انتخاب کنید.'
                : 'در هر بار ذخیره حداکثر ۸ تصویر انتخاب کنید.');
            if (activeImageInput) activeImageInput.value = '';
            renderImages([]);
            return;
        }
        const images = files.filter((file) => file.type.startsWith('image/'));
        if (images.length !== files.length) window.alert('فقط فایل‌های تصویری پذیرفته می‌شوند.');
        if (images.some((file) => file.size > 16 * 1024 * 1024)) {
            window.alert('حجم فایل اصلی هر تصویر نباید بیشتر از ۱۶ مگابایت باشد.');
            if (imageInput) imageInput.value = '';
            return;
        }
        cropQueue = images;
        cropResults = [];
        cropIndex = 0;
        // Show an immediate local preview while the crop dialog is open.
        renderImages(images);
        if (activeImageInput) activeImageInput.value = '';
        if (!cropModal || !cropCanvas || !cropContext) {
            cropResults = images;
            assignCroppedFiles();
            return;
        }
        showCropAtIndex();
    };
    const continueWithImage = (file) => {
        cropResults.push(file);
        cropIndex += 1;
        showCropAtIndex();
    };
    const cropCurrentImage = () => new Promise((resolve, reject) => {
        if (!cropRect || !cropImage.naturalWidth) {
            reject(new Error('تصویر برای برش آماده نیست.'));
            return;
        }
        const scale = Math.min(1, 1200 / cropRect.sw, 1600 / cropRect.sh);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(cropRect.sw * scale));
        canvas.height = Math.max(1, Math.round(cropRect.sh * scale));
        const context = canvas.getContext('2d');
        if (!context) {
            reject(new Error('امکان آماده‌سازی تصویر وجود ندارد.'));
            return;
        }
        context.drawImage(cropImage, cropRect.sx, cropRect.sy, cropRect.sw, cropRect.sh, 0, 0, canvas.width, canvas.height);
        const makeBlob = (type, quality) => new Promise((done) => canvas.toBlob(done, type, quality));
        (async () => {
            let type = 'image/webp';
            let blob = await makeBlob(type, 0.88);
            if (!blob || (blob.type && blob.type !== type)) {
                type = 'image/jpeg';
                blob = await makeBlob(type, 0.86);
            }
            if (blob && blob.type) type = blob.type;
            if (blob && blob.size > 4 * 1024 * 1024) {
                blob = await makeBlob(type, 0.65);
                if (blob && blob.type) type = blob.type;
            }
            if (!blob || blob.size > 4 * 1024 * 1024) throw new Error('حجم تصویر برش‌خورده هنوز بیشتر از ۴ مگابایت است.');
            const extension = type === 'image/webp' ? '.webp' : '.jpg';
            const name = cropQueue[cropIndex].name.replace(/\.[^.]+$/, '') + extension;
            resolve(new File([blob], name, { type, lastModified: Date.now() }));
        })().catch(reject);
    });
    $('[data-browse-images]')?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', () => startImageCropping(imageInput.files, imageInput));
    // Delegation supports variant rows added dynamically after page load.
    document.addEventListener('change', (event) => {
        const input = event.target;
        if (input instanceof HTMLInputElement && input.matches('[data-variant-image-input]')) {
            startImageCropping(input.files, input);
        }
    });
    [cropRatio, cropZoom, cropX, cropY].forEach((control) => control?.addEventListener('input', paintCropPreview));
    $('[data-crop-apply]')?.addEventListener('click', async () => {
        const button = $('[data-crop-apply]');
        if (button) { button.disabled = true; button.textContent = 'در حال آماده‌سازی…'; }
        try {
            continueWithImage(await cropCurrentImage());
        } catch (error) {
            window.alert(error.message || 'برش تصویر انجام نشد.');
        } finally {
            if (button) { button.disabled = false; button.textContent = 'تأیید برش و ادامه'; }
        }
    });
    $('[data-crop-skip]')?.addEventListener('click', () => continueWithImage(cropQueue[cropIndex]));
    $('[data-crop-cancel]').forEach((button) => button.addEventListener('click', () => {
        cropQueue = [];
        cropResults = [];
        cropIndex = 0;
        if (activeImageInput) activeImageInput.value = '';
        renderImages([]);
        closeCropper();
    }));
    const dropZone = $('[data-image-drop]');
    if (dropZone) {
        ['dragenter', 'dragover'].forEach((name) => dropZone.addEventListener(name, (event) => {
            event.preventDefault();
            dropZone.classList.add('is-dragging');
        }));
        ['dragleave', 'drop'].forEach((name) => dropZone.addEventListener(name, (event) => {
            event.preventDefault();
            dropZone.classList.remove('is-dragging');
        }));
        dropZone.addEventListener('drop', (event) => {
            startImageCropping(event.dataTransfer?.files || [], imageInput);
        });
    }

    // Dynamic product-variant editor.
    const variantList = $('[data-variant-list]');
    const variantTemplate = $('#variant-row-template');
    const renumberVariants = () => {
        const rows = $$('[data-variant-row]', variantList || document);
        rows.forEach((row, index) => {
            const number = $('[data-variant-number]', row);
            if (number) number.textContent = String(index + 1);
            $$('[name]', row).forEach((field) => {
                field.name = field.name.replace(/variants\[\d+\]/, 'variants[' + index + ']');
            });
            const remove = $('[data-remove-variant]', row);
            if (remove) remove.disabled = rows.length <= 1;
        });
    };
    $('[data-add-variant]')?.addEventListener('click', () => {
        if (!variantList || !variantTemplate) return;
        const index = $$('[data-variant-row]', variantList).length;
        if (index >= 30) { window.alert('A product can have at most 30 variants in one save.'); return; }
        const html = variantTemplate.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(index + 1));
        variantList.insertAdjacentHTML('beforeend', html);
        renumberVariants();
    });
    variantList?.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-variant]');
        if (remove && !remove.disabled) {
            remove.closest('[data-variant-row]')?.remove();
            renumberVariants();
        }
    });
    variantList?.addEventListener('input', (event) => {
        const target = event.target;
        if (target.matches('input[type="color"]')) {
            const mirror = $('[data-color-mirror]', target.closest('.ak-color-code'));
            if (mirror) mirror.value = target.value;
        } else if (target.matches('[data-color-mirror]')) {
            const picker = $('input[type="color"]', target.closest('.ak-color-code'));
            if (picker && /^#[0-9a-f]{6}$/i.test(target.value)) picker.value = target.value;
        }
    });
    renumberVariants();

    // Product gallery thumbnail switching.
    $$('[data-gallery-image]').forEach((button) => button.addEventListener('click', () => {
        const image = $('.ak-product-hero-image img');
        if (!image) return;
        image.src = button.dataset.galleryImage;
        image.alt = button.dataset.galleryAlt || '';
        $$('.ak-gallery-thumb').forEach((thumb) => thumb.classList.toggle('is-active', thumb === button));
    }));

    // Order form totals are previews only; authoritative pricing and stock are
    // revalidated against locked database rows in the Laravel transaction.
    const orderList = $('[data-order-items]');
    const orderTemplate = $('#order-item-template');
    const shippingInput = $('[data-shipping-cost]');
    const orderVariantSearch = $('[data-order-variant-search]');
    const orderVariantResults = $('[data-order-variant-results]');
    const orderForm = $('#orderCreateForm');
    const orderVariantSearchUrl = orderForm?.dataset.orderVariantSearchUrl;
    let pendingInitialVariantIds = (orderForm?.dataset.orderInitialVariantIds || '').split(',').filter(Boolean);
    let lastVariantResults = [];
    let variantSearchTimer = null;
    let variantSearchSequence = 0;
    const recalculateOrder = () => {
        let subtotal = 0;
        $$('[data-order-item-row]', orderList || document).forEach((row) => {
            const select = $('[data-order-variant]', row);
            const quantity = $('[data-order-quantity]', row);
            const option = select?.selectedOptions?.[0];
            const price = Number(option?.dataset.price || 0);
            const stock = Number(option?.dataset.stock || 0);
            const qty = Number(quantity?.value || 0);
            if (quantity) {
                quantity.max = stock > 0 ? String(stock) : '1';
                quantity.setCustomValidity(qty > stock ? 'Only ' + stock + ' units are currently available.' : '');
            }
            subtotal += price * qty;
            const lineTotal = $('[data-line-total]', row);
            if (lineTotal) lineTotal.textContent = money(price * qty);
        });
        const shipping = parseMoneyInput(shippingInput?.value || 0);
        const subtotalEl = $('[data-order-subtotal]');
        const totalEl = $('[data-order-total]');
        if (subtotalEl) subtotalEl.textContent = money(subtotal);
        if (totalEl) totalEl.textContent = money(subtotal + shipping);
    };
    const updateOrderVariantOptions = (items) => {
        $('[data-order-variant]', orderList || document).forEach((select) => {
            const selectedValue = select.value || select.dataset.initialValue || '';
            const selectedOption = select.selectedOptions?.[0]?.cloneNode(true);
            select.replaceChildren(new Option('ابتدا محصول را جست‌وجو کنید', ''));
            items.forEach((item) => {
                const option = new Option(`${item.label} — ${money(item.price)} (${item.stock} موجود)`, String(item.id));
                option.dataset.price = String(item.price);
                option.dataset.stock = String(item.stock);
                select.append(option);
            });
            if (selectedValue && !items.some((item) => String(item.id) === selectedValue) && selectedOption) {
                select.append(selectedOption);
            }
            select.value = selectedValue;
            delete select.dataset.initialValue;
        });
        recalculateOrder();
    };
    const loadOrderVariants = async (search = '') => {
        if (!orderVariantSearchUrl || !orderVariantResults) return;
        const sequence = ++variantSearchSequence;
        orderVariantResults.textContent = 'در حال جست‌وجوی تنوع‌های فعال…';
        const url = new URL(orderVariantSearchUrl, window.location.origin);
        if (search.trim() !== '') url.searchParams.set('q', search.trim());
        else url.searchParams.delete('q');
        const selectedIds = [...new Set([
            ...pendingInitialVariantIds,
            ...$('[data-order-variant]', orderList || document).map((select) => select.value || select.dataset.initialValue).filter(Boolean),
        ])];
        selectedIds.forEach((id) => url.searchParams.append('ids[]', id));
        try {
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Variant search failed');
            const payload = await response.json();
            if (sequence !== variantSearchSequence) return;
            lastVariantResults = Array.isArray(payload.data) ? payload.data : [];
            updateOrderVariantOptions(lastVariantResults);
            pendingInitialVariantIds = [];
            orderVariantResults.textContent = lastVariantResults.length === 0
                ? 'با این عبارت محصول فعالی پیدا نشد.'
                : `${lastVariantResults.length} گزینهٔ قابل فروش${payload.has_more ? ' — نتایج بیشتر است؛ جست‌وجو را دقیق‌تر کنید.' : ''}`;
        } catch (_) {
            if (sequence === variantSearchSequence) orderVariantResults.textContent = 'بارگذاری گزینه‌ها ناموفق بود؛ دوباره جست‌وجو کنید.';
        }
    };
    orderVariantSearch?.addEventListener('input', () => {
        if (variantSearchTimer !== null) window.clearTimeout(variantSearchTimer);
        variantSearchTimer = window.setTimeout(() => loadOrderVariants(orderVariantSearch.value), 220);
    });
    const renumberOrderItems = () => {
        const rows = $$('[data-order-item-row]', orderList || document);
        rows.forEach((row, index) => {
            $$('[name]', row).forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            const remove = $('[data-remove-order-item]', row);
            if (remove) remove.disabled = rows.length <= 1;
        });
        recalculateOrder();
    };
    $('[data-add-order-item]')?.addEventListener('click', () => {
        if (!orderList || !orderTemplate) return;
        const index = $$('[data-order-item-row]', orderList).length;
        orderList.insertAdjacentHTML('beforeend', orderTemplate.innerHTML.replaceAll('__INDEX__', String(index)));
        updateOrderVariantOptions(lastVariantResults);
        renumberOrderItems();
    });
    orderList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-order-item]');
        if (button && !button.disabled) {
            button.closest('[data-order-item-row]')?.remove();
            renumberOrderItems();
        }
    });
    orderList?.addEventListener('input', (event) => {
        if (event.target.matches('[data-order-quantity]')) recalculateOrder();
    });
    orderList?.addEventListener('change', (event) => {
        if (event.target.matches('[data-order-variant]')) recalculateOrder();
    });
    shippingInput?.addEventListener('input', recalculateOrder);
    renumberOrderItems();
    if (orderVariantSearch) loadOrderVariants('');
})();