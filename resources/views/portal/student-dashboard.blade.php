@extends('layouts.portal')
@section('title', 'Dashboard')
@section('content')
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#e4dbff] via-[#d9ccff] to-[#c2aeff] px-6 py-8 shadow-sm sm:px-10">
    @include('partials.photo-bg', ['i' => 0, 'class' => 'absolute inset-y-0 right-0 hidden w-1/2 bg-cover bg-center opacity-50 sm:block', 'style' => '-webkit-mask-image: linear-gradient(to left, #000 30%, transparent); mask-image: linear-gradient(to left, #000 30%, transparent)'])
    <div class="relative"><h1 class="text-2xl font-extrabold text-violet-950 sm:text-3xl">Welcome, {{ \Illuminate\Support\Str::limit(auth()->user()->name, 22) }} 👋</h1>
        <p class="mt-1 text-violet-900/80">{{ $student ? ($student->section->label ?? 'No class yet') . ' · ' . $student->admission_no : 'Your student profile is not set up yet.' }}</p></div>
</section>

@if ($student)
<div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <a href="{{ route('student.attendance') }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-lg"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-green-600 text-white shadow-lg">@include('partials.icon', ['name' => 'clipboard', 'class' => 'h-5 w-5'])</span>
        <div class="mt-3 text-3xl font-extrabold text-violet-950">{{ $attendance['percent'] }}%</div><div class="text-sm text-slate-500">Attendance (30 days)</div></a>
    <a href="{{ route('student.fees') }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-lg"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg">@include('partials.icon', ['name' => 'chart', 'class' => 'h-5 w-5'])</span>
        <div class="mt-3 text-3xl font-extrabold {{ $due > 0 ? 'text-rose-600' : 'text-violet-950' }}">{{ number_format($due, 2) }}</div><div class="text-sm text-slate-500">Fees due</div></a>
    <a href="{{ route('student.library') }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-lg"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 text-white shadow-lg">@include('partials.icon', ['name' => 'book', 'class' => 'h-5 w-5'])</span>
        <div class="mt-3 text-3xl font-extrabold text-violet-950">{{ $loans->count() }}</div><div class="text-sm text-slate-500">Books borrowed / requested</div></a>
    <a href="{{ route('student.results') }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-lg"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-white shadow-lg">@include('partials.icon', ['name' => 'star', 'class' => 'h-5 w-5'])</span>
        <div class="mt-3 text-lg font-extrabold text-violet-950">My results</div><div class="text-sm text-slate-500">Report cards</div></a>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <div class="card p-5 lg:col-span-2"><h3 class="mb-3 font-bold">Today's lessons</h3>
        @forelse ($today as $s)<div class="flex items-center gap-4 border-t py-2.5 first:border-0"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 font-bold text-violet-700">{{ $s->period_no }}</span>
            <div class="flex-1"><div class="font-semibold">{{ $s->subject->name }}</div><div class="text-xs text-slate-500">{{ $s->teacher->name ?? '' }}{{ $s->room ? ' · ' . $s->room : '' }}</div></div>
            <div class="text-xs text-slate-400">{{ config('school.periods')[$s->period_no][0] ?? '' }}</div></div>@empty<p class="text-sm text-slate-500">No lessons today 🎉</p>@endforelse</div>
    <div class="card p-5"><h3 class="mb-3 font-bold">Notices</h3>
        @forelse ($notices as $n)<div class="border-t py-2 first:border-0"><div class="text-sm font-semibold">{{ $n->title }}</div><div class="line-clamp-2 text-xs text-slate-500">{{ $n->body }}</div><div class="text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div></div>@empty<p class="text-sm text-slate-500">No notices.</p>@endforelse</div>
</div>

@if ($reading->isNotEmpty())
<section class="mt-5 card p-5"><h3 class="mb-3 font-bold">▶ Continue reading</h3>
    <div class="no-scrollbar flex gap-5 overflow-x-auto pb-1">@foreach ($reading as $r)
        @php $pct = min(100, (int) round($r->current_page / max($r->book->total_pages, 1) * 100)); @endphp
        @include('partials.book-card', ['book' => $r->book, 'progressPage' => $r->current_page, 'progressPct' => $pct])@endforeach</div></section>
@endif
@endif
@endsection
