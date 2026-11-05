@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
@php
    $u = auth()->user();
    // key, label, icon, icon-tile gradient, spark colour, url, permission
    $cards = [
        ['books',    'Total Books',    'book',      'from-violet-500 to-purple-700', '#7c3aed', route('admin.books.index'),                      'books'],
        ['members',  'Total Members',  'users',     'from-sky-400 to-blue-600',      '#3b82f6', route('admin.members.index'),                    'members'],
        ['loans',    'Total Loans',    'clipboard', 'from-emerald-400 to-green-600', '#10b981', route('admin.loans.index'),                      'loans'],
        ['overdue',  'Overdue Loans',  'clock',     'from-amber-400 to-orange-500',  '#f59e0b', route('admin.loans.index', ['overdue' => 1]),    'loans'],
        ['feedback', 'Feedbacks',      'star',      'from-teal-400 to-emerald-600',  '#14b8a6', route('admin.feedback.index'),                   'feedback'],
    ];
@endphp

<div id="dashArea" class="space-y-5">

    {{-- ===== welcome banner ===== --}}
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#e4dbff] via-[#d9ccff] to-[#c2aeff] px-6 py-8 shadow-sm sm:px-10 sm:py-10">
        <div class="absolute inset-y-0 right-0 hidden w-3/5 sm:block">
            @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-60 blur-[2px]'])
            <div class="absolute inset-0 bg-gradient-to-r from-[#d9ccff] via-[#d9ccff]/70 to-transparent"></div>
            @include('partials.open-book', ['class' => 'absolute -bottom-2 right-6 w-64 drop-shadow-2xl lg:w-80'])
        </div>
        <div class="relative max-w-xl">
            <h1 class="text-2xl font-extrabold text-violet-950 sm:text-4xl">Welcome back, {{ \Illuminate\Support\Str::limit($u->name, 22) }} <span class="inline-block">👋</span></h1>
            <p class="mt-2 text-violet-900/80">Manage your library, track activities and explore insights.</p>
            <p class="mt-1 text-xs text-violet-900/60">Snapshot taken <span id="stamp" class="font-semibold">–</span></p>
            <div class="mt-5 flex flex-wrap gap-2" data-html2canvas-ignore="true">
                <button id="snapBtn" class="btn btn-primary" title="Download this dashboard as a PNG image">@include('partials.icon', ['name' => 'camera', 'class' => 'h-4 w-4']) <span id="snapLabel">Snapshot</span></button>
                <button id="refreshBtn" class="btn btn-outline" title="Reload the latest numbers">@include('partials.icon', ['name' => 'refresh', 'class' => 'h-4 w-4']) Refresh</button>
            </div>
        </div>
    </section>

    {{-- ===== KPI cards ===== --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        @foreach ($cards as [$key, $label, $icon, $grad, $color, $url, $perm])
            @php $tag = $u->canDo($perm, 'read') ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($tag === 'a') href="{{ $url }}" @endif class="group relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-violet-100 transition {{ $tag === 'a' ? 'hover:-translate-y-1 hover:shadow-xl' : '' }}">
                <div class="relative z-10 flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $grad }} text-white shadow-lg">@include('partials.icon', ['name' => $icon, 'class' => 'h-6 w-6'])</span>
                    <div class="min-w-0"><div class="truncate text-sm font-medium text-slate-600">{{ $label }}</div>
                        <div id="kpi-{{ $key }}" class="text-2xl font-extrabold text-violet-950 sm:text-3xl">–</div></div>
                </div>
                <div id="trend-{{ $key }}" class="relative z-10 mt-2 text-xs font-semibold text-slate-400">&nbsp;</div>
                <svg id="spark-{{ $key }}" viewBox="0 0 120 44" preserveAspectRatio="none" class="absolute bottom-1 right-1 h-12 w-24 sm:w-28" data-color="{{ $color }}"></svg>
            </{{ $tag }}>
        @endforeach
    </div>

    {{-- ===== analytics + categories ===== --}}
    <div class="grid gap-5 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="flex items-center gap-2 font-bold text-violet-950">@include('partials.icon', ['name' => 'chart', 'class' => 'h-5 w-5 text-violet-600']) Library Analytics</h3>
                <select id="range" class="rounded-xl border border-violet-100 bg-white px-3 py-1.5 text-sm" data-html2canvas-ignore="true">
                    <option value="3">Last 3 Months</option><option value="6">Last 6 Months</option><option value="12" selected>Last 12 Months</option>
                </select>
            </div>
            <div class="h-72"><canvas id="cAnalytics"></canvas></div>
        </section>

        <section class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="flex items-center gap-2 font-bold text-violet-950">@include('partials.icon', ['name' => 'tag', 'class' => 'h-5 w-5 text-violet-600']) Top Categories</h3>
                @if ($u->canDo('categories', 'read'))<a href="{{ route('admin.categories.index') }}" class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 hover:bg-violet-100" data-html2canvas-ignore="true">View All →</a>@endif
            </div>
            <div class="flex flex-col items-center gap-4 sm:flex-row lg:flex-col xl:flex-row">
                <div class="relative h-44 w-44 shrink-0"><canvas id="cCats"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"><div id="catPct" class="text-2xl font-extrabold text-violet-950">–</div><div class="text-xs text-slate-500">Books</div></div></div>
                <ul id="catLegend" class="w-full space-y-2 text-sm"></ul>
            </div>
        </section>
    </div>

    {{-- ===== activity + e-books + promo ===== --}}
    <div class="grid gap-5 lg:grid-cols-3">
        <section class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="flex items-center gap-2 font-bold text-violet-950">@include('partials.icon', ['name' => 'bolt', 'class' => 'h-5 w-5 text-violet-600']) Recent Activity</h3>
                <button id="actToggle" class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 hover:bg-violet-100" data-html2canvas-ignore="true">View All →</button>
            </div>
            <ul id="activity" class="space-y-3 text-sm"><li class="text-slate-400">Loading…</li></ul>
        </section>

        <section class="card p-5">
            <h3 class="mb-3 flex items-center gap-2 font-bold text-violet-950">@include('partials.icon', ['name' => 'desktop', 'class' => 'h-5 w-5 text-violet-600']) E-Books vs Physical</h3>
            <div class="flex items-center gap-5">
                <div class="relative h-40 w-40 shrink-0"><canvas id="cType"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"><div id="ebookPct" class="text-2xl font-extrabold text-violet-950">–</div><div class="text-xs text-slate-500">E-Books</div></div></div>
                <ul class="w-full space-y-3 text-sm">
                    <li class="flex items-center justify-between"><span class="flex items-center gap-2"><i class="h-3 w-3 rounded-full bg-violet-700"></i>E-Books</span><b id="ebookLbl">–</b></li>
                    <li class="flex items-center justify-between"><span class="flex items-center gap-2"><i class="h-3 w-3 rounded-full bg-amber-400"></i>Physical</span><b id="physLbl">–</b></li>
                </ul>
            </div>
        </section>

        <section class="relative overflow-hidden rounded-[1.25rem] bg-gradient-to-br from-[#2a0d6b] via-[#3d1389] to-[#5b21b6] p-6 text-white shadow-sm">
            @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-25 blur-[2px]'])
            <div class="relative">
                <h3 class="text-2xl font-extrabold">Discover. Read. Learn.</h3>
                <p class="mt-1 text-sm text-violet-200">Your digital library, anytime, anywhere.</p>
                <a href="{{ route('catalog') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-violet-500 px-5 py-2.5 text-sm font-semibold shadow-lg hover:bg-violet-400">Browse Catalog @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</a>
            </div>
            @include('partials.open-book', ['class' => 'relative mx-auto mt-3 w-48 drop-shadow-2xl'])
        </section>
    </div>

    {{-- ===== more insights ===== --}}
    <h2 class="pt-2 text-lg font-bold text-violet-950">More insights</h2>
    <section id="recent" class="card hidden p-5"><h3 class="mb-3 font-bold text-violet-950">▶ Continue reading</h3><div id="recentList" class="no-scrollbar flex gap-4 overflow-x-auto"></div></section>
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="card p-5"><h3 class="mb-3 font-bold text-violet-950">Books by department</h3><div class="h-72"><canvas id="cDept"></canvas></div></div>
        <div class="card p-5"><h3 class="mb-3 font-bold text-violet-950">Most read e-books</h3><div class="h-72"><canvas id="cRead"></canvas></div></div>
        <div class="card p-5"><h3 class="mb-3 font-bold text-violet-950">Reading activity</h3><div class="h-64"><canvas id="cActivity"></canvas></div></div>
        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-violet-950">User logins</h3>
                <select id="period" class="rounded-xl border border-violet-100 px-2 py-1 text-sm" data-html2canvas-ignore="true"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="annually">Annually</option></select></div>
            <div class="h-64"><canvas id="cLogin"></canvas></div>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-5 right-5 z-50 hidden rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-2xl"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const $ = id => document.getElementById(id);
const j = u => fetch(u, { headers: { Accept: 'application/json' } }).then(r => r.json());
const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const charts = {};
const draw = (id, config) => { if (charts[id]) charts[id].destroy(); charts[id] = new Chart($(id), config); };
const toast = (msg, ms = 2500) => { const t = $('toast'); t.textContent = msg; t.classList.remove('hidden'); setTimeout(() => t.classList.add('hidden'), ms); };
const palette = n => Array.from({ length: n }, (_, i) => `hsl(${260 + (i * 100) / Math.max(n, 1)}, 65%, ${50 + (i % 3) * 6}%)`);
Chart.defaults.font.family = "'Poppins', sans-serif";

/* ---------- sparkline ---------- */
function spark(el, data, color) {
    const w = 120, h = 44, pad = 4, max = Math.max(...data, 1), min = Math.min(...data, 0);
    const pts = data.map((v, i) => [pad + i * (w - 2 * pad) / (data.length - 1 || 1), h - pad - ((v - min) / ((max - min) || 1)) * (h - 2 * pad)]);
    let d = `M${pts[0][0]},${pts[0][1]}`;
    for (let i = 1; i < pts.length; i++) { const [x0, y0] = pts[i - 1], [x1, y1] = pts[i], cx = (x0 + x1) / 2; d += ` C${cx},${y0} ${cx},${y1} ${x1},${y1}`; }
    const area = `${d} L${pts[pts.length - 1][0]},${h} L${pts[0][0]},${h} Z`;
    el.innerHTML = `<defs><linearGradient id="g-${el.id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".28"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs>
        <path d="${area}" fill="url(#g-${el.id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/>`;
}

/* ---------- welcome-dashboard widgets ---------- */
let analytics = null, activityRows = [], expanded = false;

function drawAnalytics() {
    const n = parseInt($('range').value), a = analytics, s = x => x.slice(-n);
    draw('cAnalytics', {
        data: { labels: s(a.labels), datasets: [
            { type: 'bar', label: 'Books Issued', data: s(a.issued), backgroundColor: '#6d28d9', borderRadius: 6, order: 2 },
            { type: 'bar', label: 'Books Returned', data: s(a.returned), backgroundColor: '#3b82f6', borderRadius: 6, order: 2 },
            { type: 'line', label: 'New Members', data: s(a.new_members), borderColor: '#10b981', backgroundColor: '#fff', pointBorderColor: '#10b981', pointBorderWidth: 2, pointRadius: 4, tension: .35, order: 1 },
        ] },
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1ecfd' } }, x: { grid: { display: false } } } },
    });
}

function renderActivity() {
    const rows = expanded ? activityRows : activityRows.slice(0, 5);
    $('activity').innerHTML = rows.length ? rows.map(r => `
        <li class="flex items-start gap-3"><span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${r.cls}">${r.icon}</span>
        <div class="min-w-0 flex-1"><div class="truncate font-medium" title="${esc(r.text)}">${esc(r.text)}</div><div class="text-xs text-slate-400">${esc(r.by)} · ${esc(r.time)}</div></div></li>`).join('')
        : '<li class="text-slate-400">No activity yet.</li>';
    $('actToggle').textContent = expanded ? 'Show less' : 'View All →';
    $('actToggle').classList.toggle('hidden', activityRows.length <= 5);
}

async function loadOverview() {
    const o = await j(@json(route('admin.dashboard.overview')));

    for (const key in o.kpis) {
        const k = o.kpis[key];
        $('kpi-' + key).textContent = Number(k.value).toLocaleString();
        spark($('spark-' + key), k.spark, $('spark-' + key).dataset.color);
        const up = k.trend >= 0, good = k.bad_when_up ? !up : up;
        $('trend-' + key).innerHTML = `<span class="${good ? 'text-emerald-600' : 'text-rose-600'}">${up ? '↑' : '↓'} ${Math.abs(k.trend)}%</span> <span class="font-normal text-slate-400">vs last month</span>`;
    }

    analytics = o.analytics; drawAnalytics();

    const colors = ['#6d28d9', '#3b82f6', '#f59e0b', '#10b981', '#a78bfa'];
    draw('cCats', { type: 'doughnut', data: { labels: o.categories.map(c => c.name), datasets: [{ data: o.categories.map(c => c.count), backgroundColor: colors, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } } });
    $('catPct').textContent = o.categories.length ? o.categories[0].pct + '%' : '0%';
    $('catLegend').innerHTML = o.categories.map((c, i) => `<li class="flex items-center justify-between"><span class="flex items-center gap-2"><i class="h-3 w-3 rounded-full" style="background:${colors[i % 5]}"></i>${esc(c.name)}</span><b>${c.pct}%</b></li>`).join('') || '<li class="text-slate-400">No books yet.</li>';

    const e = o.ebooks;
    draw('cType', { type: 'doughnut', data: { labels: ['E-Books', 'Physical'], datasets: [{ data: [e.ebooks, e.physical], backgroundColor: ['#6d28d9', '#fbbf24'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } } });
    $('ebookPct').textContent = e.ebook_pct + '%'; $('ebookLbl').textContent = e.ebook_pct + '%'; $('physLbl').textContent = e.physical_pct + '%';

    activityRows = o.activity; renderActivity();
    $('stamp').textContent = new Date().toLocaleString();
}

/* ---------- extra insight charts ---------- */
async function loadInsights() {
    const c = await j(@json(route('admin.dashboard.charts')));
    const bar = (id, labels, data, label, horizontal = false) => draw(id, { type: 'bar',
        data: { labels, datasets: [{ label, data, backgroundColor: palette(data.length), borderRadius: 8 }] },
        options: { indexAxis: horizontal ? 'y' : 'x', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
    bar('cDept', c.books_by_department.map(d => d.department), c.books_by_department.map(d => d.count), 'Books');
    bar('cRead', c.most_read_books.map(d => d.name), c.most_read_books.map(d => d.count), 'Reads', true);
    const a = c.reading_activity;
    bar('cActivity', ['Last 24h', '7 days', 'This month', '3 months', 'This year'], [a.daily, a.weekly, a.monthly, a.quarterly, a.yearly], 'Reads');
    await loadLogins();

    const recent = await j(@json(route('admin.dashboard.recent-reads')));
    $('recent').classList.toggle('hidden', !recent.length);
    $('recentList').innerHTML = recent.map(r => `
        <a href="${r.url}" class="w-28 shrink-0"><img src="${r.cover}" class="h-40 w-28 rounded-xl object-cover shadow">
        <div class="mt-1 truncate text-sm font-semibold">${esc(r.name)}</div><div class="text-xs text-slate-500">Page ${r.current_page}</div></a>`).join('');
}
async function loadLogins() {
    const rows = await j(@json(route('admin.dashboard.login-report')) + '?period=' + $('period').value);
    draw('cLogin', { type: 'line', data: { labels: rows.map(r => r.period_label), datasets: [{ label: 'Logins', data: rows.map(r => r.login_count), borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.15)', tension: .35, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false } });
}

/* ---------- snapshot: whole dashboard -> PNG ---------- */
async function snapshot() {
    const btn = $('snapBtn'), label = $('snapLabel');
    btn.disabled = true; label.textContent = 'Capturing…';
    try {
        $('stamp').textContent = new Date().toLocaleString();
        await new Promise(r => setTimeout(r, 150));
        const canvas = await html2canvas($('dashArea'), { scale: 2, backgroundColor: '#f4f1ff', useCORS: true, logging: false });
        const blob = await new Promise(res => canvas.toBlob(res, 'image/png'));
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'library-dashboard-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.png';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 3000);
        toast('📸 Snapshot saved to your downloads');
    } catch (e) { console.error(e); toast('Snapshot failed - see the browser console'); }
    finally { btn.disabled = false; label.textContent = 'Snapshot'; }
}

$('snapBtn').onclick = snapshot;
$('refreshBtn').onclick = async () => { $('refreshBtn').disabled = true; await Promise.all([loadOverview(), loadInsights()]); $('refreshBtn').disabled = false; toast('Dashboard refreshed', 1500); };
$('range').onchange = () => analytics && drawAnalytics();
$('period').onchange = loadLogins;
$('actToggle').onclick = () => { expanded = !expanded; renderActivity(); };
loadOverview(); loadInsights();
</script>
@endsection
