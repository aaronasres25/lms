@extends('layouts.admin')
@section('title', 'Feedback')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">User feedback</h1><p class="text-sm text-slate-500">{{ $items->total() }} messages</p></div>
@php $canDelete = auth()->user()->canDo('feedback', 'delete'); @endphp
<div class="tbl-wrap">
<div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>From</th><th>Email</th><th>Comment</th><th>Received</th>@if ($canDelete)<th></th>@endif</tr></thead>
    <tbody>@forelse ($items as $f)
        <tr class="align-top"><td class="num">{{ $items->firstItem() + $loop->index }}</td><td class="font-semibold">{{ $f->name }}</td><td>{{ $f->email ?: '—' }}</td>
            <td class="max-w-xl whitespace-pre-line">{{ $f->comment }}</td><td class="whitespace-nowrap text-slate-500">{{ $f->created_at->format('Y-m-d H:i') }}</td>
            @if ($canDelete)<td><form method="POST" action="{{ route('admin.feedback.destroy', $f) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="font-medium text-red-700">Delete</button></form></td>@endif</tr>
    @empty<tr><td colspan="6" class="tbl-empty">No feedback yet.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $items])
</div>
@endsection
