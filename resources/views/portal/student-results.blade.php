@extends('layouts.portal')
@section('title', 'Results')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My results</h1><p class="text-sm text-slate-500">Results appear here once your school publishes them.</p></div>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($exams as $e)
        @php $s = $summary[$e->id]; @endphp
        <div class="card p-5"><div class="flex items-start justify-between"><div><div class="font-bold">{{ $e->name }}</div><div class="text-xs text-slate-500">{{ $e->year->name ?? '' }}{{ $e->term ? ' · ' . $e->term->name : '' }}</div></div>
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 text-xl font-extrabold text-white">{{ $s['grade'] }}</span></div>
            <div class="mt-3 text-3xl font-extrabold text-violet-700">{{ $s['avg'] !== null ? $s['avg'] . '%' : '—' }}</div><div class="text-xs text-slate-500">average · {{ $s['subjects'] }} subjects</div>
            <a target="_blank" href="{{ route('student.report-card', $e) }}" class="btn btn-primary mt-4 w-full justify-center">View report card</a></div>
    @empty<div class="card col-span-full p-10 text-center text-slate-500">No published results yet.</div>@endforelse
</div>
@endsection
