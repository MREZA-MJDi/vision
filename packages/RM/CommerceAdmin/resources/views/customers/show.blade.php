@extends('commerce-admin::layout')
@section('title', 'جزئیات مشتری')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb"><a href="{{ route('commerce-admin.customers.index') }}">مشتری‌ها</a> <span>/</span> جزئیات</div><h1>{{ $record->name ?: 'مشتری بدون نام' }}</h1><p>{{ $record->email ?: 'ایمیل ثبت نشده' }} · تاریخچهٔ سفارش و پرداخت</p></div><div class="ak-heading-actions"><a class="ak-button ak-button-light" href="{{ route('commerce-admin.customers.index') }}">بازگشت</a></div></div>
<section class="ak-stat-grid ak-stat-grid-three">
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">کل سفارش‌ها</span></div><div class="ak-stat-value">{{ number_format((int)$record->orders_count) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">پرداخت موفق</span></div><div class="ak-stat-value">{{ config('commerce-admin.currency_symbol','$') }}{{ number_format((float)($record->paid_revenue ?? 0),2) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">تاریخ عضویت</span></div><div class="ak-stat-value">{{ $record->created_at?->locale('fa')->translatedFormat('j M Y') ?: '—' }}</div></article>
</section>
<section class="ak-panel ak-table-panel"><div class="ak-panel-heading"><div><h2>سابقهٔ سفارش‌ها</h2><p>تاریخچه برای حفظ ردپای مالی قابل حذف نیست.</p></div></div>
@if($orders->isEmpty())<div class="ak-empty-state"><strong>سفارشی ثبت نشده است</strong></div>
@else
<div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>شماره</th><th>اقلام</th><th>تاریخ</th><th>وضعیت</th><th>پرداخت</th><th>مبلغ</th><th></th></tr></thead><tbody>
@foreach($orders as $order)
<tr><td><strong class="ak-table-primary">#{{ $order->order_number }}</strong></td><td>{{ $order->items->first()?->product_name ?: '—' }} @if($order->items->count()>1)<small class="ak-table-subtext">و {{ $order->items->count()-1 }} مورد دیگر</small>@endif</td><td>{{ optional($order->placed_at ?: $order->created_at)->locale('fa')->translatedFormat('j M Y') }}</td><td><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($order->status) }}">{{ config('commerce-admin.status_labels.' . $order->status, \Illuminate\Support\Str::headline($order->status)) }}</span></td><td><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($order->payment_status) }}">{{ config('commerce-admin.status_labels.' . $order->payment_status, \Illuminate\Support\Str::headline($order->payment_status)) }}</span></td><td>{{ config('commerce-admin.currency_symbol','$') }}{{ number_format((float)$order->total,2) }}</td><td><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.orders.show',$order->getKey()) }}">سفارش</a></td></tr>
@endforeach
</tbody></table></div>
<div class="ak-pagination-wrap"><span>نمایش {{ $orders->firstItem() }} تا {{ $orders->lastItem() }} از {{ $orders->total() }}</span>{{ $orders->links('commerce-admin::partials.pagination') }}</div>
@endif
</section>
@endsection