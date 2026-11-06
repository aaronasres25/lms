@extends($layout)
@section('title', 'Notifications')
@section('content')
@php $noteColors = ['violet' => 'bg-violet-100 text-violet-600', 'amber' => 'bg-amber-100 text-amber-600', 'green' => 'bg-emerald-100 text-emerald-600', 'red' => 'bg-rose-100 text-rose-600', 'orange' => 'bg-orange-100 text-orange-600', 'sky' => 'bg-sky-100 text-sky-600']; @endphp
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Notifications</h1><p class="text-sm text-slate-500">{{ $unread }} unread · {{ $items->total() }} total</p></div>
    <div class="flex gap-2">
        <div class="flex overflow-hidden rounded-xl bg-white text-sm font-medium shadow-sm ring-1 ring-violet-200">
            <a href="{{ route($np . '.index') }}" class="px-4 py-2 {{ request('filter') !== 'unread' ? 'bg-violet-700 text-white' : 'text-violet-800' }}">All</a>
            <a href="{{ route($np . '.index', ['filter' => 'unread']) }}" class="px-4 py-2 {{ request('filter') === 'unread' ? 'bg-violet-700 text-white' : 'text-violet-800' }}">Unread</a>
        </div>
        @if ($unread)<form method="POST" action="{{ route($np . '.read-all') }}">@csrf<button class="btn btn-outline">@include('partials.icon', ['name' => 'check', 'class' => 'h-4 w-4']) Mark all as read</button></form>@endif
    </div>
</div>

<div class="tbl-wrap">
    <ul class="divide-y divide-violet-50">
    @forelse ($items as $n)
        @php $d = $n->data; $isUnread = $n->read_at === null; @endphp
        <li class="flex items-start gap-4 px-5 py-4 {{ $isUnread ? 'bg-violet-50/60' : '' }}">
            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $noteColors[$d['color'] ?? 'violet'] ?? $noteColors['violet'] }}">@include('partials.icon', ['name' => $d['icon'] ?? 'bell', 'class' => 'h-5 w-5'])</span>
            <a href="{{ route($np . '.open', $n->id) }}" class="min-w-0 flex-1">
                <span class="block text-sm {{ $isUnread ? 'font-bold' : 'font-medium text-slate-600' }}">{{ $d['title'] ?? 'Notification' }}</span>
                <span class="block text-sm text-slate-500">{{ $d['body'] ?? '' }}</span>
                <span class="mt-1 block text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}{{ $n->read_at ? ' · read ' . $n->read_at->diffForHumans() : '' }}</span>
            </a>
            @if ($isUnread)
                <form method="POST" action="{{ route($np . '.read', $n->id) }}">@csrf<button class="rounded-lg px-3 py-1 text-xs font-semibold text-violet-700 hover:bg-violet-100">Mark read</button></form>
            @else
                <span class="px-3 py-1 text-xs text-slate-400">Read</span>
            @endif
        </li>
    @empty
        <li class="tbl-empty">No notifications.</li>
    @endforelse
    </ul>
    @include('partials.pager', ['paginator' => $items])
</div>
@endsection
