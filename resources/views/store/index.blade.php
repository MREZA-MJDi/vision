@extends('layouts.store')

@section('title', 'VISION — محصولات و استایل‌ها')
@section('meta-description', 'تازه‌های VISION را ببین، فیلتر کن و با اتاق پرو استودیویی ترکیب‌های پیشنهادی را امتحان کن.')
@section('canonical', route('shop.index'))

@section('content')
<header class="vision-shop-header">
    <nav class="vision-shop-nav" aria-label="ناوبری اصلی">
        <a href="{{ route('storefront.index') }}">خانه</a>
        <a href="{{ route('shop.index') }}">فروشگاه</a>
        <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog">تازه‌ها</a>
        <a href="{{ route('shop.index', ['sale' => 1]) }}#catalog">حراج</a>
    </nav>
    <a class="vision-wordmark" href="{{ route('storefront.index') }}" aria-label="VISION صفحهٔ اصلی">VISION</a>
    <div class="vision-shop-tools">
        <button class="ibtn" id="theme" type="button" aria-label="تغییر تم">◐</button>
        <a class="ibtn" id="cartTarget" href="{{ route('cart.index') }}" aria-label="سبد خرید">🛒<span class="badge" id="cnt">۰</span></a>
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
        <span>اتاق پرو</span>
        <button id="close" type="button" aria-label="بستن اتاق پرو">×</button>
    </h4>
    <p>روی چوب‌لباسی هر محصول بزن تا روی مانکن بپوشه. بالاپوش و پایین‌تنه رو می‌تونی توی یک ست امتحان کنی.</p>
    <div class="stage" id="stage" role="img" aria-label="پیش‌نمایش زندهٔ مانکن و لباس‌ها">
        <div class="load" id="load" aria-live="polite"><div class="spin"></div><b>در حال پوشاندن مدل</b><span>ترکیب در حال آماده‌شدن است</span></div>
    </div>
    <div class="pk"><span>انتخاب‌های تو</span><span id="pcount">۰ تکه</span></div>
    <div class="picks" id="picks" aria-label="لباس‌های انتخاب‌شده"></div>

    <section class="fit-recommendations" aria-labelledby="fit-recommend-title">
        <div class="fit-section-head">
            <div>
                <h5 id="fit-recommend-title">با این ست کن</h5>
                <p id="fit-recommend-caption">برای شروع، یک لباس انتخاب کن.</p>
            </div>
        </div>
        <div class="fit-rec-tabs" role="group" aria-label="نوع لباس پیشنهادی">
            <button type="button" data-rec-slot="top" aria-pressed="true">بالاپوش</button>
            <button type="button" data-rec-slot="bottom" aria-pressed="false">پایین‌تنه</button>
        </div>
        <div class="fit-recommend-grid" id="fit-recommend-grid" aria-live="polite"></div>
    </section>

    <section class="fit-compare" aria-labelledby="fit-compare-title">
        <div class="fit-section-head fit-compare-head">
            <div>
                <h5 id="fit-compare-title">مقایسه‌ی استایل‌ها</h5>
                <p>دو ترکیب را ذخیره کن و قیمتشان را کنار هم ببین.</p>
            </div>
            <button type="button" id="clearCompare" aria-label="پاک کردن استایل‌های مقایسه">پاک‌کردن</button>
        </div>
        <div class="fit-compare-actions">
            <button type="button" id="saveCompareA">ذخیره‌ی ترکیب A</button>
            <button type="button" id="saveCompareB">ذخیره‌ی ترکیب B</button>
        </div>
        <div class="fit-compare-grid" id="fit-compare-grid" aria-live="polite"></div>
    </section>

    <div class="tot" id="tot">هنوز چیزی انتخاب نشده</div>
    <div class="btns"><button class="pri" id="addset" type="button">افزودن ست به سبد</button><button id="strip" type="button">شروع دوباره</button></div>
</aside>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
window.SHOP = @json($shop);
</script>
@endsection
