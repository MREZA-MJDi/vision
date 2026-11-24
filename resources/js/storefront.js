import { gsap } from "gsap";
import { flyToCart, pulseCartTarget, prefersReducedMotion } from "./cart-motion";

(() => {
    const root = document.querySelector('[data-storefront]');
    if (!root) return;

    const currencySymbol = root.dataset.currencySymbol || 'تومان';
    const numberFormatter = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 });
    const formatNumber = (value) => numberFormatter.format(Number(value) || 0);
    const formatMoney = (value) => formatNumber(value) + ' ' + currencySymbol;

    const sidebar = root.querySelector('[data-store-sidebar]');
    const filterToggle = root.querySelector('[data-store-filter-toggle]');
    const backdrop = root.querySelector('[data-store-filter-backdrop]');
    const toast = root.querySelector('[data-store-toast]');
    let toastTimer = null;

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

    function updateCartCount(count = 0) {
        const safeCount = Math.max(0, Number(count) || 0);
        root.querySelectorAll('[data-cart-count]').forEach((element) => {
            element.textContent = formatNumber(safeCount);
            element.setAttribute('aria-label', 'سبد خرید، ' + formatNumber(safeCount) + ' محصول');
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

    const homeCartTarget = root.querySelector('[data-home-cart-target]');
    const floatingCart = root.querySelector('[data-home-cart-floating]');
    if (floatingCart && homeCartTarget && 'IntersectionObserver' in window) {
        const cartObserver = new IntersectionObserver(([entry]) => {
            floatingCart.hidden = entry.isIntersecting;
        });
        cartObserver.observe(homeCartTarget);
    }

    let homeCartPending = false;
    let homeCartRevision = 0;

    async function syncCartCount() {
        const url = root.dataset.cartSummaryUrl;
        if (!url) return;
        const revision = homeCartRevision;
        try {
            const response = await requestCart(url);
            if (revision === homeCartRevision) updateCartCount(response.count);
        } catch {
            // Keep the storefront usable even if the count endpoint is temporarily unavailable.
        }
    }

    filterToggle?.addEventListener('click', () => setFiltersOpen(!sidebar?.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setFiltersOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setFiltersOpen(false);
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

    root.querySelectorAll('[data-cart-add]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (homeCartPending) return;

            const variantId = Number(button.dataset.cartAdd || 0);
            const addUrl = root.dataset.cartAddUrl;
            if (!Number.isInteger(variantId) || variantId < 1 || !addUrl) {
                showToast('این محصول در حال حاضر برای افزودن به سبد در دسترس نیست.');
                return;
            }

            button.disabled = true;
            homeCartPending = Boolean(homeCartTarget);
            try {
                const result = await requestCart(addUrl, 'POST', {
                    variant_id: variantId,
                    quantity: 1
                });
                if (homeCartTarget) homeCartRevision++;
                updateCartCount(result.count);
                if (homeCartTarget) {
                    const source = button.closest('.home-product-card')?.querySelector('[data-home-cart-source]');
                    const target = floatingCart && !floatingCart.hidden ? floatingCart : homeCartTarget;
                    await flyToCart(source?.querySelector('img') || source, target);
                    pulseCartTarget(target);
                }
                showToast('به سبد خرید اضافه شد · تعداد محصولات: ' + formatNumber(result.count));
            } catch (error) {
                showToast(error.message || 'افزودن محصول به سبد خرید انجام نشد.');
            } finally {
                button.disabled = false;
                homeCartPending = false;
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
                    original.textContent = formatMoney(originalPrice);
                    priceDisplay.append(original);
                }
                const current = document.createElement('strong');
                current.textContent = formatMoney(price);
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
                    ? formatNumber(stock) + ' عدد موجود'
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


    const hero = root.querySelector('[data-home-hero]');
    if (hero) {
        const slides = Array.from(hero.querySelectorAll('[data-home-hero-slide]'));
        const products = Array.from(hero.querySelectorAll('[data-home-hero-product]'));
        const selectedByProduct = new Map();
        let activeProductIndex = 0;
        const image = hero.querySelector('[data-home-hero-image]');
        const missingImage = hero.querySelector('[data-home-hero-image-missing]');
        const originalPrice = hero.querySelector('[data-home-hero-original]');
        const productLink = hero.querySelector('[data-home-hero-product-link]');
        const fields = Object.fromEntries(['title', 'description', 'price', 'stock', 'caption'].map(
            (field) => [field, hero.querySelector(`[data-home-hero-${field}]`)]
        ));

        const showImage = (visible) => {
            image.hidden = !visible;
            missingImage.hidden = visible;
        };

        image?.addEventListener('load', () => {
            showImage(true);
            if (!prefersReducedMotion()) {
                gsap.fromTo(image, { opacity: 0.35, y: 12, scale: 0.98 }, {
                    opacity: 1, y: 0, scale: 1, duration: 0.42, ease: 'power2.out', overwrite: true, clearProps: 'transform,opacity'
                });
            }
        });
        image?.addEventListener('error', () => showImage(false));
        if (image?.getAttribute('src') && image.complete && !image.naturalWidth) {
            showImage(false);
        }

        const selectSlide = (slide) => {
            if (slide.getAttribute('aria-pressed') === 'true') return;

            const productId = slide.dataset.productId;
            selectedByProduct.set(productId, slide);
            slides.forEach((item) => {
                item.hidden = item.dataset.productId !== productId;
                item.setAttribute('aria-pressed', String(item === slide));
            });
            products.forEach((product, index) => {
                const active = product.dataset.homeHeroProduct === productId;
                product.setAttribute('aria-pressed', String(active));
                if (active) activeProductIndex = index;
            });

            const { theme, title, description, price, stock, label, url, imageAlt, originalPrice: salePrice } = slide.dataset;
            hero.style.setProperty('--home-hero-base', theme);
            fields.title.textContent = title;
            fields.description.textContent = description;
            fields.price.textContent = price;
            fields.stock.textContent = stock;
            fields.caption.textContent = label;
            originalPrice.textContent = salePrice;
            originalPrice.hidden = !salePrice;
            productLink.href = url;

            image.alt = imageAlt;
            if (!slide.dataset.image) {
                image.removeAttribute('src');
                showImage(false);
            } else if (image.getAttribute('src') !== slide.dataset.image) {
                image.hidden = true;
                missingImage.hidden = true;
                image.src = slide.dataset.image;
            }
        };

        const selectProduct = (index) => {
            const product = products[(index + products.length) % products.length];
            const productId = product.dataset.homeHeroProduct;
            selectSlide(selectedByProduct.get(productId) || slides.find((slide) => slide.dataset.productId === productId));
        };

        slides.forEach((slide) => slide.addEventListener('click', () => selectSlide(slide)));
        products.forEach((product, index) => product.addEventListener('click', () => selectProduct(index)));
        hero.querySelectorAll('[data-home-hero-step]').forEach((button) => {
            button.addEventListener('click', () => selectProduct(activeProductIndex + Number(button.dataset.homeHeroStep)));
        });
    }

    root.querySelectorAll('.home-rail').forEach((rail) => {
        const controls = Array.from(root.querySelectorAll(`[data-home-rail-step][aria-controls="${rail.id}"]`));
        const updateControls = () => {
            const max = rail.scrollWidth - rail.clientWidth;
            const position = Math.abs(rail.scrollLeft);
            controls.forEach((button) => {
                button.disabled = Number(button.dataset.homeRailStep) < 0 ? position < 2 : max - position < 2;
            });
        };

        controls.forEach((button) => button.addEventListener('click', () => {
            const next = Number(button.dataset.homeRailStep);
            rail.scrollBy({
                left: -next * rail.clientWidth * 0.8,
                behavior: prefersReducedMotion() ? 'instant' : 'smooth'
            });
        }));
        rail.addEventListener('scroll', updateControls, { passive: true });
        new ResizeObserver(updateControls).observe(rail);
        updateControls();
    });

    if (!prefersReducedMotion() && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const entering = entries.filter(entry => entry.isIntersecting).map(entry => entry.target);
            entering.forEach(element => observer.unobserve(element));
            if (!entering.length) return;
            gsap.fromTo(entering, { opacity: 0.45, y: 22, scale: 0.98 }, {
                opacity: 1, y: 0, scale: 1, duration: 0.58, stagger: 0.055,
                ease: 'power2.out', clearProps: 'transform,opacity'
            });
        }, { threshold: 0.2 });
        root.querySelectorAll('.home-content-heading, .home-rail > *, .vision-shop-story > *').forEach((element) => observer.observe(element));
    }

    updateCartCount(0);
    syncCartCount();

})();
