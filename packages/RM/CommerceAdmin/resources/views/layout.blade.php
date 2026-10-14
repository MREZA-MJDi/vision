<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('commerce-admin.store_name', config('app.name', 'VISION')) }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['packages/RM/CommerceAdmin/resources/css/admin-kit.css', 'packages/RM/CommerceAdmin/resources/js/admin-kit.js'])
    @endif
    @stack('head')
</head>
<body class="admin-kit" data-currency-symbol="{{ config('commerce-admin.currency_symbol', '$') }}">
<div class="ak-app" data-sidebar-root>
    <aside class="ak-sidebar" id="adminSidebar">
        <a class="ak-brand" href="{{ route('commerce-admin.dashboard') }}" aria-label="{{ config('commerce-admin.brand_name', 'VISION Commerce') }} dashboard">
            <span class="ak-brand-mark" aria-hidden="true">◈</span>
            <span class="ak-brand-name">{{ config('commerce-admin.brand_name', 'VISION Commerce') }}</span>
            <span class="ak-brand-chevron" aria-hidden="true">⌄</span>
        </a>

        <label class="ak-search-side">
            <span aria-hidden="true">⌕</span>
            <input type="search" placeholder="جست‌وجو..." aria-label="جست‌وجوی صفحات مدیریت" data-nav-search>
            <kbd>⌘ K</kbd>
        </label>

        <nav class="ak-nav" aria-label="ناوبری اصلی">
<div class="ak-nav-caption">داشبورد</div>
<a class="ak-nav-link {{ request()->routeIs('commerce-admin.dashboard') ? 'is-active' : '' }}" href="{{ route('commerce-admin.dashboard') }}"><span class="ak-nav-icon" aria-hidden="true">▦</span><span>داشبورد اصلی</span></a>
<button class="ak-nav-group {{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*','commerce-admin.categories.*','commerce-admin.brands.*') ? 'is-open' : '' }}" type="button" data-nav-toggle aria-expanded="{{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*','commerce-admin.categories.*','commerce-admin.brands.*') ? 'true' : 'false' }}"><span class="ak-nav-icon" aria-hidden="true">▤</span><span>فروشگاه</span><span class="ak-nav-caret" aria-hidden="true">⌄</span></button>
<div class="ak-nav-submenu" data-nav-submenu>
<a class="{{ request()->routeIs('commerce-admin.analytics') ? 'is-active' : '' }}" href="{{ route('commerce-admin.analytics') }}">گزارش فروشگاه</a>
<a class="{{ request()->routeIs('commerce-admin.products.index') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.index') }}">فهرست محصولات</a>
<a class="{{ request()->routeIs('commerce-admin.products.show','commerce-admin.products.edit') ? 'is-active' : '' }}" href="{{ request()->routeIs('commerce-admin.products.show','commerce-admin.products.edit') ? url()->current() : route('commerce-admin.products.index') }}">جزئیات محصول</a>
<a class="{{ request()->routeIs('commerce-admin.products.create') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.create') }}">افزودن محصول</a>
<a class="{{ request()->routeIs('commerce-admin.categories.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.categories.index') }}">دسته‌بندی‌ها</a>
<a class="{{ request()->routeIs('commerce-admin.brands.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.brands.index') }}">برندها</a>
<a class="{{ request()->routeIs('commerce-admin.orders.index','commerce-admin.orders.create') ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index') }}">فهرست سفارش‌ها</a>
<a class="{{ request()->routeIs('commerce-admin.orders.show') ? 'is-active' : '' }}" href="{{ request()->routeIs('commerce-admin.orders.show') ? url()->current() : route('commerce-admin.orders.index') }}">جزئیات سفارش</a>
</div>
<div class="ak-nav-caption">مدیریت عملیات</div>
<a class="ak-nav-link {{ request()->routeIs('commerce-admin.payments.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.payments.index') }}"><span class="ak-nav-icon" aria-hidden="true">◷</span><span>پرداخت‌ها</span></a>
<a class="ak-nav-link {{ request()->routeIs('commerce-admin.inventory.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.inventory.index') }}"><span class="ak-nav-icon" aria-hidden="true">▣</span><span>انبار و موجودی</span></a>
<a class="ak-nav-link {{ request()->routeIs('commerce-admin.customers.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.customers.index') }}"><span class="ak-nav-icon" aria-hidden="true">♙</span><span>مشتری‌ها</span></a>
<a class="ak-nav-link {{ request()->routeIs('commerce-admin.reviews.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index') }}"><span class="ak-nav-icon" aria-hidden="true">★</span><span>نظرات مشتریان</span></a>
</nav>

        <div class="ak-sidebar-bottom">
            <div class="ak-plan-card">
                <div class="ak-plan-icon" aria-hidden="true">✦</div>
                <strong>گزارش‌های فروشگاه</strong>
                <p>محصولات، سفارش‌ها، مشتری‌ها و موجودی از دیتابیس واقعی خوانده می‌شوند.</p>
                <a href="{{ route('commerce-admin.analytics') }}">مشاهده گزارش‌ها <span aria-hidden="true">↗</span></a>
            </div>
            <div class="ak-sidebar-footer">
                <span class="ak-avatar ak-avatar-sm">{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
                <span class="ak-footer-user">
                    <strong>{{ auth()->user()?->name ?? 'Administrator' }}</strong>
                    <small>مدیر فروشگاه</small>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="ak-icon-button" type="submit" title="خروج" aria-label="خروج">⇥</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="ak-main-wrap">
        <header class="ak-topbar">
            <button class="ak-icon-button ak-mobile-menu" type="button" aria-label="بازکردن منو" data-sidebar-open>☰</button>
            <div class="ak-store-switch">
                <span class="ak-live-dot" aria-hidden="true"></span>
                <span>{{ config('commerce-admin.store_name', config('app.name', 'VISION')) }}</span>
                <span class="ak-chevron" aria-hidden="true">⌄</span>
            </div>
            <div class="ak-topbar-right">
                <a class="ak-topbar-link" href="{{ route('commerce-admin.analytics.export') }}">دریافت گزارش <span aria-hidden="true">↗</span></a>
                <button class="ak-icon-button" type="button" data-help-open aria-label="Open help" title="Help">?</button>
                <span class="ak-topbar-divider"></span>
                <span class="ak-avatar" aria-label="مدیر فروشگاه">{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
            </div>
        </header>

        <main class="ak-content">
            @if (session('status'))
                <div class="ak-flash ak-flash-success" role="status">
                    <span aria-hidden="true">✓</span>{{ session('status') }}
                    <button type="button" data-dismiss-flash aria-label="بستن">×</button>
                </div>
            @endif
            @if ($errors->any())
                <div class="ak-flash ak-flash-error" role="alert">
                    <strong>لطفاً خطاهای فرم را بررسی کنید.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    <button type="button" data-dismiss-flash aria-label="Dismiss">×</button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<div class="ak-sidebar-scrim" data-sidebar-close></div>
<div class="ak-help-popover" data-help-panel hidden>
    <button type="button" class="ak-icon-button" data-help-close aria-label="Close help">×</button>
    <strong>پنل مدیریت فروشگاه</strong>
    <p>محصولات، موجودی، گردش سفارش و گزارش‌ها از مدل‌های Laravel خوانده می‌شوند. اتصال درگاه پرداخت مستقل از این پنل است.</p>
    <a href="{{ route('commerce-admin.analytics') }}">مشاهده گزارش فروشگاه →</a>
</div>
@stack('scripts')
</body>
</html>
