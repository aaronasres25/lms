{{-- Crossfading photo background built from EVERY picture in public/images (see App\Support\SitePhotos).
     Vars: $id (unique DOM id). Control with lmsPhotos('<id>', autoplay?) --}}
@php $__photos = \App\Support\SitePhotos::all(); @endphp
<div id="{{ $id }}" class="absolute inset-0 overflow-hidden bg-[#2a0d6b]" aria-hidden="true">
    @forelse ($__photos as $i => $__url)
        <img src="{{ $__url }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
             class="pc-slide absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}"
             style="filter: brightness(1.15) saturate(1.1)">
    @empty
        @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-60 blur-[3px]'])
    @endforelse
</div>
@once
<script>
    function lmsPhotos(id, auto = false, ms = 6000) {
        const slides = [...document.getElementById(id).querySelectorAll('.pc-slide')]; let cur = 0, timer = null;
        const show = n => {
            if (!slides.length) return 0;
            cur = ((n % slides.length) + slides.length) % slides.length;
            slides.forEach((s, k) => { s.classList.toggle('opacity-100', k === cur); s.classList.toggle('opacity-0', k !== cur); });
            return cur;
        };
        const start = () => { if (auto && slides.length > 1) timer = setInterval(() => show(cur + 1), ms); };
        start();
        return { show, count: slides.length, current: () => cur };
    }
</script>
@endonce
