@extends('layouts.admin')
@section('title', 'Teachers')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Teachers</h1><p class="text-sm text-slate-500">{{ $teachers->total() }} records</p></div>
    @if (auth()->user()->canDo('teachers', 'create'))<a href="{{ route('admin.school.teachers.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) New teacher</a>@endif
</div>
<form class="filter-bar mb-4 flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Name or employee no…" class="min-w-[12rem] flex-1"><button class="btn btn-primary">Search</button></form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Teacher</th><th>Employee no.</th><th>Contact</th><th>Class teacher of</th><th>Subjects taught</th><th>Login</th><th>Status</th></tr></thead>
    <tbody>@forelse ($teachers as $t)
        <tr><td class="num">{{ $teachers->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.school.teachers.edit', $t) }}">{{ $t->name }}</a><div class="text-xs text-slate-400">{{ $t->qualification }}</div></td>
            <td>{{ $t->employee_no }}</td><td>{{ $t->phone }}<div class="text-xs text-slate-400">{{ $t->email }}</div></td>
            <td>{{ $t->class_sections_count }}</td><td>{{ $t->assignments_count }}</td>
            <td>{!! $t->user_id ? '<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">yes</span>' : '<span class="text-slate-300">no</span>' !!}</td>
            <td><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $t->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ ucfirst($t->status) }}</span></td></tr>
    @empty<tr><td colspan="8" class="tbl-empty">No teachers yet.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $teachers])
</div>
@endsection
