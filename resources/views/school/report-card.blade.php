<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Report card · {{ $student->name }} · {{ $exam->name }}</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="mx-auto max-w-3xl bg-white p-8 text-slate-800">
<div class="mb-6 flex items-end justify-between border-b-4 border-violet-700 pb-3">
    <div><h1 class="text-2xl font-extrabold text-violet-800">{{ config('lms.company_name') }}</h1><div class="text-sm text-slate-500">Student report card</div></div>
    <div class="text-right text-sm"><div class="font-bold">{{ $exam->name }}</div><div class="text-slate-500">{{ $exam->year->name ?? '' }}{{ $exam->term ? ' · ' . $exam->term->name : '' }}</div></div>
</div>

<table class="mb-6 w-full text-sm">
    <tr><td class="w-32 py-1 text-slate-500">Student</td><td class="font-semibold">{{ $student->name }}</td><td class="w-32 text-slate-500">Admission no.</td><td class="font-semibold">{{ $student->admission_no }}</td></tr>
    <tr><td class="py-1 text-slate-500">Class</td><td class="font-semibold">{{ $student->section->label ?? '—' }}</td><td class="text-slate-500">Class teacher</td><td class="font-semibold">{{ $student->section->classTeacher->name ?? '—' }}</td></tr>
</table>

<table class="w-full border-collapse text-sm">
    <thead><tr class="bg-violet-700 text-left text-white"><th class="px-3 py-2">Subject</th><th class="px-3 py-2 text-right">Marks</th><th class="px-3 py-2 text-right">Out of</th><th class="px-3 py-2 text-right">%</th><th class="px-3 py-2 text-center">Grade</th><th class="px-3 py-2">Remark</th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
        <tr class="border-b"><td class="px-3 py-2 font-medium">{{ $r['subject'] }}</td>
            <td class="px-3 py-2 text-right">{{ $r['marks'] ?? '—' }}</td><td class="px-3 py-2 text-right text-slate-500">{{ $r['max'] }}</td>
            <td class="px-3 py-2 text-right">{{ $r['pct'] !== null ? $r['pct'] . '%' : '—' }}</td><td class="px-3 py-2 text-center font-bold">{{ $r['grade'] }}</td>
            <td class="px-3 py-2 text-slate-500">{{ $r['note'] ?: $r['remark'] }}</td></tr>
    @empty<tr><td colspan="6" class="px-3 py-6 text-center text-slate-400">No marks have been entered yet.</td></tr>@endforelse
    </tbody>
</table>

<div class="mt-6 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
    @foreach ([['Total', $total . ' / ' . $maxTotal], ['Average', $average !== null ? $average . '%' : '—'], ['Overall grade', $overall], ['Position', $position ? $position . ' of ' . $classSize : '—']] as [$l, $v])
        <div class="rounded-xl bg-violet-50 p-3 text-center"><div class="text-xs uppercase tracking-wide text-slate-500">{{ $l }}</div><div class="text-lg font-extrabold text-violet-800">{{ $v }}</div></div>
    @endforeach
</div>
<p class="mt-4 text-sm text-slate-600">Attendance this year: <b>{{ $attendance['percent'] }}%</b> ({{ $attendance['present'] + $attendance['late'] }} of {{ $attendance['total'] }} days).</p>

<div class="mt-14 grid grid-cols-2 gap-10 text-center text-xs text-slate-500">
    <div class="border-t pt-1">Class teacher</div><div class="border-t pt-1">Principal</div>
</div>
<div class="mt-8 text-center print:hidden"><button onclick="window.print()" class="rounded-full bg-violet-700 px-6 py-2 font-semibold text-white">🖨 Print</button></div>
</body></html>
