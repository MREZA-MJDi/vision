@extends('layouts.store')

@section('title', 'VISION — محصولات و استایل‌ها')
@section('meta-description', 'تازه‌های VISION را ببین، فیلتر کن و با اتاق پرو استودیویی ترکیب‌های پیشنهادی را امتحان کن.')
@section('canonical', route('shop.index'))

@section('content')
<header class="vision-shop-header">
    <nav class="vision-shop-nav" aria-label="ناوبری اصلی">
        <a href="{{ route('storefront.index') }}">خانه</a>
        <a href="{{ route('shop.index') }}" @unless($filters['sale']) aria-current="page" @endunless>فروشگاه</a>
        <a href="{{ route('shop.index', ['sort' => 'newest']) }}#catalog">تازه‌ها</a>
        <a href="{{ route('shop.index', ['sale' => 1]) }}#catalog" @if($filters['sale']) aria-current="page" @endif>حراج</a>
    </nav>
    <a class="vision-wordmark" href="{{ route('storefront.index') }}" aria-label="VISION صفحهٔ اصلی">VISION</a>
    <div class="vision-shop-tools">
        @if($studio['routes']['studio'])
            <button class="shop-open-fit" id="open-fit" type="button" aria-controls="fit">اتاق پرو</button>
        @endif
        <button class="ibtn" id="theme" type="button" aria-label="تم روشن" aria-pressed="false">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M19 5l-1.5 1.5m-11 11L5 19"/></svg>
        </button>
        <a class="ibtn" id="cartTarget" href="{{ route('cart.index') }}" aria-label="سبد خرید">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2"/></svg>
            <span class="badge" id="cnt" aria-hidden="true">۰</span>
        </a>
    </div>
</header>

<main class="vision-shop-main" id="catalog">
    <form id="shop-filters" class="shop-filter" action="{{ route('shop.index') }}" method="get" aria-label="فیلترهای فروشگاه">
        <div class="shop-filter-heading">
            <h2>فیلترها</h2>
            <button type="button" id="shop-filter-toggle" aria-controls="shop-filter-fields" aria-expanded="true" hidden>
                فیلترها <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <a href="{{ route('shop.index') }}">حذف فیلترها</a>
        </div>
        <div id="shop-filter-fields" class="shop-filter-fields">
            <label for="q">جست‌وجوی محصول</label>
            <input class="shop-search" type="search" id="q" name="q" value="{{ $filters['q'] }}" maxlength="120">

            <label for="cats">دسته‌بندی</label>
            <select id="cats" name="category">
                <option value="">همه دسته‌ها</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->name }}</option>
                @endforeach
            </select>

            <label for="pr">حداکثر قیمت</label>
            <input type="range" id="pr" name="price_max" min="0" max="{{ $filters['max'] }}" step="1" value="{{ $filters['priceMax'] }}">
            <output class="prv" id="prv" for="pr">@money($filters['priceMax'])</output>

            <label class="shop-sale"><input type="checkbox" name="sale" value="1" @checked($filters['sale'])> فقط تخفیف‌ها</label>
            <button class="shop-apply" type="submit">اعمال فیلترها</button>
        </div>
    </form>

    <section class="shop-results" aria-labelledby="shop-title">
        <div class="shop-topline">
            <h1 id="shop-title">فروشگاه <small>{{ \App\Support\Money::number($products->total()) }} کالا</small></h1>
            <div class="shop-sort">
                <select id="sort" name="sort" form="shop-filters" aria-label="مرتب‌سازی">
                    @foreach(['newest' => 'جدیدترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'rating' => 'بیشترین امتیاز'] as $sort => $label)
                        <option value="{{ $sort }}" @selected($filters['sort'] === $sort)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" form="shop-filters">مرتب‌سازی</button>
            </div>
        </div>
        <div class="shop-grid" id="grid">
            @forelse($products as $product)
                @include('store.products.shop-card', ['product' => $product, 'isFavourite' => in_array($product['id'], $studio['favs'], true)])
            @empty
                <p class="shop-empty">محصولی با این فیلترها پیدا نشد.</p>
            @endforelse
        </div>
        @if($products->hasPages())
            <nav class="shop-pagination" aria-label="صفحه‌بندی فروشگاه">
                @if($products->previousPageUrl())
                    <a rel="prev" href="{{ $products->previousPageUrl() }}#catalog">صفحهٔ قبل</a>
                @endif
                <span>صفحهٔ {{ \App\Support\Money::number($products->currentPage()) }} از {{ \App\Support\Money::number($products->lastPage()) }}</span>
                @if($products->nextPageUrl())
                    <a rel="next" href="{{ $products->nextPageUrl() }}#catalog">صفحهٔ بعد</a>
                @endif
            </nav>
        @endif
    </section>

    <aside id="fit" aria-labelledby="fit-title" hidden tabindex="-1">
        <h2 class="fit-heading">
            <span id="fit-title">اتاق پرو</span>
            <button id="close" type="button" aria-label="بستن اتاق پرو">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </h2>
        <div class="fit-content">
            <p class="fit-guide">با چوب‌لباسی روی هر کارت، لباس را روی مانکن ببین.</p>
            <div class="stage" id="stage" role="group" aria-label="پیش‌نمایش مانکن و لباس‌ها" aria-busy="false">
                <div class="load" id="load" role="status" hidden><span class="spin" aria-hidden="true"></span><b>در حال آماده‌سازی</b></div>
            </div>
            <button type="button" id="sugg" class="shop-fit-suggestion" aria-pressed="false" hidden>دیدن ست پیشنهادی</button>
            <p class="fit-scene-notice" id="fit-scene-notice" role="status" aria-live="polite" hidden></p>
            <button type="button" id="fit-retry" class="shop-fit-suggestion" hidden>تلاش دوباره</button>
            <div class="pk"><span>انتخاب‌های تو</span><span id="pcount" role="status">۰ تکه</span></div>
            <div class="picks" id="picks" aria-label="لباس‌های انتخاب‌شده"></div>

            <section class="fit-recommendations" aria-labelledby="fit-recommend-title" hidden>
                <div class="fit-section-head">
                    <h3 id="fit-recommend-title">با این ست کن</h3>
                </div>
                <div class="fit-recommend-grid" id="fit-recommend-grid" aria-live="polite"></div>
            </section>

            <details class="fit-compare" aria-labelledby="fit-compare-title">
                <summary class="fit-compare-summary" id="fit-compare-title">مقایسه‌ی استایل‌ها</summary>
                <div class="fit-compare-content">
                    <div class="fit-section-head fit-compare-head">
                        <p>دو ترکیب را ذخیره کن و قیمتشان را کنار هم ببین.</p>
                        <button type="button" id="clearCompare" aria-label="پاک کردن استایل‌های مقایسه">پاک‌کردن</button>
                    </div>
                    <div class="fit-compare-actions">
                        <button type="button" id="saveCompareA">ذخیره‌ی ترکیب A</button>
                        <button type="button" id="saveCompareB">ذخیره‌ی ترکیب B</button>
                    </div>
                    <div class="fit-compare-grid" id="fit-compare-grid" aria-live="polite"></div>
                </div>
            </details>
        </div>
        <div class="fit-footer">
            <div class="tot" id="tot">هنوز چیزی انتخاب نشده</div>
            <div class="btns">
                <button class="pri" id="addset" type="button" disabled>افزودن ست به سبد</button>
                <button id="strip" type="button" disabled>شروع دوباره</button>
            </div>
        </div>
    </aside>
</main>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="shop-config">{!! \Illuminate\Support\Js::encode($studio) !!}</script>
@endsection
