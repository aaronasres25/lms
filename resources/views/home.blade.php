@extends('layouts.app')

@section('hero')
<section class="relative isolate overflow-hidden bg-[#2a0d6b] text-white">
    @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-60 blur-[3px]'])
    <div class="absolute inset-0 bg-gradient-to-r from-[#2a0d6b] via-[#4c1d95]/85 to-[#4c1d95]/20"></div>
    <div class="relative mx-auto grid max-w-7xl items-center gap-6 px-4 py-14 md:grid-cols-2 md:py-24">
        <div>
            <div class="slide">
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">{{ config('lms.company_name') }}<br>Digital Library</h1>
                <p class="mt-4 max-w-md text-lg text-violet-100">Browse official documents, manuals and e-books. Pick up where you left off, on any device.</p>
            </div>
            <div class="slide hidden">
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">{{ number_format($ebookCount) }} e-books<br>at your fingertips</h1>
                <p class="mt-4 max-w-md text-lg text-violet-100">Search by title, author or topic, filter by department and read right in your browser.</p>
            </div>
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="{{ route('catalog') }}" class="flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-500 to-purple-700 px-6 py-3 font-semibold shadow-lg shadow-violet-900/40 transition hover:scale-105">
                    @include('partials.icon', ['name' => 'book', 'class' => 'h-5 w-5']) Open the Catalog @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</a>
                <form action="{{ route('catalog') }}" class="flex items-center overflow-hidden rounded-full border border-white/40 bg-white/10 backdrop-blur">
                    <span class="pl-4">@include('partials.icon', ['name' => 'search', 'class' => 'h-5 w-5'])</span>
                    <input name="search" placeholder="Search…" class="w-32 bg-transparent px-3 py-3 text-white placeholder-white/70 outline-none sm:w-44">
                </form>
            </div>
            <div class="mt-8 flex gap-2"><button class="dot h-2.5 w-6 rounded-full bg-white" aria-label="Slide 1"></button><button class="dot h-2.5 w-2.5 rounded-full bg-white/40" aria-label="Slide 2"></button></div>
        </div>
        <div class="hidden md:block">@include('partials.open-book', ['class' => 'mx-auto w-full max-w-lg drop-shadow-2xl'])</div>
    </div>
</section>
@endsection

@section('content')
{{-- Recently added --}}
<section class="-mt-2">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="border-l-4 border-violet-600 pl-3 text-xl font-bold text-violet-900">Recently Added</h2>
        <a href="{{ route('catalog') }}" class="flex items-center gap-1 rounded-full bg-white px-4 py-1.5 text-sm font-semibold text-violet-700 shadow hover:bg-violet-50">View All @include('partials.icon', ['name' => 'arrow', 'class' => 'h-3.5 w-3.5'])</a>
    </div>
    @php $tiles = ['from-violet-500 to-purple-700', 'from-blue-500 to-violet-700', 'from-amber-400 to-orange-600', 'from-teal-400 to-cyan-600']; @endphp
    @if ($latest->isNotEmpty())
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($latest as $i => $book)
            <a href="{{ route('ebook.open', $book) }}" class="group rounded-2xl bg-white p-4 text-center shadow-md ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-xl">
                <div class="mx-auto flex h-20 w-16 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br {{ $tiles[$i % 4] }} text-white shadow-lg">
                    @if ($book->cover_path)<img src="{{ $book->cover_url }}" class="h-full w-full object-cover" alt="">@else @include('partials.icon', ['name' => 'book', 'class' => 'h-8 w-8'])@endif
                </div>
                <div class="mt-3 line-clamp-2 text-sm font-bold text-violet-950">{{ $book->name }}</div>
                <div class="mt-0.5 truncate text-xs text-slate-500">{{ $book->author->name ?? '' }}</div>
            </a>
        @endforeach
    </div>
    @else
        <p class="rounded-2xl bg-white p-8 text-center text-slate-500 shadow">No e-books have been released yet.</p>
    @endif
</section>

