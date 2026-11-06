@extends($layout)
@section('title', 'Attendance')
@section('content')
@php
    $colors = ['present' => 'peer-checked:bg-emerald-600', 'absent' => 'peer-checked:bg-rose-600', 'late' => 'peer-checked:bg-amber-500', 'excused' => 'peer-checked:bg-sky-600'];
@endphp
<div class="mb-5"><h1 class="text-2xl font-extrabold">Attendance</h1><p class="text-sm text-slate-500">Take the daily register or review a month.</p></div>

<form method="GET" class="filter-bar mb-4 flex flex-wrap items-center gap-2">
    <select name="section_id" required><option value="">Choose a class…</option>@foreach ($sections as $s)<option value="{{ $s->id }}" @selected($section?->id === $s->id)>{{ $s->label }} · {{ $s->academicYear->name ?? '' }}</option>@endforeach</select>
    <select name="mode" onchange="this.form.submit()"><option value="mark" @selected($mode === 'mark')>Take attendance</option><option value="report" @selected($mode === 'report')>Monthly report</option></select>
    @if ($mode === 'mark')<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}">
    @else<input type="month" name="month" value="{{ $month->format('Y-m') }}">@endif
    <button class="btn btn-primary">Show</button>
</form>

@if (! $section)
    <div class="card p-10 text-center text-slate-500">{{ $sections->isEmpty() ? 'No classes are assigned to you yet.' : 'Choose a class to begin.' }}</div>
@elseif ($students->isEmpty())
    <div class="card p-10 text-center text-slate-500">There are no active students in {{ $section->label }}.</div>
@elseif ($mode === 'mark')
    <form method="POST" action="{{ route($prefix . '.store') }}">@csrf
        <input type="hidden" name="section_id" value="{{ $section->id }}"><input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <div class="tbl-wrap">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-violet-50 px-5 py-3">
                <div class="font-bold">{{ $section->label }} · {{ $date->format('l, d M Y') }}</div>
                @if ($canSave)<button type="button" id="allPresent" class="btn btn-outline">Mark all present</button>@endif
            </div>
            <div class="tbl-scroll"><table class="tbl">
                <thead><tr><th class="num">#</th><th>Student</th><th>Status</th><th>Remark</th></tr></thead>
                <tbody>
                @foreach ($students as $st)
                    @php $cur = $existing[$st->id]->status ?? 'present'; @endphp
                    <tr><td class="num">{{ $loop->iteration }}</td>
                        <td><div class="font-semibold">{{ $st->name }}</div><div class="text-xs text-slate-400">{{ $st->admission_no }}</div></td>
                        <td><div class="flex flex-wrap gap-1.5">
                            @foreach ($statuses as $key => $label)
                                <label class="cursor-pointer"><input type="radio" class="peer sr-only st-{{ $key }}" name="status[{{ $st->id }}]" value="{{ $key }}" @checked($cur === $key) @disabled(! $canSave)>
                                    <span class="block rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 peer-checked:text-white {{ $colors[$key] }}">{{ $label }}</span></label>
                            @endforeach</div></td>
                        <td><input name="remark[{{ $st->id }}]" value="{{ $existing[$st->id]->remark ?? '' }}" maxlength="200" @disabled(! $canSave) class="w-full min-w-[8rem] rounded-lg border border-violet-100 px-2 py-1 text-sm"></td></tr>
                @endforeach
                </tbody></table></div>
            @if ($canSave)<div class="border-t border-violet-50 bg-slate-50 px-5 py-3 text-right"><button class="btn btn-primary">Save attendance</button></div>@endif
        </div>
    </form>
    <script>document.getElementById('allPresent')?.addEventListener('click', () => document.querySelectorAll('input.st-present').forEach(r => r.checked = true));</script>
@else
    <div class="tbl-wrap"><div class="border-b border-violet-50 px-5 py-3 font-bold">{{ $section->label }} · {{ $month->format('F Y') }}</div>
        <div class="tbl-scroll"><table class="tbl">
            <thead><tr><th class="num">#</th><th>Student</th><th>Present</th><th>Late</th><th>Excused</th><th>Absent</th><th>Days</th><th>Attendance</th></tr></thead>
            <tbody>@foreach ($students as $st) @php $r = $report[$st->id]; @endphp
                <tr><td class="num">{{ $loop->iteration }}</td><td class="font-semibold">{{ $st->name }}</td>
                    <td>{{ $r['present'] }}</td><td>{{ $r['late'] }}</td><td>{{ $r['excused'] }}</td><td class="{{ $r['absent'] ? 'font-bold text-rose-600' : '' }}">{{ $r['absent'] }}</td><td>{{ $r['total'] }}</td>
                    <td>@if ($r['percent'] === null)<span class="text-slate-300">—</span>@else<span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $r['percent'] >= 90 ? 'bg-emerald-100 text-emerald-800' : ($r['percent'] >= 75 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">{{ $r['percent'] }}%</span>@endif</td></tr>
            @endforeach</tbody></table></div></div>
@endif
@endsection
