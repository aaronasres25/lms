{{-- Vars: $grid [period][day] => slot, $mode = 'section' | 'teacher' --}}
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th>Period</th>@foreach (config('school.days') as $dn)<th>{{ $dn }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach (config('school.periods') as $p => [$from, $to])
        <tr>
            <td class="whitespace-nowrap"><b>{{ $p }}</b><div class="text-xs text-slate-400">{{ $from }} – {{ $to }}</div></td>
            @foreach (config('school.days') as $d => $dn)
                @php $slot = $grid[$p][$d] ?? null; @endphp
                <td class="min-w-[9rem] align-top">
                    @if ($slot)
                        <div class="rounded-xl bg-violet-50 px-3 py-2 ring-1 ring-violet-100">
                            <div class="font-semibold text-violet-900">{{ $slot->subject->name ?? '' }}</div>
                            <div class="text-xs text-slate-500">
                                @if (($mode ?? 'section') === 'teacher') {{ $slot->section->label ?? '' }} @else {{ $slot->teacher->name ?? 'No teacher' }} @endif
                                @if ($slot->room) · {{ $slot->room }}@endif
                            </div>
                        </div>
                    @else<span class="text-slate-300">—</span>@endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody></table></div></div>
