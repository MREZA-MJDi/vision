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
            name: element.dataset.productName || 'محصول انتخاب‌شده',
            price: element.dataset.productPrice || 'قیمت نامشخص',
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
        filterToggle.setAttribute('aria-label', open ? 'بستن فیلترها' : 'باز کردن فیلترها');
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
            element.setAttribute('aria-label', 'سبد خرید، ' + safeCount + ' محصول');
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
            throw new Error(validationMessage || payload.message || 'به‌روزرسانی سبد خرید انجام نشد؛ دوباره تلاش کن.');
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
            if (caption) caption.textContent = product?.name ? product.name + ' · تصویر در دسترس نیست' : 'برای شروع یک محصول انتخاب کن.';
            return;
        }

        const caption = fittingPlaceholder.querySelector('span:last-child');
        if (caption) caption.textContent = 'برای شروع یک محصول انتخاب کن.';

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
            fittingAction.querySelector('span:first-child').textContent = 'افزودن محصول دیگر';
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
            empty.textContent = 'هنوز محصولی انتخاب نشده است.';
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
                button.setAttribute('aria-label', 'انتخاب ' + product.name + ' یا حذف آن از فهرست');

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
            fittingAction.querySelector('span:first-child').textContent = 'افزودن محصول دیگر';
        }
    }

    filterToggle?.addEventListener('click', () => setFiltersOpen(!sidebar?.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setFiltersOpen(false));
    fittingClose?.addEventListener('click', () => {
        setFittingRoomOpen(false);
        if (window.matchMedia('(max-width: 720px)').matches) setFiltersOpen(false);
    });
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
                    showToast('حداکثر ۵ محصول می‌توانی انتخاب کنی؛ ابتدا یکی را حذف کن.');
                    return;
                } else {
                    picks.set(product.id, product);
                    selectProduct(product, button);
                    renderPicks();
                    showToast('محصول به فهرست پیش‌نمایش اضافه شد.');
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
        button.title = isSaved ? 'حذف از فهرست ذخیره‌شده' : 'ذخیرهٔ محصول';

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
            button.title = nowSaved ? 'حذف از فهرست ذخیره‌شده' : 'ذخیرهٔ محصول';
            showToast(nowSaved ? 'در فهرست ذخیره‌شدهٔ همین دستگاه ثبت شد.' : 'از فهرست ذخیره‌شده حذف شد.');
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
            ? 'حداکثر ۵ محصول می‌توانی انتخاب کنی؛ ابتدا یکی را حذف کن.'
            : 'برای ادامه، محصول دیگری از فهرست انتخاب کن.');
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
            showToast('فهرست پیش‌نمایش به‌روز شد.');
            return;
        }

        selectProduct(product, button);
    });


    root.querySelectorAll('[data-cart-add]').forEach((button) => {
        button.addEventListener('click', async () => {
            const variantId = Number(button.dataset.cartAdd || 0);
            const addUrl = root.dataset.cartAddUrl;
            if (!Number.isInteger(variantId) || variantId < 1 || !addUrl) {
                showToast('این محصول در حال حاضر برای افزودن به سبد در دسترس نیست.');
                return;
            }

            button.disabled = true;
            try {
                const result = await requestCart(addUrl, 'POST', {
                    variant_id: variantId,
                    quantity: 1
                });
                updateCartCount(result.count);
                showToast('به سبد خرید اضافه شد · تعداد محصولات: ' + result.count);
            } catch (error) {
                showToast(error.message || 'افزودن محصول به سبد خرید انجام نشد.');
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
                if (stockDisplay) stockDisplay.textContent = 'فعلاً موجود نیست';
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

            // Prefer the selected variant's own photo; otherwise use the
            // product gallery fallback, never a different variant's photo.
            const imageUrl = option.dataset.image
                || mainStage?.dataset.baseImage
                || mainImage?.dataset.fallbackSrc
                || '';

            if (mainImage) {
                if (imageUrl) {
                    mainImage.src = imageUrl;
                    mainImage.alt = option.dataset.imageAlt
                        || select.closest('.store-product-detail__info')?.querySelector('h1')?.textContent?.trim()
                        || 'تصویر محصول';
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
                    ? stock.toLocaleString('fa-IR') + ' عدد موجود'
                    : 'فعلاً موجود نیست';
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
            const nextOption = options.find((option) =>
                !option.disabled && normalizeColor(option.dataset.size) === currentSize
            ) || options.find((option) => !option.disabled);

            if (!nextOption) return;
            select.value = nextOption.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }));

        select.addEventListener('change', refreshVariant);
        root.querySelectorAll('[data-variant-pick]').forEach((button) => {
            button.addEventListener('click', () => {
                const matchingOption = Array.from(select.options).find((option) =>
                    option.value === button.dataset.variantPick
                );
                if (!matchingOption || matchingOption.disabled) return;
                select.value = matchingOption.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        refreshVariant();
    });

    root.querySelectorAll('[data-gallery-image]').forEach((button) => {
        button.addEventListener('click', () => {
            const stage = root.querySelector('[data-product-main-stage]');
            const image = root.querySelector('[data-product-main-image]');
            const placeholder = root.querySelector('[data-product-image-placeholder]');
            const imageUrl = button.dataset.galleryImage || '';
            if (!image || !imageUrl) return;
            image.src = imageUrl;
            image.alt = button.dataset.galleryAlt || 'تصویر محصول';
            image.hidden = false;
            if (placeholder) placeholder.hidden = true;
            root.querySelectorAll('[data-gallery-image]').forEach((thumb) => {
                thumb.classList.toggle('is-selected', thumb === button);
            });
            if (stage) stage.dataset.manualImageSelection = 'true';
        });
    });


    // The homepage hero is data-backed: switching a color/size updates
    // the real variant image, price, stock and thumbnail without another request.
    const heroSlides = Array.from(root.querySelectorAll('[data-home-hero-slide]'));
    if (heroSlides.length) {
        const hero = root.querySelector('[data-home-hero]');
        const heroImage = root.querySelector('[data-home-hero-image]');
        const heroImageMissing = root.querySelector('[data-home-hero-image-missing]');
        const heroStage = root.querySelector('[data-home-hero-stage]');
        const heroPrice = root.querySelector('[data-home-hero-price]');
        const heroOriginalPrice = root.querySelector('[data-home-hero-original]');
        const heroStock = root.querySelector('[data-home-hero-stock]');
        const heroCaption = root.querySelector('[data-home-hero-caption]');
        const heroThumbnail = root.querySelector('[data-home-hero-thumbnail]');
        const heroThumbnailLink = root.querySelector('[data-home-hero-thumbnail-link]');
        const heroTitle = root.querySelector('[data-home-hero-title]');
        const heroDescription = root.querySelector('[data-home-hero-description]');
        const heroCta = root.querySelector('[data-home-hero-cta]');
        const heroProductLink = root.querySelector('[data-home-hero-product-link]');
        const heroPrev = root.querySelector('[data-home-hero-prev]');
        const heroNext = root.querySelector('[data-home-hero-next]');
        let activeHeroIndex = Math.max(0, heroSlides.findIndex((slide) => slide.getAttribute('aria-pressed') === 'true'));
        let heroTimer = null;

        const selectHeroSlide = (index) => {
            activeHeroIndex = (index + heroSlides.length) % heroSlides.length;
            const slide = heroSlides[activeHeroIndex];
            const imageUrl = slide.dataset.image || '';

            heroSlides.forEach((item, itemIndex) => {
                const selected = itemIndex === activeHeroIndex;
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            hero?.style.setProperty('--home-hero-base', slide.dataset.theme || '#9a5527');
            hero?.classList.remove('is-transitioning');
            // Restart a small, compositor-friendly entrance animation without
            // reading layout for every product card on the page.
            window.requestAnimationFrame(() => hero?.classList.add('is-transitioning'));
            if (heroTitle) heroTitle.textContent = slide.dataset.title || 'محصول منتخب';
            if (heroDescription) heroDescription.textContent = slide.dataset.description || '';
            if (heroCta) heroCta.href = slide.dataset.url || heroCta.href;
            if (heroProductLink) heroProductLink.href = slide.dataset.url || heroProductLink.href;
            if (heroPrice) heroPrice.textContent = slide.dataset.price || 'قیمت نامشخص';
            if (heroOriginalPrice) {
                heroOriginalPrice.hidden = !slide.dataset.originalPrice;
                heroOriginalPrice.textContent = slide.dataset.originalPrice || '';
            }
            if (heroStock) heroStock.textContent = slide.dataset.stock || 'فعلاً ناموجود';
            if (heroCaption) heroCaption.textContent = slide.dataset.label || 'محصول منتخب';

            if (heroImage) {
                if (imageUrl) {
                    if (heroImage.getAttribute('src') !== imageUrl) heroImage.src = imageUrl;
                    heroImage.alt = slide.dataset.imageAlt || 'تصویر محصول منتخب';
                    heroImage.hidden = false;
                } else {
                    heroImage.removeAttribute('src');
                    heroImage.hidden = true;
                }
            }
            if (heroImageMissing) heroImageMissing.hidden = Boolean(imageUrl);
            if (heroThumbnail) {
                if (imageUrl) {
                    if (heroThumbnail.getAttribute('src') !== imageUrl) heroThumbnail.src = imageUrl;
                    heroThumbnail.hidden = false;
                } else {
                    heroThumbnail.removeAttribute('src');
                    heroThumbnail.hidden = true;
                }
            }
            if (heroThumbnailLink) heroThumbnailLink.hidden = !imageUrl;
        };

        const updateHeroControls = () => {
            const disabled = heroSlides.length < 2;
            if (heroPrev) heroPrev.disabled = disabled;
            if (heroNext) heroNext.disabled = disabled;
        };

        const resetHeroTimer = () => {
            if (heroTimer) window.clearInterval(heroTimer);
            if (heroSlides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            heroTimer = window.setInterval(() => selectHeroSlide(activeHeroIndex + 1), 6500);
        };

        heroSlides.forEach((slide, index) => slide.addEventListener('click', () => {
            selectHeroSlide(index);
            resetHeroTimer();
        }));
        heroPrev?.addEventListener('click', () => { selectHeroSlide(activeHeroIndex - 1); resetHeroTimer(); });
        heroNext?.addEventListener('click', () => { selectHeroSlide(activeHeroIndex + 1); resetHeroTimer(); });
        hero?.addEventListener('mouseenter', () => heroTimer && window.clearInterval(heroTimer));
        hero?.addEventListener('mouseleave', resetHeroTimer);
        updateHeroControls();
        selectHeroSlide(activeHeroIndex);
        resetHeroTimer();
    }

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
        showToast('فهرست پیش‌نمایش پاک شد.');
    });

})();
