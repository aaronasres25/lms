{{-- Poster card. Vars: $book, optional $progressPage + $progressPct (only passed from Continue reading) --}}
@php
    $__g = ['from-violet-600 to-purple-800', 'from-blue-500 to-violet-700', 'from-amber-400 to-orange-600', 'from-teal-400 to-emerald-600', 'from-rose-400 to-pink-600', 'from-sky-400 to-cyan-600'];
    $__grad = $__g[$book->id % count($__g)];
@endphp
<a href="{{ route('ebook.open', $book) }}" class="group block w-40 shrink-0 sm:w-44">
    <div class="relative aspect-[3/4] overflow-hidden rounded-2xl shadow-md ring-1 ring-black/5 transition duration-200 group-hover:-translate-y-1 group-hover:shadow-xl">
        @if ($book->cover_path)
            <img src="{{ $book->cover_url }}" alt="{{ $book->name }}" class="absolute inset-0 h-full w-full object-cover">
        @else
            <div class="absolute inset-0 bg-gradient-to-br {{ $__grad }}"></div>
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute inset-0 flex flex-col items-center justify-center p-3 text-center text-white">
                @include('partials.icon', ['name' => 'book', 'class' => 'h-10 w-10 opacity-80'])
                <span class="mt-2 line-clamp-3 text-sm font-bold leading-tight">{{ $book->name }}</span>
            </div>
        @endif
        @if ($book->is_secure_ebook)
            <span class="absolute right-2 top-2 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950 shadow">🔒 Secure</span>
        @endif
        @if (isset($progressPage, $progressPct))
            <div class="absolute inset-x-0 bottom-0 bg-black/60 px-2.5 py-2">
                <div class="h-1.5 rounded bg-white/30"><div class="h-1.5 rounded bg-amber-400" style="width: {{ $progressPct }}%"></div></div>
                <div class="mt-1 text-[10px] text-white">Page {{ $progressPage }} · {{ $progressPct }}%</div>
            </div>
        @endif
    </div>
    <div class="mt-2 px-1">
        <div class="line-clamp-2 text-sm font-semibold leading-tight text-slate-800 group-hover:text-violet-700">{{ $book->name }}</div>
        <div class="truncate text-xs text-slate-500">{{ $book->author->name ?? '' }}</div>
    </div>
</a>
