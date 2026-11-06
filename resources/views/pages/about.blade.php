@extends('layouts.app')
@section('title', 'About')

@section('hero')
<section class="relative isolate overflow-hidden bg-[#2a0d6b] text-white">
    @include('partials.photo-carousel', ['id' => 'aboutPhotos'])
    <div class="absolute inset-0 bg-gradient-to-r from-[#2a0d6b]/90 via-[#3b1485]/55 to-transparent"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 md:py-24">
        <div class="max-w-2xl [text-shadow:0_2px_14px_rgba(0,0,0,.5)]">
            <p class="text-sm font-semibold uppercase tracking-widest text-violet-200">About us</p>
            <h1 class="mt-2 text-4xl font-extrabold leading-tight sm:text-5xl">{{ config('lms.company_name') }} Library</h1>
            <p class="mt-4 text-lg text-violet-50">One trusted place for every official document, manual and e-book in the organisation.</p>
        </div>
    </div>
</section>
@endsection

@section('content')
<section class="grid items-center gap-8 md:grid-cols-2">
    <div>
        <h2 class="border-l-4 border-violet-600 pl-3 text-2xl font-extrabold text-violet-900">Our mission</h2>
        <p class="mt-4 leading-relaxed text-slate-600">We keep the organisation's knowledge organised, reviewed and easy to reach. Every document goes through a
            controlled approval process before it is released, so what you read here is the current, official version.</p>
        <p class="mt-3 leading-relaxed text-slate-600">Staff can borrow physical books, read e-books in the browser, and continue exactly where they stopped,
            on any device.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('catalog') }}" class="rounded-full bg-gradient-to-r from-violet-600 to-purple-700 px-6 py-2.5 font-semibold text-white shadow transition hover:scale-105">Browse the catalog</a>
            <a href="{{ route('contact') }}" class="rounded-full bg-white px-6 py-2.5 font-semibold text-violet-700 shadow ring-1 ring-violet-200 hover:bg-violet-50">Contact us</a>
        </div>
    </div>
    <div class="relative h-64 overflow-hidden rounded-3xl shadow-xl ring-1 ring-violet-100 md:h-80">
        @include('partials.photo-bg', ['i' => 0, 'class' => 'absolute inset-0 bg-cover bg-center'])
        <div class="absolute inset-0 bg-gradient-to-t from-[#2a0d6b]/50 to-transparent"></div>
    </div>
</section>

<section class="mt-12 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($stats as $label => $value)
        <div class="rounded-2xl bg-white p-6 text-center shadow-md ring-1 ring-violet-100">
            <div class="text-4xl font-extrabold text-violet-700">{{ number_format($value) }}</div>
            <div class="mt-1 text-sm font-medium text-slate-500">{{ $label }}</div>
        </div>
    @endforeach
</section>

<section class="mt-14">
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

<section class="mt-14">
    <h2 class="mb-6 text-center text-2xl font-extrabold text-violet-900">How it works</h2>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach ([['1', 'Browse', 'Search the catalog or filter by department and category.'], ['2', 'Read', 'Open any released e-book right in your browser - no download needed.'], ['3', 'Continue', 'Sign in and your page is remembered on every device.']] as [$n, $t, $d])
            <div class="relative rounded-2xl bg-white p-6 shadow-md ring-1 ring-violet-100">
                <span class="absolute -top-4 left-6 flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-700 font-bold text-white shadow-lg">{{ $n }}</span>
                <div class="mt-2 font-bold text-violet-950">{{ $t }}</div><p class="mt-1 text-sm text-slate-500">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</section>
@endsection

@push('scripts')<script>lmsPhotos('aboutPhotos', true, 6000);</script>@endpush
