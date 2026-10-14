@extends('commerce-admin::layout')
@section('title', 'انبار و موجودی')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb">مدیریت <span>/</span> انبار و موجودی</div><h1>انبار و موجودی</h1><p>موجودی هر تنوع محصول و دفتر رویدادهای انبار؛ هر اصلاح با دلیل و حساب مدیر ثبت می‌شود.</p></div></div>
<section class="ak-stat-grid ak-stat-grid-four">
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">تنوع فعال</span></div><div class="ak-stat-value">{{ number_format((int)$stats['variants']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">موجودی کم</span></div><div class="ak-stat-value">{{ number_format((int)$stats['low_stock']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">ناموجود</span></div><div class="ak-stat-value">{{ number_format((int)$stats['out_of_stock']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">ثبت‌های تاریخچه</span></div><div class="ak-stat-value">{{ number_format((int)$stats['movements']) }}</div></article>
</section>
<section class="ak-panel ak-table-panel"><div class="ak-panel-heading"><div><h2>موجودی تنوع‌ها <span class="ak-count-chip">{{ number_format($variants->total()) }}</span></h2><p>مقدار اصلاح مثبت برای افزودن و منفی برای کسر از موجودی است.</p></div></div>
<form class="ak-filter-bar is-visible" method="GET" action="{{ route('commerce-admin.inventory.index') }}">
<label class="ak-inline-field"><span>جست‌وجو</span><input type="search" name="q" value="{{ $search }}" placeholder="نام محصول یا SKU"></label>
<label class="ak-inline-field"><span>فیلتر</span><select name="status"><option value="all" @selected($status==='all')>همه</option><option value="low_stock" @selected($status==='low_stock')>موجودی کم</option><option value="out_of_stock" @selected($status==='out_of_stock')>ناموجود</option><option value="inactive" @selected($status==='inactive')>غیرفعال</option></select></label>
<div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">اعمال</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.inventory.index') }}">پاک‌کردن</a></div>
</form>
@if($variants->isEmpty())<div class="ak-empty-state"><strong>تنوعی پیدا نشد</strong></div>
@else
<div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>محصول / تنوع</th><th>SKU</th><th>موجودی</th><th>حد هشدار</th><th>وضعیت</th><th>اصلاح موجودی</th></tr></thead><tbody>
@foreach($variants as $variant)
<tr><td><strong class="ak-table-primary">{{ $variant->product?->name ?: 'محصول آرشیوشده' }}</strong><small class="ak-table-subtext">{{ collect([$variant->color,$variant->size])->filter()->implode(' / ') ?: 'تنوع پیش‌فرض' }}</small></td><td><span class="ak-mono">{{ $variant->sku }}</span></td><td><strong>{{ number_format((int)$variant->stock) }}</strong></td><td>{{ number_format((int)$variant->low_stock_threshold) }}</td><td><span class="ak-status-pill {{ $variant->stock <= 0 ? 'status-cancelled' : ($variant->stock <= $variant->low_stock_threshold ? 'status-pending' : 'status-active') }}">{{ $variant->stock <= 0 ? 'ناموجود' : ($variant->stock <= $variant->low_stock_threshold ? 'موجودی کم' : 'موجود') }}</span></td><td><form method="POST" action="{{ route('commerce-admin.inventory.adjust', ['variant'=>$variant->getKey(),'q'=>$search,'status'=>$status,'page'=>$variants->currentPage()]) }}" class="ak-toolbar">@csrf<input aria-label="تغییر موجودی" type="number" name="adjustment" required min="-100000" max="100000" placeholder="+/-" style="width:75px;min-height:30px"><input aria-label="دلیل تغییر" type="text" name="note" required minlength="3" maxlength="500" placeholder="دلیل تغییر" style="width:150px;min-height:30px"><button class="ak-button ak-button-dark ak-button-sm" type="submit">ثبت</button></form></td></tr>
@endforeach
</tbody></table></div>
<div class="ak-pagination-wrap"><span>نمایش {{ $variants->firstItem() }} تا {{ $variants->lastItem() }} از {{ $variants->total() }}</span>{{ $variants->links('commerce-admin::partials.pagination') }}</div>
@endif
</section>
<section class="ak-panel ak-table-panel"><div class="ak-panel-heading"><div><h2>دفتر گردش موجودی <span class="ak-count-chip">{{ number_format($movements->total()) }}</span></h2><p>تغییرات دستی، فروش، برگشت و خرید در تاریخچهٔ موجودی نگه‌داری می‌شوند.</p></div></div>
@if($movements->isEmpty())<div class="ak-empty-state"><strong>هنوز حرکتی ثبت نشده است</strong></div>
@else
<div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>زمان</th><th>محصول / تنوع</th><th>نوع</th><th>تغییر</th><th>موجودی پس از تغییر</th><th>ثبت‌کننده</th><th>دلیل</th></tr></thead><tbody>
@foreach($movements as $movement)
<tr><td>@jalali($movement->created_at, 'j F Y H:i')</td><td>{{ $movement->productVariant?->product?->name ?: ('شناسه تنوع ' . $movement->product_variant_id) }}<small class="ak-table-subtext">{{ $movement->productVariant?->sku ?: '' }}</small></td><td>{{ config('commerce-admin.movement_labels.' . $movement->type, \Illuminate\Support\Str::headline($movement->type)) }}</td><td><strong>{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format((int)$movement->quantity) }}</strong></td><td>{{ number_format((int)$movement->stock_after) }}</td><td>{{ $movement->createdBy?->name ?: 'سیستم / کاربر حذف‌شده' }}</td><td>{{ $movement->note ?: '—' }}</td></tr>
@endforeach
</tbody></table></div>
<div class="ak-pagination-wrap"><span>نمایش {{ $movements->firstItem() }} تا {{ $movements->lastItem() }} از {{ $movements->total() }}</span>{{ $movements->links('commerce-admin::partials.pagination') }}</div>
@endif
</section>
@endsection