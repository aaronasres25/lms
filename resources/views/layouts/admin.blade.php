<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Library') · {{ config('lms.company_name') }} LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        summary { list-style: none; cursor: pointer; } summary::-webkit-details-marker { display: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { scrollbar-width: none; }
        /* ---------- data tables ---------- */
        .tbl-wrap { background: #fff; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 1px 3px rgba(76,29,149,.08), 0 12px 30px -16px rgba(76,29,149,.25); }
        .tbl-scroll { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .tbl thead th { background: linear-gradient(90deg, #5b21b6, #7c3aed); color: #fff; font-weight: 600; font-size: .7rem; letter-spacing: .07em; text-transform: uppercase; text-align: left; padding: .9rem 1rem; white-space: nowrap; }
        .tbl tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1ecfd; vertical-align: middle; }
        .tbl tbody tr:nth-child(even) { background: #faf8ff; }
        .tbl tbody tr { transition: background .15s; }
        .tbl tbody tr:hover { background: #f1ebff; }
        .tbl tbody tr:last-child td { border-bottom: 0; }
        .tbl .num { width: 3.25rem; text-align: center; }
        .tbl tbody td.num { color: #a78bfa; font-weight: 700; font-size: .75rem; }
        .tbl-empty { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
        .card { background: #fff; border-radius: 1.25rem; box-shadow: 0 1px 3px rgba(76,29,149,.08), 0 12px 30px -16px rgba(76,29,149,.25); }
        .filter-bar { background: #fff; border-radius: 1.25rem; padding: .75rem; box-shadow: 0 1px 3px rgba(76,29,149,.08); }
        .filter-bar input, .filter-bar select { border: 1px solid #e7e0fb; border-radius: .7rem; padding: .5rem .75rem; font-size: .875rem; background: #fff; outline: none; }
        .filter-bar input:focus, .filter-bar select:focus { border-color: #a78bfa; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: .8rem; padding: .55rem 1.1rem; font-weight: 600; font-size: .875rem; transition: all .15s; }
        .btn-primary { background: linear-gradient(90deg, #6d28d9, #7c3aed); color: #fff; box-shadow: 0 6px 14px -6px rgba(109,40,217,.6); } .btn-primary:hover { filter: brightness(1.1); }
        .btn-outline { border: 1px solid #ddd6fe; background: #fff; color: #5b21b6; } .btn-outline:hover { background: #f5f3ff; }
        #sidebar nav::-webkit-scrollbar { width: 6px; } #sidebar nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 3px; }
    </style>
</head>
<body class="bg-[#f4f1ff] text-slate-800">
@php
    $u = auth()->user();
    // [label, route, active-pattern, icon, resource (null = always, '__admin' = admins only)]
    $sections = [
        'Overview' => [['Dashboard', 'admin.dashboard', 'admin.dashboard', 'home', null]],
        'Library' => [
            ['Books', 'admin.books.index', 'admin.books.*', 'book', 'books'],
            ['Loans', 'admin.loans.index', 'admin.loans.*', 'loan', 'loans'],
            ['Members', 'admin.members.index', 'admin.members.*', 'users', 'members'],
        ],
        'Catalog data' => [
            ['Authors', 'admin.authors.index', 'admin.authors.*', 'pencil', 'authors'],
            ['Categories', 'admin.categories.index', 'admin.categories.*', 'tag', 'categories'],
            ['Departments', 'admin.departments.index', 'admin.departments.*', 'building', 'departments'],
        ],
        'Insights' => [
            ['Reports', 'admin.reports.index', 'admin.reports.*', 'chart', 'reports'],
            ['Feedback', 'admin.feedback.index', 'admin.feedback.*', 'chat', 'feedback'],
        ],
        'Administration' => [['Access rights', 'admin.access.index', 'admin.access.*', 'shield', '__admin']],
    ];
    $visible = fn ($res) => $res === null || ($res === '__admin' ? $u->atLeast('admin') : $u->canDo($res, 'read'));

    // notification bell
    $notes = [];
    if ($u->canDo('loans', 'read')) {
        $n = \App\Models\Loan::where('state', 'issued')->whereNotNull('date_expected')->where('date_expected', '<', today()->toDateString())->count();
        if ($n) $notes[] = ['Overdue loans', $n, route('admin.loans.index', ['overdue' => 1]), 'clock', 'bg-orange-100 text-orange-600'];
    }
    if ($u->canDo('books', 'read')) {
        $n = \App\Models\Book::where('state', 'review')->count();
        if ($n) $notes[] = ['Books waiting for review', $n, route('admin.books.index', ['state' => 'review']), 'book', 'bg-amber-100 text-amber-600'];
        $n = \App\Models\Book::where('state', 'approved')->count();
        if ($n) $notes[] = ['Approved - ready to release', $n, route('admin.books.index', ['state' => 'approved']), 'shield', 'bg-emerald-100 text-emerald-600'];
    }
    if ($u->canDo('feedback', 'read')) {
        $n = \App\Models\Feedback::where('created_at', '>=', now()->startOfDay())->count();
        if ($n) $notes[] = ['New feedback today', $n, route('admin.feedback.index'), 'chat', 'bg-sky-100 text-sky-600'];
    }
    $notifTotal = array_sum(array_column($notes, 1));
@endphp

<div id="overlay" class="fixed inset-0 z-30 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-[#4a1a9c] via-[#3d1389] to-[#2a0b66] text-white shadow-2xl transition-transform duration-200 lg:translate-x-0">
    <div class="flex items-center justify-between px-5 py-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 ring-1 ring-white/25">@include('partials.icon', ['name' => 'book', 'class' => 'h-6 w-6'])</span>
            <span><span class="block text-base font-bold leading-tight">{{ config('lms.company_name') }}</span>
                <span class="block text-[10px] uppercase tracking-widest text-violet-300">Library System</span></span>
        </a>
        <button id="closeSide" class="rounded-lg p-1 hover:bg-white/10 lg:hidden" aria-label="Close menu">@include('partials.icon', ['name' => 'x'])</button>
    </div>

    <nav class="flex-1 space-y-4 overflow-y-auto px-3 pb-4">
        @foreach ($sections as $title => $items)
            @php $items = array_filter($items, fn ($i) => $visible($i[4])); @endphp
            @continue(empty($items))
            <div>
                <div class="mb-1 flex items-center gap-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[.18em] text-violet-300">{{ $title }}</span>
                    <span class="h-px flex-1 bg-white/15"></span>
                </div>
                @foreach ($items as [$label, $route, $pattern, $icon])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" class="group mb-0.5 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-white text-violet-800 shadow-lg' : 'text-violet-100 hover:bg-white/10 hover:text-white' }}">
                        <span class="{{ $active ? 'text-violet-700' : 'text-violet-300 group-hover:text-white' }}">@include('partials.icon', ['name' => $icon])</span>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @endforeach
        <div>
            <div class="mb-1 flex items-center gap-2 px-3"><span class="text-[10px] font-bold uppercase tracking-[.18em] text-violet-300">Public</span><span class="h-px flex-1 bg-white/15"></span></div>
            <a href="{{ route('catalog') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-violet-100 hover:bg-white/10 hover:text-white">
                <span class="text-violet-300 group-hover:text-white">@include('partials.icon', ['name' => 'globe'])</span> E-book catalog</a>
        </div>
    </nav>

    <div class="border-t border-white/10 bg-black/15 p-4">
        <div class="mb-3 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-rose-500 text-base font-bold">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
            <div class="min-w-0"><div class="truncate text-sm font-semibold">{{ $u->name }}</div><div class="text-[11px] uppercase tracking-wider text-violet-300">{{ $u->role }}</div></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold transition hover:bg-rose-500">@include('partials.icon', ['name' => 'logout']) Logout</button>
        </form>
    </div>
</aside>

<div class="lg:pl-64">
    {{-- top bar: search · notifications · user --}}
    <header class="sticky top-0 z-20 flex items-center gap-3 bg-[#f4f1ff]/85 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
        <button id="openSide" class="rounded-xl bg-white p-2 text-violet-700 shadow-sm lg:hidden" aria-label="Open menu">@include('partials.icon', ['name' => 'menu', 'class' => 'h-6 w-6'])</button>

        @if ($u->canDo('books', 'read'))
            <form action="{{ route('admin.books.index') }}" class="relative max-w-xl flex-1">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-violet-400">@include('partials.icon', ['name' => 'search', 'class' => 'h-5 w-5'])</span>
                <input name="q" placeholder="Search books, authors or ISBN…" class="w-full rounded-2xl bg-violet-100/80 py-2.5 pl-12 pr-4 text-sm outline-none ring-violet-300 placeholder:text-violet-400 focus:ring-2">
            </form>
        @endif
        <div class="flex-1"></div>

        <details class="dd relative">
            <summary class="relative rounded-xl bg-white p-2.5 text-violet-700 shadow-sm" aria-label="Notifications">
                @include('partials.icon', ['name' => 'bell', 'class' => 'h-5 w-5'])
                @if ($notifTotal)<span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $notifTotal > 99 ? '99+' : $notifTotal }}</span>@endif
            </summary>
            <div class="absolute right-0 mt-2 w-72 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-violet-100">
                <div class="bg-gradient-to-r from-violet-700 to-purple-600 px-4 py-3 text-sm font-semibold text-white">Notifications</div>
                @forelse ($notes as [$text, $count, $url, $ic, $cls])
                    <a href="{{ $url }}" class="flex items-center gap-3 border-b border-violet-50 px-4 py-3 hover:bg-violet-50">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $cls }}">@include('partials.icon', ['name' => $ic, 'class' => 'h-5 w-5'])</span>
                        <span class="flex-1 text-sm">{{ $text }}</span><span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-bold text-violet-700">{{ $count }}</span>
                    </a>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-slate-500">You're all caught up 🎉</div>
                @endforelse
            </div>
        </details>

        <div class="hidden h-8 w-px bg-violet-200 sm:block"></div>

        <details class="dd relative">
            <summary class="flex items-center gap-2 rounded-xl py-1 pl-1 pr-2 hover:bg-white/70">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-rose-500 text-sm font-bold text-white">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                <span class="hidden text-sm font-semibold sm:block">{{ \Illuminate\Support\Str::limit($u->name, 16) }}</span>
                @include('partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4 text-slate-500'])
            </summary>
            <div class="absolute right-0 mt-2 w-52 overflow-hidden rounded-2xl bg-white py-1 shadow-2xl ring-1 ring-violet-100">
                <div class="border-b border-violet-50 px-4 py-2"><div class="truncate text-sm font-semibold">{{ $u->name }}</div><div class="truncate text-xs text-slate-500">{{ $u->email }}</div></div>
                <a href="{{ route('catalog') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-violet-50">@include('partials.icon', ['name' => 'globe', 'class' => 'h-4 w-4']) E-book catalog</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50">@include('partials.icon', ['name' => 'logout', 'class' => 'h-4 w-4']) Logout</button></form>
            </div>
        </details>
    </header>

    <main class="px-4 pb-8 pt-2 sm:px-6 lg:px-8">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<script>
    const side = document.getElementById('sidebar'), ov = document.getElementById('overlay');
    const openSide = () => { side.classList.remove('-translate-x-full'); ov.classList.remove('hidden'); };
    const closeSide = () => { side.classList.add('-translate-x-full'); ov.classList.add('hidden'); };
    document.getElementById('openSide').onclick = openSide;
    document.getElementById('closeSide').onclick = closeSide;
    ov.onclick = closeSide;
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSide(); document.querySelectorAll('details.dd[open]').forEach(d => d.removeAttribute('open')); } });
    // close dropdowns when clicking elsewhere
    document.addEventListener('click', e => document.querySelectorAll('details.dd[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); }));
</script>
</body>
</html>
