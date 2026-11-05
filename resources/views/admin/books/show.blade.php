@extends('layouts.admin')
@section('title', $book->name)
@section('content')
@php $u = auth()->user(); @endphp
<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ $book->name }} <span class="text-base font-normal text-slate-500">v{{ $book->version }}</span></h1>
        <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">ISBN {{ $book->isbn }} @include('partials.state-badge', ['state' => $book->state])
            @unless ($book->active)<span class="rounded bg-slate-200 px-2">archived</span>@endunless
            @if ($book->is_expired)<span class="rounded bg-red-100 px-2 text-red-800">expired</span>@endif</div>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($book->state === 'draft' && $u->atLeast('qa'))
            <form method="POST" action="{{ route('admin.books.submit', $book) }}">@csrf<button class="rounded bg-[#5b21b6] px-4 py-2 text-white">Submit to review</button></form>
        @endif
        @if ($book->state === 'review' && $u->atLeast('qa'))
            <form method="POST" action="{{ route('admin.books.approve', $book) }}">@csrf<button class="rounded bg-green-700 px-4 py-2 text-white">Approve</button></form>
        @endif
        @if ($book->state === 'approved' && $u->atLeast('manager'))
            <form method="POST" action="{{ route('admin.books.release', $book) }}" onsubmit="return confirm('Release and notify all staff by e-mail?')">@csrf<button class="rounded bg-[#5b21b6] px-4 py-2 text-white">Release</button></form>
        @endif
        @if ($book->state === 'released' && $u->atLeast('manager'))
            <button onclick="document.getElementById('verDialog').showModal()" class="rounded bg-red-700 px-4 py-2 text-white">↻ New version</button>
            <form method="POST" action="{{ route('admin.books.obsolete', $book) }}" onsubmit="return confirm('Mark obsolete?')">@csrf<button class="rounded border px-4 py-2">Obsolete</button></form>
        @endif
        @if ($book->is_ebook && $book->ebook_path)<a target="_blank" href="{{ route('ebook.view', $book) }}" class="rounded border px-4 py-2">📖 Open e-book</a>@endif
        @if ($u->canDo('books', 'update'))<a href="{{ route('admin.books.edit', $book) }}" class="rounded border px-4 py-2">Edit</a>@endif
        @if ($u->canDo('books', 'delete'))
            <form method="POST" action="{{ route('admin.books.destroy', $book) }}" onsubmit="return confirm('Delete permanently?')">@csrf @method('DELETE')<button class="rounded border border-red-300 px-4 py-2 text-red-700">Delete</button></form>
        @endif
    </div>
</div>

