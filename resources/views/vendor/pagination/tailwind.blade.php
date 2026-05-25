@if ($paginator->hasPages())
<nav class="custom-pagination" aria-label="Pagination">
    <div class="page-info">
        {{ __('Showing') }}
        <strong>{{ $paginator->firstItem() }}</strong>
        {{ __('to') }}
        <strong>{{ $paginator->lastItem() }}</strong>
        {{ __('of') }}
        <strong>{{ $paginator->total() }}</strong>
        {{ __('results') }}
    </div>
    <div class="page-links">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="page-btn page-btn-disabled" aria-disabled="true">
                <i class="fas fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-btn" rel="prev" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-btn page-btn-dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-btn page-btn-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-btn" rel="next" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </a>
        @else
            <span class="page-btn page-btn-disabled" aria-disabled="true">
                <i class="fas fa-chevron-right"></i>
            </span>
        @endif
    </div>
</nav>
@endif
