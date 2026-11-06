@extends('layouts.admin')
@section('title', $book->exists ? 'Edit book' : 'New book')
@section('content')
@php
    $locked = $book->exists && in_array($book->state, ['approved', 'released', 'obsolete']);
    $isAdmin = auth()->user()->atLeast('admin');
@endphp
<h1 class="mb-4 text-2xl font-bold">{{ $book->exists ? 'Edit: ' . $book->name : 'New book' }}</h1>
@if ($book->state === 'released' && ! $isAdmin)
    <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">Released documents are locked. Create a new version from the book page to change content.</div>
@endif
@if ($book->state === 'approved')
    <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">This document is approved - changing name, description, category, cover or e-book requires a <b>Change Reason</b>.</div>
@endif

<div class="grid gap-6 xl:grid-cols-3">
<form method="POST" enctype="multipart/form-data" class="space-y-5 card p-6 xl:col-span-2"
      action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}">
    @csrf @if ($book->exists) @method('PUT') @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2"><label class="block text-sm font-medium">Title *</label>
            <input id="f_name" name="name" value="{{ old('name', $book->name) }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="block text-sm font-medium">ISBN</label>
            <input value="{{ $book->isbn ?: 'Generated automatically on save' }}" disabled class="w-full rounded border bg-slate-100 px-3 py-2"></div>
        <div><label class="block text-sm font-medium">Author *</label>
            <select id="f_author" name="author_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>@foreach ($authors as $id => $n)<option value="{{ $id }}" @selected(old('author_id', $book->author_id) == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Publication year *</label>
            <input id="f_year" type="number" name="publication_year" min="1900" max="{{ now()->year }}" value="{{ old('publication_year', $book->publication_year) }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="block text-sm font-medium">Category *</label>
            <select name="category_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected(old('category_id', $book->category_id) == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Department *</label>
            <select name="department_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>@foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(old('department_id', $book->department_id) == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Total copies *</label>
            <input type="number" min="0" name="copies_total" value="{{ old('copies_total', $book->copies_total) }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="block text-sm font-medium">Fine per day</label>
            <input type="number" step="0.01" min="0" name="fine_per_day" value="{{ old('fine_per_day', $book->fine_per_day) }}" class="w-full rounded border px-3 py-2"></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium">Internal summary</label>
            <textarea name="description" rows="3" class="w-full rounded border px-3 py-2">{{ old('description', $book->description) }}</textarea></div>
    </div>

    <fieldset class="rounded-lg border p-4">
        <legend class="px-2 text-sm font-bold uppercase text-[#5b21b6]">E-book</legend>
        <label class="mb-2 flex items-center gap-2"><input id="f_ebook" type="checkbox" name="is_ebook" value="1" @checked(old('is_ebook', $book->is_ebook))> This title is an e-book</label>
        <label class="mb-3 flex items-center gap-2"><input id="f_secure" type="checkbox" name="is_secure_ebook" value="1" @checked(old('is_secure_ebook', $book->is_secure_ebook))> Secure e-book <span class="text-xs text-slate-500">(only signed-in users can read it; otherwise it is public)</span></label>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="block text-sm font-medium">PDF file {{ $book->ebook_filename ? '(current: ' . $book->ebook_filename . ')' : '' }}</label>
                <input type="file" name="ebook_file" accept="application/pdf" @disabled($book->exists && $book->state !== 'draft') class="w-full text-sm"></div>
            <div><label class="block text-sm font-medium">Cover image</label><input id="coverInput" type="file" name="cover_image" accept="image/png,image/jpeg,image/webp,image/gif" class="w-full text-sm"></div>
        </div>
    </fieldset>

    <div><label class="block text-sm font-medium">Change reason</label>
        <textarea name="change_reason" rows="2" class="w-full rounded border px-3 py-2">{{ old('change_reason', $book->change_reason) }}</textarea></div>

    <div class="flex gap-3">
        <button class="rounded bg-[#5b21b6] px-6 py-2 font-semibold text-white">Save</button>
        <a href="{{ $book->exists ? route('admin.books.show', $book) : route('admin.books.index') }}" class="rounded border px-6 py-2">Cancel</a>
    </div>
</form>

