@extends('layouts.app')
@section('title', 'Catalog')

@section('hero')
<section class="relative isolate overflow-hidden bg-[#2a0d6b] text-white">
    @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-50 blur-[3px]'])
    <div class="absolute inset-0 bg-gradient-to-r from-[#2a0d6b] via-[#4c1d95]/85 to-[#4c1d95]/30"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-10 md:py-14">
        <h1 class="text-3xl font-extrabold sm:text-4xl">E-book Catalog</h1>
        <p class="mt-1 text-violet-100">{{ $groups->sum(fn ($g) => $g->count()) }} titles{{ $search !== '' ? " matching “{$search}”" : '' }}{{ $selectedDept ? ' in ' . $selectedDept->name : '' }}</p>
        <form method="GET" class="mt-5 flex max-w-3xl flex-col gap-3 sm:flex-row">
            <input type="hidden" name="filter_by" value="{{ $filterBy }}">
            @if ($selectedDept)<input type="hidden" name="dept_id" value="{{ $selectedDept->id }}">@endif
            <div class="flex flex-1 items-center overflow-hidden rounded-full bg-white shadow-xl">
                <span class="pl-4 text-violet-500">@include('partials.icon', ['name' => 'search', 'class' => 'h-5 w-5'])</span>
                <input name="search" value="{{ $search }}" placeholder="Search title, author or description…" class="w-full bg-transparent px-3 py-3 text-slate-800 outline-none">
            </div>
            <button class="rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-600 px-8 py-3 font-semibold shadow-xl hover:scale-105 transition">Search</button>
        </form>
    </div>
</section>
@endsection

@section('content')
{{-- department chips + view toggle --}}
<div class="mb-6 flex flex-wrap items-center gap-3">
    <div class="no-scrollbar flex min-w-0 flex-1 gap-2 overflow-x-auto pb-1">
        @php $chip = fn ($on) => 'whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium transition ' . ($on ? 'bg-violet-700 text-white shadow' : 'bg-white text-violet-800 ring-1 ring-violet-200 hover:bg-violet-100'); @endphp
        <a href="{{ request()->fullUrlWithQuery(['dept_id' => null, 'page' => null]) }}" class="{{ $chip(! $selectedDept) }}">All</a>
        @foreach ($topDepartments as $d)
            <a href="{{ request()->fullUrlWithQuery(['dept_id' => $d->id]) }}" class="{{ $chip($selectedDept?->id === $d->id) }}">{{ $d->name }}</a>
        @endforeach
    </div>
    <div class="flex overflow-hidden rounded-full bg-white text-sm font-medium shadow ring-1 ring-violet-200">
        <a href="{{ request()->fullUrlWithQuery(['filter_by' => 'department']) }}" class="px-4 py-1.5 {{ $filterBy === 'department' ? 'bg-violet-700 text-white' : 'text-violet-800' }}">By department</a>
        <a href="{{ request()->fullUrlWithQuery(['filter_by' => 'category']) }}" class="px-4 py-1.5 {{ $filterBy === 'category' ? 'bg-violet-700 text-white' : 'text-violet-800' }}">By category</a>
    </div>
</div>

@if ($history->isNotEmpty())
    <section class="mb-10 rounded-3xl bg-white p-5 shadow-md ring-1 ring-violet-100">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-violet-900"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">▶</span> Continue reading</h2>
        <div class="no-scrollbar flex gap-5 overflow-x-auto pb-2">
            @foreach ($history as $h)
                @php $histPct = min(100, (int) round($h->current_page / max($h->book->total_pages, 1) * 100)); @endphp
                @include('partials.book-card', ['book' => $h->book, 'progressPage' => $h->current_page, 'progressPct' => $histPct])
            @endforeach
        </div>
    </section>
@endif

@forelse ($groups as $label => $books)
    <section class="mb-10">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="border-l-4 border-violet-600 pl-3 text-lg font-bold text-violet-900">{{ $label }}</h2>
            <span class="rounded-full bg-violet-100 px-3 py-0.5 text-xs font-semibold text-violet-700">{{ $books->count() }} {{ \Illuminate\Support\Str::plural('title', $books->count()) }}</span>
        </div>
        <div class="no-scrollbar flex gap-5 overflow-x-auto pb-3">
            @foreach ($books as $book) @include('partials.book-card', ['book' => $book]) @endforeach
        </div>
    </section>
@empty
    <div class="rounded-3xl bg-white p-12 text-center shadow-md">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-violet-100 text-violet-600">@include('partials.icon', ['name' => 'search', 'class' => 'h-8 w-8'])</div>
        <p class="mt-4 font-semibold text-violet-900">No e-books match your search.</p>
        <a href="{{ route('catalog') }}" class="mt-3 inline-block text-sm font-semibold text-violet-700 underline">Clear filters</a>
    </div>
@endforelse

{{-- discovery --}}
<section class="mt-4 grid gap-5 md:grid-cols-3">
    @foreach ([['Recently added', 'bolt', 'bg-emerald-100 text-emerald-600', $recentBooks], ['Most popular', 'star', 'bg-amber-100 text-amber-600', $popularBooks], ['Discover something new', 'globe', 'bg-sky-100 text-sky-600', $randomBooks]] as [$title, $ic, $cl, $list])
        <div class="rounded-3xl bg-white p-5 shadow-md ring-1 ring-violet-100">
            <h3 class="mb-3 flex items-center gap-2 font-bold text-violet-900"><span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $cl }}">@include('partials.icon', ['name' => $ic, 'class' => 'h-5 w-5'])</span>{{ $title }}</h3>
            <ul class="space-y-3">
                @foreach ($list as $b)
                    <li><a href="{{ route('ebook.open', $b) }}" class="group flex items-center gap-3">
                        <span class="flex h-12 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gradient-to-br from-violet-500 to-purple-700 text-white">@if ($b->cover_path)<img src="{{ $b->cover_url }}" class="h-full w-full object-cover" alt="">@else @include('partials.icon', ['name' => 'book', 'class' => 'h-5 w-5'])@endif</span>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold group-hover:text-violet-700">{{ $b->name }}</span><span class="block truncate text-xs text-slate-500">{{ $b->author->name ?? '' }}</span></span></a></li>
                @endforeach
            </ul>
        </div>
    @endforeach
</section>
@endsection
