@extends('layouts.admin')
@section('title', 'Members')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Members</h1><p class="text-sm text-slate-500">{{ $members->total() }} records</p></div>
    @if (auth()->user()->canDo('members', 'create'))<a href="{{ route('admin.members.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) New member</a>@endif
</div>
<form class="filter-bar mb-4 flex flex-wrap gap-2"><input name="q" value="{{ request('q') }}" placeholder="Name…" class="min-w-[12rem] flex-1">
    <select name="status"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="expired" @selected(request('status') === 'expired')>Expired</option></select>
    <button class="btn btn-primary">Filter</button></form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Member</th><th>User account</th><th>Start</th><th>End</th><th>Max</th><th>Issued</th><th>Returned</th><th>Lost</th><th>Status</th></tr></thead>
    <tbody>@forelse ($members as $m)
        <tr><td class="num">{{ $members->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.members.show', $m) }}">{{ $m->name }}</a></td>
            <td>{{ $m->user->email ?? '—' }}</td><td>{{ $m->membership_start->toDateString() }}</td><td>{{ $m->membership_end->toDateString() }}</td><td>{{ $m->max_books }}</td>
            <td>{{ $m->issued_count }}</td><td>{{ $m->returned_count }}</td><td>{{ $m->lost_count }}</td>
            <td>@if ($m->active)<span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>@else<span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">Expired</span>@endif</td></tr>
    @empty<tr><td colspan="10" class="tbl-empty">No members.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $members])
</div>
@endsection
