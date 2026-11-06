@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="mx-auto my-4 grid max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-violet-100 md:grid-cols-2">
    <div class="relative hidden min-h-[26rem] overflow-hidden bg-[#2a0d6b] text-white md:block">
        @include('partials.photo-bg', ['i' => 1, 'class' => 'absolute inset-0 bg-cover bg-center opacity-70'])
        <div class="absolute inset-0 bg-gradient-to-t from-[#2a0d6b] via-[#3b1485]/60 to-[#5b21b6]/30"></div>
        <div class="relative flex h-full flex-col justify-end p-8">
            <h2 class="text-3xl font-extrabold leading-tight">Join the digital library</h2>
            <p class="mt-2 text-violet-100">Read manuals and e-books, and save your progress.</p>
        </div>
    </div>
    <div class="p-8 sm:p-10">
        <h1 class="mb-1 text-2xl font-extrabold text-violet-900">Create a reader account</h1>
        <p class="mb-6 text-sm text-slate-500">It only takes a minute.</p>
        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf
            <div><label class="mb-1 block text-sm font-medium">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
            <div><label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="mb-1 block text-sm font-medium">Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
                <div><label class="mb-1 block text-sm font-medium">Confirm</label>
                    <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
            </div>
            <button class="w-full rounded-full bg-gradient-to-r from-violet-600 to-purple-700 py-3 font-semibold text-white shadow-lg transition hover:brightness-110">Register</button>
        </form>
        <p class="mt-5 text-center text-sm">Already registered? <a class="font-semibold text-violet-700 underline" href="{{ route('login') }}">Sign in</a></p>
    </div>
</div>
@endsection
