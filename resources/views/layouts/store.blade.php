<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b090d">
    <meta name="description" content="@yield('meta-description', 'مجموعه‌ای منتخب از پوشاک روزمره و محصولات فروشگاه VISION.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <title>@yield('title', 'VISION — New Arrivals')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/css/storefront.css', 'resources/js/storefront.js'])
    @endif
    @stack('styles')
</head>
<body class="storefront-body">
    <div class="store-atmosphere" aria-hidden="true"></div>

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

            <a class="store-wordmark" href="{{ route('storefront.index') }}" aria-label="VISION store home">STORE</a>

            <div class="store-header__actions">
                <form class="store-search" action="{{ route('shop.index') }}" method="GET" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"></circle><path d="m16 16 4.2 4.2"></path></svg>
                    <input type="search" name="q" value="{{ request('q', $search ?? '') }}" placeholder="جست‌وجو" aria-label="جست‌وجوی محصولات">
                    @if(($sort ?? request('sort', 'newest')) !== 'newest')<input type="hidden" name="sort" value="{{ $sort ?? request('sort', 'newest') }}">@endif
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

        <footer class="store-footer">
            <a class="store-footer__wordmark" href="{{ route('storefront.index') }}">VISION</a>
            <span>انتخاب دقیق‌تر، شلوغی کمتر</span>
            <span>مجموعهٔ منتخب VISION</span>
        </footer>

        <div class="store-toast" data-store-toast role="status" aria-live="polite"></div>
        <button class="store-sidebar-backdrop" type="button" data-store-filter-backdrop aria-label="بستن فیلترها" tabindex="-1"></button>
    </div>
</body>
</html>
