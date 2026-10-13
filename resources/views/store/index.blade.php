@extends('layouts.store')

@section('title', 'VISION — New Arrivals')
@section('meta-description', 'Explore the latest VISION pieces, refine the edit, and preview your shortlist in the fitting room.')

@section('content')
@php
    $formatPrice = static fn ($amount) => '$' . number_format((float) $amount, 0);
@endphp

<main class="store-main">
    <div class="store-overline">
        <span class="store-overline__left"><span class="store-status-dot"></span> THE VISION EDIT <span class="store-overline__slash">/</span> 2026</span>
        <span class="store-overline__right">CONSIDERED PIECES, CONSIDERED DETAILS</span>
    </div>

    <div class="store-catalog-layout">
        <aside class="store-sidebar" id="store-sidebar" data-store-sidebar>
            <section class="filter-panel" aria-labelledby="filter-title">
                <div class="filter-panel__header">
                    <div>
                        <p class="store-eyebrow">REFINE THE EDIT</p>
                        <h2 id="filter-title">Filters</h2>
                    </div>
                    <a class="text-action" href="{{ route('storefront.index') }}">Reset <span aria-hidden="true">↺</span></a>
                </div>

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
                </a>
            </section>

            <section class="fitting-room" aria-labelledby="fitting-room-title" data-fitting-room>
                <div class="fitting-room__header">
                    <div>
                        <p class="store-eyebrow"><span class="fitting-live-dot"></span> PERSONAL PREVIEW</p>
                        <h2 id="fitting-room-title">Fitting room<span class="fitting-room__period">.</span></h2>
                    </div>
                    <button class="fitting-room__reset" type="button" data-fitting-reset aria-label="Reset fitting room" title="Reset preview">↺</button>
                </div>

                <div class="fitting-preview" data-fitting-preview>
                    <div class="fitting-preview__label"><span>LOOK <b data-fitting-look-number>{{ $initialProduct ? '01' : '—' }}</b></span><span class="fitting-preview__live"><i></i> LIVE PREVIEW</span></div>
                    <div class="fitting-preview__stage">
                        <svg class="fitting-model" viewBox="0 0 180 240" aria-hidden="true">
                            <circle cx="90" cy="34" r="19"></circle>
                            <path d="M70 57c-11 6-19 17-22 34l-9 48 19 5 10-39 2 48-8 60h22l6-50 6 50h22l-8-60 2-48 10 39 19-5-9-48c-3-17-11-28-22-34l-13-5H83z"></path>
                            <path d="M78 56 90 76l12-20M70 59l20 17 20-17M70 111l20 24 20-24" class="fitting-model__detail"></path>
                        </svg>
                        <img class="fitting-preview__image" @if($initialImage) src="{{ $initialImage }}" @endif alt="{{ $initialProduct?->name ?? 'Selected piece preview' }}" data-fitting-image @if(!$initialImage) hidden @endif>
                        <div class="fitting-preview__placeholder" data-fitting-placeholder @if($initialImage) hidden @endif>
                            <span class="fitting-preview__placeholder-mark">V</span>
                            <span>YOUR NEXT LOOK<br>STARTS HERE</span>
                        </div>
                    </div>
                    <div class="fitting-preview__footer"><span>YOUR CANVAS</span><span>01 / 05</span></div>
                </div>

                <div class="fitting-room__copy">
                    <p class="fitting-room__selected-label">CURRENT SELECTION</p>
                    <h3 data-fitting-name>{{ $initialProduct?->name ?? 'Choose a piece' }}</h3>
                    <p data-fitting-description>{{ $initialProduct ? ($initialProduct->short_description ?: 'A considered piece from the latest edit.') : 'Select the fit icon on any product to preview it here.' }}</p>
                    <div class="fitting-room__price" data-fitting-price>
                        @if($initialProduct?->primaryActiveVariant)
                            {{ $formatPrice($initialProduct->primaryActiveVariant->sale_price ?? $initialProduct->primaryActiveVariant->price) }}
                        @else
                            <span>Waiting for your edit</span>
                        @endif
                    </div>
                </div>

                <div class="fitting-picks">
                    <div class="fitting-picks__heading"><span>Your picks</span><span><b data-fitting-pick-count>{{ $initialProduct ? 1 : 0 }}</b> / 5 pieces</span></div>
                    <div class="fitting-picks__list" data-fitting-picks>
                        @if($initialProduct)
                            <button class="fitting-pick is-current" type="button" data-fitting-pick data-product-id="{{ $initialProduct->id }}" data-product-name="{{ $initialProduct->name }}" data-product-price="{{ $formatPrice($initialProduct->primaryActiveVariant?->sale_price ?? $initialProduct->primaryActiveVariant?->price ?? 0) }}" data-product-image="{{ $initialImage ?? '' }}" data-product-description="{{ $initialProduct->short_description ?? '' }}">
                                <span class="fitting-pick__dot">01</span><span class="fitting-pick__name">{{ $initialProduct->name }}</span><span class="fitting-pick__remove" aria-hidden="true">×</span>
                            </button>
                        @else
                            <p class="fitting-picks__empty" data-fitting-picks-empty>Your shortlist is waiting.</p>
                        @endif
                    </div>
                </div>

                <button class="fitting-room__action" type="button" data-fitting-action {{ $initialProduct ? '' : 'disabled' }}>
                    <span>Add to your edit</span><span class="fitting-room__action-icon" aria-hidden="true">↗</span>
                </button>
                <p class="fitting-room__note">A visual preview of your selected pieces.</p>
            </section>
        </aside>

        <section class="store-catalog" id="catalog" aria-labelledby="catalog-title">
            <div class="catalog-heading">
                <div class="catalog-heading__title">
                    <p class="store-eyebrow">CURATED / NOT CROWDED</p>
                    <h1 id="catalog-title">{{ $saleOnly ? 'The Sale Edit' : ($search !== '' ? 'Search results' : 'New Arrivals') }}<span class="catalog-heading__count">{{ number_format($products->total()) }} {{ $products->total() === 1 ? 'piece' : 'pieces' }}</span></h1>
                    <p class="catalog-heading__description">
                        @if($search !== '')
                            Showing pieces matching “{{ $search }}”.
                        @elseif($saleOnly)
                            Good pieces. Better prices. Nothing extra.
                        @else
                            New season, familiar feeling. Find the piece that feels like you.
                        @endif
                    </p>
                </div>

                <div class="catalog-heading__tools">
                    <span class="catalog-result-count">
                        @if($products->total() > 0)
                            {{ $products->firstItem() }}–{{ $products->lastItem() }} <span>of</span> {{ $products->total() }}
                        @else
                            0 <span>results</span>
                        @endif
                    </span>
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
            </div>

            @if($products->count())
                <div class="product-grid" data-product-grid>
                    @foreach($products as $product)
                        @php
                            $variant = $product->primaryActiveVariant;
                            $image = $product->primaryGalleryMedia?->url ?? $variant?->primaryGalleryMedia?->url;
                            $effectivePrice = $variant?->sale_price ?? $variant?->price;
                            $isOnSale = $variant && $variant->sale_price !== null && (float) $variant->sale_price < (float) $variant->price;
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

                                <span class="product-card__badge">{{ $isOnSale ? 'SPECIAL PRICE' : ($product->is_featured ? 'EDITOR’S PICK' : ($product->category?->name ?? 'THE EDIT')) }}</span>

                                <button class="product-card__wishlist" type="button" data-wishlist="{{ $product->id }}" aria-pressed="false" aria-label="Save {{ $product->name }}" title="Save to wishlist">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"></path></svg>
                                </button>

                                <button class="product-card__fit" type="button" data-fit-product
                                    data-product-id="{{ $product->id }}"
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ $effectivePrice !== null ? $formatPrice($effectivePrice) : 'Price unavailable' }}"
                                    data-product-image="{{ $image ?? '' }}"
                                    data-product-description="{{ $product->short_description ?? '' }}"
                                    aria-label="Preview {{ $product->name }} in the fitting room">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4m0 0-3-3m3 3 3-3M5 13l3-3 4 3 4-3 3 3M7 13v7m10-7v7M5 20h14"></path></svg>
                                    <span>Preview</span>
                                </button>
                            </div>

                            <div class="product-card__details">
                                <div class="product-card__text">
                                    <p class="product-card__category">{{ $product->brand?->name ?? ($product->category?->name ?? 'VISION SELECT') }}</p>
                                    <h2><a href="#catalog" data-card-preview>{{ $product->name }}</a></h2>
                                </div>
                                <div class="product-card__price">
                                    @if($isOnSale)
                                        <strong>{{ $formatPrice($variant->sale_price) }}</strong>
                                        <del>{{ $formatPrice($variant->price) }}</del>
                                    @elseif($effectivePrice !== null)
                                        <strong>{{ $formatPrice($effectivePrice) }}</strong>
                                    @else
                                        <strong>Price on request</strong>
                                    @endif
                                </div>
                            </div>
                            @if($product->short_description)
                                <p class="product-card__description">{{ $product->short_description }}</p>
                            @endif
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
                    <p>{{ $search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly ? 'Try a different search or reset your filters to see the full collection.' : 'The storefront is ready. Publish an active product with an active variant in the admin to bring the collection to life.' }}</p>
                    @if($search !== '' || $selectedCategoryId || $selectedBrandId || $minPrice !== null || $maxPrice !== null || $saleOnly)
                        <a class="empty-state-action" href="{{ route('storefront.index') }}">Reset filters <span aria-hidden="true">↗</span></a>
                    @else
                        <a class="empty-state-action" href="{{ route('login') }}">Open account <span aria-hidden="true">↗</span></a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
