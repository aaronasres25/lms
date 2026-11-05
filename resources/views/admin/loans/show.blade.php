@extends('layouts.admin')
@section('title', $loan->name)
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">{{ $loan->name }} @include('partials.state-badge', ['state' => $loan->state])</h1>
    <div class="flex gap-2">
        @if ($loan->state === 'draft' && auth()->user()->canDo('loans', 'update'))<form method="POST" action="{{ route('admin.loans.issue', $loan) }}">@csrf<button class="rounded bg-[#5b21b6] px-4 py-2 text-white">Issue book</button></form>@endif
        @if ($loan->state === 'issued' && auth()->user()->canDo('loans', 'update'))
            <form method="POST" action="{{ route('admin.loans.return', $loan) }}">@csrf<button class="rounded bg-green-700 px-4 py-2 text-white">Return</button></form>
            <form method="POST" action="{{ route('admin.loans.lost', $loan) }}" onsubmit="return confirm('Mark as lost? Inventory will be reduced and a {{ config('lms.lost_fine_multiplier') }}x fine applies.')">@csrf<button class="rounded bg-red-700 px-4 py-2 text-white">Mark lost</button></form>
        @endif
        @if (auth()->user()->canDo('loans', 'delete') && $loan->state !== 'issued')
            <form method="POST" action="{{ route('admin.loans.destroy', $loan) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="rounded border border-red-300 px-4 py-2 text-red-700">Delete</button></form>@endif
    </div>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl bg-white p-5 shadow lg:col-span-2"><dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div><dt class="text-slate-500">Book</dt><dd><a class="text-violet-700" href="{{ route('admin.books.show', $loan->book) }}">{{ $loan->book->name }}</a></dd></div>
        <div><dt class="text-slate-500">Member</dt><dd><a class="text-violet-700" href="{{ route('admin.members.show', $loan->member) }}">{{ $loan->member->name }}</a></dd></div>
        <div><dt class="text-slate-500">Loan date</dt><dd>{{ $loan->loan_date?->toDateString() }}</dd></div>
        <div><dt class="text-slate-500">Expected return</dt><dd>{{ $loan->date_expected?->toDateString() ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Returned</dt><dd>{{ $loan->date_returned?->toDateString() ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Issued by / Returned to</dt><dd>{{ $loan->issuer->name ?? '—' }} / {{ $loan->returner->name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Overdue days</dt><dd class="{{ $loan->overdue_days ? 'font-bold text-red-700' : '' }}">{{ $loan->overdue_days }}</dd></div>
        <div><dt class="text-slate-500">Fine</dt><dd class="font-semibold">{{ number_format($loan->fine_amount, 2) }}</dd></div></dl></div>
    <div class="rounded-xl bg-white p-5 shadow"><h3 class="mb-3 font-semibold">Activity</h3><ul class="space-y-3 text-sm">
        @foreach ($loan->activities as $a)<li><div class="text-xs text-slate-400">{{ $a->created_at->format('Y-m-d H:i') }} · {{ $a->user->name ?? 'System' }}</div>{{ $a->body }}</li>@endforeach</ul></div>
</div>
@endsection
