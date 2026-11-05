<div>
    <label class="block text-xs font-medium text-slate-600">{{ $f['label'] }}</label>
    @if ($f['type'] === 'textarea')
        <textarea name="{{ $f['name'] }}" rows="2" class="w-full rounded border px-2 py-1.5">{{ $value }}</textarea>
    @elseif ($f['type'] === 'select')
        <select name="{{ $f['name'] }}" class="w-full rounded border px-2 py-1.5"><option value="">— none —</option>
            @foreach ($parents as $id => $label)@continue(isset($selfId) && $id == $selfId)<option value="{{ $id }}" @selected($value == $id)>{{ $label }}</option>@endforeach</select>
    @else
        <input name="{{ $f['name'] }}" value="{{ $value }}" @required($f['required'] ?? false) class="w-full rounded border px-2 py-1.5">
    @endif
</div>
