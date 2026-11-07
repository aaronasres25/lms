<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') · {{ config('lms.company_name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.ui-styles')
    @include('partials.sidebar-collapse')
</head>
<body class="bg-[#f4f1ff] text-slate-800">
@php
    $u = auth()->user();
    $isTeacher = $u->isTeacher();
    $np = $isTeacher ? 'teacher.notifications' : 'student.notifications';
    $standalone = $isTeacher
        ? [['Dashboard', 'teacher.dashboard', 'teacher.dashboard', 'home'], ['Notifications', 'teacher.notifications.index', 'teacher.notifications.*', 'bell']]
        : [['Dashboard', 'student.dashboard', 'student.dashboard', 'home'], ['Notifications', 'student.notifications.index', 'student.notifications.*', 'bell']];
    $groupIcons = ['Teaching' => 'clipboard', 'My school' => 'building', 'Library' => 'book'];
    $sections = $isTeacher ? [
        'Teaching' => [['My timetable', 'teacher.timetable', 'teacher.timetable', 'clock'], ['Attendance', 'teacher.attendance.index', 'teacher.attendance.*', 'clipboard'], ['Marks', 'teacher.marks.index', 'teacher.marks.*', 'star']],
        'Library' => [['E-book catalog', 'catalog', 'catalog', 'globe']],
    ] : [
        'My school' => [['Timetable', 'student.timetable', 'student.timetable', 'clock'], ['Attendance', 'student.attendance', 'student.attendance', 'clipboard'], ['Results', 'student.results', 'student.results*', 'star'], ['Fees', 'student.fees', 'student.fees', 'chart']],
        'Library' => [['My library', 'student.library', 'student.library*', 'book'], ['E-book catalog', 'catalog', 'catalog', 'globe']],
    ];
    $unread = $u->notifications()->whereNull('read_at')->count();
    $recent = $u->notifications()->latest()->limit(6)->get();
    $noteColors = ['violet' => 'bg-violet-100 text-violet-600', 'amber' => 'bg-amber-100 text-amber-600', 'green' => 'bg-emerald-100 text-emerald-600', 'red' => 'bg-rose-100 text-rose-600', 'orange' => 'bg-orange-100 text-orange-600', 'sky' => 'bg-sky-100 text-sky-600'];
@endphp

<div id="overlay" class="fixed inset-0 z-30 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-[#4a1a9c] via-[#3d1389] to-[#2a0b66] text-white shadow-2xl transition-transform duration-200 lg:translate-x-0">
    <div class="flex items-center justify-between px-5 py-5">
        <a href="{{ $u->homeUrl() }}" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 ring-1 ring-white/25">@include('partials.icon', ['name' => 'book', 'class' => 'h-6 w-6'])</span>
            <span class="sb-label"><span class="block text-base font-bold leading-tight">{{ config('lms.company_name') }}</span>
                <span class="block text-[10px] uppercase tracking-widest text-violet-300">{{ $isTeacher ? 'Teacher portal' : 'Student portal' }}</span></span>
        </a>
        <button id="closeSide" class="rounded-lg p-1 hover:bg-white/10 lg:hidden" aria-label="Close menu">@include('partials.icon', ['name' => 'x'])</button>
    </div>
    @php
        $activeGroup = collect($sections)->search(fn ($items) => collect($items)->contains(fn ($i) => request()->routeIs($i[2])));
        if ($activeGroup === false) $activeGroup = null;
    @endphp
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4 pt-5">
        <div class="space-y-0.5 pb-2">
            @foreach ($standalone as [$label, $route, $pattern, $icon])
                @php $active = request()->routeIs($pattern); @endphp
                <a href="{{ route($route) }}" title="{{ $label }}" class="group mb-0.5 flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium transition {{ $active ? 'bg-white text-violet-800 shadow-lg' : 'text-violet-100 hover:bg-white/10 hover:text-white' }}">
                    <span class="{{ $active ? 'text-violet-700' : 'text-violet-300 group-hover:text-white' }}">@include('partials.icon', ['name' => $icon, 'class' => 'h-[22px] w-[22px]'])</span><span class="sb-label">{{ $label }}</span>
                    @if ($label === 'Notifications' && $unread)<span class="sb-label ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $unread }}</span>@endif
                </a>
            @endforeach
        </div>

        @foreach ($sections as $title => $items)
            <div class="sb-group {{ $title === $activeGroup ? 'is-open' : '' }}">
                <button type="button" class="sb-toggle flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-white/5" aria-label="{{ $title }}">
                    <span class="sb-label text-violet-300">@include('partials.icon', ['name' => $groupIcons[$title] ?? 'tag', 'class' => 'h-[22px] w-[22px]'])</span>
                    <span class="sb-label flex-1 text-[13px] font-bold uppercase tracking-wider text-violet-100">{{ $title }}</span>

                    <span class="sb-label sb-chev text-violet-300">@include('partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4'])</span>
                </button>
                <div class="sb-group-body"><div class="sb-nest">
                    @foreach ($items as [$label, $route, $pattern, $icon])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}" title="{{ $label }}" class="group mb-0.5 flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium transition {{ $active ? 'bg-white text-violet-800 shadow-lg' : 'text-violet-100 hover:bg-white/10 hover:text-white' }}">
                    <span class="{{ $active ? 'text-violet-700' : 'text-violet-300 group-hover:text-white' }}">@include('partials.icon', ['name' => $icon, 'class' => 'h-[22px] w-[22px]'])</span><span class="sb-label">{{ $label }}</span>
                        </a>
                    @endforeach
                </div></div>
            </div>
        @endforeach
    </nav>
    <div class="border-t border-white/10 bg-black/15 p-4">
        <div class="mb-3 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-rose-500 text-base font-bold">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
            <div class="sb-label min-w-0"><div class="truncate text-sm font-semibold">{{ $u->name }}</div><div class="text-[11px] uppercase tracking-wider text-violet-300">{{ $u->role }}</div></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold transition hover:bg-rose-500">@include('partials.icon', ['name' => 'logout']) <span class="sb-label">Logout</span></button></form>
    </div>
