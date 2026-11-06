@extends('layouts.portal')
@section('title', 'Attendance')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-extrabold">My attendance</h1><p class="text-sm text-slate-500">Last 90 days</p></div>
@if (! $student)<div class="card p-6 text-slate-600">Your student profile is not set up yet.</div>@else
<div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-5">
    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-violet-100"><div class="text-3xl font-extrabold text-violet-700">{{ $summary['percent'] }}%</div><div class="text-xs text-slate-500">Attendance</div></div>
    @foreach (['present' => 'text-emerald-600', 'late' => 'text-amber-600', 'excused' => 'text-sky-600', 'absent' => 'text-rose-600'] as $k => $c)
        <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-violet-100"><div class="text-3xl font-extrabold {{ $c }}">{{ $summary[$k] }}</div><div class="text-xs capitalize text-slate-500">{{ $k }}</div></div>
    @endforeach
</div>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl"><thead><tr><th class="num">#</th><th>Date</th><th>Status</th><th>Remark</th></tr></thead>
    <tbody>@forelse ($records as $r)<tr><td class="num">{{ $loop->iteration }}</td><td>{{ $r->date->format('D, d M Y') }}</td>
        <td><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['present' => 'bg-emerald-100 text-emerald-800', 'late' => 'bg-amber-100 text-amber-800', 'excused' => 'bg-sky-100 text-sky-800', 'absent' => 'bg-rose-100 text-rose-800'][$r->status] ?? '' }}">{{ ucfirst($r->status) }}</span></td><td class="text-slate-500">{{ $r->remark }}</td></tr>
    @empty<tr><td colspan="4" class="tbl-empty">No attendance recorded yet.</td></tr>@endforelse</tbody></table></div></div>
@endif
@endsection
