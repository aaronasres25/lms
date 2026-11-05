@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div class="mx-auto max-w-md rounded-xl bg-white p-8 shadow">
    <h1 class="mb-6 text-2xl font-bold text-[#5b21b6]">Sign in</h1>
    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf
        <div><label class="mb-1 block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" name="password" required class="w-full rounded border px-3 py-2"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="w-full rounded bg-[#5b21b6] py-2 font-semibold text-white hover:bg-violet-900">Sign in</button>
    </form>
    <p class="mt-4 text-center text-sm">No account? <a class="text-violet-700 underline" href="{{ route('register') }}">Register</a></p>
</div>
@endsection
