@extends('layouts.admin')
@section('title', $invoice->number)
@section('content')
@php $me = auth()->user(); @endphp
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">{{ $invoice->number }} <span class="rounded-full px-2.5 py-0.5 align-middle text-xs font-semibold {{ ['paid' => 'bg-emerald-100 text-emerald-800', 'partial' => 'bg-amber-100 text-amber-800', 'unpaid' => 'bg-rose-100 text-rose-800', 'void' => 'bg-slate-200 text-slate-600'][$invoice->status] ?? '' }}">{{ ucfirst($invoice->status) }}</span></h1>
        <p class="text-sm text-slate-500">{{ $invoice->student->name }} · {{ $invoice->student->admission_no }} · {{ $invoice->student->section->label ?? '' }}</p></div>
    <div class="flex gap-2"><button onclick="window.print()" class="btn btn-outline">🖨 Print</button>
        @if ($me->canDo('fees', 'update') && $invoice->status !== 'void' && $invoice->payments->isEmpty())<form method="POST" action="{{ route('admin.school.invoices.void', $invoice) }}" onsubmit="return confirm('Void this invoice?')">@csrf<button class="btn btn-outline text-rose-600">Void</button></form>@endif</div>
</div>
<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="card p-5"><h3 class="mb-3 font-bold">Items</h3>
            <table class="w-full text-sm">@foreach ($invoice->items as $it)<tr class="border-t first:border-0"><td class="py-2">{{ $it->description }}</td><td class="text-right">{{ number_format($it->amount, 2) }}</td></tr>@endforeach
                <tr class="border-t-2"><td class="py-2 font-bold">Total</td><td class="text-right font-bold">{{ number_format($invoice->total, 2) }}</td></tr>
                <tr><td class="py-1 text-slate-500">Paid</td><td class="text-right">{{ number_format($invoice->paid, 2) }}</td></tr>
                <tr><td class="py-1 font-bold text-rose-600">Balance</td><td class="text-right font-bold text-rose-600">{{ number_format($invoice->balance, 2) }}</td></tr></table>
            <p class="mt-3 text-xs text-slate-500">{{ $invoice->year->name ?? '' }}{{ $invoice->term ? ' · ' . $invoice->term->name : '' }}{{ $invoice->due_date ? ' · due ' . $invoice->due_date->toDateString() : '' }} {{ $invoice->description }}</p></div>
        <div class="card p-5"><h3 class="mb-3 font-bold">Payments</h3>
            <table class="w-full text-sm">@forelse ($invoice->payments as $p)<tr class="border-t first:border-0"><td class="py-2">{{ $p->paid_on->toDateString() }}</td><td>{{ ucfirst($p->method) }} {{ $p->reference }}</td><td class="text-slate-400">{{ $p->receiver->name ?? '' }}</td><td class="text-right font-semibold">{{ number_format($p->amount, 2) }}</td></tr>@empty<tr><td class="text-slate-500">No payments yet.</td></tr>@endforelse</table></div>
    </div>
    @if ($me->canDo('fees', 'update') && $invoice->status !== 'void' && $invoice->balance > 0)
    <form method="POST" action="{{ route('admin.school.invoices.pay', $invoice) }}" class="card space-y-3 self-start p-5 print:hidden">@csrf
        <h3 class="font-bold">Record a payment</h3>
        <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ $invoice->balance }}" required class="w-full rounded-lg border border-violet-100 px-3 py-2 text-sm">
        <input type="date" name="paid_on" value="{{ today()->toDateString() }}" required class="w-full rounded-lg border border-violet-100 px-3 py-2 text-sm">
        <select name="method" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-sm"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile money</option><option value="card">Card</option></select>
        <input name="reference" placeholder="Reference (optional)" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-sm">
        <button class="btn btn-primary w-full justify-center">Save payment</button>
    </form>
    @endif
</div>
@endsection