</aside>

<div id="mainWrap" class="lg:pl-64">
    <header class="sticky top-0 z-20 flex items-center gap-3 bg-[#f4f1ff]/85 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
        <button id="openSide" class="rounded-xl bg-white p-2 text-violet-700 shadow-sm lg:hidden" aria-label="Open menu">@include('partials.icon', ['name' => 'menu', 'class' => 'h-6 w-6'])</button>
        <button id="collapseBtn" type="button" class="hidden rounded-xl bg-white p-2 text-violet-700 shadow-sm lg:inline-flex" title="Collapse / expand the menu" aria-label="Toggle sidebar">@include('partials.icon', ['name' => 'menu', 'class' => 'h-6 w-6'])</button>
        <div class="flex-1"></div>

        <details class="dd relative">
            <summary class="relative rounded-xl bg-white p-2.5 text-violet-700 shadow-sm" aria-label="Notifications">
                @include('partials.icon', ['name' => 'bell', 'class' => 'h-5 w-5'])
                @if ($unread)<span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </summary>
            <div class="absolute right-0 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-violet-100">
                <div class="flex items-center justify-between bg-gradient-to-r from-violet-700 to-purple-600 px-4 py-3 text-sm font-semibold text-white"><span>Notifications</span>
                    @if ($unread)<form method="POST" action="{{ route($np . '.read-all') }}">@csrf<button class="text-xs underline">Mark all read</button></form>@endif</div>
                <div class="max-h-96 overflow-y-auto">
                @forelse ($recent as $n)
                    @php $d = $n->data; $isNew = $n->read_at === null; @endphp
                    <a href="{{ route($np . '.open', $n->id) }}" class="flex items-start gap-3 border-b border-violet-50 px-4 py-3 hover:bg-violet-50 {{ $isNew ? 'bg-violet-50/60' : 'opacity-70' }}">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $noteColors[$d['color'] ?? 'violet'] ?? $noteColors['violet'] }}">@include('partials.icon', ['name' => $d['icon'] ?? 'bell', 'class' => 'h-5 w-5'])</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm {{ $isNew ? 'font-bold' : 'font-medium' }}">{{ $d['title'] ?? 'Notification' }}</span><span class="line-clamp-2 text-xs text-slate-500">{{ $d['body'] ?? '' }}</span></span>
                        @if ($isNew)<span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-violet-600"></span>@endif
                    </a>
                @empty<div class="px-4 py-8 text-center text-sm text-slate-500">You're all caught up 🎉</div>@endforelse
                </div>
                <a href="{{ route($np . '.index') }}" class="block bg-slate-50 px-4 py-2.5 text-center text-sm font-semibold text-violet-700 hover:bg-violet-50">View all</a>
            </div>
        </details>

        <details class="dd relative">
            <summary class="flex items-center gap-2 rounded-xl py-1 pl-1 pr-2 hover:bg-white/70">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-rose-500 text-sm font-bold text-white">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                <span class="hidden text-sm font-semibold sm:block">{{ \Illuminate\Support\Str::limit($u->name, 16) }}</span>
                @include('partials.icon', ['name' => 'chevron', 'class' => 'h-4 w-4 text-slate-500'])
            </summary>
            <div class="absolute right-0 mt-2 w-52 overflow-hidden rounded-2xl bg-white py-1 shadow-2xl ring-1 ring-violet-100">
                <a href="{{ route('password.change') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-violet-50">@include('partials.icon', ['name' => 'key', 'class' => 'h-4 w-4']) Change password</a>
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
    document.getElementById('openSide').onclick = openSide; document.getElementById('closeSide').onclick = closeSide; ov.onclick = closeSide;
    document.addEventListener('click', e => document.querySelectorAll('details.dd[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); }));
</script>
</body>
</html>