{{-- status bar --}}
<div class="mb-6 flex overflow-hidden rounded-lg text-center text-sm">
    @foreach (\App\Models\Book::STATES as $k => $label)
        <div class="flex-1 py-2 {{ $book->state === $k ? 'bg-[#5b21b6] font-semibold text-white' : 'bg-white text-slate-500' }}">{{ $label }}</div>
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-xl bg-white p-5 shadow">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">Author</dt><dd>{{ $book->author->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Publication year</dt><dd>{{ $book->publication_year }}</dd></div>
                <div><dt class="text-slate-500">Category</dt><dd>{{ $book->category->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Department</dt><dd>{{ $departments[$book->department_id] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Copies (total / available / issued)</dt><dd>{{ $book->copies_total }} / {{ $book->copies_available }} / {{ $book->issued_count }}</dd></div>
                <div><dt class="text-slate-500">Fine per day</dt><dd>{{ number_format($book->fine_per_day, 2) }}</dd></div>
                <div><dt class="text-slate-500">Approved on</dt><dd>{{ $book->approval_date?->toDateString() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Valid until</dt><dd>{{ $book->expiry_date?->toDateString() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">E-book</dt><dd>{{ $book->is_ebook ? ($book->is_secure_ebook ? 'Yes - secure (login required)' : 'Yes - public') : 'No' }}</dd></div>
                <div><dt class="text-slate-500">Reads / last opened</dt><dd>{{ $book->ebook_read_count }} / {{ $book->last_opened?->diffForHumans() ?? '—' }}</dd></div>
            </dl>
            @if ($book->description)<p class="mt-4 whitespace-pre-line border-t pt-4 text-sm">{{ $book->description }}</p>@endif
            @if ($book->change_reason)<p class="mt-3 text-sm"><b>Change reason:</b> {{ $book->change_reason }}</p>@endif
        </div>

        @if ($book->versionHistory->isNotEmpty())
        <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-2 font-semibold">Version history</h3>
            <table class="w-full text-sm"><tr class="text-left text-xs uppercase text-slate-500"><th>New version</th><th>Reason</th><th>By</th><th>On</th></tr>
            @foreach ($book->versionHistory as $h)<tr class="border-t"><td class="py-1">{{ $h->version }}</td><td>{{ $h->change_reason }}</td><td>{{ $h->user->name ?? '' }}</td><td>{{ $h->created_at->toDateString() }}</td></tr>@endforeach</table></div>
        @endif

        <div class="rounded-xl bg-white p-5 shadow"><div class="mb-2 flex justify-between"><h3 class="font-semibold">Recent loans</h3><a class="text-sm text-violet-700" href="{{ route('admin.loans.index', ['q' => $book->name]) }}">All loans</a></div>
            <table class="w-full text-sm">@forelse ($book->loans as $l)<tr class="border-t"><td class="py-1"><a class="text-violet-700" href="{{ route('admin.loans.show', $l) }}">{{ $l->name }}</a></td><td>{{ $l->member->name }}</td><td>{{ $l->date_expected?->toDateString() }}</td><td>@include('partials.state-badge', ['state' => $l->state])</td></tr>@empty<tr><td class="text-slate-500">No loans.</td></tr>@endforelse</table></div>

        @if ($book->notifiedUsers->isNotEmpty())
        <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-2 font-semibold">Notification tracking ({{ $book->notifiedUsers->count() }})</h3>
            <p class="text-sm text-slate-600">{{ $book->notifiedUsers->pluck('name')->implode(', ') }}</p></div>
        @endif
    </div>

    <aside class="space-y-4">
        @if ($book->cover_path)<img src="{{ $book->cover_url }}" class="w-48 rounded-lg shadow">@endif
        <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-3 font-semibold">Activity</h3>
            <ul class="space-y-3 text-sm">@forelse ($book->activities as $a)<li><div class="text-xs text-slate-400">{{ $a->created_at->format('Y-m-d H:i') }} · {{ $a->user->name ?? 'System' }}</div>{{ $a->body }}</li>@empty<li class="text-slate-500">Nothing yet.</li>@endforelse</ul></div>
    </aside>
</div>

<dialog id="verDialog" class="w-full max-w-md rounded-xl p-0 shadow-2xl backdrop:bg-black/40">
    <form method="POST" action="{{ route('admin.books.version', $book) }}" class="space-y-4 p-6">@csrf
        <h3 class="text-lg font-bold">Create new version</h3>
        <label class="flex items-center gap-2"><input type="radio" name="version_type" value="minor" checked> New version ({{ $book->version }} → minor)</label>
        <label class="flex items-center gap-2"><input type="radio" name="version_type" value="major"> Major version</label>
        <textarea name="change_reason" rows="3" required placeholder="Change reason (required)" class="w-full rounded border px-3 py-2"></textarea>
        <p class="text-xs text-slate-500">The current version becomes obsolete and archived; a new Draft copy is created.</p>
        <div class="flex justify-end gap-2"><button type="button" onclick="this.closest('dialog').close()" class="rounded border px-4 py-2">Cancel</button><button class="rounded bg-red-700 px-4 py-2 text-white">Confirm</button></div>
    </form>
</dialog>
@endsection
