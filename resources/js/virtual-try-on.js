(() => {
    const root = document.querySelector('[data-virtual-try-on]');
    if (!root) return;

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
        total: root.querySelector('[data-vto-total]'),
        inventoryNote: root.querySelector('[data-vto-inventory-note]'),
        clearAll: root.querySelector('[data-vto-clear-all]'),
        toast: root.querySelector('[data-vto-toast]'),
        expand: root.querySelector('[data-vto-expand]'),
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
                state.selected = {};
                state.itemsByVariant.clear();
                ui.layers.replaceChildren();
                renderMannequins();
                updateMannequinStage();
                renderSlots();
                renderSummary(0);
                loadCatalog(true);
                refreshPreview();
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
            showCatalogEmpty('مانکن فعالی برای این گروه تعریف نشده است.', 'ابتدا پیکربندی مانکن را بررسی کن.');
            return;
        }
        if (state.catalogController) state.catalogController.abort();
        const controller = new AbortController();
        state.catalogController = controller;
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
                    ? 'این واریانت هنوز تصویر لایه‌ای اتاق پرو ندارد؛ می‌توانی لباس‌های آمادهٔ دیگر را در همین پیش‌نمایش ترکیب کنی.'
                    : 'هر گزینه فقط همین بخش را تغییر می‌دهد؛ انتخاب‌های دیگر حفظ می‌شوند.';
                renderProductCards();
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                showCatalogEmpty('دریافت محصولات انجام نشد.', error.message || 'اتصال را بررسی کن و دوباره تلاش کن.');
                showToast(error.message || 'دریافت محصولات ناموفق بود.');
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

        ui.selected.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'vto-selected-empty';
            empty.textContent = 'هنوز لباسی انتخاب نشده است؛ اولین بخش را از فهرست انتخاب کن.';
            ui.selected.append(empty);
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
        ui.total.textContent = formatPrice(Number.isFinite(serverTotal) ? serverTotal : estimated);
        ui.inventoryNote.textContent = items.some((item) => Number(item.stock) < 1)
            ? 'حداقل یکی از انتخاب‌ها ناموجود است؛ موجودی نهایی هنگام خرید بررسی می‌شود.'
            : 'قیمت و موجودی هنگام خرید دوباره بررسی می‌شوند.';
        ui.stageEmpty.hidden = items.length > 0;
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
            const fragment = document.createDocumentFragment();
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
                });
                const image = document.createElement('img');
                image.className = 'vto-layer is-entering';
                image.src = layer.layer_url;
                image.alt = '';
                image.decoding = 'async';
                image.loading = 'eager';
                image.style.zIndex = String(Math.max(1, Math.min(100, Number(layer.z_index) || 1)));
                image.onerror = () => {
                    image.remove();
                    if (!ui.layers.querySelector('.vto-layer')) ui.stageEmpty.hidden = false;
                    showToast('بارگذاری یکی از لایه‌ها انجام نشد؛ تصویر آن را در مدیریت محصول بررسی کن.');
                };
                fragment.append(image);
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    window.requestAnimationFrame(() => image.classList.remove('is-entering'));
                } else {
                    image.classList.remove('is-entering');
                }
            });
            ui.layers.replaceChildren(fragment);
            ui.stageEmpty.hidden = layers.length > 0;
            const validIds = new Set(layers.map((layer) => Number(layer.variant_id)));
            Object.entries(state.selected).forEach(([slot, id]) => {
                if (!validIds.has(Number(id))) delete state.selected[slot];
            });
            renderSlots();
            renderSummary(Number(payload.data.summary && payload.data.summary.total_price));
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

        renderAudienceButtons();
        ui.catalogNote.textContent = 'در حال آماده‌سازی مانکن…';
        const url = endpoint(root.dataset.bootstrapUrl, {
            audience: state.audience,
            variant_id: state.requestedVariantId,
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
            ui.catalogNote.textContent = 'این واریانت هنوز تصویر لایه‌ای اتاق پرو ندارد؛ می‌توانی لباس‌های آمادهٔ دیگر را در همین پیش‌نمایش ترکیب کنی.';
        }
        await refreshPreview();
        if (state.bootstrapController === controller) state.bootstrapController = null;
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
            window.requestAnimationFrame(() => root.classList.add('is-expanded'));
            ui.expand?.setAttribute('aria-expanded', 'true');
            if (ui.expand) ui.expand.textContent = 'بازگشت به صفحه محصول ↙';
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
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('is-expanded')) {
            setExpanded(false);
        }
    });

    root.addEventListener('vto:select-variant', (event) => {
        const variantId = Number(event.detail?.variantId) || null;
        const audience = event.detail?.audience;
        if (audience && ['women', 'men'].includes(audience) && audience !== state.audience) {
            state.audience = audience;
            state.mannequin = null;
            state.selected = {};
            state.itemsByVariant.clear();
        } else if (state.currentProductVariantId && state.currentProductSlot
            && Number(state.selected[state.currentProductSlot]) === state.currentProductVariantId) {
            delete state.selected[state.currentProductSlot];
        }

        if (variantId === state.requestedVariantId) return;
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
        loadBootstrap().catch((error) => showToast(error.message || 'آماده‌سازی مانکن انجام نشد.'));
    });

    ui.audienceButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const audience = button.dataset.vtoAudience;
            if (audience === state.audience) return;
            state.audience = audience;
            state.requestedVariantId = null;
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
        renderProductCards();
        renderSlots();
        renderSummary(0);
        refreshPreview();
    });

    loadBootstrap().catch((error) => {
        showCatalogEmpty('اتاق پرو آماده نشد.', error.message || 'اتصال را بررسی کن و صفحه را دوباره بارگذاری کن.');
        showToast(error.message || 'آماده‌سازی اتاق پرو انجام نشد.');
    });
})();
