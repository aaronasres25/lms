@extends('layouts.portal')
@section('title', 'My library')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My library</h1>
    <p class="text-sm text-slate-500">
        @if ($member) Member until {{ $member->membership_end->toDateString() }} · up to {{ $member->max_books }} books at a time @else Your library membership is not set up yet. @endif
        @if ($fines > 0) · <b class="text-rose-600">fines {{ number_format($fines, 2) }}</b>@endif</p></div>

@if ($loans->isNotEmpty())
<div class="card mb-6 p-5"><h3 class="mb-3 font-bold">My loans &amp; requests</h3>
    <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-400"><tr><th class="py-1">Book</th><th>Status</th><th>Due</th><th>Fine</th><th></th></tr></thead>
        @foreach ($loans as $l)
            <tr class="border-t"><td class="py-2 font-medium">{{ $l->book->name }}</td>
                <td>@if ($l->state === 'draft')<span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800">Requested</span>@else @include('partials.state-badge', ['state' => $l->state])@endif</td>
                <td class="{{ $l->state === 'issued' && $l->overdue_days ? 'font-bold text-rose-600' : '' }}">{{ $l->date_expected?->toDateString() ?? '—' }}{{ $l->state === 'issued' && $l->overdue_days ? ' (' . $l->overdue_days . ' days late)' : '' }}</td>
                <td>{{ $l->fine_amount > 0 ? number_format($l->fine_amount, 2) : '—' }}</td>
                <td class="text-right">@if ($l->state === 'draft')<form method="POST" action="{{ route('student.library.cancel', $l) }}" onsubmit="return confirm('Cancel this request?')">@csrf @method('DELETE')<button class="text-rose-600">Cancel</button></form>@endif</td></tr>
        @endforeach</table></div></div>
@endif

<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Search title or author…" class="min-w-[12rem] flex-1">
    <select name="type"><option value="">E-books &amp; physical</option><option value="physical" @selected(request('type') === 'physical')>Physical books</option><option value="ebook" @selected(request('type') === 'ebook')>E-books</option></select>
    <button class="btn btn-primary">Search</button>
</form>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
@forelse ($books as $b)
    @php $mine = in_array($b->id, $openIds); $avail = $b->copies_available; @endphp
    <div class="card flex gap-4 p-4">
        <div class="relative h-28 w-20 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-violet-500 to-purple-700 text-white shadow">
            @if ($b->cover_path)<img src="{{ $b->cover_url }}" class="h-full w-full object-cover" alt="">@else<span class="flex h-full items-center justify-center">@include('partials.icon', ['name' => 'book', 'class' => 'h-8 w-8 opacity-80'])</span>@endif</div>
        <div class="min-w-0 flex-1">
            <div class="line-clamp-2 font-bold leading-tight">{{ $b->name }}</div>
            <div class="truncate text-xs text-slate-500">{{ $b->author->name ?? '' }} · {{ $b->publication_year }}</div>
            <div class="mt-1 flex flex-wrap gap-1 text-[11px] font-semibold">
                @if ($b->is_ebook && $b->ebook_path)<span class="rounded-full bg-sky-100 px-2 py-0.5 text-sky-800">E-book</span>@endif
                @if ($b->copies_total > 0)<span class="rounded-full px-2 py-0.5 {{ $avail > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $avail > 0 ? $avail . ' available' : 'all borrowed' }}</span>@endif</div>
            <div class="mt-2 flex flex-wrap gap-2">
                @if ($b->is_ebook && $b->ebook_path)<a href="{{ route('ebook.open', $b) }}" class="btn btn-outline !px-3 !py-1.5 text-xs">📖 Read</a>@endif
                @if ($b->copies_total > 0)
                    @if ($mine)<span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500">Requested / borrowed</span>
                    @elseif ($avail > 0 && $member)<form method="POST" action="{{ route('student.library.request', $b) }}">@csrf<button class="btn btn-primary !px-3 !py-1.5 text-xs">Request</button></form>@endif
                @endif
            </div>
        </div>
    </div>
@empty<div class="card col-span-full p-10 text-center text-slate-500">No books found.</div>@endforelse
</div>
<div class="mt-5">{{ $books->links('vendor.pagination.lms') }}</div>
@endsection
