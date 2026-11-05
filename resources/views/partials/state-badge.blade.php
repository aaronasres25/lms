@php
    $c = ['draft' => 'bg-sky-100 text-sky-800', 'review' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-red-100 text-red-800',
          'released' => 'bg-green-100 text-green-800', 'obsolete' => 'bg-slate-200 text-slate-600',
          'issued' => 'bg-amber-100 text-amber-800', 'returned' => 'bg-green-100 text-green-800', 'lost' => 'bg-red-100 text-red-800'][$state] ?? 'bg-slate-100';
@endphp
<span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $c }}">{{ ucfirst($state) }}</span>
