@extends($layout)
@section('title', 'Marks entry')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">Marks entry</h1><p class="text-sm text-slate-500">Choose an exam, a class and a subject{{ $layout === 'layouts.portal' ? ' you teach' : '' }}, then enter the marks.</p></div>

<form method="GET" class="filter-bar mb-4 flex flex-wrap items-center gap-2">
    <select name="exam_id" required onchange="this.form.submit()"><option value="">Exam…</option>@foreach ($exams as $e)<option value="{{ $e->id }}" @selected($exam?->id === $e->id)>{{ $e->name }} (max {{ $e->max_marks }})</option>@endforeach</select>
    <select name="section_id" required onchange="this.form.submit()"><option value="">Class…</option>@foreach ($sections as $s)<option value="{{ $s->id }}" @selected($section?->id === $s->id)>{{ $s->label }} · {{ $s->academicYear->name ?? '' }}</option>@endforeach</select>
    <select name="subject_id" required @disabled(! $section)><option value="">Subject…</option>@foreach ($assignments as $a)<option value="{{ $a->subject_id }}" @selected($assignment?->subject_id === $a->subject_id)>{{ $a->subject->name }}</option>@endforeach</select>
    <button class="btn btn-primary">Open</button>
</form>

@if (! $exam || ! $section || ! $assignment)
    <div class="card p-10 text-center text-slate-500">
        @if ($section && $assignments->isEmpty()) No subjects are assigned to you in this class. @else Pick an exam, a class and a subject. @endif</div>
@elseif ($students->isEmpty())
    <div class="card p-10 text-center text-slate-500">No active students in {{ $section->label }}.</div>
@else
    <form method="POST" action="{{ route($prefix . '.store') }}">@csrf
        <input type="hidden" name="exam_id" value="{{ $exam->id }}"><input type="hidden" name="section_id" value="{{ $section->id }}"><input type="hidden" name="subject_id" value="{{ $assignment->subject_id }}">
        <div class="tbl-wrap">
            <div class="border-b border-violet-50 px-5 py-3 font-bold">{{ $exam->name }} · {{ $assignment->subject->name }} · {{ $section->label }} <span class="ml-2 text-sm font-normal text-slate-500">out of {{ $exam->max_marks }}</span></div>
            <div class="tbl-scroll"><table class="tbl">
                <thead><tr><th class="num">#</th><th>Student</th><th>Marks (max {{ $exam->max_marks }})</th><th>%</th><th>Grade</th><th>Remark</th></tr></thead>
                <tbody>
                @foreach ($students as $st)
                    @php $m = $marks[$st->id] ?? null; $pct = $m && $m->marks !== null ? round($m->marks / $exam->max_marks * 100, 1) : null; @endphp
                    <tr><td class="num">{{ $loop->iteration }}</td>
                        <td><div class="font-semibold">{{ $st->name }}</div><div class="text-xs text-slate-400">{{ $st->admission_no }}</div></td>
                        <td><input type="number" step="0.01" min="0" max="{{ $exam->max_marks }}" name="marks[{{ $st->id }}]" value="{{ old('marks.' . $st->id, $m->marks ?? '') }}" @disabled(! $canSave) class="w-28 rounded-lg border border-violet-100 px-2 py-1 text-sm"></td>
                        <td>{{ $pct !== null ? $pct . '%' : '—' }}</td>
                        <td>@if ($pct !== null)<span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-bold text-violet-800">{{ \App\Models\GradeScale::gradeFor($pct)[0] }}</span>@else<span class="text-slate-300">—</span>@endif</td>
                        <td><input name="remark[{{ $st->id }}]" value="{{ old('remark.' . $st->id, $m->remark ?? '') }}" maxlength="200" @disabled(! $canSave) class="w-full min-w-[8rem] rounded-lg border border-violet-100 px-2 py-1 text-sm"></td></tr>
                @endforeach
                </tbody></table></div>
            @if ($canSave)<div class="border-t border-violet-50 bg-slate-50 px-5 py-3 text-right"><button class="btn btn-primary">Save marks</button></div>@endif
        </div>
    </form>
@endif
@endsection
