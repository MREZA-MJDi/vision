(() => {
    'use strict';
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const symbol = document.body && document.body.dataset.currencySymbol ? document.body.dataset.currencySymbol : '$';
    const money = (value) => symbol + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

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
    $('[data-select-all]')?.addEventListener('change', (event) => {
        $$('[data-row-select]').forEach((checkbox) => { checkbox.checked = event.target.checked; });
    });

    // Destructive actions always request confirmation.
    $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
    }));
    $$('[data-dismiss-flash]').forEach((button) => button.addEventListener('click', () => button.closest('.ak-flash')?.remove()));
    $('[data-help-open]')?.addEventListener('click', () => $('[data-help-panel]')?.removeAttribute('hidden'));
    $('[data-help-close]')?.addEventListener('click', () => $('[data-help-panel]')?.setAttribute('hidden', ''));

    // Image selection preview. Server-side MIME and size validation remains mandatory.
    const imageInput = $('[data-image-input]');
    const previewBox = $('[data-image-preview]');
    const renderImages = (files) => {
        if (!previewBox) return;
        previewBox.replaceChildren();
        Array.from(files || []).slice(0, 8).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            previewBox.appendChild(image);
        });
    };
    $('[data-browse-images]')?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', () => {
        if (imageInput.files.length > 8) {
            window.alert('Upload up to 8 images per save.');
            imageInput.value = '';
            renderImages([]);
            return;
        }
        if (Array.from(imageInput.files).some((file) => file.size > 4 * 1024 * 1024)) {
            window.alert('Each image must be 4 MB or smaller. The server validates this too.');
        }
        renderImages(imageInput.files);
    });
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
            const files = Array.from(event.dataTransfer?.files || []).filter((file) => file.type.startsWith('image/')).slice(0, 8);
            if (!imageInput || !files.length) return;
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
            renderImages(imageInput.files);
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
        const shipping = Number(shippingInput?.value || 0);
        const subtotalEl = $('[data-order-subtotal]');
        const totalEl = $('[data-order-total]');
        if (subtotalEl) subtotalEl.textContent = money(subtotal);
        if (totalEl) totalEl.textContent = money(subtotal + shipping);
    };
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
})();