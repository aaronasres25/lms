@extends('layouts.admin')
@section('title', 'Access rights')
@section('content')
@php
    $actions = ['read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'];
    $roleColors = ['admin' => 'bg-rose-100 text-rose-800', 'manager' => 'bg-violet-100 text-violet-800', 'qa' => 'bg-amber-100 text-amber-800', 'user' => 'bg-sky-100 text-sky-800', 'reader' => 'bg-slate-200 text-slate-700'];
@endphp
<div class="mb-5"><h1 class="text-2xl font-extrabold">Access rights</h1>
    <p class="text-sm text-slate-500">Choose a user, set their role, then grant or revoke Read / Create / Update / Delete on each module. Administrators always have full access.</p></div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- user list --}}
    <div class="space-y-4">
        <div class="card overflow-hidden">
            <form class="border-b p-3"><input name="q" value="{{ request('q') }}" placeholder="Search users…" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></form>
            <ul class="max-h-[28rem] divide-y overflow-y-auto">
                @foreach ($users as $usr)
                    <li><a href="{{ route('admin.access.index', ['user' => $usr->id]) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-violet-50 {{ $selected?->id === $usr->id ? 'bg-violet-50 ring-2 ring-inset ring-violet-300' : '' }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 font-bold text-white">{{ strtoupper(mb_substr($usr->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ $usr->name }} @unless ($usr->active)<span class="text-xs text-red-600">(inactive)</span>@endunless</span>
                            <span class="block truncate text-xs text-slate-500">{{ $usr->email }}</span></span>
                        <span class="flex flex-col items-end gap-1"><span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $roleColors[$usr->role] ?? '' }}">{{ $usr->role }}</span>
                            @if (in_array($usr->id, $customIds))<span class="rounded-full bg-violet-600 px-2 py-0.5 text-[10px] font-bold text-white">custom</span>@endif</span>
                    </a></li>
                @endforeach
            </ul>
        </div>

        <details class="card p-4"><summary class="cursor-pointer font-semibold text-[#5b21b6]">+ Create a user</summary>
            <form method="POST" action="{{ route('admin.access.users.store') }}" class="mt-3 space-y-2 text-sm">@csrf
                <input name="name" placeholder="Full name" required class="w-full rounded-lg border px-3 py-2">
                <input type="email" name="email" placeholder="Email" required class="w-full rounded-lg border px-3 py-2">
                <input type="password" name="password" placeholder="Password (min 8)" required class="w-full rounded-lg border px-3 py-2">
                <select name="role" class="w-full rounded-lg border px-3 py-2">@foreach ($roles as $r)<option value="{{ $r }}" @selected($r === 'user')>{{ ucfirst($r) }}</option>@endforeach</select>
                <button class="btn btn-primary w-full justify-center">Create user</button></form></details>
    </div>

    {{-- matrix --}}
    <div class="lg:col-span-2">
    @if ($selected)
        @php $isAdminUser = $selected->role === 'admin'; $hasCustom = in_array($selected->id, $customIds); @endphp
        <form method="POST" action="{{ route('admin.access.update', $selected) }}" class="card p-5">@csrf @method('PUT')
            <div class="mb-5 flex flex-wrap items-end justify-between gap-4 border-b pb-4">
                <div><h2 class="text-lg font-bold">{{ $selected->name }}</h2><div class="text-sm text-slate-500">{{ $selected->email }}</div>
                    <div class="mt-1 text-xs">@if ($hasCustom)<span class="rounded bg-violet-600 px-2 py-0.5 font-bold text-white">Custom rights</span>@else<span class="rounded bg-slate-200 px-2 py-0.5 font-semibold text-slate-600">Using role defaults</span>@endif</div></div>
                <div class="flex flex-wrap items-end gap-4">
                    <div><label class="block text-xs font-semibold text-slate-500">Role</label>
                        <select name="role" class="rounded-lg border px-3 py-2 text-sm">@foreach ($roles as $r)<option value="{{ $r }}" @selected($selected->role === $r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
                    <label class="flex items-center gap-2 pb-2 text-sm font-medium"><input type="checkbox" name="active" value="1" @checked($selected->active) class="h-4 w-4"> Account active</label>
                </div>
            </div>

            @if ($isAdminUser)
                <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800">Administrators have full access to every module and cannot be restricted. Change the role above to manage individual rights.</div>
            @else
            <input type="hidden" name="save_rights" value="1">
            <div class="tbl-wrap shadow-none ring-1 ring-slate-200"><div class="tbl-scroll"><table class="tbl" id="matrix">
                <thead><tr><th>Module</th>
                    @foreach ($actions as $a => $label)<th class="text-center">{{ $label }}<br><label class="cursor-pointer text-[10px] font-normal normal-case opacity-80"><input type="checkbox" class="col-all align-middle" data-col="{{ $a }}"> all</label></th>@endforeach
                    <th class="text-center">Row</th></tr></thead>
                <tbody>
                @foreach ($resources as $res => $meta)
                    <tr data-row="{{ $res }}"><td class="font-semibold">{{ $meta['label'] }}</td>
                        @foreach ($actions as $a => $label)
                            <td class="text-center">
                                @if (in_array($a, $meta['actions'], true))
                                    <input type="checkbox" name="rights[{{ $res }}][{{ $a }}]" value="1" data-col="{{ $a }}" @checked($selected->canDo($res, $a)) class="h-5 w-5 cursor-pointer rounded accent-violet-700">
                                @else<span class="text-slate-300">—</span>@endif
                            </td>
                        @endforeach
                        <td class="text-center"><button type="button" class="row-all text-xs font-semibold text-violet-700 hover:underline">toggle</button></td></tr>
                @endforeach
                </tbody></table></div></div>
            <p class="mt-3 text-xs text-slate-500">Read is granted automatically when Create, Update or Delete is ticked. Book workflow buttons (submit, approve, release, obsolete) additionally require the QA / Manager role.</p>
            @endif

            <div class="mt-5 flex flex-wrap gap-3">
                <button class="btn btn-primary">Save changes</button>
                @if (! $isAdminUser && $hasCustom)<button type="submit" form="resetForm" class="btn btn-outline" onclick="return confirm('Remove all custom rights and use role defaults?')">Reset to role defaults</button>@endif
            </div>
        </form>
        {{-- admin password reset --}}
        <div class="card mt-6 p-5">
            <h3 class="flex items-center gap-2 text-lg font-bold">@include('partials.icon', ['name' => 'key', 'class' => 'h-5 w-5 text-violet-600']) Reset password</h3>
            <p class="mt-1 text-sm text-slate-500">Set a new password for {{ $selected->name }}, or leave it empty to generate a random one. They are signed out everywhere.</p>

            @if (session('temp_password'))
                <div class="mt-4 rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">New password - shown only once</div>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <code id="tempPw" class="rounded-lg bg-white px-3 py-2 text-lg font-bold tracking-wider text-emerald-900 shadow-sm">{{ session('temp_password') }}</code>
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('tempPw').textContent).then(() => this.textContent = 'Copied ✓')" class="btn btn-outline">Copy</button>
                    </div>
                    <p class="mt-2 text-xs text-emerald-800">Give it to the user securely. They will be asked to choose their own password at the next sign-in.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.access.password', $selected) }}" class="mt-4 grid gap-3 sm:grid-cols-3">@csrf
                <div class="sm:col-span-2"><label class="block text-xs font-medium text-slate-600">New password <span class="text-slate-400">(min 8 - empty = generate)</span></label>
                    <input type="text" name="password" autocomplete="off" minlength="8" placeholder="Leave empty to generate" class="w-full rounded-xl border border-violet-100 px-3 py-2 text-sm outline-none focus:border-violet-400"></div>
                <label class="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" name="force_change" value="1" checked class="h-4 w-4 accent-violet-700"> Require change at next login</label>
                <button class="btn btn-primary sm:col-span-3 justify-center" onclick="return confirm('Reset the password for {{ addslashes($selected->name) }}? They will be signed out.')">Reset password</button>
            </form>
        </div>
        <form id="resetForm" method="POST" action="{{ route('admin.access.reset', $selected) }}" class="hidden">@csrf @method('DELETE')</form>
    @else
        <div class="card p-10 text-center text-slate-500">No users yet.</div>
    @endif
    </div>
</div>

<script>
document.querySelectorAll('.col-all').forEach(h => h.addEventListener('change', () =>
    document.querySelectorAll(`#matrix tbody input[data-col="${h.dataset.col}"]`).forEach(c => c.checked = h.checked)));
document.querySelectorAll('.row-all').forEach(b => b.addEventListener('click', () => {
    const boxes = [...b.closest('tr').querySelectorAll('input[type=checkbox]')];
    const all = boxes.every(c => c.checked); boxes.forEach(c => c.checked = !all);
}));
</script>
@endsection
