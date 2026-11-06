@extends('layouts.admin')
@section('title', 'Students')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Students</h1><p class="text-sm text-slate-500">{{ $students->total() }} records</p></div>
    @if (auth()->user()->canDo('students', 'create'))<a href="{{ route('admin.school.students.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) New student</a>@endif
</div>
<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Name or admission no…" class="min-w-[12rem] flex-1">
    <select name="section_id"><option value="">Any class</option>@foreach ($sections as $id => $n)<option value="{{ $id }}" @selected(request('section_id') == $id)>{{ $n }}</option>@endforeach</select>
    <select name="status"><option value="">Any status</option>@foreach (config('school.student_statuses') as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Student</th><th>Admission no.</th><th>Class</th><th>Gender</th><th>Guardian</th><th>Login</th><th>Status</th></tr></thead>
    <tbody>@forelse ($students as $s)
        <tr><td class="num">{{ $students->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.school.students.show', $s) }}">{{ $s->name }}</a></td>
            <td>{{ $s->admission_no }}</td><td>{{ $s->section->label ?? '—' }}</td><td>{{ ucfirst($s->gender ?? '—') }}</td>
            <td>{{ $s->guardian_name ?? '—' }}<div class="text-xs text-slate-400">{{ $s->guardian_phone }}</div></td>
            <td>{!! $s->user_id ? '<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">yes</span>' : '<span class="text-slate-300">no</span>' !!}</td>
            <td><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $s->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ ucfirst($s->status) }}</span></td></tr>
    @empty<tr><td colspan="8" class="tbl-empty">No students found.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $students])
</div>
@endsection
