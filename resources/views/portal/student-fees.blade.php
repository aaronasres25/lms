@extends('layouts.portal')
@section('title', 'Fees')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My fees</h1><p class="text-sm text-slate-500">Invoices and payments. Pay at the school office - the balance updates once it is recorded.</p></div>
<div class="space-y-4">
@forelse ($invoices as $i)
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2"><div class="font-bold">{{ $i->number }} <span class="ml-2 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['paid' => 'bg-emerald-100 text-emerald-800', 'partial' => 'bg-amber-100 text-amber-800', 'unpaid' => 'bg-rose-100 text-rose-800'][$i->status] ?? '' }}">{{ ucfirst($i->status) }}</span></div>
            <div class="text-sm text-slate-500">{{ $i->due_date ? 'Due ' . $i->due_date->toDateString() : '' }}</div></div>
        <table class="mt-3 w-full text-sm">@foreach ($i->items as $it)<tr class="border-t first:border-0"><td class="py-1.5">{{ $it->description }}</td><td class="text-right">{{ number_format($it->amount, 2) }}</td></tr>@endforeach
            <tr class="border-t-2"><td class="py-1.5 font-bold">Total</td><td class="text-right font-bold">{{ number_format($i->total, 2) }}</td></tr>
            <tr><td class="py-1 text-slate-500">Paid</td><td class="text-right">{{ number_format($i->paid, 2) }}</td></tr>
            <tr><td class="py-1 font-bold {{ $i->balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">Balance</td><td class="text-right font-bold {{ $i->balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($i->balance, 2) }}</td></tr></table>
    </div>
@empty<div class="card p-10 text-center text-slate-500">You have no invoices.</div>@endforelse
</div>
@endsection
