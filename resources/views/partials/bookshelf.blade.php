{{-- Blurry coloured book spines used as a "library" backdrop (no image files needed) --}}
@php
    mt_srand(11);
    $__pal = ['#7c3aed', '#4c1d95', '#2563eb', '#f59e0b', '#db2777', '#0ea5e9', '#10b981', '#9333ea', '#f97316', '#6d28d9'];
@endphp
<svg class="{{ $class ?? '' }}" viewBox="0 0 800 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    @for ($row = 0; $row < 3; $row++)
        @php $x = 0; $baseY = 100 + $row * 100; @endphp
        @while ($x < 800)
            @php $w = mt_rand(14, 30); $h = mt_rand(52, 86); $c = $__pal[mt_rand(0, 9)]; $o = mt_rand(55, 95) / 100; @endphp
            <rect x="{{ $x }}" y="{{ $baseY - $h }}" width="{{ $w }}" height="{{ $h }}" rx="2" fill="{{ $c }}" opacity="{{ $o }}"/>
            @php $x += $w + mt_rand(1, 4); @endphp
        @endwhile
        <rect x="0" y="{{ $baseY }}" width="800" height="6" fill="#1e0a4d" opacity=".7"/>
    @endfor
</svg>
