@extends('store.layout')

@section('title', 'Shop the Edit — VISION')
@section('description', 'Shop the VISION edit: distinctive clothing, shoes and bags for your point of view.')

@section('content')
    <section class="catalog-intro">
        <span class="eyebrow">THE VISION SHOP / CURATED FOR YOU</span>
        <h1>SHOP THE <em>EDIT.</em></h1>
        <p>Good style is personal. Find the pieces that feel like you.</p>
        <div class="catalog-intro__bottom"><span>{{ $products->total() }} PIECES IN THIS EDIT</span><span>COLLECTION 026 — ALWAYS IN MOTION</span></div>
    </section>
    <section class="catalog-layout">
        <aside class="catalog-sidebar">
            <form action="{{ route('products.index') }}" method="get" class="catalog-filters">
                <label for="catalog-search">SEARCH THE EDIT</label>
                <div class="catalog-search"><input id="catalog-search" type="search" name="q" value="{{ request('q') }}" placeholder="Try “bag”, “heel”..."><button type="submit" aria-label="Search">↗</button></div>
                <div class="filter-block">
                    <span>CATEGORY</span>
                    <a href="{{ route('products.index', request()->except(['category', 'page'])) }}" class="{{ request('category') ? '' : 'is-active' }}">Everything <small>{{ $products->total() }}</small></a>
                    @foreach($categories as $category)
                        <a href="{{ route('products.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" class="{{ request('category') === $category->slug ? 'is-active' : '' }}">{{ $category->name }} <span>↗</span></a>
                    @endforeach
                </div>
                <div class="filter-block">
                    <span>LABELS</span>
                    @foreach($brands as $brand)
                        <a href="{{ route('products.index', array_merge(request()->except('page'), ['brand' => $brand->slug])) }}" class="{{ request('brand') === $brand->slug ? 'is-active' : '' }}">{{ $brand->name }} <span>↗</span></a>
                    @endforeach
                </div>
                <a class="clear-filters" href="{{ route('products.index') }}">CLEAR ALL FILTERS ↗</a>
            </form>
        </aside>
        <div class="catalog-results">
            <div class="catalog-toolbar"><span>SHOWING {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} OF {{ $products->total() }}</span><form method="get" action="{{ route('products.index') }}">@foreach(request()->except(['sort', 'page']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label for="catalog-sort">SORT BY</label><select id="catalog-sort" name="sort" onchange="this.form.submit()"><option value="newest" @selected($sort === 'newest')>Latest arrivals</option><option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option><option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option></select></form></div>
            @if($products->isNotEmpty())
                <div class="product-grid product-grid--catalog">
                    @foreach($products as $product)
                        @include('store.products._card', ['product' => $product])
                    @endforeach
                </div>
                <div class="catalog-pagination">{{ $products->links() }}</div>
            @else
                <div class="empty-editorial empty-editorial--catalog"><span>NO MATCHES / TRY AGAIN</span><h2>Nothing here.<br><em>Just yet.</em></h2><p>Try a different search or clear your filters.</p><a class="button button--dark" href="{{ route('products.index') }}">RESET THE EDIT ↗</a></div>
            @endif
        </div>
    </section>
@endsection
