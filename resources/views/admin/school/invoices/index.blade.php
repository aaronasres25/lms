@extends('layouts.admin')
@section('title', 'Fees')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Fee invoices</h1><p class="text-sm text-slate-500">Billed {{ number_format($billed, 2) }} · collected {{ number_format($collected, 2) }} · outstanding <b class="text-rose-600">{{ number_format($billed - $collected, 2) }}</b></p></div>
    @if (auth()->user()->canDo('fees', 'create'))<a href="{{ route('admin.school.invoices.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Create invoices</a>@endif
</div>
<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Invoice no, student…" class="min-w-[12rem] flex-1">
    <select name="status"><option value="">Any status</option>@foreach (['unpaid', 'partial', 'paid', 'void'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
    <label class="flex items-center gap-1 px-2 text-sm"><input type="checkbox" name="overdue" value="1" @checked(request('overdue'))> Overdue</label>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Invoice</th><th>Student</th><th>Class</th><th>Due</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
    <tbody>@forelse ($invoices as $i)
        <tr><td class="num">{{ $invoices->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.school.invoices.show', $i) }}">{{ $i->number }}</a></td>
            <td>{{ $i->student->name }}<div class="text-xs text-slate-400">{{ $i->student->admission_no }}</div></td><td>{{ $i->student->section->label ?? '—' }}</td>
            <td class="{{ $i->is_overdue ? 'font-bold text-rose-600' : '' }}">{{ $i->due_date?->toDateString() ?? '—' }}</td>
            <td class="text-right">{{ number_format($i->total, 2) }}</td><td class="text-right">{{ number_format($i->paid, 2) }}</td><td class="text-right font-semibold">{{ number_format($i->balance, 2) }}</td>
            <td><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['paid' => 'bg-emerald-100 text-emerald-800', 'partial' => 'bg-amber-100 text-amber-800', 'unpaid' => 'bg-rose-100 text-rose-800', 'void' => 'bg-slate-200 text-slate-600'][$i->status] ?? '' }}">{{ ucfirst($i->status) }}</span></td></tr>
    @empty<tr><td colspan="9" class="tbl-empty">No invoices.</td></tr>@endforelse</tbody></table></div>
    @include('partials.pager', ['paginator' => $invoices])
</div>
@endsection
