(() => {
    const root = document.querySelector('[data-storefront]');
    if (!root) return;

    const sidebar = root.querySelector('[data-store-sidebar]');
    const filterToggle = root.querySelector('[data-store-filter-toggle]');
    const backdrop = root.querySelector('[data-store-filter-backdrop]');
    const toast = root.querySelector('[data-store-toast]');
    const fittingImage = root.querySelector('[data-fitting-image]');
    const fittingPlaceholder = root.querySelector('[data-fitting-placeholder]');
    const fittingName = root.querySelector('[data-fitting-name]');
    const fittingDescription = root.querySelector('[data-fitting-description]');
    const fittingPrice = root.querySelector('[data-fitting-price]');
    const fittingLookNumber = root.querySelector('[data-fitting-look-number]');
    const fittingPicks = root.querySelector('[data-fitting-picks]');
    const fittingPickCount = root.querySelector('[data-fitting-pick-count]');
    const fittingAction = root.querySelector('[data-fitting-action]');
    const fittingPreview = root.querySelector('[data-fitting-preview]');
    const fittingRoom = root.querySelector('[data-fitting-room]');
    const fittingClose = root.querySelector('[data-fitting-close]');
    const fittingReopen = root.querySelector('[data-fitting-reopen]');
    const maxPicks = 5;
    const cartStorageKey = 'vision.storefront.cart.v1';
    const picks = new Map();
    let currentSelection = null;
    let toastTimer = null;

    const initialPick = fittingPicks?.querySelector('[data-fitting-pick]');
    if (initialPick) {
        const initial = readProductData(initialPick);
        picks.set(initial.id, initial);
        currentSelection = initial;
    }

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

    function readStoredCart() {
        try {
            const value = JSON.parse(window.localStorage.getItem(cartStorageKey) || '[]');
            return Array.isArray(value) ? value.filter((item) => item && typeof item === 'object' && item.id) : [];
        } catch {
            return [];
        }
    }

    function saveCart(cart) {
        try {
            window.localStorage.setItem(cartStorageKey, JSON.stringify(cart));
            return true;
        } catch {
            return false;
        }
    }

    function updateCartCount(cart = readStoredCart()) {
        const count = cart.reduce((sum, item) => sum + Math.max(0, Number(item.qty) || 0), 0);
        root.querySelectorAll('[data-cart-count]').forEach((element) => {
            element.textContent = String(count);
            element.setAttribute('aria-label', `${count} item${count === 1 ? '' : 's'} in bag`);
        });
        return count;
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

        if (fittingName) fittingName.textContent = product.name;
        if (fittingDescription) fittingDescription.textContent = product.description || 'A considered piece from the latest edit.';
        if (fittingPrice) fittingPrice.textContent = product.price || 'Price unavailable';
        if (fittingLookNumber) fittingLookNumber.textContent = String(Math.max(1, picks.size)).padStart(2, '0');
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

            if (window.matchMedia('(max-width: 720px)').matches) {
                setFiltersOpen(true);
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
                fittingName && (fittingName.textContent = 'Choose a piece');
                fittingDescription && (fittingDescription.textContent = 'Select the fit icon on any product to preview it here.');
                fittingPrice && (fittingPrice.textContent = 'Waiting for your edit');
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
        button.addEventListener('click', () => {
            const id = String(button.dataset.cartAdd || '');
            if (!id) return;

            const cart = readStoredCart();
            const existing = cart.find((item) => String(item.id) === id);
            if (existing) {
                existing.qty = Math.max(1, Number(existing.qty) || 1) + 1;
            } else {
                cart.push({
                    id,
                    name: button.dataset.cartName || 'Selected product',
                    price: button.dataset.cartPrice || 'Price unavailable',
                    qty: 1
                });
            }

            saveCart(cart);
            const count = updateCartCount(cart);
            showToast(`Added to your bag · ${count} item${count === 1 ? '' : 's'}`);
        });
    });

    root.querySelector('[data-cart-toggle]')?.addEventListener('click', () => {
        const cart = readStoredCart();
        const count = updateCartCount(cart);
        if (count === 0) {
            showToast('Your bag is empty.');
            return;
        }

        const names = cart.slice(0, 2).map((item) => item.name).join(', ');
        const more = cart.length > 2 ? ` +${cart.length - 2} more` : '';
        showToast(`Your bag: ${names}${more}. Checkout is not connected yet.`);
    });

    root.querySelector('[data-store-language]')?.addEventListener('click', () => {
        showToast('English is the only storefront language currently enabled.');
    });

    root.querySelector('[data-store-settings]')?.addEventListener('click', () => {
        const compact = root.classList.toggle('store-shell--compact');
        showToast(compact ? 'Compact product view enabled.' : 'Standard product view enabled.');
    });

    updateCartCount();

    root.querySelector('[data-fitting-reset]')?.addEventListener('click', () => {
        picks.clear();
        currentSelection = null;
        fittingName && (fittingName.textContent = 'Choose a piece');
        fittingDescription && (fittingDescription.textContent = 'Select the fit icon on any product to preview it here.');
        fittingPrice && (fittingPrice.textContent = 'Waiting for your edit');
        updatePreviewImage(null);
        root.querySelectorAll('[data-fit-product]').forEach((button) => {
            button.classList.remove('is-active');
            button.setAttribute('aria-pressed', 'false');
        });
        renderPicks();
        showToast('Fitting room reset.');
    });

    if (currentSelection) {
        selectProduct(currentSelection);
        renderPicks();
    }
})();
