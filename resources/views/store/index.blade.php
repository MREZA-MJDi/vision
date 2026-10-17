@extends('layouts.store')

@section('title', 'VISION — محصولات فروشگاه')
@section('meta-description', 'محصولات فروشگاه VISION را بر اساس دسته‌بندی، برند، قیمت و موجودی بررسی کنید.')
@section('canonical', route('shop.index'))

@section('content')
@php
    $formatPrice = static fn ($amount) => \App\Support\Money::format($amount);
@endphp

<main class="store-main">
    <div class="store-titlebar">
        <div class="store-titlebar__left">
            <h1 id="catalog-title">{{ $saleOnly ? 'محصولات تخفیف‌دار' : ($search !== '' ? 'نتایج جست‌وجو' : 'فروشگاه محصولات') }}</h1>
            <span class="store-titlebar__count">{{ number_format($products->total()) }} محصول</span>
        </div>
        <label class="catalog-sort">
            <span>مرتب‌سازی</span>
            <select data-store-sort aria-label="مرتب‌سازی محصولات">
                <option value="newest" @selected($sort === 'newest')>جدیدترین</option>
                <option value="featured" @selected($sort === 'featured')>منتخب‌ها</option>
                <option value="price_asc" @selected($sort === 'price_asc')>قیمت: از کم به زیاد</option>
                <option value="price_desc" @selected($sort === 'price_desc')>قیمت: از زیاد به کم</option>
            </select>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
        </label>
    </div>

    <div class="store-catalog-layout">
        @include('store.partials.fitting-room-shell')

        <section class="store-catalog" id="catalog" aria-labelledby="catalog-title">
            @if($products->count())
                <div class="product-grid" data-product-grid>
                    @foreach($products as $product)
                        @php
                            $variant = $product->primaryAvailableVariant ?? $product->primaryActiveVariant;
                            $image = $product->primaryGalleryMedia?->url ?? $variant?->primaryGalleryMedia?->url;
                            $effectivePrice = $variant?->effective_price ?? $variant?->price;
                            $isOnSale = $variant?->is_on_sale ?? false;
                            $rating = $product->approved_reviews_avg_rating;
                        @endphp
                        <article class="product-card" data-product-card>
                            <div class="product-card__visual">
                                <a class="product-card__image-link" href="{{ route('products.show', ['product' => $product->slug]) }}" aria-label="مشاهدهٔ {{ $product->name }}">
                                    @if($image)
                                        <img class="product-card__image" src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                    @else
                                        <div class="product-art product-art--empty" aria-label="تصویر محصول موجود نیست">
                                            <span>تصویر واقعی برای این محصول ثبت نشده است</span>
                                        </div>
                                    @endif
                                </a>

                                <button class="product-card__wishlist" type="button" data-wishlist="{{ $product->id }}" aria-pressed="false" aria-label="ذخیرهٔ {{ $product->name }}" title="ذخیرهٔ محصول">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"></path></svg>
                                </button>

                                @if(is_numeric($rating))
                                    <span class="product-card__rating" aria-label="امتیاز {{ number_format((float) $rating, 1) }} از ۵">
                                        <span aria-hidden="true">★</span> {{ number_format((float) $rating, 1) }}
                                    </span>
                                @endif

                                <button class="product-card__cart" type="button"
                                    data-cart-add="{{ $variant?->id ?? '' }}"
                                    data-cart-name="{{ $product->name }}"
                                    data-cart-price="{{ $effectivePrice !== null ? $formatPrice($effectivePrice) : 'قیمت نامشخص' }}"
                                    aria-label="افزودن {{ $product->name }} به سبد خرید" title="افزودن به سبد خرید" @disabled(!$variant || (int) $variant->stock < 1)>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2M12 12v4m-2-2h4"></path></svg>
                                </button>
                            </div>

                            <div class="product-card__details">
                                <div class="product-card__text">
                                    <h2><a href="{{ route('products.show', ['product' => $product->slug]) }}">{{ $product->name }}</a></h2>
                                </div>
                                <div class="product-card__price">
                                    @if($isOnSale)
                                        <strong>{{ $formatPrice($variant->sale_price) }}</strong>
                                        <del>{{ $formatPrice($variant->price) }}</del>
                                        <span class="product-card__sale-label">تخفیف</span>
                                    @elseif($effectivePrice !== null)
                                        <strong>{{ $formatPrice($effectivePrice) }}</strong>
                                    @else
                                        <strong>استعلام قیمت</strong>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="catalog-pagination">
                    <p>نمایش <strong>{{ $products->firstItem() }} تا {{ $products->lastItem() }}</strong> از {{ $products->total() }} محصول</p>
                    <div class="catalog-pagination__actions">
                        @if($products->previousPageUrl())
                            <a href="{{ $products->previousPageUrl() }}#catalog" aria-label="صفحهٔ قبلی">→ <span>قبلی</span></a>
                        @else
                            <span class="is-disabled" aria-hidden="true">→ <span>قبلی</span></span>
                        @endif
                        <span class="catalog-pagination__page">{{ str_pad((string) $products->currentPage(), 2, '0', STR_PAD_LEFT) }} <i>/</i> {{ str_pad((string) $products->lastPage(), 2, '0', STR_PAD_LEFT) }}</span>
                        @if($products->nextPageUrl())
                            <a href="{{ $products->nextPageUrl() }}#catalog" aria-label="صفحهٔ بعدی"><span>بعدی</span> ←</a>
                        @else
                            <span class="is-disabled" aria-hidden="true"><span>بعدی</span> ←</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="catalog-empty">
                    <div class="catalog-empty__mark" aria-hidden="true">V.</div>
                    <p class="store-eyebrow">{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'نتیجه‌ای پیدا نشد' : 'شروع مجموعه' }}</p>
                    <h2>{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'محصولی پیدا نشد.' : 'مجموعهٔ خودت را پیدا کن.' }}</h2>
                    <p>{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'عبارت دیگری جست‌وجو کن یا فیلترها را پاک کن.' : 'فروشگاه آماده است؛ هنوز محصولی برای نمایش منتشر نشده است.' }}</p>
                    @if($search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly)
                        <a class="empty-state-action" href="{{ route('shop.index') }}">پاک‌کردن فیلترها <span aria-hidden="true">↗</span></a>
                    @endif
                </div>
            @endif
        </section>

        <aside class="store-sidebar" id="store-sidebar" data-store-sidebar>
                        <section class="filter-panel filter-panel--heading" aria-labelledby="filter-title">

                <div class="filter-panel__header">
                    <div>
                        <p class="store-eyebrow">تنظیم نتایج</p>
                        <h2 id="filter-title">فیلترها</h2>
                    </div>
                    <a class="text-action" href="{{ route('shop.index') }}">پاک‌کردن <span aria-hidden="true">↺</span></a>
                </div>

            </section>

            <section class="filter-panel filter-panel--details" aria-label="فیلترهای محصولات">
                <form class="filter-search" action="{{ route('shop.index') }}" method="GET">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"></circle><path d="m16 16 4.2 4.2"></path></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="جست‌وجو در محصولات..." aria-label="جست‌وجو در محصولات">
                    @foreach(request()->query() as $key => $value)
                        @if($key !== 'q' && $key !== 'page' && is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" aria-label="جست‌وجو">↵</button>
                </form>

                <div class="filter-group">
                    <div class="filter-group__heading"><h3>دسته‌بندی</h3><span>{{ $categories->count() }}</span></div>
                    <div class="filter-options">
                        <a href="{{ route('shop.index', array_merge(request()->query(), ['category_id' => null, 'page' => null])) }}" class="filter-option {{ !$selectedCategoryId ? 'is-selected' : '' }}">
                            <span class="filter-option__label"><span class="filter-radio"></span>همهٔ محصولات</span>
                            <span class="filter-option__count">{{ $products->total() }}</span>
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ request()->fullUrlWithQuery(['category_id' => (int) $selectedCategoryId === $category->id ? null : $category->id, 'page' => null]) }}" class="filter-option {{ (int) $selectedCategoryId === $category->id ? 'is-selected' : '' }}">
                                <span class="filter-option__label"><span class="filter-radio"></span>{{ $category->name }}</span>
                                <span class="filter-option__count">{{ $category->active_products_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($brands->isNotEmpty())
                    <div class="filter-group">
                        <div class="filter-group__heading"><h3>برند</h3><span>{{ $brands->count() }}</span></div>
                        <div class="filter-options">
                            <a href="{{ request()->fullUrlWithQuery(['brand_id' => null, 'page' => null]) }}" class="filter-option {{ !$selectedBrandId ? 'is-selected' : '' }}">
                                <span class="filter-option__label"><span class="filter-checkbox"></span>همهٔ برندها</span>
                                <span class="filter-option__count">{{ $brands->sum('active_products_count') }}</span>
                            </a>
                            @foreach($brands as $brand)
                                <a href="{{ request()->fullUrlWithQuery(['brand_id' => (int) $selectedBrandId === $brand->id ? null : $brand->id, 'page' => null]) }}" class="filter-option {{ (int) $selectedBrandId === $brand->id ? 'is-selected' : '' }}">
                                    <span class="filter-option__label"><span class="filter-checkbox"></span>{{ $brand->name }}</span>
                                    <span class="filter-option__count">{{ $brand->active_products_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="filter-group filter-group--price">
                    <div class="filter-group__heading"><h3>محدودهٔ قیمت</h3><span>{{ config('commerce-admin.currency_symbol', 'تومان') }}</span></div>
                    <form class="price-filter" action="{{ route('shop.index') }}" method="GET">
                        @foreach(request()->query() as $key => $value)
                            @if(!in_array($key, ['price_min', 'price_max', 'page'], true) && is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label><span>حداقل</span><span class="price-input"><span>{{ config('commerce-admin.currency_symbol', 'تومان') }}</span><input type="number" min="0" step="1" name="price_min" value="{{ $minPrice }}" placeholder="۰" aria-label="حداقل قیمت"></span></label>
                        <label><span>حداکثر</span><span class="price-input"><span>{{ config('commerce-admin.currency_symbol', 'تومان') }}</span><input type="number" min="0" step="1" name="price_max" value="{{ $maxPrice }}" placeholder="بدون سقف" aria-label="حداکثر قیمت"></span></label>
                        <button type="submit" class="filter-apply">اعمال قیمت <span aria-hidden="true">↗</span></button>
                    </form>
                </div>

                <a class="sale-filter {{ $saleOnly ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sale' => $saleOnly ? null : 1, 'page' => null]) }}">
                    <span class="sale-filter__icon">%</span>
                    <span><strong>تخفیف‌دار</strong><small>محصولات دارای قیمت ویژه</small></span>
                    <span class="sale-filter__toggle" aria-hidden="true"><i></i></span>
                </a>            </section>
        </aside>
    </div>

</main>
@endsection
