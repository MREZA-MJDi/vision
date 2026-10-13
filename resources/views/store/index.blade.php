@extends('layouts.store')

@section('title', 'VISION — New Arrivals')
@section('meta-description', 'Explore the latest VISION pieces, refine the edit, and preview your shortlist in the fitting room.')

@section('content')
@php
    $formatPrice = static fn ($amount) => '$' . number_format((float) $amount, 0);
@endphp

<main class="store-main">
    <div class="store-titlebar">
        <div class="store-titlebar__left">
            <h1 id="catalog-title">{{ $saleOnly ? 'The Sale Edit' : ($search !== '' ? 'Search results' : 'New Arrivals') }}</h1>
            <span class="store-titlebar__count">{{ number_format($products->total()) }} {{ $products->total() === 1 ? 'product' : 'products' }}</span>
        </div>
        <label class="catalog-sort">
            <span>Sort by</span>
            <select data-store-sort aria-label="Sort products">
                <option value="newest" @selected($sort === 'newest')>Newest</option>
                <option value="featured" @selected($sort === 'featured')>Featured</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
            </select>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
        </label>
    </div>

    <div class="store-catalog-layout">
        <aside class="store-sidebar" id="store-sidebar" data-store-sidebar>
                        <section class="filter-panel filter-panel--heading" aria-labelledby="filter-title">

                <div class="filter-panel__header">
                    <div>
                        <p class="store-eyebrow">REFINE THE EDIT</p>
                        <h2 id="filter-title">Filters</h2>
                    </div>
                    <a class="text-action" href="{{ route('storefront.index') }}">Reset <span aria-hidden="true">↺</span></a>
                </div>

            </section>

            <section class="fitting-room" aria-labelledby="fitting-room-title" data-fitting-room>
                <div class="fitting-room__header">
                    <div>
                        <p class="store-eyebrow"><span class="fitting-live-dot"></span> FITTING ROOM</p>
                        <h2 id="fitting-room-title">Fitting room<span class="fitting-room__period">.</span></h2>
                        <p class="fitting-room__instruction">Tap the hanger on any product to preview it.</p>
                    </div>
                    <button class="fitting-room__close" type="button" data-fitting-close aria-label="Close fitting room" title="Close fitting room">×</button>
                </div>

                <div class="fitting-preview" data-fitting-preview>
                    <div class="fitting-preview__label"><span>LOOK <b data-fitting-look-number>—</b></span><span class="fitting-preview__live"><i></i> LIVE PREVIEW</span></div>
                    <div class="fitting-preview__stage">
                        <svg class="fitting-model" viewBox="0 0 180 240" aria-hidden="true">
                            <circle cx="90" cy="34" r="19"></circle>
                            <path d="M70 57c-11 6-19 17-22 34l-9 48 19 5 10-39 2 48-8 60h22l6-50 6 50h22l-8-60 2-48 10 39 19-5-9-48c-3-17-11-28-22-34l-13-5H83z"></path>
                            <path d="M78 56 90 76l12-20M70 59l20 17 20-17M70 111l20 24 20-24" class="fitting-model__detail"></path>
                        </svg>
                        <img class="fitting-preview__image" alt="Selected piece preview" data-fitting-image hidden>
                        <div class="fitting-preview__placeholder" data-fitting-placeholder>
                            <span class="fitting-preview__placeholder-mark">V</span>
                            <span>SELECT A PIECE<br>TO START YOUR PREVIEW</span>
                        </div>
                    </div>
                    <div class="fitting-preview__footer"><span>YOUR CANVAS</span><span data-fitting-canvas-count>00 / 05</span></div>
                </div>

                <div class="fitting-picks">
                    <div class="fitting-picks__heading"><span>Your picks</span><span><b data-fitting-pick-count>0</b> / 5 pieces</span></div>
                    <div class="fitting-picks__list" data-fitting-picks>
                        <p class="fitting-picks__empty" data-fitting-picks-empty>Your shortlist is waiting.</p>
                    </div>
                </div>

                <button class="fitting-room__action" type="button" data-fitting-action disabled>
                    <span>Add another piece</span><span class="fitting-room__action-icon" aria-hidden="true">↗</span>
                </button>
                <p class="fitting-room__note">Add another piece and it goes on top.</p>
                <div class="fitting-room__footer-actions">
                    <button type="button" data-fitting-reset>Start over <span aria-hidden="true">↻</span></button>
                </div>
            </section>

            <button class="fitting-room-reopen" type="button" data-fitting-reopen hidden>
                <span aria-hidden="true">◉</span> Fitting room <span aria-hidden="true">↗</span>
            </button>

            <section class="filter-panel filter-panel--details" aria-label="Product filters">
                <form class="filter-search" action="{{ route('storefront.index') }}" method="GET">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"></circle><path d="m16 16 4.2 4.2"></path></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search the collection..." aria-label="Search the collection">
                    @if($sort !== 'newest')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                    @if($saleOnly)<input type="hidden" name="sale" value="1">@endif
                    <button type="submit" aria-label="Search">↵</button>
                </form>

                <div class="filter-group">
                    <div class="filter-group__heading"><h3>Category</h3><span>{{ $categories->count() }}</span></div>
                    <div class="filter-options">
                        <a href="{{ route('storefront.index', array_merge(request()->query(), ['category_id' => null])) }}" class="filter-option {{ !$selectedCategoryId ? 'is-selected' : '' }}">
                            <span class="filter-option__label"><span class="filter-radio"></span>All pieces</span>
                            <span class="filter-option__count">{{ $products->total() }}</span>
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ request()->fullUrlWithQuery(['category_id' => $category->id]) }}" class="filter-option {{ (int) $selectedCategoryId === $category->id ? 'is-selected' : '' }}">
                                <span class="filter-option__label"><span class="filter-radio"></span>{{ $category->name }}</span>
                                <span class="filter-option__count">{{ $category->active_products_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($brands->isNotEmpty())
                    <div class="filter-group">
                        <div class="filter-group__heading"><h3>Brand</h3><span>{{ $brands->count() }}</span></div>
                        <div class="filter-options">
                            @foreach($brands as $brand)
                                <a href="{{ request()->fullUrlWithQuery(['brand_id' => $brand->id]) }}" class="filter-option {{ (int) $selectedBrandId === $brand->id ? 'is-selected' : '' }}">
                                    <span class="filter-option__label"><span class="filter-checkbox"></span>{{ $brand->name }}</span>
                                    <span class="filter-option__count">{{ $brand->active_products_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="filter-group filter-group--price">
                    <div class="filter-group__heading"><h3>Price range</h3><span>USD</span></div>
                    <form class="price-filter" action="{{ route('storefront.index') }}" method="GET">
                        @foreach(request()->query() as $key => $value)
                            @if(!in_array($key, ['price_min', 'price_max', 'page'], true) && is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label><span>Min</span><span class="price-input"><span>$</span><input type="number" min="0" step="1" name="price_min" value="{{ $minPrice }}" placeholder="0" aria-label="Minimum price"></span></label>
                        <label><span>Max</span><span class="price-input"><span>$</span><input type="number" min="0" step="1" name="price_max" value="{{ $maxPrice }}" placeholder="Any" aria-label="Maximum price"></span></label>
                        <button type="submit" class="filter-apply">Apply price <span aria-hidden="true">↗</span></button>
                    </form>
                </div>

                <a class="sale-filter {{ $saleOnly ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sale' => $saleOnly ? null : 1]) }}">
                    <span class="sale-filter__icon">%</span>
                    <span><strong>On sale</strong><small>Pieces with a reduced price</small></span>
                    <span class="sale-filter__toggle" aria-hidden="true"><i></i></span>
                </a>            </section>
        </aside>

        <section class="store-catalog" id="catalog" aria-labelledby="catalog-title">
            @if($products->count())
                <div class="product-grid" data-product-grid>
                    @foreach($products as $product)
                        @php
                            $variant = $product->primaryActiveVariant;
                            $image = $product->primaryGalleryMedia?->url ?? $variant?->primaryGalleryMedia?->url;
                            $effectivePrice = $variant?->sale_price ?? $variant?->price;
                            $isOnSale = $variant && $variant->sale_price !== null && (float) $variant->sale_price < (float) $variant->price;
                            $rating = data_get($product->attributes, 'rating');
                        @endphp
                        <article class="product-card" data-product-card>
                            <div class="product-card__visual">
                                <a class="product-card__image-link" href="#catalog" aria-label="Preview {{ $product->name }}" data-card-preview>
                                    @if($image)
                                        <img class="product-card__image" src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                                    @else
                                        <div class="product-art product-art--{{ $loop->iteration % 4 }}" aria-label="Product image coming soon">
                                            <svg viewBox="0 0 180 200" role="img" aria-hidden="true"><path d="M67 26c5-7 11-11 23-11s18 4 23 11l22 16-14 28-12-7 6 117H65l6-117-12 7-14-28z"></path><path d="M67 26c1 15 8 25 23 25s22-10 23-25M90 51v128M67 77l23 12 23-12"></path></svg>
                                            <span>VISION<br>STUDIO</span>
                                        </div>
                                    @endif
                                </a>

                                <button class="product-card__wishlist" type="button" data-wishlist="{{ $product->id }}" aria-pressed="false" aria-label="Save {{ $product->name }}" title="Save to wishlist">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"></path></svg>
                                </button>

                                @if(is_numeric($rating))
                                    <span class="product-card__rating" aria-label="Rating {{ number_format((float) $rating, 1) }} out of 5">
                                        <span aria-hidden="true">★</span> {{ number_format((float) $rating, 1) }}
                                    </span>
                                @endif

                                <button class="product-card__quickfit" type="button" data-fit-product
                                    data-product-id="{{ $product->id }}"
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ $effectivePrice !== null ? $formatPrice($effectivePrice) : 'Price unavailable' }}"
                                    data-product-image="{{ $image ?? '' }}"
                                    data-product-description="{{ $product->short_description ?? '' }}"
                                    aria-label="Move {{ $product->name }} to the fitting room" title="Preview in fitting room">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4m0 0-3-3m3 3 3-3M4 15l8-5 8 5M6 14v5h12v-5"></path></svg>
                                </button>

                                <button class="product-card__cart" type="button"
                                    data-cart-add="{{ $product->id }}"
                                    data-cart-name="{{ $product->name }}"
                                    data-cart-price="{{ $effectivePrice !== null ? $formatPrice($effectivePrice) : 'Price unavailable' }}"
                                    aria-label="Add {{ $product->name }} to bag" title="Add to bag">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2M12 12v4m-2-2h4"></path></svg>
                                </button>
                            </div>

                            <div class="product-card__details">
                                <div class="product-card__text">
                                    <h2><a href="#catalog" data-card-preview>{{ $product->name }}</a></h2>
                                </div>
                                <div class="product-card__price">
                                    @if($isOnSale)
                                        <strong>{{ $formatPrice($variant->sale_price) }}</strong>
                                        <del>{{ $formatPrice($variant->price) }}</del>
                                        <span class="product-card__sale-label">Sale</span>
                                    @elseif($effectivePrice !== null)
                                        <strong>{{ $formatPrice($effectivePrice) }}</strong>
                                    @else
                                        <strong>Price on request</strong>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="catalog-pagination">
                    <p>Showing <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong> of {{ $products->total() }} pieces</p>
                    <div class="catalog-pagination__actions">
                        @if($products->previousPageUrl())
                            <a href="{{ $products->previousPageUrl() }}#catalog" aria-label="Previous page">← <span>Previous</span></a>
                        @else
                            <span class="is-disabled" aria-hidden="true">← <span>Previous</span></span>
                        @endif
                        <span class="catalog-pagination__page">{{ str_pad((string) $products->currentPage(), 2, '0', STR_PAD_LEFT) }} <i>/</i> {{ str_pad((string) $products->lastPage(), 2, '0', STR_PAD_LEFT) }}</span>
                        @if($products->nextPageUrl())
                            <a href="{{ $products->nextPageUrl() }}#catalog" aria-label="Next page"><span>Next</span> →</a>
                        @else
                            <span class="is-disabled" aria-hidden="true"><span>Next</span> →</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="catalog-empty">
                    <div class="catalog-empty__mark" aria-hidden="true">V.</div>
                    <p class="store-eyebrow">{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'NOT THIS TIME' : 'THE FIRST PAGE' }}</p>
                    <h2>{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'No pieces found.' : 'Your edit starts here.' }}</h2>
                    <p>{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'Try a different search or reset your filters to see the full collection.' : 'The storefront is ready. The catalog has no published products yet.' }}</p>
                    @if($search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly)
                        <a class="empty-state-action" href="{{ route('storefront.index') }}">Reset filters <span aria-hidden="true">↗</span></a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
