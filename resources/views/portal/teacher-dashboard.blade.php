@extends('layouts.portal')
@section('title', 'Dashboard')
@section('content')
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#e4dbff] via-[#d9ccff] to-[#c2aeff] px-6 py-8 shadow-sm sm:px-10">
    @include('partials.photo-bg', ['i' => 1, 'class' => 'absolute inset-y-0 right-0 hidden w-1/2 bg-cover bg-center opacity-50 sm:block', 'style' => '-webkit-mask-image: linear-gradient(to left, #000 30%, transparent); mask-image: linear-gradient(to left, #000 30%, transparent)'])
    <div class="relative"><h1 class="text-2xl font-extrabold text-violet-950 sm:text-3xl">Hello, {{ \Illuminate\Support\Str::limit(auth()->user()->name, 22) }} 👋</h1>
        <p class="mt-1 text-violet-900/80">{{ now()->format('l, d F Y') }}</p></div>
</section>

@if (! $teacher)
    <div class="card mt-5 p-6 text-slate-600">Your teacher profile is not set up yet. Please ask the school office to link it to your account.</div>
@else
<div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([['My classes', $classSections->count(), 'building', 'from-violet-500 to-purple-700'], ['Subjects I teach', $assignments->count(), 'book', 'from-sky-400 to-blue-600'], ['Students', $studentCount, 'users', 'from-emerald-400 to-green-600'], ['Periods today', $today->count(), 'clock', 'from-amber-400 to-orange-500']] as [$l, $v, $ic, $g])
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br {{ $g }} text-white shadow-lg">@include('partials.icon', ['name' => $ic, 'class' => 'h-5 w-5'])</span>
            <div class="mt-3 text-3xl font-extrabold text-violet-950">{{ $v }}</div><div class="text-sm text-slate-500">{{ $l }}</div></div>
    @endforeach
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <div class="card p-5 lg:col-span-2"><h3 class="mb-3 font-bold">Today's schedule</h3>
        @forelse ($today as $s)<div class="flex items-center gap-4 border-t py-2.5 first:border-0"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 font-bold text-violet-700">{{ $s->period_no }}</span>
            <div class="flex-1"><div class="font-semibold">{{ $s->subject->name }}</div><div class="text-xs text-slate-500">{{ $s->section->label ?? '' }}{{ $s->room ? ' · ' . $s->room : '' }}</div></div>
            <div class="text-xs text-slate-400">{{ config('school.periods')[$s->period_no][0] ?? '' }}</div></div>@empty<p class="text-sm text-slate-500">No lessons today 🎉</p>@endforelse</div>
    <div class="space-y-5">
        <div class="card p-5"><h3 class="mb-3 font-bold">Quick actions</h3><div class="grid gap-2">
            <a href="{{ route('teacher.attendance.index') }}" class="btn btn-primary justify-center">Take attendance</a>
            <a href="{{ route('teacher.marks.index') }}" class="btn btn-outline justify-center">Enter marks</a>
            <a href="{{ route('teacher.timetable') }}" class="btn btn-outline justify-center">My timetable</a></div></div>
        <div class="card p-5"><h3 class="mb-3 font-bold">Notices</h3>
            @forelse ($notices as $n)<div class="border-t py-2 first:border-0"><div class="text-sm font-semibold">{{ $n->title }}</div><div class="line-clamp-2 text-xs text-slate-500">{{ $n->body }}</div><div class="text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div></div>@empty<p class="text-sm text-slate-500">No notices.</p>@endforelse</div>
    </div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-2">
    <div class="card p-5"><h3 class="mb-3 font-bold">Class teacher of</h3>@forelse ($classSections as $s)<div class="flex items-center justify-between border-t py-2 text-sm first:border-0"><span class="font-medium">{{ $s->label }}</span>
        <span class="flex items-center gap-3"><span class="text-slate-400">{{ $s->students_count }} students</span><a class="font-semibold text-violet-700" href="{{ route('teacher.attendance.index', ['section_id' => $s->id]) }}">Attendance</a></span></div>@empty<p class="text-sm text-slate-500">You are not a class teacher.</p>@endforelse</div>
    <div class="card p-5"><h3 class="mb-3 font-bold">My subjects</h3>@forelse ($assignments as $a)<div class="flex items-center justify-between border-t py-2 text-sm first:border-0"><span><b>{{ $a->subject->name }}</b> <span class="text-slate-400">· {{ $a->section->label ?? '' }}</span></span>
        <a class="font-semibold text-violet-700" href="{{ route('teacher.marks.index', ['section_id' => $a->section_id, 'subject_id' => $a->subject_id]) }}">Marks</a></div>@empty<p class="text-sm text-slate-500">No subjects assigned yet.</p>@endforelse</div>
</div>
@endif
@endsection
