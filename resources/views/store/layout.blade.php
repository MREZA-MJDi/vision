<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VISION — Wear Your Point of View')</title>
    <meta name="description" content="@yield('description', 'Discover statement fashion, modern footwear and bags curated for your own point of view.')">
    <meta name="theme-color" content="#f4f0e9">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/storefront.css', 'resources/js/app.js', 'resources/js/storefront.js'])
    @stack('head')
</head>
<body id="top" class="storefront-body">
    <div class="announcement-bar"><span>THE NEW SEASON HAS ARRIVED</span><span class="announcement-bar__center">COMPLIMENTARY SHIPPING ON ORDERS OVER $150</span><span>DISCOVER YOUR NEXT ICON&nbsp; ↗</span></div>
    <header class="store-header" data-store-header>
        <button class="store-header__menu" type="button" data-menu-toggle aria-expanded="false" aria-label="Open menu"><span></span><span></span></button>
        <nav class="store-nav" data-store-nav aria-label="Main navigation">
            <a href="{{ route('products.index') }}">SHOP ALL</a>
            <a href="{{ route('products.index', ['sort' => 'newest']) }}">NEW IN</a>
            <a href="{{ route('products.index') }}">SHOES</a>
            <a href="{{ route('products.index') }}">BAGS</a>
            <a href="{{ route('products.index') }}">CLOTHING</a>
        </nav>
        <a class="wordmark" href="{{ route('home') }}" aria-label="Vision home">VISION<span>®</span><small>WEAR YOUR POINT OF VIEW</small></a>
        <div class="store-tools">
            <a href="{{ route('products.index') }}" class="store-tools__search" aria-label="Search products"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg><span>SEARCH</span></a>
            <a href="{{ route('login') }}" class="store-tools__account">ACCOUNT</a>
            <a href="{{ route('products.index') }}" class="store-tools__bag">BAG <span aria-label="cart count">0</span></a>
        </div>
    </header>
    <main>@yield('content')</main>
    <footer class="store-footer">
        <div class="store-footer__top"><a class="wordmark wordmark--footer" href="{{ route('home') }}">VISION<span>®</span><small>WEAR YOUR POINT OF VIEW</small></a><p>PERSONALITY FIRST.<br>EVERYTHING ELSE, SECOND.</p><a class="back-top" href="#top" data-back-top>BACK TO TOP ↑</a></div>
        <div class="store-footer__links"><div><span class="footer-label">DISCOVER</span><a href="{{ route('products.index') }}">Shop all</a><a href="{{ route('products.index', ['sort' => 'newest']) }}">New in</a><a href="{{ route('products.index') }}">The selection</a></div><div><span class="footer-label">HERE TO HELP</span><a href="{{ route('login') }}">Account</a><a href="mailto:hello@vision.example">Contact</a><a href="{{ route('products.index') }}">Shipping & returns</a></div><div class="footer-note"><span class="footer-label">A DIFFERENT POINT OF VIEW.</span><p>Curated pieces. Considered details.<br>Made to feel like you.</p></div></div>
        <div class="store-footer__bottom"><span>© {{ date('Y') }} VISION STUDIO</span><span>DESIGNED TO BE YOURS.</span><a href="{{ route('login') }}">ADMIN ↗</a></div>
    </footer>
</body>
</html>
