<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b090d">
    <meta name="description" content="@yield('meta-description', 'A considered edit of modern wardrobe essentials by VISION.')">
    <title>@yield('title', 'VISION — New Arrivals')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|space-grotesk:400,500,600,700" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/css/storefront.css', 'resources/js/storefront.js'])
    @endif
    @stack('styles')
</head>
<body class="storefront-body">
    <div class="store-atmosphere" aria-hidden="true"></div>

    <div class="store-shell" data-storefront>
        <header class="store-header">
            <nav class="store-nav" aria-label="Main navigation">
                <a href="{{ route('storefront.index') }}" class="store-nav__link {{ request()->query->count() === 0 ? 'is-current' : '' }}">Home</a>
                <a href="{{ route('storefront.index') }}#catalog" class="store-nav__link">Shop</a>
                <a href="{{ route('storefront.index', ['sort' => 'newest']) }}#catalog" class="store-nav__link">New arrivals</a>
                <a href="{{ route('storefront.index', ['sale' => 1]) }}#catalog" class="store-nav__link">Sale</a>
            </nav>

            <a class="store-wordmark" href="{{ route('storefront.index') }}" aria-label="VISION store home">STORE</a>

            <div class="store-header__actions">
                <form class="store-search" action="{{ route('storefront.index') }}" method="GET" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"></circle><path d="m16 16 4.2 4.2"></path></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search" aria-label="Search products">
                    @if($sort !== 'newest')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                    <button type="submit" aria-label="Submit search">↵</button>
                </form>

                <a class="store-icon-button" href="{{ route('login') }}" aria-label="Account" title="Account">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.3"></circle><path d="M5.3 20c.5-3.4 2.8-5.3 6.7-5.3s6.2 1.9 6.7 5.3"></path></svg>
                </a>
                <button class="store-icon-button store-mobile-filter" type="button" data-store-filter-toggle aria-controls="store-sidebar" aria-expanded="false" aria-label="Open filters">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4"></path></svg>
                </button>
            </div>
        </header>

        @yield('content')

        <footer class="store-footer">
            <a class="store-footer__wordmark" href="{{ route('storefront.index') }}">VISION</a>
            <span>Less noise. Better pieces.</span>
            <a href="{{ route('login') }}">Account / Admin</a>
        </footer>

        <div class="store-toast" data-store-toast role="status" aria-live="polite"></div>
        <button class="store-sidebar-backdrop" type="button" data-store-filter-backdrop aria-label="Close filters" tabindex="-1"></button>
    </div>
</body>
</html>
