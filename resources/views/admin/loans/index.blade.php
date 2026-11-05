@extends('layouts.admin')
@section('title', 'Loans')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Loans</h1><p class="text-sm text-slate-500">{{ $loans->total() }} records</p></div>
    @if (auth()->user()->canDo('loans', 'create'))<a href="{{ route('admin.loans.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) New loan</a>@endif
</div>
<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Loan, book or member…" class="min-w-[12rem] flex-1">
    <select name="state"><option value="">Any state</option>@foreach (\App\Models\Loan::STATES as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach</select>
    <label class="flex items-center gap-1 px-2 text-sm"><input type="checkbox" name="overdue" value="1" @checked(request('overdue'))> Overdue only</label>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Loan</th><th>Book</th><th>Member</th><th>Loan date</th><th>Expected</th><th>Returned</th><th>Overdue</th><th>Fine</th><th>State</th></tr></thead>
    <tbody>@forelse ($loans as $l)
        <tr><td class="num">{{ $loans->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.loans.show', $l) }}">{{ $l->name }}</a></td>
            <td>{{ $l->book->name }}</td><td>{{ $l->member->name }}</td><td>{{ $l->loan_date?->toDateString() }}</td>
            <td>{{ $l->date_expected?->toDateString() }}</td><td>{{ $l->date_returned?->toDateString() }}</td>
            <td class="{{ $l->overdue_days ? 'font-bold text-red-700' : 'text-slate-400' }}">{{ $l->overdue_days }}</td>
            <td class="font-semibold">{{ number_format($l->fine_amount, 2) }}</td>
            <td>@include('partials.state-badge', ['state' => $l->state])</td></tr>
    @empty<tr><td colspan="10" class="tbl-empty">No loans.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $loans])
</div>
@endsection
