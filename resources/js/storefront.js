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
    const maxPicks = 5;
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

    function updatePreviewImage(product) {
        if (!fittingImage || !fittingPlaceholder) return;

        if (!product?.image) {
            fittingImage.removeAttribute('src');
            fittingImage.hidden = true;
            fittingPlaceholder.hidden = false;
            fittingPreview?.classList.add('is-placeholder');
            return;
        }

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
            fittingAction.querySelector('span:first-child').textContent = picks.has(product.id) ? 'Remove from your edit' : 'Add to your edit';
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

                const name = document.createElement('span');
                name.className = 'fitting-pick__name';
                name.textContent = product.name;

                const remove = document.createElement('span');
                remove.className = 'fitting-pick__remove';
                remove.setAttribute('aria-hidden', 'true');
                remove.textContent = '×';

                button.append(number, name, remove);
                fittingPicks.append(button);
            });
        }

        if (fittingLookNumber) fittingLookNumber.textContent = String(Math.max(1, picks.size)).padStart(2, '0');
        if (fittingAction) {
            fittingAction.disabled = !currentSelection;
            fittingAction.querySelector('span:first-child').textContent = currentSelection
                ? (picks.has(currentSelection.id) ? 'Remove from your edit' : 'Add to your edit')
                : 'Choose a piece first';
        }
    }

    filterToggle?.addEventListener('click', () => setFiltersOpen(!sidebar?.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setFiltersOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setFiltersOpen(false);
    });

    root.querySelectorAll('[data-fit-product]').forEach((button) => {
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => {
            selectProduct(readProductData(button), button);
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
                selectProduct(readProductData(button), button);
                root.querySelector('[data-fitting-room]')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                if (window.matchMedia('(max-width: 720px)').matches) setFiltersOpen(true);
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

        if (picks.has(currentSelection.id)) {
            picks.delete(currentSelection.id);
            showToast('Removed from your edit.');
            if (picks.size > 0) {
                const next = [...picks.values()][picks.size - 1];
                selectProduct(next);
            } else {
                currentSelection = null;
                fittingName && (fittingName.textContent = 'Choose a piece');
                fittingDescription && (fittingDescription.textContent = 'Select the fit icon on any product to preview it here.');
                fittingPrice && (fittingPrice.textContent = 'Waiting for your edit');
                updatePreviewImage(null);
                root.querySelectorAll('[data-fit-product]').forEach((button) => {
                    button.classList.remove('is-active');
                    button.setAttribute('aria-pressed', 'false');
                });
            }
        } else {
            if (picks.size >= maxPicks) {
                showToast('Your edit is full. Remove a piece before adding another.');
                return;
            }
            picks.set(currentSelection.id, currentSelection);
            showToast('Added to your edit.');
        }

        renderPicks();
        if (currentSelection) selectProduct(currentSelection);
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
