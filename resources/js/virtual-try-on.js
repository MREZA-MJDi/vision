(() => {
    const root = document.querySelector('[data-virtual-try-on]');
    if (!root) return;

    const inlineMode = root.dataset.vtoInline === 'true';

    const ui = {
        audienceButtons: Array.from(root.querySelectorAll('[data-vto-audience]')),
        mannequins: root.querySelector('[data-vto-mannequins]'),
        slots: Array.from(root.querySelectorAll('[data-vto-slot]')),
        products: root.querySelector('[data-vto-products]'),
        resultCount: root.querySelector('[data-vto-result-count]'),
        catalogNote: root.querySelector('[data-vto-catalog-note]'),
        loadMore: root.querySelector('[data-vto-load-more]'),
        stage: root.querySelector('[data-vto-stage]'),
        baseImage: root.querySelector('[data-vto-base-image]'),
        silhouette: root.querySelector('[data-vto-fallback-mannequin]'),
        layers: root.querySelector('[data-vto-layers]'),
        stageEmpty: root.querySelector('[data-vto-stage-empty]'),
        stageLoading: root.querySelector('[data-vto-stage-loading]'),
        caption: root.querySelector('[data-vto-mannequin-caption]'),
        selected: root.querySelector('[data-vto-selected-list]'),
        picksCount: root.querySelector('[data-vto-picks-count]'),
        total: root.querySelector('[data-vto-total]'),
        inventoryNote: root.querySelector('[data-vto-inventory-note]'),
        clearAll: root.querySelector('[data-vto-clear-all]'),
        toast: root.querySelector('[data-vto-toast]'),
        expand: root.querySelector('[data-vto-expand]'),
        close: root.querySelector('[data-vto-close]'),
        launcher: document.querySelector('[data-vto-launcher]'),
        launcherCount: document.querySelector('[data-vto-launcher-count]'),
    };

    const labels = { top: 'بالاپوش', outerwear: 'لایهٔ رویی', bottom: 'پایین‌تنه', shoes: 'کفش', accessory: 'اکسسوری' };
    const order = { shoes: 10, bottom: 20, top: 30, outerwear: 40, accessory: 50 };
    const initialAudience = ['women', 'men'].includes(root.dataset.initialAudience)
        ? root.dataset.initialAudience
        : 'women';
    const state = {
        audience: initialAudience,
        requestedVariantId: Number(root.dataset.initialVariantId) || null,
        pendingInitialVariantId: null,
        initialVariantHasLayer: false,
        currentProductVariantId: null,
        currentProductSlot: null,
        mannequins: [],
        mannequin: null,
        defaultMannequinId: null,
        slot: 'top',
        catalog: [],
        itemsByVariant: new Map(),
        selected: {},
        page: 1,
        nextPage: null,
        catalogController: null,
        previewController: null,
        bootstrapController: null,
        bootstrapVersion: 0,
        requestVersion: 0,
        revision: 0,
        toastTimer: null,
    };

    let expansionMarker = null;
    let expansionReturnFocus = null;
    let previousDocumentOverflow = '';
    let inlineReturnFocus = null;

    function setInlineOpen(open, initialize = true) {
        if (!inlineMode) return;
        const currentlyOpen = root.classList.contains('is-open');
        if (currentlyOpen === open) return;

        if (open) {
            inlineReturnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : ui.launcher;
            root.inert = false;
            root.setAttribute('aria-hidden', 'false');
            root.classList.add('is-open');
            ui.launcher?.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(() => ui.close?.focus({ preventScroll: true }));
            if (initialize && !state.mannequin && !state.bootstrapController) {
                loadBootstrap().catch((error) => {
                    showCatalogEmpty('اتاق پرو آماده نشد.', error.message || 'اتصال را بررسی کن و صفحه را دوباره بارگذاری کن.');
                    showToast(error.message || 'آماده‌سازی اتاق پرو انجام نشد.');
                });
            }
            return;
        }

        root.classList.remove('is-open');
        root.inert = true;
        root.setAttribute('aria-hidden', 'true');
        ui.launcher?.setAttribute('aria-expanded', 'false');
        const focusTarget = inlineReturnFocus?.isConnected && !root.contains(inlineReturnFocus)
            ? inlineReturnFocus
            : ui.launcher;
        focusTarget?.focus?.({ preventScroll: true });
        inlineReturnFocus = null;
    }

    // The shop card owns the click target; this controller owns the drawer and
    // the single source of truth for selection. Animate a decorative clone only.
    function animateShopSelection(button, drawerAlreadyOpen = false) {
        const thumbnail = button.closest('[data-product-card]')?.querySelector('.product-card__image');
        if (!thumbnail || !ui.stage || !thumbnail.complete || !thumbnail.naturalWidth) return;

        const source = thumbnail.getBoundingClientRect();
        if (source.width < 1 || source.height < 1) return;

        const clone = document.createElement('img');
        clone.src = thumbnail.currentSrc || thumbnail.src;
        clone.alt = '';
        clone.setAttribute('aria-hidden', 'true');
        clone.style.cssText = [
            'position:fixed',
            `left:${source.left}px`,
            `top:${source.top}px`,
            `width:${source.width}px`,
            `height:${source.height}px`,
            'max-width:none',
            'object-fit:contain',
            'pointer-events:none',
            'z-index:1305',
            'transform-origin:center center',
            'filter:drop-shadow(0 12px 18px rgba(0,0,0,.36))',
            'will-change:transform,opacity,filter',
        ].join(';');
        document.body.append(clone);

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion || typeof clone.animate !== 'function') {
            clone.remove();
            return;
        }

        const waitForDrawer = () => new Promise((resolve) => {
            if (drawerAlreadyOpen || !inlineMode || !root.classList.contains('is-open')) {
                resolve();
                return;
            }

            let settled = false;
            let timer = null;
            const finish = () => {
                if (settled) return;
                settled = true;
                if (timer !== null) window.clearTimeout(timer);
                root.removeEventListener('transitionend', onTransitionEnd);
                resolve();
            };
            const onTransitionEnd = (event) => {
                if (event.target === root && event.propertyName === 'transform') finish();
            };

            root.addEventListener('transitionend', onTransitionEnd);
            // Also covers reduced CSS transitions and browsers that omit the event.
            timer = window.setTimeout(finish, 360);
        });

        waitForDrawer().then(() => {
            if (!clone.isConnected || !ui.stage.isConnected) return;
            const target = ui.stage.getBoundingClientRect();
            if (target.width < 1 || target.height < 1) return;

            const dx = target.left + target.width / 2 - (source.left + source.width / 2);
            const dy = target.top + target.height * .36 - (source.top + source.height / 2);
            const animation = clone.animate([
                { transform: 'translate(0,0) scale(1) rotate(0deg)', opacity: 1, filter: 'blur(0px) drop-shadow(0 12px 18px rgba(0,0,0,.36))' },
                { offset: .58, transform: `translate(${dx * .58}px,${dy * .58 - 22}px) scale(.48) rotate(5deg)`, opacity: .94, filter: 'blur(1px) drop-shadow(0 8px 12px rgba(0,0,0,.24))' },
                { transform: `translate(${dx}px,${dy}px) scale(.13) rotate(10deg)`, opacity: 0, filter: 'blur(4px) drop-shadow(0 0 0 rgba(0,0,0,0))' },
            ], {
                duration: 620,
                easing: 'cubic-bezier(.16,1,.3,1)',
                fill: 'forwards',
            });
            return animation.finished.catch(() => undefined);
        }).catch(() => undefined).finally(() => clone.remove());
    }

    function renderShopCardSelections() {
        if (!inlineMode) return;
        document.querySelectorAll('[data-vto-select-product]').forEach((button) => {
            const variantId = Number(button.dataset.vtoVariantId) || null;
            const slot = button.dataset.vtoSlot || '';
            const selected = Boolean(variantId && slot && Number(state.selected[slot]) === variantId);
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', String(selected));
            button.setAttribute('aria-label', selected
                ? 'حذف ' + (button.dataset.productName || 'این محصول') + ' از اتاق پرو'
                : (button.dataset.vtoReady === '1' ? 'امتحان روی مانکن' : 'تصویر پرو برای این محصول ثبت نشده است'));
            button.title = selected ? 'حذف از اتاق پرو' : (button.dataset.vtoReady === '1' ? 'امتحان روی مانکن' : 'تصویر لایه‌ای اتاق پرو هنوز ثبت نشده است');
        });

        const count = Object.values(state.selected).filter(Boolean).length;
        if (ui.launcherCount) ui.launcherCount.textContent = new Intl.NumberFormat('fa-IR').format(count);
    }

    function invalidatePendingRequests() {
        if (state.catalogController) state.catalogController.abort();
        if (state.previewController) state.previewController.abort();
        if (state.bootstrapController) state.bootstrapController.abort();
        state.catalogController = null;
        state.previewController = null;
        state.bootstrapController = null;
        state.bootstrapVersion++;
        state.requestVersion++;
    }

    function removeSelection(slot, variantId) {
        if (!slot || Number(state.selected[slot]) !== Number(variantId)) return;
        delete state.selected[slot];
        if (state.currentProductVariantId === Number(variantId)) {
            state.currentProductVariantId = null;
            state.currentProductSlot = null;
        }
        if (Number(state.requestedVariantId) === Number(variantId)) {
            state.requestedVariantId = null;
            state.pendingInitialVariantId = null;
        }
        invalidatePendingRequests();
        renderProductCards();
        renderSlots();
        renderSummary();
        renderShopCardSelections();
        if (state.mannequin) {
            refreshPreview();
        } else {
            loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
        }
    }

    function formatPrice(value) {
        return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 }).format(Number(value || 0)) + ' ' + root.dataset.currency;
    }

    function showToast(message) {
        ui.toast.textContent = String(message || 'مشکلی پیش آمد.');
        ui.toast.classList.add('is-visible');
        window.clearTimeout(state.toastTimer);
        state.toastTimer = window.setTimeout(() => ui.toast.classList.remove('is-visible'), 3200);
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, options || {});
        let body;
        try {
            body = await response.json();
        } catch {
            throw new Error('پاسخ سرور قابل خواندن نبود؛ دوباره تلاش کن.');
        }
        if (!response.ok) {
            const messages = body.errors ? Object.values(body.errors).flat() : [];
            throw new Error(messages[0] || body.message || 'در ارتباط با سرور مشکلی پیش آمد.');
        }
        return body;
    }

    function endpoint(url, params) {
        const result = new URL(url, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') result.searchParams.set(key, value);
        });
        return result.toString();
    }

    function setBusy(element, busy) {
        if (element) element.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function renderAudienceButtons() {
        ui.audienceButtons.forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.vtoAudience === state.audience ? 'true' : 'false');
        });
    }

    function renderSlots() {
        ui.slots.forEach((button) => {
            const slot = button.dataset.vtoSlot;
            const selectedId = state.selected[slot];
            button.setAttribute('aria-pressed', slot === state.slot ? 'true' : 'false');
            button.classList.toggle('has-selection', Boolean(selectedId));
            const stateLabel = root.querySelector('[data-vto-slot-state="' + slot + '"]');
            const selectedItem = selectedId ? state.itemsByVariant.get(Number(selectedId)) : null;
            if (stateLabel) stateLabel.textContent = selectedId ? (selectedItem ? selectedItem.label : 'انتخاب شده') : 'انتخاب نشده';
        });
        renderShopCardSelections();
    }

    function renderMannequins() {
        ui.mannequins.replaceChildren();
        state.mannequins.forEach((mannequin) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'vto-mannequin-chip';
            button.textContent = mannequin.label;
            button.setAttribute('aria-pressed', String(Number(state.mannequin && state.mannequin.id) === Number(mannequin.id)));
            button.addEventListener('click', () => {
                if (Number(state.mannequin && state.mannequin.id) === Number(mannequin.id)) return;
                state.mannequin = mannequin;
                state.defaultMannequinId = null;
                state.selected = {};
                state.itemsByVariant.clear();
                state.catalog = [];
                state.requestedVariantId = null;
                state.pendingInitialVariantId = null;
                state.currentProductVariantId = null;
                state.currentProductSlot = null;
                if (state.catalogController) state.catalogController.abort();
                if (state.previewController) state.previewController.abort();
                if (state.bootstrapController) state.bootstrapController.abort();
                state.requestVersion++;
                state.bootstrapVersion++;
                ui.layers.replaceChildren();
                renderMannequins();
                updateMannequinStage();
                renderSlots();
                renderSummary(0);
                loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
            });
            ui.mannequins.append(button);
        });
        ui.mannequins.hidden = state.mannequins.length < 2;
    }

    function updateMannequinStage() {
        const mannequin = state.mannequin;
        if (!mannequin) return;
        const width = Math.max(1, Number(mannequin.canvas_width) || 1024);
        const height = Math.max(1, Number(mannequin.canvas_height) || 1536);
        ui.stage.style.aspectRatio = width + ' / ' + height;
        ui.caption.textContent = (state.audience === 'women' ? 'مانکن زنانه' : 'مانکن مردانه') + ' · ' + (mannequin.label || 'نمای روبه‌رو');

        if (mannequin.base_image_url) {
            ui.baseImage.onerror = () => {
                ui.baseImage.hidden = true;
                ui.silhouette.hidden = false;
            };
            ui.baseImage.onload = () => {
                ui.baseImage.hidden = false;
                ui.silhouette.hidden = true;
            };
            ui.baseImage.src = mannequin.base_image_url;
        } else {
            ui.baseImage.removeAttribute('src');
            ui.baseImage.hidden = true;
            ui.silhouette.hidden = false;
        }

        ui.silhouette.querySelectorAll('[data-vto-silhouette]').forEach((shape) => {
            shape.style.display = shape.dataset.vtoSilhouette === state.audience ? '' : 'none';
        });
    }

    function renderProductCards() {
        ui.products.replaceChildren();
        state.catalog.forEach((item) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'vto-product-card';
            card.dataset.vtoProduct = String(item.variant_id);
            card.setAttribute('aria-pressed', String(Number(state.selected[state.slot]) === Number(item.variant_id)));

            const visual = document.createElement('span');
            visual.className = 'vto-product-card__visual';
            if (item.thumbnail_url) {
                const image = document.createElement('img');
                image.src = item.thumbnail_url;
                image.alt = '';
                image.loading = 'lazy';
                image.decoding = 'async';
                image.onerror = () => image.remove();
                visual.append(image);
            } else {
                const placeholder = document.createElement('span');
                placeholder.className = 'vto-product-card__placeholder';
                placeholder.textContent = 'تصویر بندانگشتی موجود نیست';
                visual.append(placeholder);
            }
            const selected = document.createElement('span');
            selected.className = 'vto-product-card__selected';
            selected.textContent = '✓';
            selected.setAttribute('aria-hidden', 'true');
            visual.append(selected);

            const copy = document.createElement('span');
            copy.className = 'vto-product-card__copy';
            const name = document.createElement('span');
            name.className = 'vto-product-card__name';
            name.textContent = item.name || 'محصول';
            const meta = document.createElement('span');
            meta.className = 'vto-product-card__meta';
            const variantLabel = document.createElement('span');
            variantLabel.textContent = item.label || labels[item.slot] || '';
            const stock = document.createElement('span');
            stock.className = 'vto-product-card__stock';
            stock.textContent = Number(item.stock) > 0 ? 'موجود' : 'ناموجود';
            meta.append(variantLabel, stock);
            const price = document.createElement('span');
            price.className = 'vto-product-card__price';
            price.textContent = formatPrice(item.price);
            copy.append(name, meta, price);
            card.append(visual, copy);

            card.addEventListener('click', () => {
                if (Number(state.selected[state.slot]) === Number(item.variant_id)) {
                    delete state.selected[state.slot];
                } else {
                    state.selected[state.slot] = Number(item.variant_id);
                    state.itemsByVariant.set(Number(item.variant_id), item);
                }
                renderProductCards();
                renderSlots();
                renderSummary();
                refreshPreview();
            });
            ui.products.append(card);
        });

        ui.resultCount.textContent = new Intl.NumberFormat('fa-IR').format(state.catalog.length) + ' گزینه';
        ui.loadMore.hidden = !state.nextPage;
        renderShopCardSelections();
    }

    function showCatalogEmpty(title, message) {
        ui.products.replaceChildren();
        const empty = document.createElement('div');
        empty.className = 'vto-empty-catalog';
        const strong = document.createElement('strong');
        strong.textContent = title;
        const detail = document.createElement('span');
        detail.textContent = message;
        empty.append(strong, detail);
        ui.products.append(empty);
        ui.resultCount.textContent = '۰ گزینه';
        ui.loadMore.hidden = !state.nextPage;
        ui.catalogNote.textContent = 'فقط تصویرهای لایه‌ای معتبر و متناسب با مانکن نمایش داده می‌شوند.';
    }

    async function loadCatalog(reset) {
        if (!state.mannequin) {
            state.catalog = [];
            state.page = 1;
            state.nextPage = null;
            showCatalogEmpty('مانکن فعالی برای این گروه تعریف نشده است.', 'ابتدا پیکربندی مانکن را بررسی کن.');
            return;
        }
        if (state.catalogController) state.catalogController.abort();
        const controller = new AbortController();
        state.catalogController = controller;
        const requestVersion = state.bootstrapVersion;
        const selectedSlot = state.slot;
        const selectedMannequinId = Number(state.mannequin.id);
        if (reset) {
            state.page = 1;
            state.nextPage = null;
            state.catalog = [];
            ui.products.replaceChildren();
        }
        setBusy(ui.products, true);
        ui.catalogNote.textContent = 'در حال دریافت گزینه‌های آمادهٔ پیش‌نمایش…';

        try {
            const url = endpoint(root.dataset.catalogUrl, {
                audience: state.audience,
                mannequin_id: state.mannequin.id,
                slot: state.slot,
                page: state.page,
                variant_id: reset ? state.pendingInitialVariantId : null,
            });
            const payload = await fetchJson(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            // Abort is advisory; generation checks protect against late responses
            // from old slot/audience/mannequin requests.
            if (state.catalogController !== controller
                || requestVersion !== state.bootstrapVersion
                || selectedSlot !== state.slot
                || selectedMannequinId !== Number(state.mannequin && state.mannequin.id)) return;
            const results = Array.isArray(payload.data) ? payload.data : [];
            results.forEach((item) => {
                if (item.layer_url) state.itemsByVariant.set(Number(item.variant_id), item);
            });

            // Bootstrap promotes the product-page variant into the first catalog
            // page. Apply it only after the server confirms a valid layer asset.
            if (reset && state.pendingInitialVariantId) {
                const initialItem = results.find((item) => Number(item.variant_id) === state.pendingInitialVariantId
                    && item.slot === state.slot
                    && Boolean(item.layer_url));
                if (initialItem) {
                    state.selected[state.slot] = Number(initialItem.variant_id);
                    state.itemsByVariant.set(Number(initialItem.variant_id), initialItem);
                    state.currentProductVariantId = Number(initialItem.variant_id);
                    state.currentProductSlot = state.slot;
                    state.initialVariantHasLayer = true;
                } else {
                    state.currentProductVariantId = null;
                    state.currentProductSlot = null;
                    state.initialVariantHasLayer = false;
                }
                state.pendingInitialVariantId = null;
            }

            state.catalog = reset ? results.filter((item) => Boolean(item.layer_url)) : state.catalog.concat(results.filter((item) => Boolean(item.layer_url)));
            state.nextPage = payload.links ? payload.links.next_page : null;

            if (state.catalog.length === 0) {
                showCatalogEmpty('هنوز لایهٔ آماده‌ای برای این بخش وجود ندارد.', 'در مدیریت محصول گروه مناسب و تصویر شفاف این جایگاه را ثبت کن.');
            } else {
                ui.catalogNote.textContent = state.requestedVariantId && !state.initialVariantHasLayer
                    ? 'این واریانت برای مانکن انتخاب‌شده لایهٔ سازگار ندارد؛ می‌توانی لباس‌های آمادهٔ دیگر را در همین پیش‌نمایش ترکیب کنی.'
                    : 'هر گزینه فقط همین بخش را تغییر می‌دهد؛ انتخاب‌های دیگر حفظ می‌شوند.';
                renderProductCards();
            }
        } catch (error) {
            const isCurrentRequest = state.catalogController === controller
                && requestVersion === state.bootstrapVersion
                && selectedSlot === state.slot
                && selectedMannequinId === Number(state.mannequin && state.mannequin.id);

            if (error.name !== 'AbortError' && isCurrentRequest) {
                const message = error.message || 'اتصال را بررسی کن و دوباره تلاش کن.';
                if (reset || state.catalog.length === 0) {
                    showCatalogEmpty('دریافت محصولات انجام نشد.', message);
                } else {
                    ui.catalogNote.textContent = 'دریافت گزینه‌های بیشتر ناموفق بود؛ دوباره تلاش کن.';
                    ui.loadMore.hidden = !state.nextPage;
                }
                showToast(message);
            }
        } finally {
            if (state.catalogController === controller) setBusy(ui.products, false);
        }
    }

    function renderSummary(serverTotal) {
        const items = Object.entries(state.selected)
            .map(([slot, variantId]) => {
                const item = state.itemsByVariant.get(Number(variantId));
                return item ? { ...item, slot } : null;
            })
            .filter(Boolean)
            .sort((a, b) => (order[a.slot] || 99) - (order[b.slot] || 99));

        if (ui.picksCount) {
            ui.picksCount.textContent = new Intl.NumberFormat('fa-IR').format(items.length) + ' تکه';
        }
        ui.selected.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = inlineMode ? 'vto-inline-picks-empty' : 'vto-selected-empty';
            empty.textContent = inlineMode
                ? 'لباس‌های انتخابی اینجا نمایش داده می‌شوند.'
                : 'هنوز لباسی انتخاب نشده است؛ اولین بخش را از فهرست انتخاب کن.';
            ui.selected.append(empty);
        } else if (inlineMode) {
            items.forEach((item) => {
                const tile = document.createElement('div');
                tile.className = 'vto-inline-pick';
                const image = document.createElement('img');
                image.alt = item.name || 'محصول انتخاب‌شده';
                image.loading = 'lazy';
                image.decoding = 'async';
                if (item.thumbnail_url) {
                    image.src = item.thumbnail_url;
                    image.onerror = () => image.remove();
                } else {
                    image.hidden = true;
                }

                const name = document.createElement('span');
                name.className = 'vto-inline-pick__name';
                name.textContent = item.name || 'محصول';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'vto-inline-pick__remove';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'حذف ' + (item.name || 'محصول') + ' از اتاق پرو');
                remove.addEventListener('click', () => removeSelection(item.slot, item.variant_id));

                tile.append(image, name, remove);
                ui.selected.append(tile);
            });
        } else {
            items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'vto-selected-row';
                const main = document.createElement('span');
                main.className = 'vto-selected-row__main';
                const name = document.createElement('strong');
                name.textContent = item.name || 'محصول';
                const detail = document.createElement('small');
                detail.textContent = labels[item.slot] + ' · ' + (item.label || '');
                main.append(name, detail);
                const price = document.createElement('span');
                price.className = 'vto-selected-row__price';
                price.textContent = formatPrice(item.price);
                row.append(main, price);
                ui.selected.append(row);
            });
        }

        const estimated = items.reduce((sum, item) => sum + Number(item.price || 0), 0);
        if (ui.total) ui.total.textContent = formatPrice(Number.isFinite(serverTotal) ? serverTotal : estimated);
        if (ui.inventoryNote) {
            ui.inventoryNote.textContent = items.some((item) => Number(item.stock) < 1)
                ? 'حداقل یکی از انتخاب‌ها ناموجود است؛ موجودی نهایی هنگام خرید بررسی می‌شود.'
                : 'قیمت و موجودی هنگام خرید دوباره بررسی می‌شوند.';
        }
        ui.stageEmpty.hidden = items.length > 0;
        renderShopCardSelections();
    }

    async function refreshPreview() {
        if (!state.mannequin) {
            ui.layers.replaceChildren();
            ui.stageEmpty.hidden = false;
            return;
        }
        const version = ++state.requestVersion;
        const revision = ++state.revision;
        if (state.previewController) state.previewController.abort();
        state.previewController = new AbortController();
        ui.stageLoading.hidden = false;

        try {
            const payload = await fetchJson(root.dataset.previewUrl, {
                method: 'POST',
                signal: state.previewController.signal,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    audience: state.audience,
                    mannequin_id: Number(state.mannequin.id),
                    revision,
                    selections: state.selected,
                }),
            });
            if (version !== state.requestVersion || Number(payload.data.revision) !== revision) return;

            const layers = Array.isArray(payload.data.layers) ? payload.data.layers : [];
            const existingBySlot = new Map(
                Array.from(ui.layers.querySelectorAll('.vto-layer'))
                    .map((image) => [image.dataset.vtoSlot, image])
            );
            const fragment = document.createDocumentFragment();
            const enteringImages = [];

            layers.forEach((layer) => {
                const variantId = Number(layer.variant_id);
                const previous = state.itemsByVariant.get(variantId) || {};
                state.itemsByVariant.set(variantId, {
                    ...previous,
                    variant_id: variantId,
                    name: layer.product_name || previous.name,
                    label: layer.label || previous.label,
                    price: Number(layer.price ?? previous.price ?? 0),
                    stock: Number(layer.stock ?? previous.stock ?? 0),
                    slot: layer.slot,
                    layer_url: layer.layer_url,
                    thumbnail_url: layer.thumbnail_url || previous.thumbnail_url,
                });

                const existingImage = existingBySlot.get(layer.slot);
                const sameLayer = existingImage
                    && Number(existingImage.dataset.variantId) === variantId
                    && existingImage.dataset.layerUrl === layer.layer_url;
                let image;

                if (sameLayer) {
                    // Reuse unchanged DOM nodes so selecting trousers does not
                    // replay the shirt animation or flash the entire outfit.
                    image = existingImage;
                    existingBySlot.delete(layer.slot);
                } else {
                    image = document.createElement('img');
                    image.className = 'vto-layer is-entering';
                    image.src = layer.layer_url;
                    image.alt = '';
                    image.decoding = 'async';
                    image.loading = 'eager';
                    image.dataset.vtoSlot = layer.slot;
                    image.dataset.variantId = String(variantId);
                    image.dataset.layerUrl = layer.layer_url;
                    image.onerror = () => {
                        if (!image.isConnected) return;
                        image.remove();
                        if (!ui.layers.querySelector('.vto-layer')) ui.stageEmpty.hidden = false;
                        showToast('بارگذاری یکی از لایه‌ها انجام نشد؛ تصویر آن را در مدیریت محصول بررسی کن.');
                    };
                    enteringImages.push(image);
                }

                image.style.zIndex = String(Math.max(1, Math.min(100, Number(layer.z_index) || 1)));
                fragment.append(image);
            });

            // Replace children once to enforce the server-supplied layer order.
            // Unmatched old nodes are dropped; matched nodes keep their loaded image.
            ui.layers.replaceChildren(fragment);
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                window.requestAnimationFrame(() => {
                    enteringImages.forEach((image) => image.classList.remove('is-entering'));
                });
            } else {
                enteringImages.forEach((image) => image.classList.remove('is-entering'));
            }
            ui.stageEmpty.hidden = layers.length > 0;
            const validIds = new Set(layers.map((layer) => Number(layer.variant_id)));
            Object.entries(state.selected).forEach(([slot, id]) => {
                if (!validIds.has(Number(id))) delete state.selected[slot];
            });
            renderSlots();
            renderSummary(Number(payload.data.summary && payload.data.summary.total_price));
            renderShopCardSelections();
            if (payload.data.summary && payload.data.summary.all_in_stock === false) {
                ui.inventoryNote.textContent = 'یک یا چند انتخاب فعلاً موجود نیست؛ پیش از خرید موجودی نهایی بررسی می‌شود.';
            }
        } catch (error) {
            if (error.name !== 'AbortError' && version === state.requestVersion) {
                showToast(error.message || 'ساخت پیش‌نمایش انجام نشد.');
            }
        } finally {
            if (version === state.requestVersion) ui.stageLoading.hidden = true;
        }
    }

    async function loadBootstrap() {
        if (state.bootstrapController) state.bootstrapController.abort();
        const controller = new AbortController();
        state.bootstrapController = controller;
        const version = ++state.bootstrapVersion;

        try {
            renderAudienceButtons();
        ui.catalogNote.textContent = 'در حال آماده‌سازی مانکن…';
        const url = endpoint(root.dataset.bootstrapUrl, {
            audience: state.audience,
            variant_id: state.requestedVariantId,
            mannequin_id: state.mannequin?.id,
        });

        let payload;
        try {
            payload = await fetchJson(url, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            });
        } catch (error) {
            if (error.name === 'AbortError' || version !== state.bootstrapVersion) return;
            throw error;
        }
        if (version !== state.bootstrapVersion) return;

        const data = payload.data || {};
        state.mannequins = Array.isArray(data.mannequins) ? data.mannequins : [];
        const initial = data.initial_selection;
        const targetMannequin = initial
            ? state.mannequins.find((item) => Number(item.id) === Number(initial.mannequin_id))
            : null;

        if (initial && targetMannequin
            && Number(initial.variant_id) === Number(state.requestedVariantId)
            && Object.prototype.hasOwnProperty.call(labels, initial.slot)) {
            if (state.mannequin && Number(state.mannequin.id) !== Number(targetMannequin.id)) {
                state.selected = {};
                state.itemsByVariant.clear();
            }
            state.mannequin = targetMannequin;
            state.slot = initial.slot;
            state.pendingInitialVariantId = Number(initial.variant_id);
            state.initialVariantHasLayer = true;
        } else {
            state.pendingInitialVariantId = null;
            state.initialVariantHasLayer = false;
            if (state.currentProductVariantId && state.currentProductSlot
                && Number(state.selected[state.currentProductSlot]) === state.currentProductVariantId) {
                delete state.selected[state.currentProductSlot];
            }
            state.currentProductVariantId = null;
            state.currentProductSlot = null;

            if (!state.mannequin || !state.mannequins.some((item) => Number(item.id) === Number(state.mannequin.id))) {
                state.selected = {};
                state.itemsByVariant.clear();
                state.mannequin = state.mannequins[0] || null;
            }
        }

        const activeMannequinId = Number(state.mannequin && state.mannequin.id) || 0;
        if (activeMannequinId > 0 && Number(state.defaultMannequinId) !== activeMannequinId) {
            // Default layers are an initial outfit only. Once a shopper starts
            // changing pieces, subsequent selections must not re-apply defaults.
            state.selected = {};
            state.itemsByVariant.clear();
            const defaults = data.default_selections && typeof data.default_selections === 'object'
                ? data.default_selections
                : {};
            Object.entries(defaults).forEach(([slot, item]) => {
                const variantId = Number(item && item.variant_id) || null;
                if (!Object.prototype.hasOwnProperty.call(labels, slot) || !variantId || !item.layer_url) return;
                state.selected[slot] = variantId;
                state.itemsByVariant.set(variantId, { ...item, slot, variant_id: variantId });
            });
            state.defaultMannequinId = activeMannequinId;
        }

        renderMannequins();
        renderSlots();
        updateMannequinStage();
        if (!state.mannequin) {
            showCatalogEmpty('مانکن فعالی برای این گروه تعریف نشده است.', 'پیکربندی پایهٔ مانکن را اجرا کن.');
            ui.stageEmpty.hidden = false;
            return;
        }
        await loadCatalog(true);
        if (version !== state.bootstrapVersion) return;
        if (state.requestedVariantId && !state.initialVariantHasLayer) {
            ui.catalogNote.textContent = 'این واریانت برای مانکن انتخاب‌شده لایهٔ سازگار ندارد؛ می‌توانی لباس‌های آمادهٔ دیگر را در همین پیش‌نمایش ترکیب کنی.';
        }
        await refreshPreview();
        } finally {
            if (state.bootstrapController === controller) state.bootstrapController = null;
        }
    }

    function setExpanded(expanded) {
        const currentlyExpanded = root.classList.contains('is-expanded');
        if (expanded === currentlyExpanded) return;

        if (expanded) {
            expansionMarker = document.createComment('vto-workspace-position');
            root.parentNode?.insertBefore(expansionMarker, root);
            expansionReturnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            previousDocumentOverflow = document.documentElement.style.overflow;
            document.body.append(root);
            document.body.classList.add('vto-workspace-expanded');
            document.documentElement.style.overflow = 'hidden';
            window.requestAnimationFrame(() => {
                root.classList.add('is-expanded');
                ui.expand?.focus({ preventScroll: true });
            });
            ui.expand?.setAttribute('aria-expanded', 'true');
            if (ui.expand) ui.expand.textContent = 'بازگشت به صفحه ↙';
            ui.expand?.setAttribute('aria-label', 'بازگشت اتاق پرو به صفحه');
        } else {
            root.classList.remove('is-expanded');
            document.body.classList.remove('vto-workspace-expanded');
            document.documentElement.style.overflow = previousDocumentOverflow;
            if (expansionMarker?.parentNode) {
                expansionMarker.parentNode.insertBefore(root, expansionMarker.nextSibling);
                expansionMarker.remove();
            }
            expansionMarker = null;
            ui.expand?.setAttribute('aria-expanded', 'false');
            if (ui.expand) ui.expand.textContent = 'بزرگ‌نمایی اتاق پرو ↗';
            ui.expand?.setAttribute('aria-label', 'بزرگ‌نمایی اتاق پرو');
            const focusTarget = expansionReturnFocus?.isConnected ? expansionReturnFocus : ui.expand;
            focusTarget?.focus?.({ preventScroll: true });
            expansionReturnFocus = null;
        }
    }

    ui.expand?.addEventListener('click', () => setExpanded(!root.classList.contains('is-expanded')));
    ui.close?.addEventListener('click', () => setInlineOpen(false));
    ui.launcher?.addEventListener('click', () => setInlineOpen(!root.classList.contains('is-open')));

    if (inlineMode) {
        root.inert = true;
        root.setAttribute('aria-hidden', 'true');
        ui.launcher?.setAttribute('aria-expanded', 'false');

        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target.closest('[data-vto-select-product]') : null;
            if (!target) return;
            event.preventDefault();
            const drawerAlreadyOpen = root.classList.contains('is-open');
            setInlineOpen(true, false);

            const variantId = Number(target.dataset.vtoVariantId) || null;
            if (!variantId) {
                showToast('برای این محصول واریانت فعالی ثبت نشده است.');
                if (!state.mannequin && !state.bootstrapController) {
                    loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی اتاق پرو انجام نشد.'));
                }
                return;
            }
            if (target.dataset.vtoReady !== '1') {
                showToast('برای پرو این محصول، تصویر لایه‌ای معتبر را از مدیریت محصول ثبت کن.');
                if (!state.mannequin && !state.bootstrapController) {
                    loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی اتاق پرو انجام نشد.'));
                }
                return;
            }

            const alreadySelected = Object.values(state.selected).some((id) => Number(id) === variantId);
            const sameSelectionPending = Number(state.requestedVariantId) === variantId && !alreadySelected;
            if (!alreadySelected && !sameSelectionPending) animateShopSelection(target, drawerAlreadyOpen);

            root.dispatchEvent(new CustomEvent('vto:select-variant', {
                detail: {
                    variantId,
                    audience: target.dataset.vtoAudience,
                    toggle: true,
                    fromCatalog: true,
                },
            }));
        });
    }

    document.addEventListener('keydown', (event) => {
        if (inlineMode && event.key === 'Escape' && root.classList.contains('is-open') && !root.classList.contains('is-expanded')) {
            event.preventDefault();
            setInlineOpen(false);
            return;
        }
        if (!root.classList.contains('is-expanded')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            setExpanded(false);
            return;
        }

        if (event.key === 'Tab') {
            const focusable = Array.from(root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter((element) => !element.hidden && !element.closest('[hidden]'));
            if (!focusable.length) {
                event.preventDefault();
                root.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || !root.contains(document.activeElement))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !root.contains(document.activeElement))) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    root.addEventListener('vto:select-variant', (event) => {
        const detail = event.detail || {};
        const variantId = Number(detail.variantId) || null;
        const requestedAudience = detail.audience;
        const audience = ['women', 'men'].includes(requestedAudience) ? requestedAudience : state.audience;
        const audienceChanged = audience !== state.audience;

        if (inlineMode) setInlineOpen(true);

        const selectedEntry = variantId
            ? Object.entries(state.selected).find(([, selectedId]) => Number(selectedId) === variantId)
            : null;

        // Hanger controls toggle selected pieces; do not swallow deselection as a duplicate.
        if (detail.toggle && selectedEntry && !audienceChanged) {
            removeSelection(selectedEntry[0], variantId);
            return;
        }

        // A second tap while bootstrap is pending cancels that selection, so a late
        // response cannot silently re-add an item the user tried to remove.
        if (detail.toggle && variantId && Number(state.requestedVariantId) === variantId && !selectedEntry && !audienceChanged) {
            state.requestedVariantId = null;
            state.pendingInitialVariantId = null;
            state.currentProductVariantId = null;
            state.currentProductSlot = null;
            invalidatePendingRequests();
            renderShopCardSelections();
            if (state.mannequin) refreshPreview();
            else loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
            return;
        }

        if (variantId === state.requestedVariantId && !audienceChanged) return;

        if (audienceChanged) {
            state.audience = audience;
            state.mannequin = null;
            state.defaultMannequinId = null;
            state.selected = {};
            state.itemsByVariant.clear();
            state.catalog = [];
            ui.layers.replaceChildren();
            ui.stageEmpty.hidden = false;
        } else if (!detail.fromCatalog && state.currentProductVariantId && state.currentProductSlot
            && Number(state.selected[state.currentProductSlot]) === state.currentProductVariantId) {
            // The product detail selector owns one changing slot. Shop hanger clicks
            // resolve the new slot first and replace only that slot.
            delete state.selected[state.currentProductSlot];
        }

        state.requestedVariantId = variantId;
        state.pendingInitialVariantId = null;
        state.currentProductVariantId = null;
        state.currentProductSlot = null;
        if (state.catalogController) state.catalogController.abort();
        if (state.previewController) state.previewController.abort();
        if (state.bootstrapController) state.bootstrapController.abort();
        state.requestVersion++;
        state.bootstrapVersion++;
        ui.stageLoading.hidden = true;
        loadBootstrap().catch((error) => {
            if (state.requestedVariantId === variantId) state.requestedVariantId = null;
            showToast(error.message || 'آماده‌سازی مانکن انجام نشد.');
        });
    });

    ui.audienceButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const audience = button.dataset.vtoAudience;
            if (audience === state.audience) return;
            state.audience = audience;
            state.defaultMannequinId = null;
            // Keep the current product variant as a candidate for a later return
            // to its compatible audience; clear layers because they are audience-bound.
            state.pendingInitialVariantId = null;
            state.currentProductVariantId = null;
            state.currentProductSlot = null;
            state.initialVariantHasLayer = false;
            state.mannequin = null;
            state.selected = {};
            state.itemsByVariant.clear();
            state.catalog = [];
            if (state.catalogController) state.catalogController.abort();
            state.catalogController = null;
            if (state.previewController) state.previewController.abort();
            state.previewController = null;
            if (state.bootstrapController) state.bootstrapController.abort();
            state.bootstrapController = null;
            state.bootstrapVersion++;
            state.requestVersion++;
            ui.stageLoading.hidden = true;
            ui.products.replaceChildren();
            ui.layers.replaceChildren();
            ui.stageEmpty.hidden = false;
            renderAudienceButtons();
            renderSlots();
            renderSummary(0);
            loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
        });
    });

    ui.slots.forEach((button) => {
        button.addEventListener('click', () => {
            const slot = button.dataset.vtoSlot;
            if (slot === state.slot) return;
            state.slot = slot;
            renderSlots();
            loadCatalog(true);
        });
    });

    ui.loadMore.addEventListener('click', () => {
        if (!state.nextPage) return;
        state.page = Number(state.nextPage);
        loadCatalog(false);
    });

    ui.clearAll.addEventListener('click', () => {
        state.selected = {};
        state.requestedVariantId = null;
        state.pendingInitialVariantId = null;
        state.currentProductVariantId = null;
        state.currentProductSlot = null;
        state.initialVariantHasLayer = false;
        invalidatePendingRequests();
        renderProductCards();
        renderSlots();
        renderSummary(0);
        renderShopCardSelections();
        if (state.mannequin) refreshPreview();
        else loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
    });

    if (!inlineMode) {
        loadBootstrap().catch((error) => {
            showCatalogEmpty('اتاق پرو آماده نشد.', error.message || 'اتصال را بررسی کن و صفحه را دوباره بارگذاری کن.');
            showToast(error.message || 'آماده‌سازی اتاق پرو انجام نشد.');
        });
    }
})();
