@extends('layouts.store')

@section('title', 'VISION — محصولات و استایل‌ها')
@section('meta-description', 'تازه‌های VISION را ببین، فیلتر کن و با اتاق پرو استودیویی ترکیب‌های پیشنهادی را امتحان کن.')
@section('canonical', route('shop.index'))

@section('content')
<header class="vision-shop-header">
    <nav class="vision-shop-nav" aria-label="ناوبری اصلی">
        <a href="{{ route('storefront.index') }}">خانه</a>
        <a href="{{ route('shop.index') }}">فروشگاه</a>
        <a href="{{ route('shop.index', ['sort' => 'new']) }}#catalog">تازه‌ها</a>
        <a href="{{ route('shop.index', ['sale' => 1]) }}#catalog">حراج</a>
    </nav>
    <a class="vision-wordmark" href="{{ route('storefront.index') }}" aria-label="VISION صفحهٔ اصلی">VISION</a>
    <div class="vision-shop-tools">
        <button class="ibtn" id="theme" type="button" aria-label="تغییر تم" title="تغییر تم">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 3a9 9 0 0 0 0 18z"></path></svg>
        </button>
        <a class="ibtn" href="{{ route('cart.index') }}" aria-label="سبد خرید" title="سبد خرید">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"></path><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2"></path></svg>
            <span class="badge" id="cnt">۰</span>
        </a>
    </div>
</header>

<main class="vision-shop-main" id="catalog">
    <aside class="shop-filter" aria-label="فیلترهای فروشگاه">
        <h3>فیلترها <button id="reset" type="button">حذف فیلترها</button></h3>
        <input class="shop-search" id="q" value="{{ $shop['initialQuery'] ?? '' }}" placeholder="جستجوی محصول…" aria-label="جستجوی محصول" autocomplete="off">
        <h3>دسته‌بندی</h3>
        <div id="cats" class="shop-categories" aria-label="دسته‌بندی‌ها"></div>
        <h3>حداکثر قیمت</h3>
        <input type="range" id="pr" min="{{ $shop['min'] }}" max="{{ $shop['max'] }}" step="100000" value="{{ $shop['max'] }}" aria-label="حداکثر قیمت">
        <div class="prv" id="prv"></div>
    </aside>

    <section class="shop-results" aria-labelledby="shop-title">
        <div class="shop-topline">
            <h1 id="shop-title">تازه‌ها <small id="n"></small></h1>
            <select id="sort" aria-label="مرتب‌سازی">
                <option value="new">جدیدترین</option>
                <option value="lo">ارزان‌ترین</option>
                <option value="hi">گران‌ترین</option>
                <option value="rt">بیشترین امتیاز</option>
            </select>
        </div>
        <div class="shop-grid" id="grid" aria-live="polite"></div>
    </section>
</main>

<aside id="fit" aria-label="اتاق پرو استودیویی VISION" aria-hidden="true">
    <h4>
        <span class="fit-brand"><span class="fit-brand-mark" aria-hidden="true">V.</span><span>اتاق پرو <small>VISION FIT STUDIO</small></span></span>
        <button id="close" type="button" aria-label="بستن اتاق پرو">×</button>
    </h4>
    <p class="fit-copy">با چوب‌لباسی لباس را روی مانکن ببین؛ تکه‌های ست و پیشنهاد استایل با همان انیمیشن زنده نمایش داده می‌شوند.</p>
    <div class="stage" id="stage" role="img" aria-label="پیش‌نمایش زندهٔ مانکن و لباس‌ها">
        <div class="stage-fallback" id="stageFallback"><span>V.</span><small>مدل مانکن از استودیو بارگذاری می‌شود</small></div>
        <div class="load" id="load" aria-live="polite"><div class="spin"></div><b>در حال پوشاندن مدل</b><span>ترکیب در حال آماده‌شدن است</span></div>
    </div>
    <div class="pk"><span>انتخاب‌های تو</span><span id="pcount">۰ تکه</span></div>
    <div class="picks" id="picks" aria-label="لباس‌های انتخاب‌شده"></div>
    <div class="tot" id="tot">هنوز چیزی انتخاب نشده</div>
    <button class="sg" id="sugg" type="button">دیدن ست پیشنهادی</button>
    <div class="btns"><button class="pri" id="addset" type="button">افزودن ست به سبد</button><button id="strip" type="button">شروع دوباره</button></div>
</aside>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
window.SHOP = @json($shop);
</script>
@endsection
