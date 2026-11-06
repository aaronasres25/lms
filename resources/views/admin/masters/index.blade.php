@extends('layouts.admin')
@section('title', $title)
@section('content')
@php
    $me = auth()->user();
    $canCreate = $me->canDo($perm, 'create'); $canUpdate = $me->canDo($perm, 'update'); $canDelete = $me->canDo($perm, 'delete');
    $cols = min(count($fields) + 1, 4);
@endphp
<div class="mb-5"><h1 class="text-2xl font-extrabold">{{ \Illuminate\Support\Str::plural($title) }}</h1><p class="text-sm text-slate-500">{{ $rows->total() }} records</p></div>

@if ($canCreate)
<form method="POST" action="{{ route($prefix . '.store') }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-{{ $cols }}">@csrf
    @foreach ($fields as $f) @include('admin.masters._field', ['f' => $f, 'value' => old($f['name']), 'parents' => $parents]) @endforeach
    <button class="btn btn-primary self-end justify-center">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Add {{ strtolower($title) }}</button>
</form>
@endif

<form class="filter-bar mb-4 flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Search…" class="min-w-[12rem] flex-1"><button class="btn btn-primary">Search</button></form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Name</th>@foreach ($extra as $label => $fn)<th>{{ $label }}</th>@endforeach @if ($canUpdate || $canDelete)<th class="text-right">Actions</th>@endif</tr></thead>
    <tbody>@forelse ($rows as $row)
        <tr class="align-top"><td class="num">{{ $rows->firstItem() + $loop->index }}</td>
            <td class="font-semibold">@if ($hasShow)<a class="text-violet-800 hover:underline" href="{{ route($prefix . '.show', $row->id) }}">{{ $row->label ?? $row->name ?? $row->title }}</a>@else{{ $row->label ?? $row->name ?? $row->title }}@endif</td>
            @foreach ($extra as $label => $fn)<td>{{ $fn($row) }}</td>@endforeach
            @if ($canUpdate || $canDelete)<td class="text-right">
                @if ($canUpdate)
                <details class="inline-block text-left"><summary class="cursor-pointer font-medium text-violet-700">Edit</summary>
                    <form method="POST" action="{{ route($prefix . '.update', $row->id) }}" class="absolute right-6 z-10 mt-2 w-72 space-y-2 rounded-xl border bg-white p-3 shadow-xl">@csrf @method('PUT')
                        @foreach ($fields as $f) @include('admin.masters._field', ['f' => $f, 'value' => $row->{$f['name']}, 'parents' => $parents, 'selfId' => $row->id]) @endforeach
                        <button class="btn btn-primary">Save</button></form></details>
                @endif
                @if ($canDelete)
                <form method="POST" action="{{ route($prefix . '.destroy', $row->id) }}" class="ml-3 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="font-medium text-red-700">Delete</button></form>
                @endif
            </td>@endif</tr>
    @empty<tr><td colspan="{{ 3 + count($extra) }}" class="tbl-empty">No records.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $rows])
</div>
@endsection
