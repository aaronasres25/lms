<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body class="flex h-screen flex-col bg-slate-800 text-white">
<header class="flex flex-wrap items-center gap-3 bg-[#5b21b6] px-4 py-2 text-sm">
    <a href="{{ route('catalog') }}" class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">← Catalog</a>
    <span class="font-semibold">{{ $book->name }}</span>
    <span class="opacity-60">v{{ $book->version }}</span>
    <div class="ml-auto flex items-center gap-2">
        <button id="prev" class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">‹ Prev</button>
        <span>Page <input id="pageInput" type="number" min="1" value="{{ $currentPage }}" class="w-16 rounded bg-white/15 px-1 text-center"> / <span id="pageCount">…</span></span>
        <button id="next" class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">Next ›</button>
        <button id="zoomOut" class="rounded bg-white/15 px-3 py-1">−</button>
        <span id="zoomLabel" class="w-12 text-center">100%</span>
        <button id="zoomIn" class="rounded bg-white/15 px-3 py-1">+</button>
        @auth
            <a href="{{ route('ebook.download', $book) }}" class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">⬇ Download</a>
        @endauth
    </div>
</header>

@if ($welcome)
    <div id="welcome" class="bg-amber-100 px-4 py-2 text-center text-sm text-amber-900">{{ $welcome }}
        <button onclick="document.getElementById('welcome').remove()" class="ml-3 font-bold">×</button></div>
@endif
@guest <div class="bg-slate-700 px-4 py-1 text-center text-xs">Sign in to save your reading progress.</div> @endguest

<main id="stage" class="flex-1 overflow-auto p-4 text-center"><canvas id="canvas" class="mx-auto shadow-2xl"></canvas></main>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
const url = @json(route('ebook.file', $book));
const canSave = @json(auth()->check());
const saveUrl = @json(route('ebook.progress.save', $book));
const csrf = document.querySelector('meta[name=csrf-token]').content;
let pdf, page = {{ (int) $currentPage }}, scale = 1.2, rendering = false, pending = null, timer = null;
const canvas = document.getElementById('canvas'), ctx = canvas.getContext('2d');
const $ = id => document.getElementById(id);

async function render(n) {
    if (rendering) { pending = n; return; }
    rendering = true;
    page = Math.min(Math.max(1, n), pdf.numPages);
    const p = await pdf.getPage(page);
    const vp = p.getViewport({ scale });
    canvas.width = vp.width; canvas.height = vp.height;
    await p.render({ canvasContext: ctx, viewport: vp }).promise;
    rendering = false;
    $('pageInput').value = page; $('zoomLabel').textContent = Math.round(scale / 1.2 * 100) + '%';
    $('stage').scrollTop = 0;
    if (pending !== null) { const q = pending; pending = null; render(q); }
    queueSave();
}
function queueSave() {
    if (!canSave) return;
    clearTimeout(timer);
    timer = setTimeout(() => fetch(saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ page_number: page, total_pages: pdf.numPages }),
    }), 600);
}
pdfjsLib.getDocument(url).promise.then(d => { pdf = d; $('pageCount').textContent = d.numPages; render(page); })
    .catch(() => { document.getElementById('stage').innerHTML = '<p class="mt-20">Unable to load this document.</p>'; });

$('prev').onclick = () => render(page - 1);
$('next').onclick = () => render(page + 1);
$('zoomIn').onclick = () => { scale = Math.min(scale + 0.2, 4); render(page); };
$('zoomOut').onclick = () => { scale = Math.max(scale - 0.2, 0.4); render(page); };
$('pageInput').onchange = e => render(parseInt(e.target.value) || 1);
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT') return;
    if (e.key === 'ArrowRight' || e.key === 'PageDown') render(page + 1);
    if (e.key === 'ArrowLeft' || e.key === 'PageUp') render(page - 1);
});
</script>
</body>
</html>
