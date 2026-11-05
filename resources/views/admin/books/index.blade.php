@extends('layouts.admin')
@section('title', 'Books')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-extrabold">Books</h1><p class="text-sm text-slate-500">{{ $books->total() }} records</p></div>
    @if (auth()->user()->canDo('books', 'create'))<a href="{{ route('admin.books.create') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) New book</a>@endif
</div>
<form class="filter-bar mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Name, ISBN, author…" class="min-w-[12rem] flex-1">
    <select name="state"><option value="">Any state</option>@foreach (\App\Models\Book::STATES as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach</select>
    <select name="department_id"><option value="">Any department</option>@foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $n }}</option>@endforeach</select>
    <select name="category_id"><option value="">Any category</option>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected(request('category_id') == $id)>{{ $n }}</option>@endforeach</select>
    <select name="type"><option value="">E-book + physical</option><option value="ebook" @selected(request('type') === 'ebook')>E-books</option><option value="physical" @selected(request('type') === 'physical')>Physical</option></select>
    <label class="flex items-center gap-1 px-2 text-sm"><input type="checkbox" name="archived" value="1" @checked(request('archived'))> Archived</label>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="tbl-wrap"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th class="num">#</th><th>Title</th><th>Author</th><th>Year</th><th>Ver.</th><th>Category</th><th>Department</th><th>Total</th><th>Avail.</th><th>Issued</th><th>State</th></tr></thead>
    <tbody>
    @forelse ($books as $b)
        <tr>
            <td class="num">{{ $books->firstItem() + $loop->index }}</td>
            <td><a class="font-semibold text-violet-800 hover:underline" href="{{ route('admin.books.show', $b) }}">{{ $b->name }}</a>
                @if ($b->is_ebook)<span class="ml-1 rounded bg-sky-100 px-1.5 text-xs font-semibold text-sky-800">e-book</span>@endif
                <div class="text-xs text-slate-400">{{ $b->isbn }}</div></td>
            <td>{{ $b->author->name ?? '' }}</td><td>{{ $b->publication_year }}</td><td>{{ $b->version }}</td>
            <td>{{ $b->category->name ?? '' }}</td><td>{{ $departments[$b->department_id] ?? '' }}</td>
            <td>{{ $b->copies_total }}</td><td class="font-semibold text-emerald-700">{{ $b->copies_available }}</td><td>{{ $b->issued_count }}</td>
            <td>@include('partials.state-badge', ['state' => $b->state])</td>
        </tr>
    @empty<tr><td colspan="11" class="tbl-empty">No books found.</td></tr>
    @endforelse
    </tbody></table></div>
    @include('partials.pager', ['paginator' => $books])
</div>
@endsection
