<section class="home-content-section home-identity" aria-labelledby="home-identity-title">
    <span class="home-editorial-kicker" lang="en" dir="ltr">VISION / EVERYDAY STYLE</span>
    <h2 id="home-identity-title">برای استایلی که شبیه خودت است.</h2>
    <p>از لباس‌های روزمره تا انتخاب‌های متفاوت؛ مجموعه‌ها را بر اساس سلیقه‌ات مرور کن و جزئیات هر محصول را پیش از خرید ببین.</p>
    <nav class="home-identity-links" aria-label="مسیرهای خرید">
        <a href="{{ route('shop.index') }}">همه محصولات</a>
        <a href="{{ route('shop.index', ['sort' => 'newest']) }}">تازه‌ها</a>
        <a href="{{ route('shop.index', ['sale' => 1]) }}">تخفیف‌ها</a>
    </nav>
</section>
