@props(['pagination'])

@php
    $current = max(1, (int) ($pagination['currentPage'] ?? 1));
    $last = max(1, (int) ($pagination['lastPage'] ?? 1));
    $start = max(1, $current - 2);
    $end = min($last, $current + 2);
    $pageUrl = fn (int $page) => request()->fullUrlWithQuery(['page' => $page]) . '#catalog';
@endphp

@if ($last > 1)
    <nav class="store-pagination" aria-label="صفحه‌بندی محصولات">
        <p class="store-pagination__summary">
            نمایش
            <strong>{{ number_format((int) ($pagination['from'] ?? 0)) }}</strong>
            تا
            <strong>{{ number_format((int) ($pagination['to'] ?? 0)) }}</strong>
            از
            <strong>{{ number_format((int) ($pagination['total'] ?? 0)) }}</strong>
            محصول
        </p>

        <div class="store-pagination__controls">
            @if ($current > 1)
                <a class="store-pagination__arrow"
                   href="{{ $pageUrl($current - 1) }}"
                   rel="prev"
                   aria-label="صفحه قبلی"
                   title="صفحه قبلی">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span class="store-pagination__arrow is-disabled" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif

            @if ($start > 1)
                <a class="store-pagination__number" href="{{ $pageUrl(1) }}" aria-label="صفحه ۱">۱</a>
                @if ($start > 2)
                    <span class="store-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @foreach (range($start, $end) as $page)
                @if ($page === $current)
                    <span class="store-pagination__number is-current"
                          aria-current="page"
                          aria-label="صفحه {{ $page }}">{{ number_format($page) }}</span>
                @else
                    <a class="store-pagination__number"
                       href="{{ $pageUrl($page) }}"
                       aria-label="رفتن به صفحه {{ $page }}">{{ number_format($page) }}</a>
                @endif
            @endforeach

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="store-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="store-pagination__number"
                   href="{{ $pageUrl($last) }}"
                   aria-label="صفحه {{ $last }}">{{ number_format($last) }}</a>
            @endif

            @if ($current < $last)
                <a class="store-pagination__arrow"
                   href="{{ $pageUrl($current + 1) }}"
                   rel="next"
                   aria-label="صفحه بعدی"
                   title="صفحه بعدی">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @else
                <span class="store-pagination__arrow is-disabled" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
