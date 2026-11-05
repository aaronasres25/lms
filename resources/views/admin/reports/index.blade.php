@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-extrabold">Library reports</h1>
    <p class="text-sm text-slate-500">{{ $rows->total() }} loans · total fines <b>{{ number_format($totalFine, 2) }}</b></p></div>
    <div class="flex gap-2"><a href="{{ route('admin.reports.summary') }}" target="_blank" class="btn btn-outline">🖨 Consolidated report</a>
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-primary">⬇ CSV</a></div></div>
<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Book or member…" class="min-w-[12rem] flex-1">
    <select name="department_id"><option value="">Any department</option>@foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $n }}</option>@endforeach</select>
    <select name="loan_state"><option value="">Any loan state</option>@foreach (\App\Models\Loan::STATES as $k => $l)<option value="{{ $k }}" @selected(request('loan_state') === $k)>{{ $l }}</option>@endforeach</select>
    <select name="book_state"><option value="">Any book state</option>@foreach (\App\Models\Book::STATES as $k => $l)<option value="{{ $k }}" @selected(request('book_state') === $k)>{{ $l }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}"><input type="date" name="to" value="{{ request('to') }}">
    <button class="btn btn-primary">Filter</button></form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Loan</th><th>Book</th><th>Member</th><th>Department</th><th>Loan date</th><th>Expected</th><th>Returned</th><th>Loan</th><th>Book</th><th>Overdue</th><th>Fine</th></tr></thead>
    <tbody>@forelse ($rows as $l)<tr><td class="num">{{ $rows->firstItem() + $loop->index }}</td><td class="font-semibold">{{ $l->name }}</td><td>{{ $l->book->name }}</td><td>{{ $l->member->name }}</td><td>{{ $l->book->department->name ?? '' }}</td>
        <td>{{ $l->loan_date?->toDateString() }}</td><td>{{ $l->date_expected?->toDateString() }}</td><td>{{ $l->date_returned?->toDateString() }}</td>
        <td>@include('partials.state-badge', ['state' => $l->state])</td><td>@include('partials.state-badge', ['state' => $l->book->state])</td>
        <td class="{{ $l->report_overdue_days ? 'font-bold text-red-700' : 'text-slate-400' }}">{{ $l->report_overdue_days }}</td><td class="font-semibold">{{ number_format($l->fine_amount, 2) }}</td></tr>
    @empty<tr><td colspan="12" class="tbl-empty">No data for this selection.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $rows])
</div>
@endsection
