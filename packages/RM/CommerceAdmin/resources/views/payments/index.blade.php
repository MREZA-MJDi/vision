@extends('commerce-admin::layout')
@section('title', 'پرداخت‌ها')
@section('content')
<div class="ak-page-heading"><div><div class="ak-breadcrumb">مدیریت <span>/</span> پرداخت‌ها</div><h1>داشبورد پرداخت</h1><p>وضعیت پرداخت‌ها مستقیماً از جدول پرداخت خوانده می‌شود؛ تغییر وضعیت مالی از گردش امن سفارش انجام می‌شود.</p></div></div>
<section class="ak-stat-grid ak-stat-grid-four">
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">کل تراکنش‌ها</span></div><div class="ak-stat-value">{{ number_format((int)$stats['all']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">پرداخت موفق</span></div><div class="ak-stat-value">{{ number_format((int)$stats['paid']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">در انتظار</span></div><div class="ak-stat-value">{{ number_format((int)$stats['pending']) }}</div></article>
<article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-kicker">بازپرداخت</span></div><div class="ak-stat-value">{{ number_format((int)$stats['refunded']) }}</div></article>
</section>
<section class="ak-panel ak-table-panel"><div class="ak-panel-heading"><div><h2>تراکنش‌ها <span class="ak-count-chip">{{ number_format($payments->total()) }}</span></h2><p>مجموع پرداخت‌های موفق: {{ config('commerce-admin.currency_symbol','$') }}{{ number_format((float)$stats['paid_total'],2) }}</p></div></div>
<form class="ak-filter-bar is-visible" method="GET" action="{{ route('commerce-admin.payments.index') }}">
<label class="ak-inline-field"><span>جست‌وجو</span><input type="search" name="q" value="{{ $search }}" placeholder="شماره سفارش، مشتری یا شناسه تراکنش"></label>
<label class="ak-inline-field"><span>وضعیت</span><select name="status"><option value="all" @selected($status==='all')>همه</option>@foreach($paymentStatuses as $paymentStatus)<option value="{{ $paymentStatus }}" @selected($status===$paymentStatus)>{{ config('commerce-admin.status_labels.' . $paymentStatus, \Illuminate\Support\Str::headline($paymentStatus)) }}</option>@endforeach</select></label>
<div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">اعمال</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.payments.index') }}">پاک‌کردن</a></div>
</form>
@if($payments->isEmpty())<div class="ak-empty-state"><strong>تراکنشی پیدا نشد</strong><p>پس از ثبت سفارش‌ها، پرداخت‌های واقعی اینجا نمایش داده می‌شوند.</p></div>
@else
<div class="ak-table-scroll"><table class="ak-table"><thead><tr><th>سفارش</th><th>مشتری</th><th>درگاه</th><th>شناسهٔ مرجع</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead><tbody>
@foreach($payments as $payment)
<tr><td><strong class="ak-table-primary">#{{ $payment->order?->order_number ?: 'سفارش حذف‌شده' }}</strong></td><td>{{ $payment->order?->customer_name ?: '—' }}<small class="ak-table-subtext">{{ $payment->order?->customer_email ?: '' }}</small></td><td>{{ $payment->gateway }}</td><td><span class="ak-mono">{{ $payment->reference_number ?: $payment->transaction_id ?: '—' }}</span></td><td>{{ config('commerce-admin.currency_symbol','$') }}{{ number_format((float)$payment->amount,2) }}</td><td><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($payment->status) }}">{{ \Illuminate\Support\Str::headline($payment->status) }}</span></td><td>{{ $payment->created_at?->locale('fa')->translatedFormat('j M Y') ?: '—' }}</td><td>@if($payment->order)<a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.orders.show',$payment->order->getKey()) }}">جزئیات سفارش</a>@endif</td></tr>
@endforeach
</tbody></table></div>
<div class="ak-pagination-wrap"><span>نمایش {{ $payments->firstItem() }} تا {{ $payments->lastItem() }} از {{ $payments->total() }}</span>{{ $payments->links('commerce-admin::partials.pagination') }}</div>
@endif
</section>
@endsection