{{-- Why choose --}}
<section id="about" class="mt-14 scroll-mt-24">
    <h2 class="mb-6 text-center text-2xl font-extrabold text-violet-900">Why Choose Our Library?</h2>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Easy Access', 'Find what you need quickly.', 'book', 'bg-violet-100 text-violet-600'], ['Any Device', 'Read on any device, anywhere.', 'cloud', 'bg-sky-100 text-sky-600'], ['Trusted Content', 'Official and reliable documents.', 'shield', 'bg-emerald-100 text-emerald-600'], ['Support', 'Get help when you need it.', 'support', 'bg-amber-100 text-amber-600']] as [$t, $d, $ic, $cl])
            <div class="rounded-2xl bg-white p-5 text-center shadow-md ring-1 ring-violet-100">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $cl }}">@include('partials.icon', ['name' => $ic, 'class' => 'h-7 w-7'])</span>
                <div class="mt-3 font-bold text-violet-950">{{ $t }}</div><div class="mt-1 text-xs text-slate-500">{{ $d }}</div>
            </div>
        @endforeach
    </div>
</section>

{{-- CTA --}}
<section class="relative mt-14 overflow-hidden rounded-3xl bg-gradient-to-r from-[#5b21b6] via-[#7e22ce] to-[#9333ea] p-8 text-white shadow-xl sm:p-10">
    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
    <div class="relative flex flex-col items-center gap-6 md:flex-row">
        @include('partials.open-book', ['class' => 'w-48 shrink-0 drop-shadow-xl'])
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-extrabold sm:text-3xl">Start Exploring Today!</h2>
            <p class="mt-1 text-violet-100">Discover knowledge, learn more, and grow with us.</p>
            <a href="{{ route('catalog') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 font-semibold text-violet-700 shadow hover:scale-105 transition">Browse Catalog @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</a>
        </div>
    </div>
</section>

{{-- Contact / feedback --}}
<section id="contact" class="mx-auto mt-14 grid max-w-4xl scroll-mt-24 gap-6 rounded-3xl bg-white p-6 shadow-lg ring-1 ring-violet-100 md:grid-cols-5 md:p-8">
    <div class="md:col-span-2">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">@include('partials.icon', ['name' => 'chat', 'class' => 'h-6 w-6'])</span>
        <h2 class="mt-3 text-xl font-extrabold text-violet-900">Share your opinion</h2>
        <p class="mt-1 text-sm text-slate-500">Tell us what works and what doesn't. Your feedback goes straight to the library team.</p>
    </div>
    <form method="POST" action="{{ route('feedback.store') }}" class="space-y-3 md:col-span-3">@csrf
        <div class="grid grid-cols-2 gap-3">
            <input name="first_name" placeholder="First name" value="{{ old('first_name') }}" class="rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
            <input name="last_name" placeholder="Last name" value="{{ old('last_name') }}" class="rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
        </div>
        <input type="email" name="email" placeholder="Email (optional)" value="{{ old('email') }}" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
        <textarea name="comment" rows="4" required placeholder="Your feedback" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">{{ old('comment') }}</textarea>
        <button class="rounded-full bg-gradient-to-r from-violet-600 to-purple-700 px-7 py-2.5 font-semibold text-white shadow hover:scale-105 transition">Send feedback</button>
    </form>
</section>
@endsection

@push('scripts')
<script>
    const slides = [...document.querySelectorAll('.slide')], dots = [...document.querySelectorAll('.dot')]; let cur = 0;
    const show = n => { cur = n;
        slides.forEach((s, k) => s.classList.toggle('hidden', k !== n));
        dots.forEach((d, k) => { d.classList.toggle('bg-white', k === n); d.classList.toggle('bg-white/40', k !== n); d.classList.toggle('w-6', k === n); d.classList.toggle('w-2.5', k !== n); }); };
    dots.forEach((d, k) => d.onclick = () => show(k));
    setInterval(() => show((cur + 1) % slides.length), 6000);
</script>
@endpush
