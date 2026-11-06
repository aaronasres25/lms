@extends('layouts.portal')
@section('title', 'My timetable')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My timetable</h1><p class="text-sm text-slate-500">Your weekly lessons across all classes.</p></div>
@if (! $teacher)<div class="card p-6 text-slate-600">Your teacher profile is not set up yet.</div>@else @include('school._grid', ['grid' => $grid, 'mode' => 'teacher']) @endif
@endsection
