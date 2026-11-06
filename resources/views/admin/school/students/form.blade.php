@extends('layouts.admin')
@section('title', $student->exists ? 'Edit student' : 'New student')
@section('content')
<h1 class="mb-4 text-2xl font-extrabold">{{ $student->exists ? 'Edit: ' . $student->name : 'New student' }}</h1>
@php $in = 'w-full rounded-xl border border-violet-100 bg-white px-3 py-2 text-sm outline-none focus:border-violet-400'; @endphp
<form method="POST" action="{{ $student->exists ? route('admin.school.students.update', $student) : route('admin.school.students.store') }}" class="card max-w-4xl space-y-6 p-6">
    @csrf @if ($student->exists) @method('PUT') @endif
    <div class="grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2"><label class="block text-sm font-medium">Full name *</label><input name="name" value="{{ old('name', $student->name) }}" required class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Admission no. <span class="text-xs text-slate-400">(auto if empty)</span></label><input name="admission_no" value="{{ old('admission_no', $student->admission_no) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Gender</label><select name="gender" class="{{ $in }}"><option value="">—</option><option value="male" @selected(old('gender', $student->gender) === 'male')>Male</option><option value="female" @selected(old('gender', $student->gender) === 'female')>Female</option></select></div>
        <div><label class="block text-sm font-medium">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->toDateString()) }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Admitted on</label><input type="date" name="admitted_on" value="{{ old('admitted_on', $student->admitted_on?->toDateString()) }}" class="{{ $in }}"></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium">Class</label><select name="section_id" class="{{ $in }}"><option value="">— not assigned —</option>@foreach ($sections as $id => $n)<option value="{{ $id }}" @selected(old('section_id', $student->section_id) == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Status</label><select name="status" class="{{ $in }}">@foreach (config('school.student_statuses') as $k => $l)<option value="{{ $k }}" @selected(old('status', $student->status) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Phone</label><input name="phone" value="{{ old('phone', $student->phone) }}" class="{{ $in }}"></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium">Address</label><input name="address" value="{{ old('address', $student->address) }}" class="{{ $in }}"></div>
    </div>

    <fieldset class="rounded-2xl border border-violet-100 p-4"><legend class="px-2 text-sm font-bold uppercase text-violet-700">Guardian</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="block text-sm font-medium">Name</label><input name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" class="{{ $in }}"></div>
            <div><label class="block text-sm font-medium">Phone</label><input name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}" class="{{ $in }}"></div>
            <div><label class="block text-sm font-medium">Email</label><input type="email" name="guardian_email" value="{{ old('guardian_email', $student->guardian_email) }}" class="{{ $in }}"></div>
        </div>
    </fieldset>

    <fieldset class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4"><legend class="px-2 text-sm font-bold uppercase text-violet-700">Student login &amp; library</legend>
        @if ($student->user_id)
            <p class="text-sm text-slate-600">This student can sign in as <b>{{ $student->user->email ?? '' }}</b> and borrow library books. Reset the password from <a class="font-semibold text-violet-700 underline" href="{{ route('admin.access.index', ['user' => $student->user_id]) }}">Access rights</a>.</p>
        @else
            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="create_login" value="1" id="mkLogin" @checked(old('create_login')) class="h-4 w-4 accent-violet-700"> Give this student a login (also creates their library membership)</label>
            <div id="loginBox" class="mt-3 grid gap-4 md:grid-cols-2 {{ old('create_login') ? '' : 'hidden' }}">
                <div><label class="block text-sm font-medium">Login e-mail *</label><input type="email" name="login_email" value="{{ old('login_email') }}" class="{{ $in }}"></div>
                <div><label class="block text-sm font-medium">Password <span class="text-xs text-slate-400">(empty = generate)</span></label><input name="login_password" autocomplete="off" class="{{ $in }}"></div>
            </div>
            <script>document.getElementById('mkLogin').addEventListener('change', e => document.getElementById('loginBox').classList.toggle('hidden', !e.target.checked));</script>
        @endif
    </fieldset>

    <div class="flex gap-3"><button class="btn btn-primary">Save student</button><a href="{{ $student->exists ? route('admin.school.students.show', $student) : route('admin.school.students.index') }}" class="btn btn-outline">Cancel</a></div>
</form>
@endsection
