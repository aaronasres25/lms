@extends('layouts.app')
@section('title', 'Change password')
@section('content')
<div class="mx-auto my-6 max-w-md rounded-3xl bg-white p-8 shadow-xl ring-1 ring-violet-100">
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">@include('partials.icon', ['name' => 'key', 'class' => 'h-6 w-6'])</span>
    <h1 class="mt-3 text-2xl font-extrabold text-violet-900">Change password</h1>
    @if (auth()->user()->must_change_password)
        <p class="mt-1 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">An administrator reset your password. Please choose a new one to continue.</p>
    @endif
    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">@csrf @method('PUT')
        <div><label class="mb-1 block text-sm font-medium">Current (temporary) password</label>
            <input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
        <div><label class="mb-1 block text-sm font-medium">New password (min 8)</label>
            <input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
        <div><label class="mb-1 block text-sm font-medium">Confirm new password</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-2.5 outline-none focus:border-violet-400"></div>
        <button class="w-full rounded-full bg-gradient-to-r from-violet-600 to-purple-700 py-3 font-semibold text-white shadow-lg transition hover:brightness-110">Update password</button>
    </form>
</div>
@endsection
