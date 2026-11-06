@extends('layouts.app')

@section('hero')
<section class="relative isolate min-h-[30rem] overflow-hidden bg-[#2a0d6b] text-white md:min-h-[34rem]">
    @include('partials.photo-carousel', ['id' => 'heroPhotos'])
    <div class="absolute inset-0 bg-gradient-to-r from-[#2a0d6b]/90 via-[#3b1485]/55 to-transparent"></div>
    <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#2a0d6b]/60 to-transparent"></div>

    <div class="relative mx-auto flex min-h-[30rem] max-w-7xl items-center px-4 py-14 md:min-h-[34rem]">
        <div class="max-w-xl [text-shadow:0_2px_14px_rgba(0,0,0,.5)]">
            <div class="slide">
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">{{ config('lms.company_name') }}<br>Digital Library</h1>
                <p class="mt-4 text-lg text-violet-50">Browse official documents, manuals and e-books. Pick up where you left off, on any device.</p>
            </div>
            <div class="slide hidden">
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">{{ number_format($ebookCount) }} e-books<br>at your fingertips</h1>
                <p class="mt-4 text-lg text-violet-50">Search by title, author or topic, filter by department and read right in your browser.</p>
            </div>
            <div class="slide hidden">
                <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl">Read anywhere,<br>on any device</h1>
                <p class="mt-4 text-lg text-violet-50">Your page is saved automatically, so you can continue on your phone, tablet or desktop.</p>
            </div>

            <div class="mt-7 flex flex-wrap items-center gap-3 [text-shadow:none]">
                <a href="{{ route('catalog') }}" class="flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-500 to-purple-700 px-6 py-3 font-semibold shadow-lg shadow-violet-900/40 transition hover:scale-105">
                    @include('partials.icon', ['name' => 'book', 'class' => 'h-5 w-5']) Open the Catalog @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</a>
                <form action="{{ route('catalog') }}" class="flex items-center overflow-hidden rounded-full border border-white/50 bg-white/15 backdrop-blur">
                    <span class="pl-4">@include('partials.icon', ['name' => 'search', 'class' => 'h-5 w-5'])</span>
                    <input name="search" placeholder="Search…" class="w-32 bg-transparent px-3 py-3 text-white placeholder-white/80 outline-none sm:w-44">
                </form>
            </div>
            <div class="mt-8 flex gap-2">
                <button class="dot h-2.5 w-6 rounded-full bg-white" aria-label="Slide 1"></button>
                <button class="dot h-2.5 w-2.5 rounded-full bg-white/50" aria-label="Slide 2"></button>
                <button class="dot h-2.5 w-2.5 rounded-full bg-white/50" aria-label="Slide 3"></button>
            </div>
        </div>
    </div>

    <button id="heroPrev" class="absolute left-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-2xl backdrop-blur hover:bg-black/50 md:flex" aria-label="Previous">‹</button>
    <button id="heroNext" class="absolute right-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-2xl backdrop-blur hover:bg-black/50 md:flex" aria-label="Next">›</button>
</section>
@endsection

@section('content')
{{-- Recently added --}}
<section class="-mt-2">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="border-l-4 border-violet-600 pl-3 text-xl font-bold text-violet-900">Recently Added</h2>
        <a href="{{ route('catalog') }}" class="flex items-center gap-1 rounded-full bg-white px-4 py-1.5 text-sm font-semibold text-violet-700 shadow hover:bg-violet-50">View All @include('partials.icon', ['name' => 'arrow', 'class' => 'h-3.5 w-3.5'])</a>
    </div>
    @php $tiles = ['from-violet-500 to-purple-700', 'from-blue-500 to-indigo-700', 'from-amber-400 to-orange-600', 'from-teal-400 to-cyan-600']; @endphp
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

{{-- Explore tiles (About / Contact / Catalog are separate pages) --}}
<section class="mt-12 grid gap-4 md:grid-cols-3">
    @foreach ([
        ['About us', 'Who we are and why our library is worth your time.', route('about'), 'book', 'bg-violet-100 text-violet-600'],
        ['E-book catalog', 'Browse every released document by department or category.', route('catalog'), 'search', 'bg-sky-100 text-sky-600'],
        ['Contact us', 'Questions or feedback? Send us a message.', route('contact'), 'chat', 'bg-emerald-100 text-emerald-600'],
    ] as [$t, $d, $url, $ic, $cl])
        <a href="{{ $url }}" class="group flex items-start gap-4 rounded-2xl bg-white p-5 shadow-md ring-1 ring-violet-100 transition hover:-translate-y-1 hover:shadow-xl">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $cl }}">@include('partials.icon', ['name' => $ic, 'class' => 'h-6 w-6'])</span>
            <span><span class="block font-bold text-violet-950">{{ $t }}</span><span class="mt-1 block text-sm text-slate-500">{{ $d }}</span>
                <span class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-violet-700">Open @include('partials.icon', ['name' => 'arrow', 'class' => 'h-3.5 w-3.5 transition group-hover:translate-x-1'])</span></span>
        </a>
    @endforeach
</section>

{{-- CTA --}}
<section class="relative mt-12 overflow-hidden rounded-3xl bg-gradient-to-r from-[#5b21b6] via-[#7e22ce] to-[#9333ea] p-8 text-white shadow-xl sm:p-10">
    <div class="hidden md:block">@include('partials.photo-bg', ['i' => 1, 'class' => 'absolute inset-y-0 right-0 w-3/5 bg-cover bg-center opacity-50', 'style' => '-webkit-mask-image: linear-gradient(to left, #000 35%, transparent); mask-image: linear-gradient(to left, #000 35%, transparent)'])</div>
    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
    <div class="relative flex flex-col items-center gap-6 md:flex-row">
        @include('partials.open-book', ['class' => 'w-48 shrink-0 drop-shadow-xl'])
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-extrabold sm:text-3xl">Start Exploring Today!</h2>
            <p class="mt-1 text-violet-100">Discover knowledge, learn more, and grow with us.</p>
            <a href="{{ route('catalog') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 font-semibold text-violet-700 shadow transition hover:scale-105">Browse Catalog @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // hero carousel: photos + text slides + dots + arrows stay in sync
    const photos = lmsPhotos('heroPhotos');
    const slides = [...document.querySelectorAll('.slide')], dots = [...document.querySelectorAll('.dot')];
    let cur = 0, timer;
    const go = n => { cur = (n + slides.length) % slides.length; photos.show(cur);
        slides.forEach((s, k) => s.classList.toggle('hidden', k !== cur));
        dots.forEach((d, k) => { d.classList.toggle('bg-white', k === cur); d.classList.toggle('bg-white/50', k !== cur); d.classList.toggle('w-6', k === cur); d.classList.toggle('w-2.5', k !== cur); }); };
    const restart = () => { clearInterval(timer); timer = setInterval(() => go(cur + 1), 6000); };
    dots.forEach((d, k) => d.onclick = () => { go(k); restart(); });
    document.getElementById('heroPrev').onclick = () => { go(cur - 1); restart(); };
    document.getElementById('heroNext').onclick = () => { go(cur + 1); restart(); };
    restart();
</script>
@endpush
