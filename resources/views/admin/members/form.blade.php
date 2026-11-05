@extends('layouts.admin')
@section('title', 'Member')
@section('content')
<h1 class="mb-4 text-2xl font-bold">{{ $member->exists ? 'Edit member' : 'New member' }}</h1>
<form method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">
    @csrf @if ($member->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium">Member name *</label><input name="name" value="{{ old('name', $member->name) }}" required class="w-full rounded border px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Linked user account <span class="text-xs text-slate-500">(lets them borrow as themselves)</span></label>
        <select name="user_id" class="w-full rounded border px-3 py-2"><option value="">— none —</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(old('user_id', $member->user_id) == $u->id)>{{ $u->name }} ({{ $u->email }})</option>@endforeach</select></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium">Membership start *</label><input type="date" name="membership_start" value="{{ old('membership_start', $member->membership_start?->toDateString()) }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="block text-sm font-medium">Membership end *</label><input type="date" name="membership_end" value="{{ old('membership_end', $member->membership_end?->toDateString()) }}" required class="w-full rounded border px-3 py-2"></div>
    </div>
    <div><label class="block text-sm font-medium">Max books</label><input type="number" min="1" name="max_books" value="{{ old('max_books', $member->max_books) }}" class="w-32 rounded border px-3 py-2"></div>
    <div class="flex gap-3"><button class="rounded bg-[#5b21b6] px-6 py-2 font-semibold text-white">Save</button><a href="{{ route('admin.members.index') }}" class="rounded border px-6 py-2">Cancel</a></div>
</form>
@endsection
