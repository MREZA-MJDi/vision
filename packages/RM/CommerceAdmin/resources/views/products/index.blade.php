@extends('commerce-admin::layout')

@section('title', 'Products')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">E-Commerce <span>/</span> Product list</div><h1>Products</h1><p>Manage catalog information, variants, prices and stock from the live database.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.products.create') }}"><span>＋</span> Add product</a></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-four">
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">$</span><span class="ak-stat-kicker">TOTAL SALES</span></div>
            <div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $stats['sales'], 2) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Paid, non-cancelled order items</span><span class="ak-mini-badge">Live</span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">▤</span><span class="ak-stat-kicker">NUMBER OF SALES</span></div>
            <div class="ak-stat-value">{{ number_format((int) $stats['orders']) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Orders excluding cancelled / returned</span><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index') }}">View orders ↗</a></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">♙</span><span class="ak-stat-kicker">CUSTOMERS</span></div>
            <div class="ak-stat-value">{{ number_format((int) $stats['customers']) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Registered customer accounts</span><span class="ak-mini-badge">Live</span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-red">↘</span><span class="ak-stat-kicker">DISCOUNTS</span></div>
            <div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float) $stats['discount_total'], 2) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">Applied to saved non-terminal orders</span><span class="ak-mini-badge">{{ number_format((int) $stats['discounted']) }} sale variants</span></div>
        </article>
    </section>

    <section class="ak-panel ak-product-list-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>Product list <span class="ak-count-chip">{{ $products->total() }}</span></h2><p>Product catalog records with current variant data</p></div>
            <div class="ak-toolbar">
                <button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ Filters <span class="ak-filter-active">{{ $status !== 'all' || $categoryId || $search !== '' ? '•' : '' }}</span></button>
                <button class="ak-button ak-button-light ak-button-sm" type="button" data-columns-toggle>☷ Columns</button>
                <a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.products.create') }}">＋ Add product</a>
            </div>
        </div>
        <form class="ak-filter-bar {{ $status !== 'all' || $categoryId || $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.products.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>Search</span><input type="search" name="q" value="{{ $search }}" placeholder="Product name, SKU or barcode"></label>
            <label class="ak-inline-field"><span>Category</span><select name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$categoryId === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="ak-inline-field"><span>Status</span><select name="status"><option value="all" @selected($status === 'all')>All products</option><option value="active" @selected($status === 'active')>Active</option><option value="draft" @selected(in_array($status, ['draft','inactive'], true))>Draft / inactive</option><option value="sale" @selected($status === 'sale')>On sale</option><option value="low_stock" @selected($status === 'low_stock')>Low stock</option></select></label>
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">Apply filters</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.products.index') }}">Reset</a></div>
        </form>

        <div class="ak-column-popover" data-columns-panel hidden>
            <label><input type="checkbox" data-column-check="category" checked> Category</label>
            <label><input type="checkbox" data-column-check="rating" checked> Rating</label>
            <label><input type="checkbox" data-column-check="price" checked> Price</label>
            <label><input type="checkbox" data-column-check="stock" checked> Stock</label>
            <label><input type="checkbox" data-column-check="status" checked> Status</label>
        </div>

        @if($products->isEmpty())
            <div class="ak-empty-state"><span>▦</span><strong>No products match this view</strong><p>Change your filters or add a catalog product. No demo products are inserted automatically.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.products.create') }}">Add product</a></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table ak-products-table">
                <thead><tr><th><input type="checkbox" data-select-all aria-label="Select all visible products"></th><th>Product</th><th data-column="category">Category</th><th data-column="price">Price</th><th data-column="stock">Stock</th><th data-column="rating">Rating</th><th data-column="status">Status</th><th></th></tr></thead>
                <tbody>
                @foreach($products as $item)
                    @php
                        $variant = $item->variants->firstWhere('is_active', true) ?: $item->variants->first();
                        $asset = $item->galleryMedia->first();
                        $stock = (int)$item->variants->sum('stock');
                        $isLow = $item->variants->contains(fn($row) => $row->is_active && $row->stock <= $row->low_stock_threshold);
                        $onSale = $item->variants->contains(fn($row) => $row->sale_price !== null && $row->sale_price < $row->price);
                    @endphp
                    <tr>
                        <td><input type="checkbox" aria-label="Select {{ $item->name }}" data-row-select></td>
                        <td><div class="ak-product-cell">@if($asset?->url)<img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $item->name }}">@else<span class="ak-product-thumb-placeholder">▦</span>@endif<div><a class="ak-table-primary" href="{{ route('commerce-admin.products.show', $item->getKey()) }}">{{ $item->name }}</a><small>SKU {{ $variant?->sku ?: '—' }}</small></div></div></td>
                        <td data-column="category">{{ $item->category?->name ?: 'Uncategorized' }}</td>
                        
                        <td data-column="price"><div class="ak-price-cell"><strong>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant?->sale_price ?? $variant?->price ?? 0), 2) }}</strong>@if($onSale)<small class="ak-old-price">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant?->price ?? 0), 2) }}</small>@endif</div></td>
                        <td data-column="stock"><span class="ak-stock-indicator {{ $stock <= 0 ? 'is-out' : ($isLow ? 'is-low' : 'is-ok') }}"><i></i>{{ number_format($stock) }}</span></td>
                        <td data-column="rating"><div class="ak-rating-cell"><span aria-hidden="true">★</span><strong>{{ number_format((float) ($item->approved_reviews_avg_rating ?? 0), 1) }}</strong><small>({{ number_format((int) $item->approved_reviews_count) }})</small></div></td>
                        <td data-column="status"><span class="ak-status-pill {{ $item->is_active ? 'status-active' : 'status-draft' }}">{{ $item->is_active ? 'Active' : 'Draft' }}</span></td>
                        <td><div class="ak-row-menu"><a href="{{ route('commerce-admin.products.show', $item->getKey()) }}" title="View product">↗</a><a href="{{ route('commerce-admin.products.edit', $item->getKey()) }}" title="Edit product">✎</a><form method="POST" action="{{ route('commerce-admin.products.destroy', $item->getKey()) }}" data-confirm="Archive this product? Existing order history will be retained.">@csrf @method('DELETE')<button type="submit" title="Archive product" aria-label="Archive product">×</button></form></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products</span>{{ $products->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
