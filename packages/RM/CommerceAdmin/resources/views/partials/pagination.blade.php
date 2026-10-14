@if ($paginator->hasPages())
    <nav class="ak-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())<span class="is-disabled" aria-disabled="true">←</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="صفحهٔ قبل">←</a>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="is-disabled">{{ $element }}</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="is-current" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="صفحهٔ بعد">→</a>@else<span class="is-disabled" aria-disabled="true">→</span>@endif
    </nav>
@endif
