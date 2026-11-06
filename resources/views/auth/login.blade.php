@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div class="mx-auto my-4 grid max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-violet-100 md:grid-cols-2">
    <div class="relative hidden min-h-[26rem] overflow-hidden bg-[#2a0d6b] text-white md:block">
        @include('partials.photo-bg', ['i' => 0, 'class' => 'absolute inset-0 bg-cover bg-center opacity-70'])
        <div class="absolute inset-0 bg-gradient-to-t from-[#2a0d6b] via-[#3b1485]/60 to-[#5b21b6]/30"></div>
        <div class="relative flex h-full flex-col justify-end p-8">
            <h2 class="text-3xl font-extrabold leading-tight">Welcome back to the library</h2>
            <p class="mt-2 text-violet-100">Pick up where you left off, on any device.</p>
        </div>
    </div>
    <div class="p-8 sm:p-10">
        <h1 class="mb-1 text-2xl font-extrabold text-violet-900">Sign in</h1>
        <p class="mb-6 text-sm text-slate-500">Use your library account to continue.</p>
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div><label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
            <div><label class="mb-1 block text-sm font-medium">Password</label>
                <input type="password" name="password" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="accent-violet-700"> Remember me</label>
            <button class="w-full rounded-full bg-gradient-to-r from-violet-600 to-purple-700 py-3 font-semibold text-white shadow-lg transition hover:brightness-110">Sign in</button>
        </form>
        <p class="mt-5 text-center text-sm">No account? <a class="font-semibold text-violet-700 underline" href="{{ route('register') }}">Register</a></p>
    </div>
</div>
@endsection
