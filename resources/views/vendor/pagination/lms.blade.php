@if ($paginator->hasPages())
<nav class="flex items-center gap-1 text-sm" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="rounded-lg px-3 py-1.5 text-slate-300">‹ Prev</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-3 py-1.5 text-slate-600 hover:bg-violet-100">‹ Prev</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))<span class="px-1 text-slate-400">{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="rounded-lg bg-[#5b21b6] px-3 py-1.5 font-semibold text-white shadow">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="rounded-lg px-3 py-1.5 text-slate-600 hover:bg-violet-100">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-3 py-1.5 text-slate-600 hover:bg-violet-100">Next ›</a>
    @else
        <span class="rounded-lg px-3 py-1.5 text-slate-300">Next ›</span>
    @endif
</nav>
@endif
