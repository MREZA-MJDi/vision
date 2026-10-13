<!doctype html>
<html lang="en">
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
        <a class="ak-brand" href="{{ route('commerce-admin.dashboard') }}">
            <span class="ak-brand-mark" aria-hidden="true">◈</span>
            <span>{{ config('commerce-admin.brand_name', 'VISION Commerce') }}</span>
            <span class="ak-brand-chevron" aria-hidden="true">⌄</span>
        </a>

        <label class="ak-search-side">
            <span aria-hidden="true">⌕</span>
            <input type="search" placeholder="Search admin pages" aria-label="Search admin pages" data-nav-search>
            <kbd>⌘K</kbd>
        </label>

        <nav class="ak-nav" aria-label="Primary navigation">
            <div class="ak-nav-caption">Dashboard</div>
            <a class="ak-nav-link {{ request()->routeIs('commerce-admin.dashboard') ? 'is-active' : '' }}"
               href="{{ route('commerce-admin.dashboard') }}">
                <span class="ak-nav-icon" aria-hidden="true">◫</span><span>Overview</span>
            </a>

            <button class="ak-nav-group {{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*','commerce-admin.reviews.*') ? 'is-open' : '' }}"
                    type="button" data-nav-toggle
                    aria-expanded="{{ request()->routeIs('commerce-admin.analytics','commerce-admin.products.*','commerce-admin.orders.*','commerce-admin.reviews.*') ? 'true' : 'false' }}">
                <span class="ak-nav-icon" aria-hidden="true">▦</span><span>E-Commerce</span><span class="ak-nav-caret" aria-hidden="true">⌄</span>
            </button>
            <div class="ak-nav-submenu" data-nav-submenu>
                <a class="{{ request()->routeIs('commerce-admin.analytics') ? 'is-active' : '' }}" href="{{ route('commerce-admin.analytics') }}">Analytics</a>
                <a class="{{ request()->routeIs('commerce-admin.products.index') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.index') }}">Products</a>
                <a class="{{ request()->routeIs('commerce-admin.products.create') ? 'is-active' : '' }}" href="{{ route('commerce-admin.products.create') }}">Add product</a>
                <a class="{{ request()->routeIs('commerce-admin.products.show') ? 'is-active' : '' }}"
                   href="{{ request()->routeIs('commerce-admin.products.show') ? url()->current() : route('commerce-admin.products.index') }}">Product detail</a>
                <a class="{{ request()->routeIs('commerce-admin.orders.index','commerce-admin.orders.create') ? 'is-active' : '' }}" href="{{ route('commerce-admin.orders.index') }}">Orders</a>
                <a class="{{ request()->routeIs('commerce-admin.orders.show') ? 'is-active' : '' }}"
                   href="{{ request()->routeIs('commerce-admin.orders.show') ? url()->current() : route('commerce-admin.orders.index') }}">Order detail</a>
                <a class="{{ request()->routeIs('commerce-admin.reviews.*') ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index') }}">Reviews</a>
            </div>

            <a class="ak-nav-link {{ request()->routeIs('commerce-admin.analytics') && request()->query('panel') === 'payments' ? 'is-active' : '' }}"
               href="{{ route('commerce-admin.analytics', ['panel' => 'payments']) }}">
                <span class="ak-nav-icon" aria-hidden="true">◷</span><span>Payment analytics</span>
            </a>
            <button class="ak-nav-link ak-nav-disabled" type="button" disabled title="Not included in the commerce module">
                <span class="ak-nav-icon" aria-hidden="true">⌂</span><span>Hotel dashboard</span>
            </button>
            <button class="ak-nav-link ak-nav-disabled" type="button" disabled title="Not included in the commerce module">
                <span class="ak-nav-icon" aria-hidden="true">▤</span><span>Project management</span>
            </button>
        </nav>

        <div class="ak-sidebar-bottom">
            <div class="ak-plan-card">
                <div class="ak-plan-icon" aria-hidden="true">↗</div>
                <strong>Commerce Admin Kit</strong>
                <p>Catalog, orders and reporting connected to your store database.</p>
                <a href="{{ route('commerce-admin.analytics') }}">View store insights <span aria-hidden="true">↗</span></a>
            </div>
            <div class="ak-sidebar-footer">
                <span class="ak-avatar ak-avatar-sm">{{ strtoupper(substr(auth(config('commerce-admin.guard', 'web'))->user()?->name ?? 'A', 0, 1)) }}</span>
                <span class="ak-footer-user">
                    <strong>{{ auth(config('commerce-admin.guard', 'web'))->user()?->name ?? 'Administrator' }}</strong>
                    <small>Administrator</small>
                </span>
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
                <span class="ak-live-dot" aria-hidden="true"></span>
                <span>{{ config('commerce-admin.store_name', config('app.name', 'VISION')) }}</span>
                <span class="ak-chevron" aria-hidden="true">⌄</span>
            </div>
            <div class="ak-topbar-right">
                <span class="ak-topbar-link" title="Commerce module loaded">Commerce active <span class="ak-status-dot"></span></span>
                <span class="ak-topbar-divider"></span>
                <span class="ak-avatar" aria-label="Administrator">{{ strtoupper(substr(auth(config('commerce-admin.guard', 'web'))->user()?->name ?? 'A', 0, 1)) }}</span>
            </div>
        </header>

        <main class="ak-content">
            @if (session('status'))
                <div class="ak-flash ak-flash-success" role="status">
                    <span aria-hidden="true">✓</span>{{ session('status') }}
                    <button type="button" data-dismiss-flash aria-label="Dismiss">×</button>
                </div>
            @endif
            @if ($errors->any())
                <div class="ak-flash ak-flash-error" role="alert">
                    <strong>Please review the form.</strong>
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
    <strong>Commerce Admin Kit</strong>
    <p>Catalog, inventory records, orders and analytics use the configured Laravel models. Online payment gateways are not enabled by this module.</p>
    <a href="{{ route('commerce-admin.analytics') }}">Open store analytics →</a>
</div>
@stack('scripts')
</body>
</html>
