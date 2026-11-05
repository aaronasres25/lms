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

<form method="POST" enctype="multipart/form-data" class="max-w-4xl space-y-5 rounded-xl bg-white p-6 shadow"
      action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}">
    @csrf @if ($book->exists) @method('PUT') @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2"><label class="block text-sm font-medium">Title *</label>
            <input name="name" value="{{ old('name', $book->name) }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="block text-sm font-medium">ISBN</label>
            <input value="{{ $book->isbn ?: 'Generated automatically on save' }}" disabled class="w-full rounded border bg-slate-100 px-3 py-2"></div>
        <div><label class="block text-sm font-medium">Author *</label>
            <select name="author_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>@foreach ($authors as $id => $n)<option value="{{ $id }}" @selected(old('author_id', $book->author_id) == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Publication year *</label>
            <input type="number" name="publication_year" min="1900" max="{{ now()->year }}" value="{{ old('publication_year', $book->publication_year) }}" required class="w-full rounded border px-3 py-2"></div>
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
        <label class="mb-2 flex items-center gap-2"><input type="checkbox" name="is_ebook" value="1" @checked(old('is_ebook', $book->is_ebook))> This title is an e-book</label>
        <label class="mb-3 flex items-center gap-2"><input type="checkbox" name="is_secure_ebook" value="1" @checked(old('is_secure_ebook', $book->is_secure_ebook))> Secure e-book <span class="text-xs text-slate-500">(only signed-in users can read it; otherwise it is public)</span></label>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="block text-sm font-medium">PDF file {{ $book->ebook_filename ? '(current: ' . $book->ebook_filename . ')' : '' }}</label>
                <input type="file" name="ebook_file" accept="application/pdf" @disabled($book->exists && $book->state !== 'draft') class="w-full text-sm"></div>
            <div><label class="block text-sm font-medium">Cover image</label><input type="file" name="cover_image" accept="image/*" class="w-full text-sm"></div>
        </div>
    </fieldset>

    <div><label class="block text-sm font-medium">Change reason</label>
        <textarea name="change_reason" rows="2" class="w-full rounded border px-3 py-2">{{ old('change_reason', $book->change_reason) }}</textarea></div>

    <div class="flex gap-3">
        <button class="rounded bg-[#5b21b6] px-6 py-2 font-semibold text-white">Save</button>
        <a href="{{ $book->exists ? route('admin.books.show', $book) : route('admin.books.index') }}" class="rounded border px-6 py-2">Cancel</a>
    </div>
</form>
@endsection
