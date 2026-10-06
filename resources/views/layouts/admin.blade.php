<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'پنل مدیریت Vision')</title>

    @vite([
        'resources/css/admin.css',
        'resources/css/admin-responsive.css',
        'resources/js/admin.js',
    ])

    @stack('styles')
</head>

<body class="admin-body">

<div class="admin-shell">

    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-brand">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand__main">
                <span class="admin-brand__name">VISION</span>
                <span class="admin-brand__sub">ADMIN / STORE</span>
            </a>

            <span class="admin-brand__status">
                <i aria-hidden="true"></i>
                LIVE
            </span>
        </div>

        <nav class="admin-menu" aria-label="منوی مدیریت">

            <span class="admin-menu-label">نمای کلی</span>

            <a
                href="{{ route('admin.dashboard') }}"
                class="admin-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
            >
                <span class="admin-link-icon">⌂</span>
                <span>داشبورد</span>
            </a>

            <span class="admin-menu-label">فروشگاه</span>

            <a
                href="{{ route('admin.products.index') }}"
                class="admin-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">◈</span>
                <span>محصولات</span>
            </a>

            <a
                href="{{ route('admin.categories.index') }}"
                class="admin-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">▦</span>
                <span>دسته‌بندی‌ها</span>
            </a>

            <a
                href="{{ route('admin.brands.index') }}"
                class="admin-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">◇</span>
                <span>برندها</span>
            </a>

            <a
                href="{{ route('admin.customers.index') }}"
                class="admin-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">◉</span>
                <span>مشتریان</span>
            </a>

            <a
                href="{{ route('admin.orders.index') }}"
                class="admin-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">◫</span>
                <span>سفارش‌ها</span>
            </a>

            <a
                href="{{ route('admin.wholesale.index') }}"
                class="admin-link {{ request()->routeIs('admin.wholesale.*') || request()->routeIs('admin.customers.wholesale.*') || request()->routeIs('admin.customers.cheque.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">ع</span>
                <span>عمده و مجوز چک</span>
            </a>

            <a
                href="{{ route('admin.wholesale-packs.index') }}"
                class="admin-link {{ request()->routeIs('admin.wholesale-packs.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">▤</span>
                <span>پک‌های عمده</span>
            </a>

            <a
                href="{{ route('admin.cheques.index') }}"
                class="admin-link {{ request()->routeIs('admin.cheques.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">▣</span>
                <span>پرداخت‌های چکی</span>
            </a>

            <a
                href="{{ route('admin.contact.index') }}"
                class="admin-link {{ request()->routeIs('admin.contact.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">✉</span>
                <span>پیام‌ها</span>
            </a>

            <a
                href="{{ route('admin.content.about') }}"
                class="admin-link {{ request()->routeIs('admin.content.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">✎</span>
                <span>محتوای سایت</span>
            </a>

            <span class="admin-menu-label">عملیات</span>

            <a
                href="{{ route('admin.inventory.index') }}"
                class="admin-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">▤</span>
                <span>انبار</span>
            </a>

            <a
                href="{{ route('admin.nila.index') }}"
                class="admin-link {{ request()->routeIs('admin.nila.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">N</span>
                <span>نیلا</span>
            </a>

            <a
                href="{{ route('admin.accounting.index') }}"
                class="admin-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}"
            >
                <span class="admin-link-icon">₮</span>
                <span>حسابداری</span>
            </a>

            </nav>

        <div class="admin-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="admin-side-action admin-side-action--logout">
                    <span>↪</span>
                    خروج از حساب
                </button>
            </form>
        </div>
    </aside>

    <button
        type="button"
        class="admin-sidebar-backdrop"
        data-admin-sidebar-backdrop
        aria-label="بستن منوی مدیریت"
        tabindex="-1"
    ></button>

    <main class="admin-main">

        <header class="admin-header">
            <div class="admin-header__right">
                <button
                    type="button"
                    class="admin-menu-toggle"
                    data-admin-menu
                    aria-label="باز کردن منوی مدیریت"
                    aria-expanded="false"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <button
                    type="button"
                    class="admin-header-back"
                    onclick="if (history.length > 1) history.back(); else window.location='{{ route('admin.dashboard') }}';"
                    aria-label="بازگشت"
                >
                    ←
                    <span>بازگشت</span>
                </button>

                <div>
                    <div class="admin-header__title">
                        @yield('page-title', 'مدیریت فروشگاه')
                    </div>

                    <div class="admin-header__subtitle">
                        کنترل، تحلیل و مدیریت Vision
                    </div>
                </div>
            </div>

            <div class="admin-header__left">
                <a href="{{ route('home') }}" class="admin-header-link">
                    فروشگاه
                    <span aria-hidden="true">↗</span>
                </a>

                <a
                    href="{{ route('admin.profile.edit') }}"
                    class="admin-user admin-user--link"
                    title="پروفایل مدیر"
                >
                    <div class="admin-user__avatar" aria-hidden="true">
                        {{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>

                    <div class="admin-user__meta">
                        <span class="admin-user__name">
                            {{ auth()->user()->name ?? 'Admin' }}
                        </span>
                        <span class="admin-user__role">مدیر فروشگاه</span>
                    </div>
                </a>
            </div>
        </header>

        @if(session('success'))
            <div class="alert success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert error">
                {{ session('error') }}
            </div>
        @endif

        <section class="admin-content">
            @yield('content')
        </section>
    </main>
</div>

<x-mobile-bottom-nav context="admin" />

@stack('scripts')

</body>
</html>
