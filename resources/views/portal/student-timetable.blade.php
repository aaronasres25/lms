@extends('layouts.portal')
@section('title', 'Timetable')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My timetable</h1><p class="text-sm text-slate-500">{{ $student?->section->label ?? 'You are not in a class yet.' }}</p></div>
@include('school._grid', ['grid' => $grid, 'mode' => 'section'])
@endsection
