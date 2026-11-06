@extends('layouts.app')
@section('title', 'Contact')

@section('hero')
<section class="relative isolate overflow-hidden bg-[#2a0d6b] text-white">
    @include('partials.photo-carousel', ['id' => 'contactPhotos'])
    <div class="absolute inset-0 bg-gradient-to-r from-[#2a0d6b]/90 via-[#3b1485]/55 to-transparent"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 md:py-20">
        <div class="max-w-2xl [text-shadow:0_2px_14px_rgba(0,0,0,.5)]">
            <p class="text-sm font-semibold uppercase tracking-widest text-violet-200">Contact</p>
            <h1 class="mt-2 text-4xl font-extrabold leading-tight sm:text-5xl">We'd love to hear from you</h1>
            <p class="mt-4 text-lg text-violet-50">Questions, suggestions or a document you can't find - send us a message.</p>
        </div>
    </div>
</section>
@endsection

@section('content')
<section class="grid gap-6 md:grid-cols-3">
    @foreach ([['Email', $contact['email'], 'mail', 'bg-violet-100 text-violet-600'], ['Phone', $contact['phone'], 'phone', 'bg-emerald-100 text-emerald-600'], ['Visit us', $contact['address'], 'pin', 'bg-amber-100 text-amber-600']] as [$t, $v, $ic, $cl])
        <div class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-md ring-1 ring-violet-100">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $cl }}">@include('partials.icon', ['name' => $ic, 'class' => 'h-6 w-6'])</span>
            <div class="min-w-0"><div class="font-bold text-violet-950">{{ $t }}</div><div class="mt-1 break-words text-sm text-slate-600">{{ $v }}</div></div>
        </div>
    @endforeach
</section>

<section class="mx-auto mt-10 grid max-w-5xl gap-6 rounded-3xl bg-white p-6 shadow-lg ring-1 ring-violet-100 md:grid-cols-5 md:p-8">
    <div class="md:col-span-2">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">@include('partials.icon', ['name' => 'chat', 'class' => 'h-6 w-6'])</span>
        <h2 class="mt-3 text-xl font-extrabold text-violet-900">Share your opinion</h2>
        <p class="mt-1 text-sm text-slate-500">Tell us what works and what doesn't. Your message goes straight to the library team.</p>
        <div class="mt-5 rounded-2xl bg-violet-50 p-4 text-sm text-violet-900">
            <div class="flex items-center gap-2 font-semibold">@include('partials.icon', ['name' => 'clock', 'class' => 'h-4 w-4']) Opening hours</div>
            <div class="mt-1 text-slate-600">{{ $contact['hours'] }}</div>
        </div>
    </div>
    <form method="POST" action="{{ route('feedback.store') }}" class="space-y-3 md:col-span-3">@csrf
        <div class="grid grid-cols-2 gap-3">
            <input name="first_name" placeholder="First name" value="{{ old('first_name') }}" class="rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
            <input name="last_name" placeholder="Last name" value="{{ old('last_name') }}" class="rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
        </div>
        <input type="email" name="email" placeholder="Email (optional)" value="{{ old('email') }}" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">
        <textarea name="comment" rows="5" required placeholder="Your message" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400">{{ old('comment') }}</textarea>
        <button class="rounded-full bg-gradient-to-r from-violet-600 to-purple-700 px-7 py-2.5 font-semibold text-white shadow transition hover:scale-105">Send message</button>
    </form>
</section>
@endsection

@push('scripts')<script>lmsPhotos('contactPhotos', true, 6000);</script>@endpush
