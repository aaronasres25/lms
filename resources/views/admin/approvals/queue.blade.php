@extends('layouts.admin')
@section('title', 'My approvals')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My approvals</h1>
    <p class="text-sm text-slate-500">Documents where it is <b>your turn</b> to approve. {{ $rows->total() }} waiting.
        @if ($override)<span class="ml-1 rounded bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">Admin override: showing every pending step</span>@endif</p></div>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Document</th><th>Department</th><th>Step</th><th>Assigned to</th><th>Requested by</th><th>Waiting since</th><th></th></tr></thead>
    <tbody>@forelse ($rows as $r)
        <tr><td class="num">{{ $rows->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.books.show', $r->book) }}">{{ $r->book->name }}</a><div class="text-xs text-slate-400">v{{ $r->book->version }}</div></td>
            <td>{{ $r->book->department->name ?? '' }}</td>
            <td><span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Step {{ $r->sequence }}: {{ $r->title }}</span></td>
            <td>{{ $r->assignee_label }}</td><td>{{ $r->requester->name ?? '—' }}</td><td class="text-slate-500">{{ $r->updated_at->diffForHumans() }}</td>
            <td class="text-right"><a href="{{ route('admin.books.show', $r->book) }}" class="btn btn-primary">Review</a></td></tr>
    @empty<tr><td colspan="8" class="tbl-empty">Nothing is waiting for your approval 🎉</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $rows])
</div>
@endsection
