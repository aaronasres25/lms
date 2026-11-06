@extends('layouts.admin')
@section('title', $student->name)
@section('content')
@php $me = auth()->user(); @endphp
@include('partials.temp-password')
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex items-center gap-4">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 text-2xl font-bold text-white">{{ strtoupper(mb_substr($student->name, 0, 1)) }}</span>
        <div><h1 class="text-2xl font-extrabold">{{ $student->name }}</h1>
            <div class="text-sm text-slate-500">{{ $student->admission_no }} · {{ $student->section->label ?? 'No class' }} · <span class="font-semibold">{{ ucfirst($student->status) }}</span></div></div>
    </div>
    <div class="flex gap-2">
        @if ($me->canDo('students', 'update'))<a href="{{ route('admin.school.students.edit', $student) }}" class="btn btn-outline">Edit</a>@endif
        @if ($me->canDo('students', 'delete'))<form method="POST" action="{{ route('admin.school.students.destroy', $student) }}" onsubmit="return confirm('Delete this student?')">@csrf @method('DELETE')<button class="btn btn-outline text-rose-600">Delete</button></form>@endif
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="card p-5"><h3 class="mb-3 font-bold">Profile</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">Gender</dt><dd>{{ ucfirst($student->gender ?? '—') }}</dd></div>
                <div><dt class="text-slate-500">Date of birth</dt><dd>{{ $student->date_of_birth?->toDateString() ?? '—' }}{{ $student->age !== null ? ' (' . $student->age . ' yrs)' : '' }}</dd></div>
                <div><dt class="text-slate-500">Phone</dt><dd>{{ $student->phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Admitted</dt><dd>{{ $student->admitted_on?->toDateString() ?? '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500">Address</dt><dd>{{ $student->address ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Guardian</dt><dd>{{ $student->guardian_name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Guardian contact</dt><dd>{{ $student->guardian_phone ?: '—' }} {{ $student->guardian_email }}</dd></div>
                <div><dt class="text-slate-500">Class teacher</dt><dd>{{ $student->section->classTeacher->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Login</dt><dd>{{ $student->user->email ?? 'none' }}</dd></div>
            </dl></div>

        <div class="card p-5"><h3 class="mb-3 font-bold">Library</h3>
            @if ($member)
                <p class="mb-3 text-sm text-slate-500">Member until {{ $member->membership_end->toDateString() }} · up to {{ $member->max_books }} books.</p>
                <table class="w-full text-sm">@forelse ($loans as $l)<tr class="border-t"><td class="py-1.5">{{ $l->book->name }}</td><td>{{ $l->date_expected?->toDateString() }}</td><td>@include('partials.state-badge', ['state' => $l->state])</td>
                    <td class="text-right"><a class="text-violet-700" href="{{ route('admin.loans.show', $l) }}">{{ $l->name }}</a></td></tr>@empty<tr><td class="text-slate-500">No loans yet.</td></tr>@endforelse</table>
            @else<p class="text-sm text-slate-500">No library membership - the student has no login yet.</p>@endif</div>

        <div class="card p-5"><h3 class="mb-3 font-bold">Results</h3>
            @forelse ($exams as $e)<div class="flex items-center justify-between border-t py-2 text-sm first:border-0"><span>{{ $e->name }} <span class="text-slate-400">· {{ $e->is_published ? 'published' : 'not published' }}</span></span>
                <a class="font-semibold text-violet-700" target="_blank" href="{{ route('admin.school.report-card', [$student, $e]) }}">Report card</a></div>@empty<p class="text-sm text-slate-500">No marks entered yet.</p>@endforelse</div>
    </div>

    <aside class="space-y-5">
        <div class="card p-5"><h3 class="mb-3 font-bold">Attendance (30 days)</h3>
            <div class="text-4xl font-extrabold text-violet-700">{{ $attendance['percent'] }}%</div>
            <div class="mt-2 grid grid-cols-2 gap-1 text-sm text-slate-600"><span>Present {{ $attendance['present'] }}</span><span>Absent {{ $attendance['absent'] }}</span><span>Late {{ $attendance['late'] }}</span><span>Excused {{ $attendance['excused'] }}</span></div></div>
        <div class="card p-5"><h3 class="mb-3 font-bold">Fees</h3>
            @forelse ($invoices as $i)<a href="{{ route('admin.school.invoices.show', $i) }}" class="flex items-center justify-between border-t py-2 text-sm first:border-0 hover:text-violet-700"><span>{{ $i->number }}</span><span>{{ number_format($i->balance, 2) }} <span class="text-xs text-slate-400">due</span></span></a>@empty<p class="text-sm text-slate-500">No invoices.</p>@endforelse</div>
        @if ($history->isNotEmpty())<div class="card p-5"><h3 class="mb-3 font-bold">Class history</h3>
            @foreach ($history as $h)<div class="border-t py-1.5 text-sm first:border-0">{{ $h->section->label ?? '' }} <span class="text-slate-400">· {{ $h->result ?: 'enrolled' }}</span></div>@endforeach</div>@endif
    </aside>
</div>
@endsection
