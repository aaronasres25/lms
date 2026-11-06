<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('lms.company_name') . ' Library')</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%235b21b6'/%3E%3Cpath d='M12 7v10m0-10C10.8 6.3 9.3 6 8 6S5.2 6.3 5 7v10c.2-.7 1.7-1 3-1s2.8.3 4 1m0-10c1.2-.7 2.7-1 4-1s2.8.3 3 1v10c-.2-.7-1.7-1-3-1s-2.8.3-4 1' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { scrollbar-width: none; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-[#f6f2ff] text-slate-800">
<header class="sticky top-0 z-40 bg-gradient-to-r from-[#4c1d95] via-[#5b21b6] to-[#7e22ce] text-white shadow-lg">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-3">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-violet-700 shadow ring-2 ring-white/40">@include('partials.icon', ['name' => 'book', 'class' => 'h-6 w-6'])</span>
            <span class="text-base font-bold leading-tight sm:text-lg">{{ config('lms.company_name') }} Library</span>
        </a>
        <button id="navBtn" class="rounded-lg p-2 hover:bg-white/10 md:hidden" aria-label="Menu">@include('partials.icon', ['name' => 'menu', 'class' => 'h-6 w-6'])</button>
        <nav id="nav" class="hidden w-full flex-col gap-1 pt-2 text-sm font-medium md:flex md:w-auto md:flex-row md:items-center md:pt-0">
            @php $pill = fn ($on) => 'flex items-center gap-1.5 rounded-full px-4 py-2 transition ' . ($on ? 'bg-white/20 ring-1 ring-white/30' : 'hover:bg-white/10'); @endphp
            <a href="{{ route('home') }}" class="{{ $pill(request()->routeIs('home')) }}">@include('partials.icon', ['name' => 'home', 'class' => 'h-4 w-4']) Home</a>
            <a href="{{ route('catalog') }}" class="{{ $pill(request()->routeIs('catalog')) }}">Catalog</a>
            <a href="{{ route('about') }}" class="{{ $pill(request()->routeIs('about')) }}">About</a>
            <a href="{{ route('contact') }}" class="{{ $pill(request()->routeIs('contact')) }}">Contact</a>
            @auth
                @if (auth()->user()->isStaff())<a href="{{ route('admin.dashboard') }}" class="{{ $pill(false) }}">Back-office</a>
                @elseif (auth()->user()->isTeacher() || auth()->user()->isStudent())<a href="{{ auth()->user()->homeUrl() }}" class="{{ $pill(false) }}">My portal</a>@endif
                <a href="{{ route('password.change') }}" class="{{ $pill(request()->routeIs('password.change')) }}" title="Change my password">@include('partials.icon', ['name' => 'key', 'class' => 'h-4 w-4'])<span class="md:hidden">Password</span></a>
                <form method="POST" action="{{ route('logout') }}" class="md:ml-1">@csrf
                    <button class="flex w-full items-center gap-2 rounded-full border border-white/40 bg-white/10 px-4 py-2 hover:bg-white/25">
                        @include('partials.icon', ['name' => 'user', 'class' => 'h-4 w-4']) {{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }} · Logout</button></form>
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-full border border-white/40 bg-white/10 px-5 py-2 hover:bg-white/25 md:ml-1">@include('partials.icon', ['name' => 'user', 'class' => 'h-4 w-4']) Login</a>
            @endauth
        </nav>
    </div>
</header>

@yield('hero')

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="bg-gradient-to-r from-[#2a0d6b] to-[#4c1d95] py-6 text-center text-sm text-violet-200">
    &copy; {{ date('Y') }} {{ config('lms.company_name') }}. All rights reserved.
</footer>
<script>
    document.getElementById('navBtn').onclick = () => { const n = document.getElementById('nav'); n.classList.toggle('hidden'); n.classList.toggle('flex'); };
</script>
@stack('scripts')
</body>
</html>
