@extends('layouts.admin')
@section('title', $section->label)
@section('content')
@php $canEdit = auth()->user()->canDo('academics', 'update'); @endphp
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">{{ $section->label }}</h1>
        <p class="text-sm text-slate-500">{{ $section->academicYear->name ?? '' }} · Class teacher: <b>{{ $section->classTeacher->name ?? 'not set' }}</b>{{ $section->room ? ' · Room ' . $section->room : '' }}</p></div>
    <div class="flex gap-2">
        <a href="{{ route('admin.school.timetable.index', ['section_id' => $section->id]) }}" class="btn btn-outline">Timetable</a>
        <a href="{{ route('admin.school.attendance.index', ['section_id' => $section->id]) }}" class="btn btn-outline">Attendance</a>
        <a href="{{ route('admin.school.sections.index') }}" class="btn btn-outline">All classes</a>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-2">
    <div class="card p-5"><h3 class="mb-3 font-bold">Subjects &amp; teachers</h3>
        <table class="w-full text-sm">
            @forelse ($assignments as $a)
                <tr class="border-t first:border-0"><td class="py-2 font-medium">{{ $a->subject->name }}</td><td class="text-slate-500">{{ $a->teacher->name ?? 'No teacher' }}</td>
                    <td class="text-right">@if ($canEdit)<form method="POST" action="{{ route('admin.school.sections.unassign', [$section, $a]) }}" onsubmit="return confirm('Remove this subject?')">@csrf @method('DELETE')<button class="text-rose-600">Remove</button></form>@endif</td></tr>
            @empty<tr><td class="text-slate-500">No subjects assigned yet.</td></tr>@endforelse
        </table>
        @if ($canEdit)
        <form method="POST" action="{{ route('admin.school.sections.assign', $section) }}" class="mt-4 grid gap-2 border-t pt-4 sm:grid-cols-3">@csrf
            <select name="subject_id" required class="rounded-lg border border-violet-100 px-2 py-2 text-sm"><option value="">Subject…</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            <select name="teacher_id" class="rounded-lg border border-violet-100 px-2 py-2 text-sm"><option value="">Teacher…</option>@foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            <button class="btn btn-primary justify-center">Assign</button>
            <p class="text-xs text-slate-500 sm:col-span-3">Assigning an existing subject again just changes its teacher.</p>
        </form>
        @endif
    </div>

    <div class="card p-5"><h3 class="mb-3 font-bold">Students <span class="ml-1 rounded-full bg-violet-100 px-2 text-xs font-bold text-violet-700">{{ $students->count() }}</span></h3>
        <table class="w-full text-sm">@forelse ($students as $s)
            <tr class="border-t first:border-0"><td class="w-8 py-1.5 text-slate-400">{{ $loop->iteration }}</td><td><a class="font-medium text-violet-800 hover:underline" href="{{ route('admin.school.students.show', $s) }}">{{ $s->name }}</a></td><td class="text-slate-400">{{ $s->admission_no }}</td></tr>
            @empty<tr><td class="text-slate-500">No students in this class.</td></tr>@endforelse</table></div>
</div>
@endsection
