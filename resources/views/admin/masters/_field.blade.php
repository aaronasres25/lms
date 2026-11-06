@php
    $__val = $value instanceof \Carbon\CarbonInterface ? $value->toDateString() : $value;
    $__opts = $f['options'] ?? $parents;
@endphp
<div>
    @if ($f['type'] === 'checkbox')
        <label class="flex items-center gap-2 pt-5 text-sm font-medium text-slate-700"><input type="checkbox" name="{{ $f['name'] }}" value="1" @checked($__val) class="h-4 w-4 accent-violet-700"> {{ $f['label'] }}</label>
    @else
        <label class="block text-xs font-medium text-slate-600">{{ $f['label'] }}</label>
        @if ($f['type'] === 'textarea')
            <textarea name="{{ $f['name'] }}" rows="2" class="w-full rounded border px-2 py-1.5">{{ $__val }}</textarea>
        @elseif ($f['type'] === 'select')
            <select name="{{ $f['name'] }}" @required($f['required'] ?? false) class="w-full rounded border px-2 py-1.5"><option value="">— none —</option>
                @foreach ($__opts as $id => $label)@continue(isset($selfId) && ! isset($f['options']) && $id == $selfId)<option value="{{ $id }}" @selected((string) $__val === (string) $id)>{{ $label }}</option>@endforeach</select>
        @elseif ($f['type'] === 'date')
            <input type="date" name="{{ $f['name'] }}" value="{{ $__val }}" @required($f['required'] ?? false) class="w-full rounded border px-2 py-1.5">
        @elseif ($f['type'] === 'number')
            <input type="number" step="any" name="{{ $f['name'] }}" value="{{ $__val }}" @required($f['required'] ?? false) class="w-full rounded border px-2 py-1.5">
        @else
            <input name="{{ $f['name'] }}" value="{{ $__val }}" @required($f['required'] ?? false) class="w-full rounded border px-2 py-1.5">
        @endif
    @endif
</div>
