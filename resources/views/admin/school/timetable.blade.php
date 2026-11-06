@extends('layouts.admin')
@section('title', 'Timetable')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-extrabold">Timetable</h1><p class="text-sm text-slate-500">Weekly schedule per class. A teacher cannot be in two classes in the same period.</p></div>
    @if ($section && $canEdit && ! $edit)<a href="{{ route('admin.school.timetable.index', ['section_id' => $section->id, 'edit' => 1]) }}" class="btn btn-primary">Edit timetable</a>@endif</div>

<form method="GET" class="filter-bar mb-4 flex flex-wrap gap-2">
    <select name="section_id" onchange="this.form.submit()" class="min-w-[14rem]"><option value="">Choose a class…</option>@foreach ($sections as $s)<option value="{{ $s->id }}" @selected($section?->id === $s->id)>{{ $s->label }} · {{ $s->academicYear->name ?? '' }}</option>@endforeach</select>
    <button class="btn btn-primary">Show</button>
</form>

@if (! $section)
    <div class="card p-10 text-center text-slate-500">Choose a class to see its timetable.</div>
@elseif (! $edit)
    @if ($assignments->isEmpty())<div class="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">No subjects are assigned to this class yet. Assign them on the <a class="font-semibold underline" href="{{ route('admin.school.sections.show', $section) }}">class page</a> first.</div>@endif
    @include('school._grid', ['grid' => $grid, 'mode' => 'section'])
@else
    <form method="POST" action="{{ route('admin.school.timetable.save') }}">@csrf
        <input type="hidden" name="section_id" value="{{ $section->id }}">
        <div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
            <thead><tr><th>Period</th>@foreach (config('school.days') as $dn)<th>{{ $dn }}</th>@endforeach</tr></thead>
            <tbody>@foreach (config('school.periods') as $p => [$from, $to])
                <tr><td class="whitespace-nowrap"><b>{{ $p }}</b><div class="text-xs text-slate-400">{{ $from }} – {{ $to }}</div></td>
                @foreach (config('school.days') as $d => $dn)
                    @php $slot = $grid[$p][$d] ?? null; @endphp
                    <td class="min-w-[10rem] align-top">
                        <select name="slots[{{ $d }}][{{ $p }}]" class="w-full rounded-lg border border-violet-100 px-2 py-1.5 text-sm"><option value="">—</option>
                            @foreach ($assignments as $a)<option value="{{ $a->subject_id }}" @selected($slot && $slot->subject_id === $a->subject_id)>{{ $a->subject->name }}{{ $a->teacher ? ' (' . $a->teacher->name . ')' : '' }}</option>@endforeach</select>
                        <input name="rooms[{{ $d }}][{{ $p }}]" value="{{ $slot->room ?? '' }}" placeholder="room" class="mt-1 w-full rounded-lg border border-violet-100 px-2 py-1 text-xs">
                    </td>
                @endforeach</tr>
            @endforeach</tbody></table></div></div>
        <div class="mt-4 flex gap-3"><button class="btn btn-primary">Save timetable</button><a href="{{ route('admin.school.timetable.index', ['section_id' => $section->id]) }}" class="btn btn-outline">Cancel</a></div>
    </form>
@endif
@endsection
