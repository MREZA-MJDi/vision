(() => {
    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    ready(() => {
        const header = document.querySelector('[data-store-header]');
        const menuButton = document.querySelector('[data-menu-toggle]');
        const nav = document.querySelector('[data-store-nav]');

        if (header) {
            const setCompact = () => header.classList.toggle('is-compact', window.scrollY > 24);
            setCompact();
            window.addEventListener('scroll', setCompact, { passive: true });
        }

        if (menuButton && nav) {
            menuButton.addEventListener('click', () => {
                const open = menuButton.getAttribute('aria-expanded') !== 'true';
                menuButton.setAttribute('aria-expanded', String(open));
                nav.classList.toggle('is-open', open);
            });

            nav.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    menuButton.setAttribute('aria-expanded', 'false');
                    nav.classList.remove('is-open');
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    menuButton.setAttribute('aria-expanded', 'false');
                    nav.classList.remove('is-open');
                }
            });
        }

        const backTop = document.querySelector('[data-back-top]');
        backTop?.addEventListener('click', (event) => {
            event.preventDefault();
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        });

        const variantSelect = document.querySelector('[data-variant-select]');
        const priceSlot = document.querySelector('[data-product-price]');
        const stockSlot = document.querySelector('[data-product-stock]');
        const addButton = document.querySelector('[data-demo-add-to-bag]');
        const notice = document.querySelector('[data-buy-notice]');

        const updateVariant = () => {
            const option = variantSelect?.selectedOptions?.[0];
            if (!option) return;

            const stock = Number(option.dataset.stock || 0);
            const price = Number(option.dataset.price || 0);
            const original = Number(option.dataset.originalPrice || 0);
            const sale = option.dataset.sale === '1';

            if (priceSlot) {
                priceSlot.replaceChildren();
                if (sale) {
                    const oldPrice = document.createElement('del');
                    oldPrice.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(original);
                    priceSlot.append(oldPrice);
                }
                const value = document.createElement('strong');
                value.append(document.createTextNode(new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(price) + ' '));
                const unit = document.createElement('small');
                unit.textContent = 'IRR';
                value.append(unit);
                priceSlot.append(value);
            }

            if (stockSlot) {
                stockSlot.textContent = stock > 0 ? 'IN STOCK — READY FOR YOUR EDIT' : 'CURRENTLY UNAVAILABLE';
            }

            if (addButton) addButton.disabled = stock < 1;
        };

        variantSelect?.addEventListener('change', updateVariant);
        updateVariant();

        addButton?.addEventListener('click', () => {
            if (notice) {
                notice.textContent = 'The selected variant is available, but live cart and checkout are not connected yet. No order was created.';
            }
        });

        const newsletter = document.querySelector('[data-newsletter-form]');
        newsletter?.addEventListener('submit', (event) => {
            event.preventDefault();
            const message = newsletter.querySelector('[data-newsletter-message]');
            if (message) message.textContent = 'Thanks for your interest. Newsletter signup needs a mailing-list endpoint before launch.';
        });

        const revealTargets = document.querySelectorAll('.section-heading, .category-card, .product-card, .campaign__content, .newsletter > div, .newsletter__form');
        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const observer = new IntersectionObserver((entries, currentObserver) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        currentObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });

            revealTargets.forEach((element) => {
                element.setAttribute('data-reveal', '');
                observer.observe(element);
            });
        }
    });
})();
