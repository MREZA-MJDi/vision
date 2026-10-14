@extends('commerce-admin::layout')
@section('title', 'مشتری‌ها')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb">مدیریت <span>/</span> مشتری‌ها</div><h1>مشتری‌ها</h1><p>فهرست حساب‌های مشتری و خلاصهٔ سفارش‌های واقعی؛ حذف حساب از این صفحه انجام نمی‌شود تا تاریخچهٔ خرید محفوظ بماند.</p></div></div>
<section class="ak-stat-grid ak-stat-grid-three">
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">کل مشتری‌ها</span></div><div class="ak-stat-value">{{ number_format((int)$stats['all']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">مشتری دارای سفارش</span></div><div class="ak-stat-value">{{ number_format((int)$stats['with_orders']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">ثبت‌نام این ماه</span></div><div class="ak-stat-value">{{ number_format((int)$stats['new_month']) }}</div></article>
</section>
<section class="ak-panel ak-table-panel">
<div class="ak-panel-heading"><div><h2>فهرست مشتری‌ها <span class="ak-count-chip">{{ number_format($customers->total()) }}</span></h2></div></div>
<form class="ak-filter-bar is-visible" method="GET" action="{{ route('commerce-admin.customers.index') }}">
<label class="ak-inline-field"><span>جست‌وجو</span><input type="search" name="q" value="{{ $search }}" placeholder="نام، ایمیل یا تلفن"></label>
<div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">جست‌وجو</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.customers.index') }}">پاک‌کردن</a></div>
</form>
@if($customers->isEmpty())
<div class="ak-empty-state"><strong>مشتری‌ای پیدا نشد</strong><p>با تغییر متن جست‌وجو دوباره تلاش کنید.</p></div>
@else
<div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>مشتری</th><th>تلفن</th><th>تعداد سفارش</th><th>مجموع پرداخت موفق</th><th>عضویت</th><th>عملیات</th></tr></thead><tbody>
@foreach($customers as $customer)
<tr><td><strong class="ak-table-primary">{{ $customer->name ?: 'مشتری بدون نام' }}</strong><small class="ak-table-subtext">{{ $customer->email ?: 'ایمیل ثبت نشده' }}</small></td><td>{{ $customer->phone ?: '—' }}</td><td>{{ number_format((int)$customer->orders_count) }}</td><td>@commerceMoney(($customer->paid_revenue ?? 0))</td><td>@commerceDate($customer->created_at, 'j F Y')</td><td><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.customers.show',$customer->getKey()) }}">مشاهده سابقه</a></td></tr>
@endforeach
</tbody></table></div>
<div class="ak-pagination-wrap"><span>نمایش {{ $customers->firstItem() }} تا {{ $customers->lastItem() }} از {{ $customers->total() }}</span>{{ $customers->links('commerce-admin::partials.pagination') }}</div>
@endif
</section>
@endsection