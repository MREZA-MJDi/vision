<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b090d">
    <meta name="description" content="{{ $__env->yieldContent('meta-description', 'مجموعه‌ای منتخب از پوشاک روزمره و محصولات فروشگاه VISION.') }}">
    <link rel="canonical" href="{{ $__env->yieldContent('canonical', url()->current()) }}">
    <meta property="og:site_name" content="VISION">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:type" content="{{ $__env->yieldContent('og-type', 'website') }}">
    <meta property="og:title" content="{{ $__env->yieldContent('title', 'VISION — فروشگاه پوشاک') }}">
    <meta property="og:description" content="{{ $__env->yieldContent('meta-description', 'مجموعه‌ای منتخب از پوشاک روزمره و محصولات فروشگاه VISION.') }}">
    <meta property="og:url" content="{{ $__env->yieldContent('canonical', url()->current()) }}">
    @if (filled(trim($__env->yieldContent('og-image'))))
        <meta property="og:image" content="{{ trim($__env->yieldContent('og-image')) }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ trim($__env->yieldContent('og-image')) }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $__env->yieldContent('title', 'VISION — فروشگاه پوشاک') }}">
    <meta name="twitter:description" content="{{ $__env->yieldContent('meta-description', 'مجموعه‌ای منتخب از پوشاک روزمره و محصولات فروشگاه VISION.') }}">
    <title>{{ $__env->yieldContent('title', 'VISION — فروشگاه پوشاک') }}</title>

    @if (request()->routeIs('storefront.index'))
        <link rel="preconnect" href="https://tiamir.com" crossorigin>
        <link rel="preload" as="image" href="https://tiamir.com/wp-content/uploads/2026/09/Tiamir-white.png" fetchpriority="high">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/css/storefront.css', 'resources/css/home-hero.css', 'resources/js/storefront.js'])
    @elseif (app()->environment('testing'))
        {{-- Feature tests assert rendered storefront markup without requiring a Vite build. --}}
    @endif
    @stack('styles')