<aside class="xl:sticky xl:top-24 xl:self-start">
    <div class="card p-5">
        <h3 class="mb-1 flex items-center gap-2 font-bold">@include('partials.icon', ['name' => 'image', 'class' => 'h-5 w-5 text-violet-600']) Cover preview</h3>
        <p class="mb-4 text-xs text-slate-500">This is how the book appears on the public catalog. Choose an image to preview it instantly.</p>

        @php $__g = ['from-violet-600 to-purple-800', 'from-blue-500 to-indigo-700', 'from-amber-400 to-orange-600', 'from-teal-400 to-emerald-600', 'from-rose-400 to-pink-600', 'from-sky-400 to-cyan-600']; @endphp
        <div class="mx-auto w-44">
            <div class="relative aspect-[3/4] overflow-hidden rounded-2xl shadow-xl ring-1 ring-black/5">
                <img id="pvImg" src="{{ $book->cover_path ? $book->cover_url : '' }}" alt="Cover preview" class="absolute inset-0 h-full w-full object-cover {{ $book->cover_path ? '' : 'hidden' }}">
                <div id="pvTile" class="absolute inset-0 bg-gradient-to-br {{ $__g[($book->id ?? 0) % 6] }} {{ $book->cover_path ? 'hidden' : '' }}">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-3 text-center text-white">
                        @include('partials.icon', ['name' => 'book', 'class' => 'h-10 w-10 opacity-80'])
                        <span id="pvTileTitle" class="mt-2 line-clamp-3 text-sm font-bold leading-tight">{{ $book->name ?: 'Book title' }}</span>
                    </div>
                </div>
                <span id="pvSecure" class="absolute right-2 top-2 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950 shadow {{ $book->is_secure_ebook ? '' : 'hidden' }}">🔒 Secure</span>
                <span id="pvEbook" class="absolute left-2 top-2 rounded-full bg-sky-500 px-2 py-0.5 text-[10px] font-bold text-white shadow {{ $book->is_ebook ? '' : 'hidden' }}">E-book</span>
            </div>
            <div class="mt-2 px-1">
                <div id="pvTitle" class="line-clamp-2 text-sm font-semibold leading-tight">{{ $book->name ?: 'Book title' }}</div>
                <div id="pvMeta" class="truncate text-xs text-slate-500">{{ $book->author->name ?? 'Author' }} · {{ $book->publication_year ?: now()->year }}</div>
            </div>
        </div>
        <p id="pvFile" class="mt-4 text-center text-xs text-slate-400">{{ $book->cover_path ? 'Current cover' : 'No cover yet - a coloured tile is used' }}</p>
        <p id="pvWarn" class="mt-1 hidden text-center text-xs font-semibold text-rose-600"></p>
    </div>
</aside>
</div>

<script>
(() => {
    const $ = id => document.getElementById(id);
    const MAX = 4 * 1024 * 1024;     // server limit: 4 MB
    const meta = () => { const a = $('f_author'); const name = a && a.selectedIndex > 0 ? a.options[a.selectedIndex].text : 'Author'; return name + ' · ' + ($('f_year').value || ''); };

    $('f_name').addEventListener('input', e => { const v = e.target.value.trim() || 'Book title'; $('pvTitle').textContent = v; $('pvTileTitle').textContent = v; });
    $('f_author').addEventListener('change', () => $('pvMeta').textContent = meta());
    $('f_year').addEventListener('input', () => $('pvMeta').textContent = meta());
    $('f_ebook').addEventListener('change', e => $('pvEbook').classList.toggle('hidden', !e.target.checked));
    $('f_secure').addEventListener('change', e => $('pvSecure').classList.toggle('hidden', !e.target.checked));

    let objectUrl = null;
    $('coverInput').addEventListener('change', e => {
        const file = e.target.files[0], warn = $('pvWarn');
        warn.classList.add('hidden');
        if (!file) return;
        if (!file.type.startsWith('image/')) { warn.textContent = 'That file is not an image.'; warn.classList.remove('hidden'); e.target.value = ''; return; }
        if (file.size > MAX) { warn.textContent = 'Too large (' + (file.size / 1048576).toFixed(1) + ' MB). Maximum is 4 MB.'; warn.classList.remove('hidden'); e.target.value = ''; return; }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        $('pvImg').src = objectUrl; $('pvImg').classList.remove('hidden'); $('pvTile').classList.add('hidden');
        $('pvFile').textContent = file.name + ' · ' + Math.round(file.size / 1024) + ' KB (not saved until you press Save)';
    });
})();
</script>
@endsection
