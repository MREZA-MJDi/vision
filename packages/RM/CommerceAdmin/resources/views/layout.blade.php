<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('commerce-admin.store_name', config('app.name', 'Shadow Outlet')) }}</title>
    @vite(['packages/RM/CommerceAdmin/resources/css/admin-kit.css', 'packages/RM/CommerceAdmin/resources/js/admin-kit.js'])
    @stack('head')
</head>
<body class="admin-kit">
<div class="ak-app" data-sidebar-root>
    <aside class="ak-sidebar" id="adminSidebar">
        <a class="ak-brand" href="{{ route('commerce-admin.dashboard') }}">
            <span class="ak-brand-mark" aria-hidden="true">◈</span>
            <span>{{ config('commerce-admin.brand_name', 'Shadow UI Kit') }}</span>
            <span class="ak-brand-chevron">⌄</span>
        </a>

        <label class="ak-search-side">
            <span aria-hidden="true">⌕</span>
            <input type="search" placeholder="Search..." aria-label="Search admin pages" data-nav-search>
            <kbd>⌘K</kbd>
        </label>

        <nav class="ak-nav" aria-label="Primary navigation">
            <div class="ak-nav-caption">Dashboards</div>
            <a class="ak-nav-link {{ request()->routeIs('commerce-admin.dashboard') ? 'is-active' : '' }}" href="{{ route('commerce-admin.dashboard') }}">
                <span class="ak-nav-icon">◫</span><span>Classic Dashboard</span>
            </a>

            <button class="ak-nav-group {{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*') ? 'is-open' : '' }}" type="button" data-nav-toggle aria-expanded="{{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*') ? 'true' : 'false' }}">
                <span class="ak-nav-icon">▦</span><span>E-Commerce</span><span class="ak-nav-caret">⌄</span>
            </button>
            <div class="ak-nav-submenu" data-nav-submenu>
                <a class="{{ request()->routeIs('commerce-admin.analytics') ? 'is-active' : '' }}" href="{{ route('commerce-admin.analytics') }}">Dashboard</a>
                <a class="{{ request()->routeIs('commerce-admin.products.index') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.index') }}">Product List</a>
                <a class="{{ request()->routeIs('commerce-admin.products.create','commerce-admin.products.edit') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.create') }}">Add Product</a>
                <a class="{{ request()->routeIs('commerce-admin.products.show') ? 'is-active' : '' }}" href="{{ request()->routeIs('commerce-admin.products.show') ? url()->current() : route('commerce-admin.products.index') }}">Product Detail</a>
                <a class="{{ request()->routeIs('commerce-admin.orders.index','commerce-admin.orders.create') ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index') }}">Order List</a>
                <a class="{{ request()->routeIs('commerce-admin.orders.show') ? 'is-active' : '' }}" href="{{ request()->routeIs('commerce-admin.orders.show') ? url()->current() : route('commerce-admin.orders.index') }}">Order Detail</a>
            </div>

            <a class="ak-nav-link {{ request()->query('panel') === 'payments' ? 'is-active' : '' }}" href="{{ route('commerce-admin.analytics', ['panel' => 'payments']) }}">
                <span class="ak-nav-icon">◷</span><span>Payment Dashboard</span>
            </a>
            <button class="ak-nav-link ak-nav-disabled" type="button" disabled title="This application is configured for commerce management">
                <span class="ak-nav-icon">⌂</span><span>Hotel Dashboard</span>
            </button>
            <button class="ak-nav-link ak-nav-disabled" type="button" disabled title="Project management is not part of the commerce module">
                <span class="ak-nav-icon">▤</span><span>Project Management</span>
            </button>
            <button class="ak-nav-link ak-nav-disabled" type="button" disabled title="Real estate is not part of the commerce module">
                <span class="ak-nav-icon">⌂</span><span>Real Estate</span>
            </button>
        </nav>

        <div class="ak-sidebar-bottom">
            <div class="ak-plan-card">
                <div class="ak-plan-icon">↗</div>
                <strong>Commerce Admin Kit</strong>
                <p>Reusable tools for your store, backed by your real catalog and orders.</p>
                <a href="{{ route('commerce-admin.analytics') }}">View store insights <span>↗</span></a>
            </div>
            <div class="ak-sidebar-footer">
                <span class="ak-avatar ak-avatar-sm">{{ strtoupper(substr(auth(config('commerce-admin.guard', 'web'))->user()?->name ?? 'A', 0, 1)) }}</span>
                <span class="ak-footer-user"><strong>{{ auth(config('commerce-admin.guard', 'web'))->user()?->name }}</strong><small>Administrator</small></span>
                <form method="POST" action="{{ route('commerce-admin.logout') }}">
                    @csrf
                    <button class="ak-icon-button" type="submit" title="Sign out" aria-label="Sign out">⇥</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="ak-main-wrap">
        <header class="ak-topbar">
            <button class="ak-icon-button ak-mobile-menu" type="button" aria-label="Open navigation" data-sidebar-open>☰</button>
            <div class="ak-store-switch">
                <span class="ak-live-dot"></span>
                <span>{{ config('commerce-admin.store_name', config('app.name', 'Shadow Outlet')) }}</span>
                <span class="ak-chevron">⌄</span>
            </div>
            <div class="ak-topbar-right">
                <span class="ak-topbar-link" title="Module is installed locally">Module active <span class="ak-status-dot"></span></span>
                <button class="ak-icon-button" type="button" title="Notifications" aria-label="Notifications">♧</button>
                <button class="ak-icon-button" type="button" title="Help" aria-label="Help" data-help-open>?</button>
                <span class="ak-topbar-divider"></span>
                <span class="ak-avatar">{{ strtoupper(substr(auth(config('commerce-admin.guard', 'web'))->user()?->name ?? 'A', 0, 1)) }}</span>
            </div>
        </header>

        <main class="ak-content">
            @if (session('status'))
                <div class="ak-flash ak-flash-success" role="status"><span>✓</span>{{ session('status') }}<button type="button" data-dismiss-flash aria-label="Dismiss">×</button></div>
            @endif
            @if ($errors->any())
                <div class="ak-flash ak-flash-error" role="alert"><strong>Please review the form.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" data-dismiss-flash aria-label="Dismiss">×</button></div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<div class="ak-sidebar-scrim" data-sidebar-close></div>
<div class="ak-help-popover" data-help-panel hidden>
    <button type="button" class="ak-icon-button" data-help-close aria-label="Close help">×</button>
    <strong>Commerce Admin Kit</strong>
    <p>Catalog, stock, orders and reporting are connected to the configured Laravel models. No template demo records are seeded.</p>
    <a href="{{ route('commerce-admin.analytics') }}">Open store analytics →</a>
</div>
@stack('scripts')
</body>
</html>
