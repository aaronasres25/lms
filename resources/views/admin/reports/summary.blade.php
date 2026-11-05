<!DOCTYPE html><html><head><meta charset="utf-8"><title>Library report {{ $today->toDateString() }}</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="mx-auto max-w-3xl p-8 text-slate-800">
<div class="mb-6 flex items-end justify-between border-b-4 border-[#5b21b6] pb-3"><div><h1 class="text-2xl font-bold text-[#5b21b6]">{{ config('lms.company_name') }}</h1><div>Library Management System - Consolidated Report</div></div><div class="text-sm">{{ $today->format('d M Y') }}</div></div>
@foreach (['Books' => $bookStats, 'Loans' => $loanStats, 'Members' => $memberStats] as $title => $stats)
    <h2 class="mb-2 mt-6 font-bold text-[#8B0000]">{{ $title }}</h2>
    <table class="w-full text-sm">@foreach ($stats as $k => $v)<tr class="border-b"><td class="py-1">{{ $k }}</td><td class="text-right font-semibold">{{ $v }}</td></tr>@endforeach</table>
@endforeach
<h2 class="mb-2 mt-6 font-bold text-[#8B0000]">By department</h2>
<table class="w-full text-sm"><tr class="border-b text-left text-xs uppercase text-slate-500"><th>Department</th><th class="text-right">Books</th><th class="text-right">Released</th><th class="text-right">Issued</th></tr>
@foreach ($departments as $d)<tr class="border-b"><td class="py-1">{{ $d['name'] }}</td><td class="text-right">{{ $d['books'] }}</td><td class="text-right">{{ $d['released'] }}</td><td class="text-right">{{ $d['issued'] }}</td></tr>@endforeach</table>
<p class="mt-8 text-center text-xs text-slate-400 print:hidden">Use your browser's Print → Save as PDF.</p>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 600));</script>
</body></html>
