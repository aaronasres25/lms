@extends('layouts.admin')
@section('title', 'New loan')
@section('content')
<h1 class="mb-4 text-2xl font-bold">New loan</h1>
<form method="POST" action="{{ route('admin.loans.store') }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">@csrf
    <div><label class="block text-sm font-medium">Book * <span class="text-xs text-slate-500">(released, copies available)</span></label>
        <select name="book_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>
            @foreach ($books as $b)<option value="{{ $b->id }}" @selected(old('book_id', $preselect) == $b->id)>{{ $b->name }} (v{{ $b->version }}) — {{ $b->copies_available }} available</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium">Member *</label>
        <select name="member_id" required class="w-full rounded border px-3 py-2"><option value="">—</option>
            @foreach ($members as $m)<option value="{{ $m->id }}" @selected(old('member_id', $myMember) == $m->id)>{{ $m->name }} (max {{ $m->max_books }})</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium">Expected return <span class="text-xs text-slate-500">(max {{ config('lms.loan_period_days') }} days; default = {{ config('lms.loan_period_days') }})</span></label>
        <input type="date" name="date_expected" value="{{ old('date_expected') }}" class="w-full rounded border px-3 py-2"></div>
    <label class="flex items-center gap-2"><input type="checkbox" name="issue_now" value="1" checked> Issue the book immediately</label>
    <div class="flex gap-3"><button class="rounded bg-[#5b21b6] px-6 py-2 font-semibold text-white">Create</button><a href="{{ route('admin.loans.index') }}" class="rounded border px-6 py-2">Cancel</a></div>
</form>
@endsection
