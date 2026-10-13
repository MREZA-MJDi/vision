@extends('commerce-admin::layout')

@section('title', $model->name)

@section('content')
    @php
        $heroAsset = $model->galleryMedia->first();
        $primaryVariant = $model->variants->firstWhere('is_active', true) ?: $model->variants->first();
        $price = $primaryVariant?->sale_price ?? $primaryVariant?->price ?? 0;
    @endphp
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.products.index') }}">Products</a> <span>/</span> Product detail</div><h1>{{ $model->name }}</h1><p>{{ $model->category?->name ?: 'Uncategorized' }} · {{ $model->created_at?->format('M d, Y') ?: 'Date unavailable' }}</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.products.index') }}">← Product list</a><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.products.edit', $model->getKey()) }}">✎ Edit product</a><form method="POST" action="{{ route('commerce-admin.products.destroy', $model->getKey()) }}" data-confirm="Archive this product? Existing order history will be retained.">@csrf @method('DELETE')<button class="ak-icon-button ak-danger-icon-button" type="submit" aria-label="Archive product">⌫</button></form></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-four">
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">$</span><span class="ak-stat-kicker">CURRENT PRICE</span></div><div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)$price, 2) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">{{ $primaryVariant?->is_on_sale ? 'Discounted variant price' : 'Primary active variant price' }}</span><span class="ak-mini-badge">{{ $model->is_active ? 'Active' : 'Draft' }}</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">▤</span><span class="ak-stat-kicker">ORDERS CONTAINING ITEM</span></div><div class="ak-stat-value">{{ number_format($ordersCount) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Derived from order line items</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">▦</span><span class="ak-stat-kicker">AVAILABLE STOCK</span></div><div class="ak-stat-value">{{ number_format($stock) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">{{ $model->variants->count() }} total variants</span><a class="ak-stat-link" href="{{ route('commerce-admin.products.edit', $model->getKey()) }}">Manage</a></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">↗</span><span class="ak-stat-kicker">GROSS SALES</span></div><div class="ak-stat-value">{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)$grossSales, 2) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">{{ number_format($unitsSold) }} units on order records</span></div></article>
    </section>

    <div class="ak-product-detail-grid">
        <section class="ak-panel ak-product-gallery-panel">
            <div class="ak-product-hero-image">
                @if($heroAsset?->url)<img src="{{ $heroAsset->url }}" alt="{{ $heroAsset->alt_text ?: $model->name }}">@else<span class="ak-product-large-placeholder">▦<small>No product image uploaded</small></span>@endif
            </div>
            @if($model->galleryMedia->count() > 1)
                <div class="ak-gallery-thumbnails">@foreach($model->galleryMedia as $asset)<button type="button" class="ak-gallery-thumb {{ $loop->first ? 'is-active' : '' }}" data-gallery-image="{{ $asset->url }}" data-gallery-alt="{{ $asset->alt_text ?: $model->name }}">@if($asset->url)<img src="{{ $asset->url }}" alt="">@endif</button>@endforeach</div>
            @endif
            <div class="ak-product-long-description"><h2>Description</h2><p>{{ $model->description ?: ($model->short_description ?: 'No description has been saved for this product yet.') }}</p></div>
        </section>

        <section class="ak-panel ak-product-spec-panel">
            <div class="ak-product-title-row"><div><span class="ak-eyebrow">PRODUCT INFORMATION</span><h2>{{ $model->name }}</h2><div class="ak-product-subtitle">{{ $model->brand?->name ?: 'No brand' }} <span>·</span> {{ $model->category?->name ?: 'Uncategorized' }}</div></div><span class="ak-status-pill {{ $model->is_active ? 'status-active' : 'status-draft' }}">{{ $model->is_active ? 'Active' : 'Draft' }}</span></div>

            <div class="ak-spec-grid">
                <div><small>SKU</small><strong class="ak-mono">{{ $primaryVariant?->sku ?: '—' }}</strong></div>
                <div><small>Barcode</small><strong class="ak-mono">{{ $primaryVariant?->barcode ?: '—' }}</strong></div>
                <div><small>Category</small><strong>{{ $model->category?->name ?: '—' }}</strong></div>
                <div><small>Brand</small><strong>{{ $model->brand?->name ?: '—' }}</strong></div>
                <div><small>Color</small><strong>{{ $primaryVariant?->color ?: '—' }}</strong></div>
                <div><small>Variant</small><strong>{{ $primaryVariant?->size ?: 'Standard' }}</strong></div>
            </div>

            <div class="ak-spec-section"><div class="ak-spec-heading"><h3>Price and inventory by variant</h3><a class="ak-text-link" href="{{ route('commerce-admin.products.edit', $model->getKey()) }}">Edit variants ↗</a></div>
                @if($model->variants->isEmpty())<div class="ak-inline-empty"><p>No variants are attached to this product.</p></div>@else
                    <div class="ak-variant-table-wrap"><table class="ak-table ak-compact-table"><thead><tr><th>Variant</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead><tbody>
                    @foreach($model->variants as $variant)
                        <tr><td><strong>{{ $variant->display_name }}</strong><small class="ak-mono">{{ $variant->sku ?: 'No SKU' }}</small></td><td>{{ config('commerce-admin.currency_symbol', '$') }}{{ number_format((float)($variant->sale_price ?? $variant->price), 2) }}</td><td><span class="ak-stock-indicator {{ $variant->stock <= 0 ? 'is-out' : ($variant->is_low_stock ? 'is-low' : 'is-ok') }}"><i></i>{{ $variant->stock }}</span></td><td><span class="ak-status-pill {{ $variant->is_active ? 'status-active' : 'status-draft' }}">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </div>

            <div class="ak-spec-section"><h3>Product attributes</h3>
                @if(is_array($model->attributes) && count($model->attributes))
                    <div class="ak-attribute-list">@foreach($model->attributes as $key => $value)<span>{{ \Illuminate\Support\Str::headline((string)$key) }}<strong>{{ is_scalar($value) ? $value : json_encode($value) }}</strong></span>@endforeach</div>
                @else <p class="ak-muted">No additional attributes have been stored.</p> @endif
            </div>
        </section>
    </div>
@endsection
