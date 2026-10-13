@extends('commerce-admin::layout')

@section('title', 'Customer reviews')

@section('content')
    <div class="ak-page-heading">
        <div><div class="ak-breadcrumb">E-Commerce <span>/</span> Customer reviews</div><h1>Customer reviews</h1><p>Moderate submitted ratings. Only approved reviews contribute to product and storefront ratings.</p></div>
        <div class="ak-heading-actions"><span class="ak-date-chip">★ {{ number_format((float)$stats['average'], 2) }} average approved rating</span></div>
    </div>

    <section class="ak-stat-grid ak-stat-grid-three">
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-yellow">◷</span><span class="ak-stat-kicker">PENDING REVIEW</span></div><div class="ak-stat-value">{{ number_format($stats['pending']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Not included in storefront ratings yet</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-green">★</span><span class="ak-stat-kicker">APPROVED REVIEWS</span></div><div class="ak-stat-value">{{ number_format($stats['approved']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Live reviews visible to customers</span></div></article>
        <article class="ak-stat-card"><div class="ak-stat-top"><span class="ak-stat-icon ak-icon-slate">▤</span><span class="ak-stat-kicker">REJECTED REVIEWS</span></div><div class="ak-stat-value">{{ number_format($stats['rejected']) }}</div><div class="ak-stat-footer"><span class="ak-stat-note">Retained for moderation history</span></div></article>
    </section>

    <section class="ak-panel ak-table-panel">
        <div class="ak-panel-heading ak-panel-heading-wrap">
            <div><h2>Review inbox <span class="ak-count-chip">{{ $reviews->total() }}</span></h2><p>Real review rows from the database; no demo ratings are injected.</p></div>
            <div class="ak-toolbar"><button class="ak-button ak-button-light ak-button-sm" type="button" data-toggle-filters>⚙ Filters</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.analytics') }}">Analytics ↗</a></div>
        </div>

        <div class="ak-order-tabs" role="tablist" aria-label="Review moderation status">
            <a class="{{ $status === 'pending' ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index', ['status'=>'pending']) }}">Pending <span>{{ number_format($stats['pending']) }}</span></a>
            <a class="{{ $status === 'approved' ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index', ['status'=>'approved']) }}">Approved <span>{{ number_format($stats['approved']) }}</span></a>
            <a class="{{ $status === 'rejected' ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index', ['status'=>'rejected']) }}">Rejected <span>{{ number_format($stats['rejected']) }}</span></a>
            <a class="{{ $status === 'all' ? 'is-active' : '' }}" href="{{ route('commerce-admin.reviews.index', ['status'=>'all']) }}">All</a>
        </div>

        <form class="ak-filter-bar {{ $search !== '' ? 'is-visible' : '' }}" method="GET" action="{{ route('commerce-admin.reviews.index') }}" data-filter-panel>
            <label class="ak-inline-field"><span>Search reviews</span><input type="search" name="q" value="{{ $search }}" placeholder="Reviewer, email, product or title"></label>
            <label class="ak-inline-field"><span>Status</span><select name="status"><option value="all" @selected($status === 'all')>All statuses</option><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option><option value="rejected" @selected($status === 'rejected')>Rejected</option></select></label>
            <div class="ak-filter-actions"><button class="ak-button ak-button-dark ak-button-sm" type="submit">Apply</button><a class="ak-button ak-button-light ak-button-sm" href="{{ route('commerce-admin.reviews.index', ['status'=>'pending']) }}">Reset</a></div>
        </form>

        @if($reviews->isEmpty())
            <div class="ak-empty-state"><span>★</span><strong>No reviews in this view</strong><p>Submitted reviews will appear here. Product ratings stay blank until real reviews are approved.</p></div>
        @else
            <div class="ak-table-scroll"><table class="ak-table ak-reviews-table">
                <thead><tr><th>Reviewer</th><th>Product</th><th>Rating</th><th>Review</th><th>Date</th><th>Status</th><th>Moderate</th></tr></thead>
                <tbody>
                @foreach($reviews as $review)
                    <tr>
                        <td><div class="ak-customer-cell"><span class="ak-avatar ak-avatar-tiny">{{ strtoupper(substr($review->reviewer_name ?: $review->user?->name ?: 'G', 0, 1)) }}</span><span><strong>{{ $review->reviewer_name ?: $review->user?->name ?: 'Guest reviewer' }}</strong><small>{{ $review->reviewer_email ?: $review->user?->email ?: 'No email saved' }}</small></span></div></td>
                        <td><a class="ak-table-primary" href="{{ $review->product ? route('commerce-admin.products.show', $review->product->getKey()) : '#' }}">{{ $review->product?->name ?: 'Deleted product' }}</a></td>
                        <td><span class="ak-rating-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', max(0,min(5,(int)$review->rating))) }}<small>{{ $review->rating }}/5</small></span></td>
                        <td class="ak-review-text"><strong>{{ $review->title ?: 'Customer review' }}</strong><p>{{ \Illuminate\Support\Str::limit($review->body ?: 'No written comment.', 130) }}</p></td>
                        <td>{{ $review->created_at?->format('M d, Y') ?: '—' }}</td>
                        <td><span class="ak-status-pill status-{{ \Illuminate\Support\Str::slug($review->status) }}">{{ \Illuminate\Support\Str::headline($review->status) }}</span></td>
                        <td><form method="POST" action="{{ route('commerce-admin.reviews.status', [$review->getKey(), 'status'=>$status]) }}" class="ak-review-moderation">@csrf @method('PATCH')<select name="status" aria-label="Review status">@foreach(\App\Models\ProductReview::STATUSES as $reviewStatus)<option value="{{ $reviewStatus }}" @selected($review->status === $reviewStatus)>{{ \Illuminate\Support\Str::headline($reviewStatus) }}</option>@endforeach</select><button class="ak-button ak-button-dark ak-button-sm" type="submit">Save</button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="ak-pagination-wrap"><span>Showing {{ $reviews->firstItem() }}–{{ $reviews->lastItem() }} of {{ $reviews->total() }} reviews</span>{{ $reviews->links('commerce-admin::partials.pagination') }}</div>
        @endif
    </section>
@endsection
