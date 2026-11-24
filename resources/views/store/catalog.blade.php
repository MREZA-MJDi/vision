@extends('layouts.store')

@section('title', $fittingPage ? 'VISION — اتاق پرو' : 'VISION — فروشگاه پوشاک')
@section('meta-description', 'تازه‌های VISION را ببین، فیلتر کن و با اتاق پرو استودیویی ترکیب‌های پیشنهادی را امتحان کن.')
@section('canonical', route($fittingPage ? 'try-on.index' : 'shop.index'))

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
            <a class="shop-open-fit" href="{{ route($fittingPage ? 'shop.index' : 'try-on.index') }}">{{ $fittingPage ? 'بازگشت به فروشگاه' : 'اتاق پرو' }}</a>
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

<main class="vision-shop-main{{ $fittingPage ? ' fitting-page' : '' }}" id="catalog" data-fitting-page="{{ $fittingPage ? '1' : '0' }}">
    @if($fittingPage)
        <header class="fitting-page-intro"><h1>اتاق پرو VISION</h1><p>۱. لباس را انتخاب کن &nbsp; ۲. روی مانکن ببین &nbsp; ۳. به سبد خرید اضافه کن</p></header>
    @endif
    <form id="shop-filters" class="shop-filter" action="{{ route($fittingPage ? 'try-on.index' : 'shop.index') }}" method="get" aria-label="فیلترهای فروشگاه">
        <div class="shop-filter-heading">
            <h2>فیلترها</h2>
            <button type="button" id="shop-filter-toggle" aria-controls="shop-filter-fields" aria-expanded="true" hidden>
                فیلترها <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <a href="{{ route($fittingPage ? 'try-on.index' : 'shop.index') }}">حذف فیلترها</a>
        </div>
        <div id="shop-filter-fields" class="shop-filter-fields">
            @if(filled($filters['brand'] ?? null))
                <input type="hidden" name="brand" value="{{ $filters['brand'] }}">
            @endif
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
            <h1 id="shop-title">{{ $fittingPage ? 'لباس‌های قابل پرو' : 'فروشگاه' }} <small>{{ \App\Support\Money::number($products->total()) }} کالا</small></h1>
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
                @include('store.products.shop-card', ['product' => $product, 'isFavourite' => in_array($product['id'], $studio['favs'], true), 'fittingPage' => $fittingPage])
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

    @if($fittingPage)
        @include('store.fitting.panel')
    @endif

</main>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="shop-config">{!! \Illuminate\Support\Js::encode($studio) !!}</script>
@endsection
