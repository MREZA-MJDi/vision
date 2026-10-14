@extends('commerce-admin::layout')

@section('title', 'نظرات مشتریان')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">فروشگاه <span>/</span> نظرات مشتریان</div><h1>نظرات مشتریان</h1><p>نظرات ارسال‌شده را بررسی کنید. فقط نظرات تأییدشده در امتیاز محصول و فروشگاه محاسبه می‌شوند.</p></div>
        <div class="ak-heading-actions"><span class="ak-date-chip">★ {{ number_format((float)$stats['average'], 2) }} میانگین امتیاز نظرات تأییدشده</span></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-three">
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-yellow">◷</span><span class="ak-stat-kicker">در انتظار بررسی</span></div><div class="ak-stat-value">{{ number_format($stats['pending']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">هنوز در امتیاز فروشگاه لحاظ نشده</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">★</span><span class="ak-stat-kicker">نظرات تأییدشده</span></div><div class="ak-stat-value">{{ number_format($stats['approved']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">نظرات قابل مشاهده برای مشتریان</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">▤</span><span class="ak-stat-kicker">نظرات ردشده</span></div><div class="ak-stat-value">{{ number_format($stats['rejected']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">برای حفظ سابقهٔ بررسی نگه‌داری می‌شود</span></div></article>
    </section>

    <section class="ak-panel ak-table-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>فهرست نظرات <span class="ak-count-chip">{{ $نظر->total() }}</span></h2><p>نظرات واقعی دیتابیس؛ هیچ امتیاز نمایشی اضافه نمی‌شود.</p></div>
            <div class="ak-toolbar"><button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ فیلترها</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.analytics') }}">گزارش تحلیلی ↗</a></div>
        </div>

        <div class="ak-order-tabs" role="tablist" aria-label="نظر moderation status">
            <a class="{{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('commerce-admin.نظر.index', ['status'=>'pending']) }}">در انتظار <span>{{ number_format($stats['pending']) }}</span></a>
            <a class="{{ $status === 'approved' ? 'is-active' : '' }}" href="{{ route('commerce-admin.نظر.index', ['status'=>'approved']) }}">تأییدشده <span>{{ number_format($stats['approved']) }}</span></a>
            <a class="{{ $status === 'rejected' ? 'is-active' : '' }}" href="{{ route('commerce-admin.نظر.index', ['status'=>'rejected']) }}">ردشده <span>{{ number_format($stats['rejected']) }}</span></a>
            <a class="{{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('commerce-admin.نظر.index', ['status'=>'all']) }}">همه</a>
        </div>

        <form class="ak-filter-bar {{ $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.نظر.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>جست‌وجو نظر</span><input type="search" name="q" value="{{ $search }}" placeholder="نظر‌دهنده, email, product or title"></label>
            <label class="ak-inline-field"><span>وضعیت</span><select name="status"><option value="all" @selected($status === 'all')>همهٔ وضعیت‌ها</option><option value="pending" @selected($status === 'pending')>در انتظار</option><option value="approved" @selected($status === 'approved')>تأییدشده</option><option value="rejected" @selected($status === 'rejected')>ردشده</option></select></label>
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">اعمال</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.نظر.index', ['status'=>'pending']) }}">پاک‌کردن</a></div>
        </form>

        @if($نظر->isEmpty())
            <div class="ak-empty-state"><span>★</span><strong>نظری در این بخش پیدا نشد</strong><p>نظرات ارسال‌شده در این بخش نمایش داده می‌شوند. تا تأیید نظر واقعی، امتیاز محصول خالی می‌ماند.</p></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table ak-نظر-table">
                <thead><tr><th>نظر‌دهنده</th><th>محصول</th><th>امتیاز</th><th>نظر</th><th>تاریخ</th><th>وضعیت</th><th>بررسی</th></tr></thead>
                <tbody>
                @foreach($نظر as $review)
                    <tr>
                        <td><div class="ak-customer-cell"><span class="ak-avatar ak-avatar-tiny">{{ strtoupper(substr($review->reviewer_name ?: $review->user?->name ?: 'G', 0, 1)) }}</span><span><strong>{{ $review->reviewer_name ?: $review->user?->name ?: 'Guest reviewer' }}</strong><small>{{ $review->reviewer_email ?: $review->user?->email ?: 'No email saved' }}</small></span></div></td>
                        <td><a class="ak-table-primary" href="{{ $review->product ? route('commerce-admin.محصول.show', $review->product->getKey()) : '#' }}">{{ $review->product?->name ?: 'Deleted product' }}</a></td>
                        <td><span class="ak-rating-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', max(0,min(5,(int)$review->rating))) }}<small>{{ $review->rating }}/5</small></span></td>
                        <td class="ak-review-text"><strong>{{ $review->title ?: 'مشتری review' }}</strong><p>{{ \Illuminate\Support\Str::limit($review->body ?: 'No written comment.', 130) }}</p></td>
                        <td>{{ $review->created_at?->format('M d, Y') ?: '—' }}</td>
                        <td><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($review->status) }}">{{ config('commerce-admin.status_labels.' . $review->status, \Illuminate\Support\Str::headline($review->status)) }}</span></td>
                        <td><form method="POST" action="{{ route('commerce-admin.نظر.status', [$review->getKey(), 'status'=>$status]) }}" class="ak-review-moderation">@csrf @method('PATCH')<select name="status" aria-label="نظر status">@foreach($reviewوضعیتes as $reviewوضعیت)<option value="{{ $reviewوضعیت }}" @selected($review->status === $reviewوضعیت)>{{ \Illuminate\Support\Str::headline($reviewوضعیت) }}</option>@endforeach</select><button class="ak-button ak-button-dark ak-button-sm" type="submit">ذخیره</button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>نمایش {{ $نظر->firstItem() }}–{{ $نظر->lastItem() }} of {{ $نظر->total() }} نظر</span>{{ $نظر->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
