<div class="sm:col-span-2"><label class="block text-xs font-medium text-slate-600">Step title</label>
    <input name="title" value="{{ $title }}" required maxlength="100" placeholder="e.g. Department head review" class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-sm"></div>
<div><label class="block text-xs font-medium text-slate-600">Any user with role</label>
    <select name="role" class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-sm"><option value="">—</option>@foreach ($roles as $r)<option value="{{ $r }}" @selected($role === $r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
<div><label class="block text-xs font-medium text-slate-600">…or this person</label>
    <select name="user_id" class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-sm"><option value="">—</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected($userId == $u->id)>{{ $u->name }} ({{ $u->role }})</option>@endforeach</select></div>
