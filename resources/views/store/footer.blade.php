<footer class="vision-footer" aria-label="پاورقی فروشگاه VISION">
    <div class="vision-footer__main">
        <div class="vision-footer__intro">
            <a class="vision-footer__wordmark" href="{{ route('storefront.index') }}" aria-label="صفحهٔ اصلی VISION">VISION</a>
            <p>فروشگاه پوشاک و اکسسوری</p>
            <a class="vision-footer__contact" href="{{ route('contact.index') }}">تماس با ما</a>
        </div>
        <nav aria-label="راهنمای فروشگاه">
            <h2>فروشگاه</h2>
            <a href="{{ route('shop.index') }}#catalog">همهٔ محصولات</a>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog">تازه‌ها</a>
            <a href="{{ route('shop.index', ['sale' => 1]) }}#catalog">تخفیف‌ها</a>
            <a href="{{ route('shop.index') }}#catalog">اتاق پرو استودیویی</a>
        </nav>
        <nav aria-label="راهنمای سفارش و حساب">
            <h2>سفارش و حساب</h2>
            <a href="{{ route('cart.index') }}">سبد خرید</a>
            <a href="{{ route('account.show') }}">سفارش‌های من</a>
            @guest
                <a href="{{ route('login') }}">ورود به حساب</a>
                <a href="{{ route('register') }}">ساخت حساب</a>
            @endguest
        </nav>
        @include('store.trust-slots')
    </div>
    <div class="vision-footer__bottom">
        <span>© {{ \App\Support\Money::persianDigits((string) now()->year) }} VISION</span>
        <span>تمام حقوق محفوظ است.</span>
    </div>
</footer>
