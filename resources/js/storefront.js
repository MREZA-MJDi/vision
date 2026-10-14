(() => {
    const root = document.querySelector('[data-storefront]');
    if (!root) return;

    const currencySymbol = root.dataset.currencySymbol || 'تومان';
    const formatMoney = (value) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value) || 0) + ' ' + currencySymbol;

    const sidebar = root.querySelector('[data-store-sidebar]');
    const filterToggle = root.querySelector('[data-store-filter-toggle]');
    const backdrop = root.querySelector('[data-store-filter-backdrop]');
    const toast = root.querySelector('[data-store-toast]');
    const fittingImage = root.querySelector('[data-fitting-image]');
    const fittingPlaceholder = root.querySelector('[data-fitting-placeholder]');
    const fittingLookNumber = root.querySelector('[data-fitting-look-number]');
    const fittingCanvasCount = root.querySelector('[data-fitting-canvas-count]');
    const fittingPicks = root.querySelector('[data-fitting-picks]');
    const fittingPickCount = root.querySelector('[data-fitting-pick-count]');
    const fittingAction = root.querySelector('[data-fitting-action]');
    const fittingPreview = root.querySelector('[data-fitting-preview]');
    const fittingRoom = root.querySelector('[data-fitting-room]');
    const fittingClose = root.querySelector('[data-fitting-close]');
    const fittingReopen = root.querySelector('[data-fitting-reopen]');
    const maxPicks = 5;
    const picks = new Map();
    let currentSelection = null;
    let toastTimer = null;

    function readProductData(element) {
        return {
            id: String(element.dataset.productId || ''),
            name: element.dataset.productName || 'Selected piece',
            price: element.dataset.productPrice || 'Price unavailable',
            image: element.dataset.productImage || '',
            description: element.dataset.productDescription || ''
        };
    }

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('is-visible');
        if (toastTimer) window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2500);
    }

    function setFiltersOpen(open) {
        if (!sidebar || !filterToggle || !backdrop) return;
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        filterToggle.setAttribute('aria-expanded', String(open));
        filterToggle.setAttribute('aria-label', open ? 'Close filters' : 'Open filters');
        document.documentElement.style.overflow = open ? 'hidden' : '';
    }

    function setFittingRoomOpen(open) {
        if (fittingRoom) fittingRoom.hidden = !open;
        if (fittingReopen) fittingReopen.hidden = open;
        if (open) fittingRoom?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function updateCartCount(count = 0) {
        const safeCount = Math.max(0, Number(count) || 0);
        root.querySelectorAll('[data-cart-count]').forEach((element) => {
            element.textContent = String(safeCount);
            element.setAttribute('aria-label', safeCount + ' item' + (safeCount === 1 ? '' : 's') + ' in bag');
        });
        return safeCount;
    }

    async function requestCart(url, method = 'GET', body = null) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
        };
        if (body !== null) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            body: body === null ? undefined : JSON.stringify(body)
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;
            throw new Error(validationMessage || payload.message || 'Could not update your bag. Please try again.');
        }

        return payload;
    }

    async function syncCartCount() {
        const url = root.dataset.cartSummaryUrl;
        if (!url) return;
        try {
            const response = await requestCart(url);
            updateCartCount(response.count);
        } catch {
            // Keep the storefront usable even if the count endpoint is temporarily unavailable.
        }
    }

    function updatePreviewImage(product) {
        if (!fittingImage || !fittingPlaceholder) return;

        if (!product?.image) {
            fittingImage.removeAttribute('src');
            fittingImage.hidden = true;
            fittingPlaceholder.hidden = false;
            fittingPreview?.classList.add('is-placeholder');
            const caption = fittingPlaceholder.querySelector('span:last-child');
            if (caption) caption.textContent = product?.name ? product.name.toUpperCase() + ' · IMAGE UNAVAILABLE' : 'SELECT A PIECE TO START YOUR PREVIEW';
            return;
        }

        const caption = fittingPlaceholder.querySelector('span:last-child');
        if (caption) caption.innerHTML = 'SELECT A PIECE<br>TO START YOUR PREVIEW';

        fittingImage.hidden = false;
        fittingPlaceholder.hidden = true;
        fittingPreview?.classList.remove('is-placeholder');
        fittingImage.onerror = () => {
            fittingImage.hidden = true;
            fittingPlaceholder.hidden = false;
            fittingPreview?.classList.add('is-placeholder');
        };
        fittingImage.onload = () => {
            fittingImage.hidden = false;
            fittingPlaceholder.hidden = true;
            fittingPreview?.classList.remove('is-placeholder');
        };
        fittingImage.src = product.image;
        fittingImage.alt = product.name;
    }

    function selectProduct(product, sourceButton = null) {
        if (!product || !product.id) return;
        currentSelection = product;
        updatePreviewImage(product);

        if (fittingLookNumber) fittingLookNumber.textContent = picks.size ? String(picks.size).padStart(2, '0') : '—';
        if (fittingCanvasCount) fittingCanvasCount.textContent = String(picks.size).padStart(2, '0') + ' / 05';
        if (fittingAction) {
            fittingAction.disabled = false;
            fittingAction.querySelector('span:first-child').textContent = 'Add another piece';
        }

        root.querySelectorAll('[data-fit-product]').forEach((button) => {
            const active = button.dataset.productId === product.id;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        root.querySelectorAll('[data-fitting-pick]').forEach((button) => {
            button.classList.toggle('is-current', button.dataset.productId === product.id);
        });

        if (sourceButton) sourceButton.blur?.();
    }

    function renderPicks() {
        if (!fittingPicks || !fittingPickCount) return;
        fittingPicks.replaceChildren();
        fittingPickCount.textContent = String(picks.size);

        if (picks.size === 0) {
            const empty = document.createElement('p');
            empty.className = 'fitting-picks__empty';
            empty.dataset.fittingPicksEmpty = '';
            empty.textContent = 'Your shortlist is waiting.';
            fittingPicks.append(empty);
        } else {
            let index = 0;
            picks.forEach((product) => {
                index += 1;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'fitting-pick';
                button.classList.toggle('is-current', currentSelection?.id === product.id);
                button.dataset.fittingPick = '';
                button.dataset.productId = product.id;
                button.dataset.productName = product.name;
                button.dataset.productPrice = product.price;
                button.dataset.productImage = product.image;
                button.dataset.productDescription = product.description;
                button.setAttribute('aria-label', 'Select ' + product.name + ' or remove it from your edit');

                const number = document.createElement('span');
                number.className = 'fitting-pick__dot';
                number.textContent = String(index).padStart(2, '0');

                const thumb = document.createElement('span');
                thumb.className = 'fitting-pick__thumb';
                if (product.image) {
                    const image = document.createElement('img');
                    image.src = product.image;
                    image.alt = '';
                    image.loading = 'lazy';
                    thumb.append(image);
                } else {
                    thumb.classList.add('fitting-pick__thumb--empty');
                    thumb.textContent = 'V';
                }

                const name = document.createElement('span');
                name.className = 'fitting-pick__name';
                name.textContent = product.name;

                const remove = document.createElement('span');
                remove.className = 'fitting-pick__remove';
                remove.setAttribute('aria-hidden', 'true');
                remove.textContent = '×';

                button.append(number, thumb, name, remove);
                fittingPicks.append(button);
            });
        }

        if (fittingLookNumber) fittingLookNumber.textContent = String(Math.max(1, picks.size)).padStart(2, '0');
        if (fittingCanvasCount) fittingCanvasCount.textContent = String(picks.size).padStart(2, '0') + ' / 05';
        if (fittingAction) {
            fittingAction.disabled = !currentSelection;
            fittingAction.querySelector('span:first-child').textContent = 'Add another piece';
        }
    }

    filterToggle?.addEventListener('click', () => setFiltersOpen(!sidebar?.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setFiltersOpen(false));
    fittingClose?.addEventListener('click', () => setFittingRoomOpen(false));
    fittingReopen?.addEventListener('click', () => setFittingRoomOpen(true));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setFiltersOpen(false);
        }
    });

    root.querySelectorAll('[data-fit-product]').forEach((button) => {
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => {
            const product = readProductData(button);
            const isMobile = window.matchMedia('(max-width: 720px)').matches;
            if (isMobile) setFiltersOpen(true);
            setFittingRoomOpen(true);

            if (!picks.has(product.id)) {
                if (picks.size >= maxPicks) {
                    selectProduct(product, button);
                    showToast('Your edit is full. Remove a piece before adding another.');
                } else {
                    picks.set(product.id, product);
                    selectProduct(product, button);
                    renderPicks();
                    showToast('Piece added to your fitting room.');
                }
            } else {
                selectProduct(product, button);
                renderPicks();
            }

        });
    });

    root.querySelectorAll('[data-card-preview]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const card = link.closest('[data-product-card]');
            const button = card?.querySelector('[data-fit-product]');
            if (button) {
                button.click();
                root.querySelector('[data-fitting-room]')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    const sortSelect = root.querySelector('[data-store-sort]');
    sortSelect?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortSelect.value);
        url.searchParams.delete('page');
        url.hash = 'catalog';
        window.location.assign(url.toString());
    });

    root.querySelectorAll('[data-wishlist]').forEach((button) => {
        const key = 'vision.storefront.wishlist.v1';
        let saved = [];
        try {
            saved = JSON.parse(window.localStorage.getItem(key) || '[]');
            if (!Array.isArray(saved)) saved = [];
        } catch {
            saved = [];
        }
        const id = String(button.dataset.wishlist);
        const isSaved = saved.includes(id);
        button.setAttribute('aria-pressed', String(isSaved));
        button.title = isSaved ? 'Remove from saved pieces' : 'Save to wishlist';

        button.addEventListener('click', () => {
            let current = [];
            try {
                current = JSON.parse(window.localStorage.getItem(key) || '[]');
                if (!Array.isArray(current)) current = [];
            } catch {
                current = [];
            }

            const nowSaved = !current.includes(id);
            current = nowSaved ? [...current, id] : current.filter((value) => value !== id);
            try {
                window.localStorage.setItem(key, JSON.stringify(current));
            } catch {
                // The heart still responds if browser storage is unavailable.
            }
            button.setAttribute('aria-pressed', String(nowSaved));
            button.title = nowSaved ? 'Remove from saved pieces' : 'Save to wishlist';
            showToast(nowSaved ? 'Saved to your shortlist on this device.' : 'Removed from your shortlist.');
        });
    });

    fittingAction?.addEventListener('click', () => {
        if (!currentSelection) return;
        if (window.matchMedia('(max-width: 720px)').matches) {
            setFiltersOpen(false);
        }
        root.querySelector('[data-product-grid]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const nextProductButton = [...root.querySelectorAll('[data-fit-product]')]
            .find((button) => !picks.has(String(button.dataset.productId)));
        nextProductButton?.focus({ preventScroll: true });
        showToast(picks.size >= maxPicks
            ? 'Your edit is full. Remove a piece before adding another.'
            : 'Choose another piece to add it to your look.');
    });

    fittingPicks?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-fitting-pick]');
        if (!button) return;
        const product = readProductData(button);
        if (event.target.closest('.fitting-pick__remove')) {
            picks.delete(product.id);
            if (currentSelection?.id === product.id) {
                currentSelection = picks.size ? [...picks.values()][picks.size - 1] : null;
            }
            if (currentSelection) {
                selectProduct(currentSelection);
            } else {
                updatePreviewImage(null);
                root.querySelectorAll('[data-fit-product]').forEach((fitButton) => {
                    fitButton.classList.remove('is-active');
                    fitButton.setAttribute('aria-pressed', 'false');
                });
            }
            renderPicks();
            showToast('Updated your edit.');
            return;
        }

        selectProduct(product, button);
    });


    root.querySelectorAll('[data-cart-add]').forEach((button) => {
        button.addEventListener('click', async () => {
            const variantId = Number(button.dataset.cartAdd || 0);
            const addUrl = root.dataset.cartAddUrl;
            if (!Number.isInteger(variantId) || variantId < 1 || !addUrl) {
                showToast('This piece is not available to add right now.');
                return;
            }

            button.disabled = true;
            try {
                const result = await requestCart(addUrl, 'POST', {
                    variant_id: variantId,
                    quantity: 1
                });
                updateCartCount(result.count);
                showToast('Added to your bag · ' + result.count + ' item' + (result.count === 1 ? '' : 's'));
            } catch (error) {
                showToast(error.message || 'Could not add this piece to your bag.');
            } finally {
                button.disabled = false;
            }
        });
    });

    root.querySelector('[data-cart-toggle]')?.addEventListener('click', () => {
        const url = root.dataset.cartUrl;
        if (url) window.location.assign(url);
    });

    root.querySelector('[data-store-account]')?.addEventListener('click', () => {
        const url = root.dataset.accountUrl;
        if (url) window.location.assign(url);
    });

    root.querySelectorAll('[data-variant-select]').forEach((select) => {
        const priceDisplay = root.querySelector('[data-variant-price]');
        const stockDisplay = root.querySelector('[data-variant-stock]');
        const mainImage = root.querySelector('[data-product-main-image]');
        const imagePlaceholder = root.querySelector('[data-product-image-placeholder]');
        const mainStage = root.querySelector('[data-product-main-stage]');
        const colorButtons = Array.from(root.querySelectorAll('[data-variant-color-pick]'));
        const quantityInput = root.querySelector('[data-variant-quantity]');
        const addButton = root.querySelector('[data-product-add]');
        const normalizeColor = (value) => String(value || '').trim().toLocaleLowerCase();
        const currency = root.dataset.currencySymbol || 'تومان';
        const formatVariantMoney = (value) => Number(value || 0).toLocaleString('en-US', {
            maximumFractionDigits: 0
        }) + ' ' + currency;

        const refreshVariant = () => {
            const option = select.selectedOptions?.[0];
            if (!option) {
                if (addButton) addButton.disabled = true;
                if (stockDisplay) stockDisplay.textContent = 'Currently unavailable';
                if (mainImage) {
                    mainImage.removeAttribute('src');
                    mainImage.hidden = true;
                }
                if (imagePlaceholder) imagePlaceholder.hidden = false;
                return;
            }

            const price = Number(option.dataset.price);
            const originalPrice = Number(option.dataset.originalPrice);
            const onSale = option.dataset.sale === '1';
            const stock = Math.max(0, Number(option.dataset.stock) || 0);

            if (priceDisplay && Number.isFinite(price)) {
                priceDisplay.replaceChildren();
                if (onSale && Number.isFinite(originalPrice)) {
                    const original = document.createElement('del');
                    original.textContent = formatVariantMoney(originalPrice);
                    priceDisplay.append(original);
                }
                const current = document.createElement('strong');
                current.textContent = formatVariantMoney(price);
                priceDisplay.append(current);
            }

            // Selected-variant media takes priority. If it has no image, use
            // only the product gallery fallback; never another variant's image.
            const imageUrl = option.dataset.image
                || mainStage?.dataset.baseImage
                || mainImage?.dataset.fallbackSrc
                || '';

            if (mainImage) {
                if (imageUrl) {
                    mainImage.src = imageUrl;
                    mainImage.alt = option.dataset.imageAlt
                        || select.closest('.store-product-detail__info')?.querySelector('h1')?.textContent?.trim()
                        || 'Product image';
                    mainImage.hidden = false;
                } else {
                    mainImage.removeAttribute('src');
                    mainImage.hidden = true;
                }
            }
            if (imagePlaceholder) imagePlaceholder.hidden = Boolean(imageUrl);

            const selectedColor = normalizeColor(option.dataset.color);
            colorButtons.forEach((button) => {
                const selected = selectedColor !== ''
                    && normalizeColor(button.dataset.variantColorPick) === selectedColor;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            root.querySelectorAll('[data-variant-pick]').forEach((button) => {
                if (button.hasAttribute('data-variant-color-pick')) return;
                const selected = String(button.dataset.variantPick) === String(option.value);
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            root.querySelectorAll('[data-gallery-image]').forEach((button) => {
                button.classList.toggle('is-selected', button.dataset.galleryImage === imageUrl);
            });

            if (stockDisplay) {
                stockDisplay.textContent = stock > 0
                    ? stock.toLocaleString('en-US') + ' available'
                    : 'Currently unavailable';
            }
            if (quantityInput) {
                quantityInput.max = String(Math.min(100, Math.max(1, stock)));
                if (stock > 0 && Number(quantityInput.value) > stock) {
                    quantityInput.value = String(stock);
                }
            }
            if (addButton) addButton.disabled = stock < 1;
        };

        colorButtons.forEach((button) => button.addEventListener('click', () => {
            const requestedColor = normalizeColor(button.dataset.variantColorPick);
            const options = Array.from(select.options).filter((option) =>
                normalizeColor(option.dataset.color) === requestedColor
            );
            const currentSize = normalizeColor(select.selectedOptions?.[0]?.dataset.size);
            const nextOption = options.find((option) => !option.disabled && normalizeColor(option.dataset.size) === currentSize)
                || options.find((option) => !option.disabled);

            if (!nextOption) return;
            select.value = nextOption.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }));

        select.addEventListener('change', refreshVariant);
        root.querySelectorAll('[data-variant-pick]').forEach((button) => {
            button.addEventListener('click', () => {
                const matchingOption = Array.from(select.options).find((option) => option.value === button.dataset.variantPick);
                if (!matchingOption || matchingOption.disabled) return;
                select.value = matchingOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        refreshVariant();
    });

    root.querySelectorAll('[data-gallery-image]').forEach((button) => {
                button.classList.toggle('is-selected', button.dataset.galleryImage === imageUrl);
            });

            if (stockDisplay) {
                stockDisplay.textContent = stock > 0
                    ? stock.toLocaleString('en-US') + ' available'
                    : 'Currently unavailable';
            }
            if (quantityInput) {
                quantityInput.max = String(Math.min(100, Math.max(1, stock)));
                if (stock > 0 && Number(quantityInput.value) > stock) {
                    quantityInput.value = String(stock);
                }
            }
            if (addButton) addButton.disabled = stock < 1;
        };

        colorButtons.forEach((button) => button.addEventListener('click', () => {
            const requestedColor = normalizeColor(button.dataset.variantColorPick);
            const options = Array.from(select.options).filter((option) =>
                normalizeColor(option.dataset.color) === requestedColor
            );
            const currentSize = normalizeColor(select.selectedOptions?.[0]?.dataset.size);
            const nextOption = options.find((option) => !option.disabled && normalizeColor(option.dataset.size) === currentSize)
                || options.find((option) => !option.disabled);

            if (!nextOption) return;
            select.value = nextOption.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }));

        select.addEventListener('change', refreshVariant);
        root.querySelectorAll('[data-variant-pick]').forEach((button) => {
            button.addEventListener('click', () => {
                const matchingOption = Array.from(select.options).find((option) => option.value === button.dataset.variantPick);
                if (!matchingOption) return;
                select.value = matchingOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        refreshVariant();
        syncColorButtons();
    });

    root.querySelectorAll('[data-gallery-image]').forEach((button) => {
        button.addEventListener('click', () => {
            const stage = root.querySelector('[data-product-main-stage]');
            const image = root.querySelector('[data-product-main-image]');
            const placeholder = root.querySelector('[data-product-image-placeholder]');
            const imageUrl = button.dataset.galleryImage || '';
            if (!image || !imageUrl) return;
            image.src = imageUrl;
            image.alt = button.dataset.galleryAlt || 'Product image';
            image.hidden = false;
            if (placeholder) placeholder.hidden = true;
            root.querySelectorAll('[data-gallery-image]').forEach((thumb) => {
                thumb.classList.toggle('is-selected', thumb === button);
            });
            if (stage) stage.dataset.manualImageSelection = 'true';
        });
    });

    root.querySelector('[data-store-language]')?.addEventListener('click', () => {
        showToast('English is the only storefront language currently enabled.');
    });

    root.querySelector('[data-store-settings]')?.addEventListener('click', () => {
        const compact = root.classList.toggle('store-shell--compact');
        showToast(compact ? 'Compact product view enabled.' : 'Standard product view enabled.');
    });

    updateCartCount(0);
    syncCartCount();

    root.querySelector('[data-fitting-reset]')?.addEventListener('click', () => {
        picks.clear();
        currentSelection = null;
        updatePreviewImage(null);
        root.querySelectorAll('[data-fit-product]').forEach((button) => {
            button.classList.remove('is-active');
            button.setAttribute('aria-pressed', 'false');
        });
        renderPicks();
        showToast('Fitting room reset.');
    });

})();
