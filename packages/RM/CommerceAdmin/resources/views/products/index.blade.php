@extends('commerce-admin::layout')

@section('title', 'محصولات')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">فروشگاه <span>/</span> فهرست محصولات</div><h1>محصولات</h1><p>اطلاعات کاتالوگ، تنوع‌ها، قیمت و موجودی را از دیتابیس مدیریت کنید.</p></div>
        <div class="ak-heading-actions"><a class="ak-button ak-button-dark" href="{{ route('commerce-admin.products.create') }}"><span>＋</span> افزودن محصول</a></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-four">
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">ت</span><span class="ak-stat-kicker">مجموع فروش</span></div>
            <div class="ak-stat-value">@money($stats['sales'])</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">اقلام سفارش‌های پرداخت‌شده و لغونشده</span><span class="ak-mini-badge">به‌روز</span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-blue">▤</span><span class="ak-stat-kicker">تعداد سفارش‌ها</span></div>
            <div class="ak-stat-value">{{ number_format((int) $stats['orders']) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">سفارش‌ها به‌جز موارد لغو یا مرجوع‌شده</span><a class="ak-stat-link" href="{{ route('commerce-admin.orders.index') }}">مشاهدهٔ سفارش‌ها ↗</a></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">♙</span><span class="ak-stat-kicker">مشتریان</span></div>
            <div class="ak-stat-value">{{ number_format((int) $stats['customers']) }}</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">حساب‌های مشتریان ثبت‌شده</span><span class="ak-mini-badge">به‌روز</span></div>
        </article>
        <article class="ak-stat-card">
            <div class="ak-stat-top"><span class="ak-stat-icon ak-icon-red">↘</span><span class="ak-stat-kicker">تخفیف‌ها</span></div>
            <div class="ak-stat-value">@money($stats['discount_total'])</div>
            <div class="ak-stat-footer"><span class="ak-stat-note">تخفیف سفارش‌های پرداخت‌شده، به‌جز لغو یا مرجوعی</span><span class="ak-mini-badge">{{ number_format((int) $stats['discounted']) }} تنوع تخفیف‌دار</span></div>
        </article>
    </section>

    <section class="ak-panel ak-product-list-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>فهرست محصولات <span class="ak-count-chip">{{ $products->total() }}</span></h2><p>محصولات کاتالوگ همراه با اطلاعات تنوع‌های فعلی</p></div>
            <div class="ak-toolbar">
                <button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ فیلترها <span class="ak-filter-active">{{ $status !== 'all' || $categoryId || $search !== '' ? '•' : '' }}</span></button>
                <button class="ak-button ak-button-light ak-button-sm" type="button" data-columns-toggle>☷ ستون‌ها</button>
                <a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.products.create') }}">＋ افزودن محصول</a>
            </div>
        </div>
        <form class="ak-filter-bar {{ $status !== 'all' || $categoryId || $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.products.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>جست‌وجو</span><input type="search" name="q" value="{{ $search }}" placeholder="نام محصول یا شناسهٔ SKU"></label>
            <label class="ak-inline-field"><span>دسته‌بندی</span><select name="category_id"><option value="">همهٔ دسته‌بندی‌ها</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$categoryId === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="ak-inline-field"><span>وضعیت</span><select name="status"><option value="all" @selected($status === 'all')>همهٔ محصولات</option><option value="active" @selected($status === 'active')>فعال</option><option value="draft" @selected(in_array($status, ['draft','inactive'], true))>پیش‌نویس / غیرفعال</option><option value="sale" @selected($status === 'sale')>تخفیف‌دار</option><option value="low_stock" @selected($status === 'low_stock')>موجودی کم</option></select></label>
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">اعمال فیلترها</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.products.index') }}">پاک‌کردن</a></div>
        </form>

        <div class="ak-column-popover" data-columns-panel hidden>
            <label><input type="checkbox" data-column-check="category" checked> دسته‌بندی</label>
            <label><input type="checkbox" data-column-check="rating" checked> امتیاز</label>
            <label><input type="checkbox" data-column-check="price" checked> قیمت</label>
            <label><input type="checkbox" data-column-check="stock" checked> موجودی</label>
            <label><input type="checkbox" data-column-check="status" checked> وضعیت</label>
        </div>

        @if($products->isEmpty())
            <div class="ak-empty-state"><span>▦</span><strong>محصولی با این فیلترها پیدا نشد</strong><p>فیلترها را تغییر دهید یا محصولی به کاتالوگ اضافه کنید. هیچ محصول نمایشی به‌صورت خودکار اضافه نمی‌شود.</p><a class="ak-button ak-button-primary ak-button-sm" href="{{ route('commerce-admin.products.create') }}">افزودن محصول</a></div>
        @else
            <form id="bulkArchiveForm" method="POST" action="{{ route('commerce-admin.products.bulk-archive') }}" data-confirm="محصولات انتخاب‌شده بایگانی شوند؟ سوابق سفارش‌ها حفظ می‌شود.">
                @csrf
                <button class="ak-button ak-button-light ak-button-sm" type="submit" data-bulk-archive-button disabled>بایگانی انتخاب‌شده‌ها</button>
            </form>
            <div class="ak-table-scroll"><table class="ak-table ak-products-table">
                <thead><tr><th><input type="checkbox" data-select-all aria-label="انتخاب همهٔ محصولات این صفحه"></th><th>محصول</th><th data-column="category">دسته‌بندی</th><th data-column="price">قیمت</th><th data-column="stock">موجودی</th><th data-column="rating">امتیاز</th><th data-column="status">وضعیت</th><th></th></tr></thead>
                <tbody>
                @foreach($products as $item)
                    @php
                        $variant = $item->variants->first(fn ($row) => $row->is_active && (int) $row->stock > 0)
                            ?: $item->variants->firstWhere('is_active', true)
                            ?: $item->variants->first();
                        $variantAsset = $variant?->primaryGalleryMedia;
                        $asset = $variantAsset?->url ? $variantAsset : null;
                        $asset ??= $item->galleryMedia->first(fn ($row) => $row->url);
                        $asset ??= $item->variants->first(fn ($row) => $row->primaryGalleryMedia?->url)?->primaryGalleryMedia;
                        $stock = (int) $item->variants->where('is_active', true)->sum('stock');
                        $isLow = $item->variants->contains(fn($row) => $row->is_active && $row->stock <= $row->low_stock_threshold);
                        $onSale = (bool) ($variant?->is_on_sale ?? false);
                    @endphp
                    <tr>
                        <td><input type="checkbox" form="bulkArchiveForm" name="product_ids[]" value="{{ $item->getKey() }}" aria-label="انتخاب {{ $item->name }}" data-row-select></td>
                        <td><div class="ak-product-cell">@if($asset?->url)<img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $item->name }}" loading="lazy" decoding="async">@else<span class="ak-product-thumb-placeholder">▦</span>@endif<div><a class="ak-table-primary" href="{{ route('commerce-admin.products.show', $item->getKey()) }}">{{ $item->name }}</a><small>SKU {{ $variant?->sku ?: '—' }}</small></div></div></td>
                        <td data-column="category">{{ $item->category?->name ?: 'بدون دسته‌بندی' }}</td>
                        
                        <td data-column="price"><div class="ak-price-cell"><strong>@money(($variant?->effective_price ?? $variant?->price ?? 0))</strong>@if($onSale)<small class="ak-old-price">@money(($variant?->price ?? 0))</small>@endif</div></td>
                        <td data-column="stock"><span class="ak-stock-indicator {{ $stock <= 0 ? 'is-out' : ($isLow ? 'is-low' : 'is-ok') }}"><i></i>{{ number_format($stock) }}</span></td>
                        <td data-column="rating"><div class="ak-rating-cell"><span aria-hidden="true">★</span><strong>{{ number_format((float) ($item->approved_reviews_avg_rating ?? 0), 1) }}</strong><small>({{ number_format((int) $item->approved_reviews_count) }})</small></div></td>
                        <td data-column="status"><span class="ak-status-pill {{ $item->is_active ? 'status-active' : 'status-draft' }}">{{ $item->is_active ? 'فعال' : 'پیش‌نویس' }}</span></td>
                        <td><div class="ak-row-menu"><a href="{{ route('commerce-admin.products.show', $item->getKey()) }}" title="مشاهده محصول">↗</a><a href="{{ route('commerce-admin.products.edit', $item->getKey()) }}" title="ویرایش product">✎</a><form method="POST" action="{{ route('commerce-admin.products.destroy', $item->getKey()) }}" data-confirm="این محصول بایگانی شود؟ سابقهٔ سفارش‌ها حفظ می‌شود.">@csrf @method('DELETE')<button type="submit" title="بایگانی محصول" aria-label="بایگانی محصول">×</button></form></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>نمایش {{ $products->firstItem() }} تا {{ $products->lastItem() }} از {{ $products->total() }} محصول</span>{{ $products->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
