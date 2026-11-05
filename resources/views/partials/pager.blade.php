{{-- Table footer: "Showing a-b of N", rows-per-page selector, page links. Usage: @include('partials.pager', ['paginator' => $rows]) --}}
<div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row">
    <div>Showing <b>{{ $paginator->firstItem() ?? 0 }}</b> – <b>{{ $paginator->lastItem() ?? 0 }}</b> of <b>{{ $paginator->total() }}</b> records</div>
    <div class="flex flex-wrap items-center justify-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            @foreach (request()->except(['per_page', 'page']) as $k => $v)
                @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            <label class="text-xs text-slate-500">Rows</label>
            <select name="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm">
                @foreach ([10, 20, 25, 50, 100] as $n)<option value="{{ $n }}" @selected($paginator->perPage() == $n)>{{ $n }}</option>@endforeach
            </select>
        </form>
        {{ $paginator->onEachSide(1)->links('vendor.pagination.lms') }}
    </div>
</div>
