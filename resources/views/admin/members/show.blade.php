@extends('layouts.admin')
@section('title', $member->name)
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-2xl font-bold">{{ $member->name }}</h1>
    <div class="flex gap-2">
        @if (auth()->user()->canDo('members', 'update'))<a href="{{ route('admin.members.edit', $member) }}" class="rounded border px-4 py-2">Edit</a>@endif
        @if (auth()->user()->canDo('members', 'delete'))<form method="POST" action="{{ route('admin.members.destroy', $member) }}" onsubmit="return confirm('Delete member?')">@csrf @method('DELETE')<button class="rounded border border-red-300 px-4 py-2 text-red-700">Delete</button></form>@endif
    </div></div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-xl bg-white p-5 shadow"><dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-slate-500">User account</dt><dd>{{ $member->user->email ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Membership</dt><dd>{{ $member->membership_start->toDateString() }} → {{ $member->membership_end->toDateString() }} ({{ $member->active ? 'active' : 'expired' }})</dd></div>
            <div><dt class="text-slate-500">Max books</dt><dd>{{ $member->max_books }}</dd></div>
            <div><dt class="text-slate-500">Issued / Returned / Lost</dt><dd>{{ $member->loan_count_issued }} / {{ $member->loan_count_returned }} / {{ $member->loan_count_lost }}</dd></div></dl></div>
        <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-2 font-semibold">Loans</h3><table class="w-full text-sm">
            @forelse ($member->loans as $l)<tr class="border-t"><td class="py-1"><a class="text-violet-700" href="{{ route('admin.loans.show', $l) }}">{{ $l->name }}</a></td><td>{{ $l->book->name }}</td><td>{{ $l->date_expected?->toDateString() }}</td><td>{{ number_format($l->fine_amount, 2) }}</td><td>@include('partials.state-badge', ['state' => $l->state])</td></tr>@empty<tr><td class="text-slate-500">No loans.</td></tr>@endforelse</table></div>
    </div>
    <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-3 font-semibold">Activity</h3><ul class="space-y-3 text-sm">@foreach ($member->activities as $a)<li><div class="text-xs text-slate-400">{{ $a->created_at->format('Y-m-d H:i') }} · {{ $a->user->name ?? 'System' }}</div>{{ $a->body }}</li>@endforeach</ul></div>
</div>
@endsection
