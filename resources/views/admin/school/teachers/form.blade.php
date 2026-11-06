@extends('layouts.admin')
@section('title', $teacher->exists ? 'Edit teacher' : 'New teacher')
@section('content')
@php $in = 'w-full rounded-xl border border-violet-100 bg-white px-3 py-2 text-sm outline-none focus:border-violet-400'; @endphp
@include('partials.temp-password')
<div class="mb-4 flex items-center justify-between"><h1 class="text-2xl font-extrabold">{{ $teacher->exists ? 'Edit: ' . $teacher->name : 'New teacher' }}</h1>
    @if ($teacher->exists && auth()->user()->canDo('teachers', 'delete'))<form method="POST" action="{{ route('admin.school.teachers.destroy', $teacher) }}" onsubmit="return confirm('Remove this teacher?')">@csrf @method('DELETE')<button class="btn btn-outline text-rose-600">Delete</button></form>@endif</div>

<div class="grid gap-5 xl:grid-cols-3">
<form method="POST" action="{{ $teacher->exists ? route('admin.school.teachers.update', $teacher) : route('admin.school.teachers.store') }}" class="card space-y-5 p-6 xl:col-span-2">
    @csrf @if ($teacher->exists) @method('PUT') @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div><label class="block text-sm font-medium">Full name *</label><input name="name" value="{{ old('name', $teacher->name) }}" required class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Employee no. <span class="text-xs text-slate-400">(auto if empty)</span></label><input name="employee_no" value="{{ old('employee_no', $teacher->employee_no) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Phone</label><input name="phone" value="{{ old('phone', $teacher->phone) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Email</label><input type="email" name="email" value="{{ old('email', $teacher->email) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Qualification</label><input name="qualification" value="{{ old('qualification', $teacher->qualification) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Joined on</label><input type="date" name="joined_on" value="{{ old('joined_on', $teacher->joined_on?->toDateString()) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Status</label><select name="status" class="{{ $in }}"><option value="active" @selected(old('status', $teacher->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $teacher->status) === 'inactive')>Inactive</option></select></div>
    </div>
    <fieldset class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4"><legend class="px-2 text-sm font-bold uppercase text-violet-700">Teacher login &amp; library</legend>
        @if ($teacher->user_id)<p class="text-sm text-slate-600">Signs in as <b>{{ $teacher->user->email ?? '' }}</b>. Reset the password from <a class="font-semibold text-violet-700 underline" href="{{ route('admin.access.index', ['user' => $teacher->user_id]) }}">Access rights</a>.</p>
        @else
            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="create_login" value="1" id="mkLogin" @checked(old('create_login')) class="h-4 w-4 accent-violet-700"> Give this teacher a login (also creates a library membership)</label>
            <div id="loginBox" class="mt-3 grid gap-4 md:grid-cols-2 {{ old('create_login') ? '' : 'hidden' }}">
                <div><label class="block text-sm font-medium">Login e-mail *</label><input type="email" name="login_email" value="{{ old('login_email') }}" class="{{ $in }}"></div>
                <div><label class="block text-sm font-medium">Password <span class="text-xs text-slate-400">(empty = generate)</span></label><input name="login_password" autocomplete="off" class="{{ $in }}"></div>
            </div>
            <script>document.getElementById('mkLogin').addEventListener('change', e => document.getElementById('loginBox').classList.toggle('hidden', !e.target.checked));</script>
        @endif
    </fieldset>
    <div class="flex gap-3"><button class="btn btn-primary">Save teacher</button><a href="{{ route('admin.school.teachers.index') }}" class="btn btn-outline">Cancel</a></div>
</form>

@if ($teacher->exists)
<aside class="space-y-5">
    <div class="card p-5"><h3 class="mb-3 font-bold">Class teacher of</h3>
        @forelse ($teacher->classSections as $s)<a class="block border-t py-1.5 text-sm first:border-0 hover:text-violet-700" href="{{ route('admin.school.sections.show', $s) }}">{{ $s->label }}</a>@empty<p class="text-sm text-slate-500">None.</p>@endforelse</div>
    <div class="card p-5"><h3 class="mb-3 font-bold">Teaches</h3>
        @forelse ($assignments as $a)<a class="block border-t py-1.5 text-sm first:border-0 hover:text-violet-700" href="{{ route('admin.school.sections.show', $a->section_id) }}">{{ $a->subject->name }} <span class="text-slate-400">· {{ $a->section->label ?? '' }}</span></a>@empty<p class="text-sm text-slate-500">No subjects assigned.</p>@endforelse</div>
</aside>
@endif
</div>
@endsection
