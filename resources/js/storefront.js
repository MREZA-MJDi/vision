(() => {
    function initTiamirIntro(intro) {
        const storageKey = 'tiamir_intro_seen';
        const hasSeenCookie = document.cookie.split(';').some((cookie) => cookie.trim() === storageKey + '=1');
        let hasSeenSession = false;

        try {
            hasSeenSession = window.sessionStorage.getItem(storageKey) === '1';
        } catch {
            // A blocked browser store must not prevent the intro from running.
        }

        const returningVisitor = hasSeenCookie || hasSeenSession;
        const firstDuration = Math.max(1000, Number(intro.dataset.firstDuration) || 3200);
        const returnDuration = Math.max(1000, Number(intro.dataset.returnDuration) || 1900);
        const exitDuration = 220;
        const shell = document.querySelector('.store-shell');
        const canvas = intro.querySelector('[data-tiamir-static]');
        const mainLogo = intro.querySelector('[data-tiamir-logo]');
        const fallback = intro.querySelector('[data-tiamir-fallback]');
        const ghostLogos = intro.querySelectorAll('.tiamir-intro__logo--ghost');
        const identity = intro.querySelector('.tiamir-intro__identity');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let finishTimer = null;
        let fadeTimer = null;
        let noiseTimer = null;
        let pointerTimer = null;
        let finishDeadline = 0;
        let remaining = 0;
        let lastTapAt = 0;
        let canvasContext = null;
        let canvasWidth = 0;
        let canvasHeight = 0;
        let active = false;
        let finished = false;
        let canDismiss = returningVisitor;

        function markSeen() {
            try {
                window.sessionStorage.setItem(storageKey, '1');
            } catch {
                // Cookie and in-memory behavior remain available if storage is blocked.
            }

            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = storageKey + '=1; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
        }

        function revealFallback() {
            if (mainLogo) mainLogo.hidden = true;
            ghostLogos.forEach((logo) => { logo.hidden = true; });
            if (fallback) fallback.hidden = false;
        }

        if (mainLogo) {
            mainLogo.addEventListener('error', revealFallback);
            mainLogo.addEventListener('load', () => {
                if (fallback) fallback.hidden = true;
                ghostLogos.forEach((logo) => { logo.hidden = false; });
                mainLogo.hidden = false;
            });
            if (mainLogo.complete && mainLogo.naturalWidth === 0) revealFallback();
        }

        function prepareCanvas() {
            if (!canvas || reducedMotion) return false;

            if (!canvasContext) {
                canvasContext = canvas.getContext('2d', { alpha: true, desynchronized: true });
                if (!canvasContext) return false;
            }

            const rect = canvas.getBoundingClientRect();
            // Deliberately low resolution: the signal grain is texture, not full-screen HD video.
            const scale = Math.min(window.devicePixelRatio || 1, 1.1) * 0.24;
            const width = Math.max(1, Math.floor(rect.width * scale));
            const height = Math.max(1, Math.floor(rect.height * scale));

            if (width !== canvasWidth || height !== canvasHeight) {
                canvasWidth = width;
                canvasHeight = height;
                canvas.width = width;
                canvas.height = height;
            }

            return true;
        }

        function drawStatic() {
            if (!active || document.hidden || reducedMotion || !canvas || !prepareCanvas()) return;

            const width = canvas.width;
            const height = canvas.height;
            canvasContext.clearRect(0, 0, width, height);

            // Sparse short bands are cheaper than regenerating every pixel on every frame.
            const bands = Math.min(24, Math.max(10, Math.ceil(width / 24)));
            for (let index = 0; index < bands; index += 1) {
                if (Math.random() < 0.3) continue;
                const shade = Math.floor(145 + Math.random() * 110);
                const alpha = 0.035 + Math.random() * 0.11;
                const bandWidth = Math.max(2, Math.floor(width * (0.025 + Math.random() * 0.22)));
                const y = Math.floor(Math.random() * height);
                const x = Math.floor(Math.random() * Math.max(1, width - bandWidth));
                canvasContext.fillStyle = 'rgba(' + shade + ',' + shade + ',' + shade + ',' + alpha + ')';
                canvasContext.fillRect(x, y, bandWidth, Math.random() > 0.82 ? 2 : 1);
            }

            if (Math.random() > 0.975) {
                canvasContext.fillStyle = 'rgba(255,255,255,0.12)';
                canvasContext.fillRect(0, Math.floor(Math.random() * height), width, 1);
            }
        }

        function startStatic() {
            if (!active || document.hidden || reducedMotion || noiseTimer !== null || !canvas) return;
            if (!prepareCanvas()) return;
            drawStatic();
            noiseTimer = window.setInterval(drawStatic, 80);
        }

        function stopStatic() {
            if (noiseTimer !== null) window.clearInterval(noiseTimer);
            noiseTimer = null;
        }

        function scheduleFinish(milliseconds) {
            if (finishTimer !== null) window.clearTimeout(finishTimer);
            remaining = Math.max(0, milliseconds);
            finishDeadline = performance.now() + remaining;
            finishTimer = window.setTimeout(() => {
                finishTimer = null;
                remaining = 0;
                finishIntro(true);
            }, remaining);
        }

        function finishIntro(naturalCompletion) {
            if (!active || finished || (!canDismiss && !naturalCompletion)) return;

            finished = true;
            active = false;
            markSeen();

            if (finishTimer !== null) window.clearTimeout(finishTimer);
            finishTimer = null;
            stopStatic();
            if (pointerTimer !== null) window.clearTimeout(pointerTimer);
            pointerTimer = null;
            intro.classList.remove('has-signal-hit');
            intro.classList.add('is-exiting');

            fadeTimer = window.setTimeout(() => {
                intro.classList.remove('is-visible', 'is-exiting', 'is-revealing', 'is-returning');
                document.body.classList.remove('tiamir-intro-active');
                if (shell) shell.inert = false;
                fadeTimer = null;
            }, exitDuration);
        }

        function runIntro(duration, allowDismiss) {
            if (finishTimer !== null) window.clearTimeout(finishTimer);
            if (fadeTimer !== null) window.clearTimeout(fadeTimer);
            finishTimer = null;
            fadeTimer = null;
            stopStatic();

            active = true;
            finished = false;
            canDismiss = Boolean(allowDismiss);
            remaining = Math.max(0, duration - exitDuration);

            intro.classList.remove('is-visible', 'is-exiting', 'is-revealing', 'is-returning', 'has-signal-hit');
            intro.style.setProperty('--tiamir-duration', duration + 'ms');
            void intro.offsetWidth;
            intro.classList.add('is-visible');
            if (canDismiss) intro.classList.add('is-returning');
            document.body.classList.add('tiamir-intro-active');
            if (shell) shell.inert = true;

            if (reducedMotion) {
                window.requestAnimationFrame(() => intro.classList.add('is-revealing'));
            }

            startStatic();
            if (document.hidden) {
                if (finishTimer !== null) window.clearTimeout(finishTimer);
                finishTimer = null;
                finishDeadline = 0;
            } else {
                scheduleFinish(remaining);
            }
        }

        function handleVisibilityChange() {
            if (!active) return;
            if (document.hidden) {
                if (finishTimer !== null) {
                    window.clearTimeout(finishTimer);
                    finishTimer = null;
                    remaining = Math.max(0, finishDeadline - performance.now());
                }
                stopStatic();
                return;
            }

            scheduleFinish(remaining);
            startStatic();
        }

        function handleKeyDown(event) {
            if (!active || !canDismiss || event.repeat) return;
            if (event.key === 'Escape' || event.key === 'Enter') finishIntro(false);
        }

        function handlePointerMove(event) {
            if (!active || reducedMotion || !identity) return;
            const x = (event.clientX / Math.max(1, window.innerWidth) - 0.5) * 2;
            const y = (event.clientY / Math.max(1, window.innerHeight) - 0.5) * 2;
            identity.style.transform = 'translate3d(' + (x * 2).toFixed(1) + 'px,' + (y * 1.2).toFixed(1) + 'px,0)';
            intro.classList.add('has-signal-hit');
            if (pointerTimer !== null) window.clearTimeout(pointerTimer);
            pointerTimer = window.setTimeout(() => {
                intro.classList.remove('has-signal-hit');
                pointerTimer = null;
            }, 220);
        }

        intro.addEventListener('dblclick', () => {
            if (active && canDismiss) finishIntro(false);
        });
        intro.addEventListener('touchend', () => {
            if (!active || !canDismiss) return;
            const now = Date.now();
            if (now - lastTapAt < 360) {
                lastTapAt = 0;
                finishIntro(false);
            } else {
                lastTapAt = now;
            }
        }, { passive: true });

        document.addEventListener('visibilitychange', handleVisibilityChange);
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('pointermove', handlePointerMove, { passive: true });
        window.addEventListener('resize', () => {
            if (active && !reducedMotion) prepareCanvas();
        }, { passive: true });

        runIntro(returningVisitor ? returnDuration : firstDuration, returningVisitor);
    }

    const intro = document.querySelector('[data-tiamir-intro]');
    if (intro) initTiamirIntro(intro);

    const root = document.querySelector('[data-storefront]');
    if (!root) return;

    const currencySymbol = root.dataset.currencySymbol || 'تومان';
    const formatMoney = (value) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value) || 0) + ' ' + currencySymbol;

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
            if (heroThumbnailLink) {
                heroThumbnailLink.href = slide.dataset.url || heroThumbnailLink.href;
                heroThumbnailLink.hidden = !imageUrl;
            }
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


    // The homepage category cube uses GSAP only when this section exists.
    const categoryCube = root.querySelector('[data-category-cube]');
    if (categoryCube) {
        import('gsap').then(({ gsap }) => {
            const stage = categoryCube.querySelector('[data-category-cube-stage]');
            const container = categoryCube.querySelector('[data-category-cube-container]');
            const cube = categoryCube.querySelector('[data-category-cube-object]');
            const faces = Array.from(categoryCube.querySelectorAll('[data-category-cube-face]'));
            const images = faces.map((face) => face.querySelector('img')).filter(Boolean);
            const previous = categoryCube.querySelector('[data-category-cube-prev]');
            const next = categoryCube.querySelector('[data-category-cube-next]');
            const status = categoryCube.querySelector('[data-category-cube-status]');

            if (!stage || !container || !cube || faces.length !== 6) return;

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const duration = reduceMotion ? 0 : 0.72;
            const faceRotations = [
                { x: -18, y: -26 },
                { x: -18, y: -116 },
                { x: -18, y: 154 },
                { x: -18, y: 64 },
                { x: -90, y: -26 },
                { x: 90, y: -26 },
            ];
            const rotateXTo = gsap.quickTo(cube, 'rotationX', { duration, ease: 'power3.out' });
            const rotateYTo = gsap.quickTo(cube, 'rotationY', { duration, ease: 'power3.out' });
            const imageXTo = images.map((image) => gsap.quickTo(image, 'x', { duration, ease: 'power3.out' }));
            const imageYTo = images.map((image) => gsap.quickTo(image, 'y', { duration, ease: 'power3.out' }));
            const clamp = (value) => Math.min(1, Math.max(0, value));
            const normalizeAngle = (angle) => ((angle % 360) + 360) % 360;
            let activeFace = -1;

            const faceFromRotation = (rotationX, rotationY) => {
                const x = normalizeAngle(rotationX);
                if (x >= 225 && x < 315) return 4;
                if (x >= 45 && x < 135) return 5;
                return (4 - (Math.round(normalizeAngle(rotationY) / 90) % 4)) % 4;
            };

            const showFace = (index) => {
                const nextIndex = (index + faces.length) % faces.length;
                if (nextIndex === activeFace) return;
                activeFace = nextIndex;
                faces.forEach((face, faceIndex) => {
                    const isActive = faceIndex === activeFace;
                    face.tabIndex = isActive ? 0 : -1;
                    face.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
                if (status) status.textContent = 'دسته: ' + (faces[activeFace].dataset.categoryName || 'دسته‌بندی');
            };

            const animateTo = (rotationX, rotationY) => {
                rotateXTo(rotationX);
                rotateYTo(rotationY);
                showFace(faceFromRotation(rotationX, rotationY));
            };

            const moveImages = (x, y) => {
                const size = container.getBoundingClientRect().width || 280;
                const maxShift = Math.min(8, size * 0.025);
                const targetX = (x - 0.5) * 2 * maxShift;
                const targetY = (y - 0.5) * 2 * maxShift;
                images.forEach((image, index) => {
                    imageXTo[index](targetX);
                    imageYTo[index](targetY);
                });
            };

            const updateFromPointer = (event) => {
                const bounds = stage.getBoundingClientRect();
                if (!bounds.width || !bounds.height) return;
                const x = clamp((event.clientX - bounds.left) / bounds.width);
                const y = clamp((event.clientY - bounds.top) / bounds.height);
                animateTo(-180 + 360 * y, -180 + 360 * x);
                moveImages(x, y);
            };

            const setGeometry = () => {
                const size = container.getBoundingClientRect().width || 280;
                stage.style.perspective = String(Math.round(size * 5)) + 'px';
                gsap.set(images, { x: 0, y: 0 });
            };

            gsap.set(cube, { rotationX: -18, rotationY: -26, transformStyle: 'preserve-3d' });
            gsap.fromTo(
                container,
                { autoAlpha: 0, scale: 0.94 },
                { autoAlpha: 1, scale: 1, duration: reduceMotion ? 0 : 0.72, ease: 'power3.out' }
            );

            stage.addEventListener('pointermove', (event) => {
                if (event.pointerType === 'touch' && event.buttons === 0) return;
                updateFromPointer(event);
            }, { passive: true });
            stage.addEventListener('pointerdown', (event) => {
                if (event.pointerType === 'touch') updateFromPointer(event);
            }, { passive: true });
            stage.addEventListener('pointerleave', () => {
                animateTo(-18, -26);
                moveImages(0.5, 0.5);
            }, { passive: true });

            previous?.addEventListener('click', () => {
                const index = (Math.max(activeFace, 0) - 1 + faces.length) % faces.length;
                const rotation = faceRotations[index];
                animateTo(rotation.x, rotation.y);
            });
            next?.addEventListener('click', () => {
                const index = (Math.max(activeFace, 0) + 1) % faces.length;
                const rotation = faceRotations[index];
                animateTo(rotation.x, rotation.y);
            });
            stage.addEventListener('keydown', (event) => {
                if (event.target !== stage) return;
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    next?.click();
                } else if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    previous?.click();
                }
            });

            setGeometry();
            showFace(0);
            if ('ResizeObserver' in window) new ResizeObserver(setGeometry).observe(container);
            else window.addEventListener('resize', setGeometry, { passive: true });
        }).catch((error) => {
            console.error('Could not load GSAP for the homepage category cube.', error);
        });
    }

    updateCartCount(0);
    syncCartCount();

})();
