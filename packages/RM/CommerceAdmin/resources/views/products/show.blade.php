@extends('commerce-admin::layout')

@section('title', $model->name)

@section('content')
    @php
        $heroAsset = $model->galleryMedia->first();
        $primaryVariant = $model->variants->firstWhere('is_active', true) ?: $model->variants->first();
        $price = $primaryVariant?->sale_price ?? $primaryVariant?->price ?? 0;
        $colors = $model->variants->pluck('color')->filter()->unique()->values();
        $sizes = $model->variants->pluck('size')->filter()->unique()->values();
        $currency = config('commerce-admin.currency_symbol', '$');
    @endphp

    <div class="ak-page-heading">
        <div>
            <div class="ak-breadcrumb"><a href="{{ route('commerce-admin.products.index') }}">Product List</a> <span>/</span> Product Detail</div>
            <h1>{{ $model->name }}</h1>
            <p>{{ $model->brand?->name ?: 'No brand' }} · Published {{ $model->created_at?->format('M d, Y') ?: 'date unavailable' }} · SKU {{ $primaryVariant?->sku ?: 'not assigned' }}</p>
        </div>
        <div class="ak-heading-actions">
            <a class="ak-button ak-button-light" href="{{ route('commerce-admin.products.edit', $model->getKey()) }}">✎ Edit</a>
            <form method="POST" action="{{ route('commerce-admin.products.destroy', $model->getKey()) }}" data-confirm="Archive this product? Existing order history will be retained.">
                @csrf
                @method('DELETE')
                <button class="ak-icon-button ak-danger-icon-button" type="submit" aria-label="Archive product" title="Archive product">⌫</button>
            </form>
        </div>
    </div>

    <div class="ak-product-reference-grid">
        <section class="ak-panel ak-product-reference-gallery">
            <div class="ak-product-hero-image">
                @if($heroAsset?->url)
                    <img src="{{ $heroAsset->url }}" alt="{{ $heroAsset->alt_text ?: $model->name }}">
                @else
                    <span class="ak-product-large-placeholder" aria-label="No product image">▦<small>No product image uploaded</small></span>
                @endif
            </div>
            @if($model->galleryMedia->count() > 1)
                <div class="ak-gallery-thumbnails">
                    @foreach($model->galleryMedia as $asset)
                        @if($asset->url)
                            <button type="button" class="ak-gallery-thumb {{ $loop->first ? 'is-active' : '' }}" data-gallery-image="{{ $asset->url }}" data-gallery-alt="{{ $asset->alt_text ?: $model->name }}" aria-label="Show image {{ $loop->iteration }}">
                                <img src="{{ $asset->url }}" alt="">
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="ak-gallery-caption">
                <span>{{ $model->galleryMedia->count() }} product images</span>
                <span class="ak-status-pill {{ $model->is_active ? 'status-active' : 'status-draft' }}">{{ $model->is_active ? 'Active' : 'Draft' }}</span>
            </div>
        </section>

        <div class="ak-product-reference-details">
            <section class="ak-product-inline-stats">
                <article class="ak-product-inline-stat">
                    <span>Price</span>
                    <strong>{{ $currency }}{{ number_format((float) $price, 2) }}</strong>
                    <small>{{ $primaryVariant?->is_on_sale ? 'Sale price' : 'Base price' }}</small>
                </article>
                <article class="ak-product-inline-stat">
                    <span>No. of Orders</span>
                    <strong>{{ number_format((int) $ordersCount) }}</strong>
                    <small>Orders containing this item</small>
                </article>
                <article class="ak-product-inline-stat">
                    <span>Available Stock</span>
                    <strong>{{ number_format((int) $stock) }}</strong>
                    <small>{{ number_format($model->variants->count()) }} variants</small>
                </article>
                <article class="ak-product-inline-stat">
                    <span>Total Revenue</span>
                    <strong>{{ $currency }}{{ number_format((float) $grossSales, 2) }}</strong>
                    <small>{{ number_format((int) $unitsSold) }} units sold</small>
                </article>
            </section>

            <section class="ak-panel ak-product-info-card">
                <div class="ak-product-section-title"><h2>Description</h2><span class="ak-eyebrow">PRODUCT INFORMATION</span></div>
                <p class="ak-description-copy">{{ $model->description ?: ($model->short_description ?: 'No description has been saved for this product yet.') }}</p>
                <div class="ak-product-detail-divider"></div>
                <div class="ak-attribute-grid">
                    <div><small>Category</small><strong>{{ $model->category?->name ?: 'Uncategorized' }}</strong></div>
                    <div><small>Brand</small><strong>{{ $model->brand?->name ?: 'No brand' }}</strong></div>
                    <div><small>Product status</small><strong>{{ $model->is_active ? 'Active' : 'Draft' }}</strong></div>
                    <div><small>Tax</small><strong>{{ $model->is_taxable ? 'Taxable' : 'Not taxable' }}</strong></div>
                </div>
                @if($colors->isNotEmpty())
                    <div class="ak-variant-attribute">
                        <small>Colors</small>
                        <div class="ak-color-swatches">
                            @foreach($colors as $color)
                                @php
                                    $colorVariant = $model->variants->firstWhere('color', $color);
                                    $colorHex = $colorVariant?->color_code ?: '#d9dce2';
                                @endphp
                                <span class="ak-color-swatch" title="{{ $color }}" style="--product-color: {{ $colorHex }}"></span>
                            @endforeach
                            <span class="ak-color-names">{{ $colors->implode(', ') }}</span>
                        </div>
                    </div>
                @endif
                @if($sizes->isNotEmpty())
                    <div class="ak-variant-attribute">
                        <small>Sizes / variants</small>
                        <div class="ak-size-chips">@foreach($sizes as $size)<span>{{ $size }}</span>@endforeach</div>
                    </div>
                @endif
            </section>

            <section class="ak-panel ak-product-variants-panel">
                <div class="ak-panel-heading">
                    <div><h2>Variants & inventory</h2><p>Prices, SKUs and stock levels from the product record.</p></div>
                    <a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.products.edit', $model->getKey()) }}">Manage variants ↗</a>
                </div>
                @if($model->variants->isEmpty())
                    <div class="ak-compact-empty">No variants have been created for this product yet.</div>
                @else
                    <div class="ak-table-scroll">
                        <table class="ak-table">
                            <thead><tr><th>Variant</th><th>SKU / Barcode</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($model->variants as $variant)
                                <tr>
                                    <td>
                                        <div class="ak-variant-name-cell">
                                            @if($variant->color_code)<span class="ak-variant-color-dot" style="--product-color: {{ $variant->color_code }}"></span>@endif
                                            <span>{{ $variant->display_name ?: collect([$variant->color, $variant->size])->filter()->implode(' / ') ?: 'Default variant' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="ak-mono">{{ $variant->sku ?: '—' }}</span><small class="ak-table-subtext">{{ $variant->barcode ?: 'No barcode' }}</small></td>
                                    <td><strong>{{ $currency }}{{ number_format((float) ($variant->sale_price ?? $variant->price ?? 0), 2) }}</strong></td>
                                    <td>{{ number_format((int) $variant->stock) }}</td>
                                    <td><span class="ak-status-pill {{ $variant->is_active ? 'status-active' : 'status-draft' }}">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
