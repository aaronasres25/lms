@extends('layouts.admin')
@section('title', 'Promotion')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">Student promotion</h1><p class="text-sm text-slate-500">At the end of the year, move a class up to the next class, or graduate it. History is kept.</p></div>
<form method="GET" class="filter-bar mb-4 flex flex-wrap gap-2">
    <select name="from" required class="min-w-[14rem]"><option value="">From class…</option>@foreach ($sections as $id => $n)<option value="{{ $id }}" @selected($from?->id == $id)>{{ $n }}</option>@endforeach</select>
    <button class="btn btn-primary">Load students</button>
</form>

@if ($from)
<form method="POST" action="{{ route('admin.school.promotion.store') }}">@csrf
    <input type="hidden" name="from" value="{{ $from->id }}">
    <div class="tbl-wrap">
        <div class="flex flex-wrap items-center gap-3 border-b border-violet-50 px-5 py-3">
            <div class="font-bold">{{ $from->label }} · {{ $from->academicYear->name ?? '' }}</div>
            <select name="action" id="act" class="rounded-lg border border-violet-100 px-2 py-1.5 text-sm"><option value="promote">Promote to…</option><option value="graduate">Graduate (leave school)</option></select>
            <select name="to" id="toSel" class="min-w-[14rem] rounded-lg border border-violet-100 px-2 py-1.5 text-sm"><option value="">Next class…</option>@foreach ($sections as $id => $n)@continue($id == $from->id)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
        </div>
        <div class="tbl-scroll"><table class="tbl">
            <thead><tr><th class="num"><input type="checkbox" id="all" checked></th><th>Student</th><th>Admission no.</th></tr></thead>
            <tbody>@forelse ($students as $s)<tr><td class="num"><input type="checkbox" name="students[]" value="{{ $s->id }}" checked class="row"></td><td class="font-semibold">{{ $s->name }}</td><td>{{ $s->admission_no }}</td></tr>
                @empty<tr><td colspan="3" class="tbl-empty">No active students in this class.</td></tr>@endforelse</tbody></table></div>
        @if ($students->isNotEmpty())<div class="border-t border-violet-50 bg-slate-50 px-5 py-3 text-right"><button class="btn btn-primary" onclick="return confirm('Apply to the ticked students?')">Apply</button></div>@endif
    </div>
</form>
<script>
    document.getElementById('all').addEventListener('change', e => document.querySelectorAll('.row').forEach(c => c.checked = e.target.checked));
    const act = document.getElementById('act'), to = document.getElementById('toSel');
    act.addEventListener('change', () => { to.disabled = act.value === 'graduate'; });
</script>
@endif
@endsection
