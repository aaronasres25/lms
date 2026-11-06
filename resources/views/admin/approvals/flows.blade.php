@extends('layouts.admin')
@section('title', 'Approval flows')
@section('content')
@php $scopeName = $deptId ? ($departments[$deptId] ?? 'Department') : 'Default (all departments)'; @endphp
<div class="mb-5"><h1 class="text-2xl font-extrabold">Approval flows</h1>
    <p class="text-sm text-slate-500">Decide <b>who approves which department's books</b> and in <b>what order</b>. A step is assigned to a role (anyone holding it) or to one named person. The approve button only appears for the assignee of the <b>current</b> step.</p></div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- scope list --}}
    <div class="card overflow-hidden">
        <div class="border-b px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Applies to</div>
        <ul class="max-h-[32rem] divide-y overflow-y-auto text-sm">
            <li><a href="{{ route('admin.flows.index', ['department' => 'default']) }}" class="flex items-center justify-between px-4 py-3 hover:bg-violet-50 {{ $deptId === null ? 'bg-violet-50 font-semibold ring-2 ring-inset ring-violet-300' : '' }}">
                <span>★ Default (all departments)</span><span class="rounded-full bg-violet-100 px-2 text-xs font-bold text-violet-700">{{ $defaultCount }}</span></a></li>
            @foreach ($departments as $id => $name)
                <li><a href="{{ route('admin.flows.index', ['department' => $id]) }}" class="flex items-center justify-between px-4 py-2.5 hover:bg-violet-50 {{ $deptId === $id ? 'bg-violet-50 font-semibold ring-2 ring-inset ring-violet-300' : '' }}">
                    <span class="truncate">{{ $name }}</span>@if (($counts[$id] ?? 0) > 0)<span class="rounded-full bg-violet-100 px-2 text-xs font-bold text-violet-700">{{ $counts[$id] }}</span>@endif</a></li>
            @endforeach
        </ul>
    </div>

    <div class="space-y-5 lg:col-span-2">
        <div class="card p-5">
            <h2 class="text-lg font-bold">{{ $scopeName }}</h2>
            @if ($steps->isEmpty())
                <p class="mt-1 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                    No own steps here. @if ($deptId) This department <b>inherits</b> the nearest parent department's flow, otherwise the Default flow. @else Without any steps, the built-in flow is used: a single <b>QA review</b> by the QA role. @endif</p>
            @endif

            <ol class="mt-4 space-y-3">
                @foreach ($steps as $st)
                    <li class="rounded-2xl border border-violet-100 bg-violet-50/40 p-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-700 font-bold text-white">{{ $st->sequence }}</span>
                            <div class="min-w-0 flex-1"><div class="font-semibold">{{ $st->title }}</div>
                                <div class="text-sm text-slate-500">{{ $st->user ? '👤 ' . $st->user->name . ' (' . $st->user->role . ')' : '🎭 Any ' . $st->role }}</div></div>
                            @php $isFirst = $loop->first; $isLast = $loop->last; @endphp
                            <div class="flex items-center gap-1">
                                @foreach (['up' => 'up', 'down' => 'down'] as $dir => $ic)
                                    <form method="POST" action="{{ route('admin.flows.move', $st) }}">@csrf<input type="hidden" name="direction" value="{{ $dir }}"
                                        ><button class="rounded-lg p-2 text-slate-500 hover:bg-white hover:text-violet-700" title="Move {{ $dir }}" @disabled(($dir === 'up' && $isFirst) || ($dir === 'down' && $isLast))>@include('partials.icon', ['name' => $ic, 'class' => 'h-4 w-4'])</button></form>
                                @endforeach
                                <form method="POST" action="{{ route('admin.flows.destroy', $st) }}" onsubmit="return confirm('Remove this step?')">@csrf @method('DELETE')<button class="rounded-lg p-2 text-rose-500 hover:bg-rose-50" title="Remove">@include('partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button></form>
                            </div>
                        </div>
                        <details class="mt-2"><summary class="cursor-pointer text-xs font-semibold text-violet-700">Edit step</summary>
                            <form method="POST" action="{{ route('admin.flows.update', $st) }}" class="mt-2 grid gap-2 sm:grid-cols-4">@csrf @method('PUT')
                                @include('admin.approvals._fields', ['title' => $st->title, 'role' => $st->role, 'userId' => $st->user_id])
                                <button class="btn btn-primary justify-center sm:col-span-4">Save step</button></form></details>
                    </li>
                @endforeach
            </ol>
        </div>

        <form method="POST" action="{{ route('admin.flows.store') }}" class="card p-5">@csrf
            <input type="hidden" name="department" value="{{ $deptId ?? 'default' }}">
            <h3 class="mb-3 font-bold">Add step {{ $steps->count() + 1 }} to “{{ $scopeName }}”</h3>
            <div class="grid gap-2 sm:grid-cols-4">
                @include('admin.approvals._fields', ['title' => '', 'role' => null, 'userId' => null])
                <button class="btn btn-primary justify-center sm:col-span-4">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Add step</button>
            </div>
            <p class="mt-3 text-xs text-slate-500">Steps run in order: step 2 only becomes available after step 1 is approved. A rejection at any step sends the document back to Draft. Documents already under review keep the flow they were submitted with.</p>
        </form>
    </div>
</div>
@endsection