</head>
<body class="storefront-body {{ request()->routeIs('storefront.index') ? 'storefront-home-body' : '' }} {{ request()->routeIs('shop.index', 'products.index') ? 'store-shop-body' : '' }}">
    <div class="store-atmosphere" aria-hidden="true"></div>

    @if (request()->routeIs('storefront.index'))
        <div class="tiamir-intro" data-tiamir-intro data-first-duration="3200" data-return-duration="1900" aria-hidden="true">
            <canvas class="tiamir-intro__static" data-tiamir-static></canvas>
            <span class="tiamir-intro__grain" aria-hidden="true"></span>
            <span class="tiamir-intro__scanlines" aria-hidden="true"></span>
            <span class="tiamir-intro__scan-beam" aria-hidden="true"></span>
            <span class="tiamir-intro__disturbance" aria-hidden="true"></span>
            <span class="tiamir-intro__flash" aria-hidden="true"></span>

            <div class="tiamir-intro__identity">
                <span class="tiamir-intro__logo-wrap">
                    <img class="tiamir-intro__logo tiamir-intro__logo--ghost tiamir-intro__logo--ghost-cyan"
                         src="https://tiamir.com/wp-content/uploads/2026/09/Tiamir-white.png"
                         alt="" aria-hidden="true" loading="eager" decoding="async">
                    <img class="tiamir-intro__logo tiamir-intro__logo--ghost tiamir-intro__logo--ghost-red"
                         src="https://tiamir.com/wp-content/uploads/2026/09/Tiamir-white.png"
                         alt="" aria-hidden="true" loading="eager" decoding="async">
                    <img class="tiamir-intro__logo tiamir-intro__logo--main"
                         src="https://tiamir.com/wp-content/uploads/2026/09/Tiamir-white.png"
                         alt="" aria-hidden="true" data-tiamir-logo fetchpriority="high" loading="eager" decoding="async">
                    <span class="tiamir-intro__fallback" data-tiamir-fallback hidden>TIAMIR</span>
                </span>
            </div>

            <div class="tiamir-intro__signature">Created by R MAJIDI</div>
            <div class="tiamir-intro__hint" data-tiamir-hint aria-hidden="true">ANALOG SIGNAL / 01</div>
        </div>
    @endif

    <div class="store-shell" data-storefront
         data-currency-symbol="{{ config('commerce-admin.currency_symbol', 'تومان') }}"
         data-cart-url="{{ route('cart.index') }}"
         data-cart-summary-url="{{ route('cart.summary') }}"
         data-cart-add-url="{{ route('cart.items.store') }}"
         data-account-url="{{ route('account.show') }}">
        <header class="store-header">
            <nav class="store-nav" aria-label="ناوبری اصلی">
                <a href="{{ route('storefront.index') }}" class="store-nav__link {{ request()->routeIs('storefront.index') ? 'is-current' : '' }}">خانه</a>
                <a href="{{ route('shop.index') }}#catalog" class="store-nav__link {{ request()->routeIs('shop.index', 'products.index') ? 'is-current' : '' }}">فروشگاه</a>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog" class="store-nav__link">تازه‌ها</a>
                <a href="{{ route('shop.index', ['sale' => 1]) }}#catalog" class="store-nav__link {{ request()->boolean('sale') ? 'is-current' : '' }}">تخفیف‌ها</a>
            </nav>

            <a class="store-wordmark" href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی فروشگاه VISION">VISION</a>

            <div class="store-header__actions">
                <form class="store-search" action="{{ route('shop.index') }}" method="GET" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"></circle><path d="m16 16 4.2 4.2"></path></svg>
                    <input type="search" name="q" value="{{ request('q', $search ?? '') }}" placeholder="جست‌وجو" aria-label="جست‌وجوی محصولات">
                    @foreach(request()->query() as $key => $value)
                        @if($key !== 'q' && $key !== 'page' && is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" aria-label="اجرای جست‌وجو">↵</button>
                </form>

                <button class="store-icon-button" type="button" data-store-account aria-label="حساب کاربری" title="حساب کاربری">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.3"></circle><path d="M5.3 20c.5-3.4 2.8-5.3 6.7-5.3s6.2 1.9 6.7 5.3"></path></svg>
                </button>
                <button class="store-icon-button store-cart-button" type="button" data-cart-toggle aria-label="مشاهده سبد خرید" title="سبد خرید">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path></svg>
                    <span class="store-cart-count" data-cart-count>0</span>
                </button>
                <button class="store-icon-button store-mobile-filter" type="button" data-store-filter-toggle aria-controls="store-sidebar" aria-expanded="false" aria-label="باز کردن فیلترها">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4"></path></svg>
                </button>
            </div>
        </header>

        @yield('content')

        <footer class="store-footer" aria-label="پاورقی فروشگاه VISION">
            <div class="store-footer__main">
                <div class="store-footer__brand">
                    <a class="store-footer__wordmark" href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی VISION">VISION</a>
                    <p>انتخاب دقیق‌تر، شلوغی کمتر؛ محصولات و اطلاعات سفارش در یک فروشگاه یکپارچه.</p>
                </div>

                <nav class="store-footer__column" aria-label="دسترسی سریع">
                    <h2>دسترسی سریع</h2>
                    <a href="{{ route('storefront.index') }}">خانه</a>
                    <a href="{{ route('shop.index') }}">فروشگاه</a>
                    <a href="{{ route('shop.index', ['sort' => 'newest']) }}">تازه‌های فروشگاه</a>
                    <a href="{{ route('shop.index', ['sale' => 1]) }}">تخفیف‌ها</a>
                </nav>

                <nav class="store-footer__column" aria-label="خرید و حساب کاربری">
                    <h2>خرید و حساب کاربری</h2>
                    <a href="{{ route('cart.index') }}">سبد خرید</a>
                    <a href="{{ route('checkout.index') }}">ادامهٔ خرید</a>
                    <a href="{{ route('login') }}">ورود</a>
                    <a href="{{ route('register') }}">ساخت حساب</a>
                </nav>
            </div>

            <div class="store-footer__bottom">
                <span>© {{ now()->year }} VISION — تمام حقوق محفوظ است.</span>
                <a href="{{ route('storefront.index') }}">بازگشت به صفحهٔ اصلی <span aria-hidden="true">↑</span></a>
            </div>
        </footer>

        <div class="store-toast" data-store-toast role="status" aria-live="polite"></div>
        <button class="store-sidebar-backdrop" type="button" data-store-filter-backdrop aria-label="بستن فیلترها" tabindex="-1"></button>
    </div>
</body>
</html>
