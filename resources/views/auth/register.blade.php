@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="mx-auto max-w-md rounded-xl bg-white p-8 shadow">
    <h1 class="mb-6 text-2xl font-bold text-[#5b21b6]">Create a reader account</h1>
    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf
        <div><label class="mb-1 block text-sm font-medium">Name</label>
            <input name="name" value="{{ old('name') }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Password (min 8)</label>
            <input type="password" name="password" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Confirm password</label>
            <input type="password" name="password_confirmation" required class="w-full rounded border px-3 py-2"></div>
        <button class="w-full rounded bg-[#5b21b6] py-2 font-semibold text-white hover:bg-violet-900">Register</button>
    </form>
</div>
@endsection
