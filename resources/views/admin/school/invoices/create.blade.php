@extends('layouts.admin')
@section('title', 'Create invoices')
@section('content')
@php $in = 'w-full rounded-xl border border-violet-100 bg-white px-3 py-2 text-sm outline-none focus:border-violet-400'; @endphp
<h1 class="mb-1 text-2xl font-extrabold">Create invoices</h1>
<p class="mb-4 text-sm text-slate-500">Bill every active student of a class at once, or one student by admission number. Academic year: <b>{{ $year->name ?? 'none - create one first' }}</b>.</p>
<form method="POST" action="{{ route('admin.school.invoices.store') }}" class="card max-w-3xl space-y-5 p-6">@csrf
    <div class="grid gap-4 md:grid-cols-2">
        <div><label class="block text-sm font-medium">Whole class</label><select name="section_id" class="{{ $in }}"><option value="">—</option>@foreach ($sections as $id => $n)<option value="{{ $id }}" @selected(old('section_id') == $id)>{{ $n }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">…or one student (admission no.)</label><input name="admission_no" value="{{ old('admission_no') }}" class="{{ $in }}"></div>
        <div><label class="block text-sm font-medium">Term</label><select name="term_id" class="{{ $in }}"><option value="">—</option>@foreach ($terms as $t)<option value="{{ $t->id }}" @selected(old('term_id') == $t->id)>{{ $t->name }} · {{ $t->year->name ?? '' }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium">Due date</label><input type="date" name="due_date" value="{{ old('due_date') }}" class="{{ $in }}"></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium">Description</label><input name="description" value="{{ old('description') }}" placeholder="e.g. Term 1 fees" class="{{ $in }}"></div>
    </div>
    <div><div class="mb-2 text-sm font-bold uppercase text-violet-700">Fees to include</div>
        @forelse ($feeTypes as $f)
            <div class="flex items-center gap-3 border-t py-2 first:border-0"><label class="flex flex-1 items-center gap-2 text-sm font-medium"><input type="checkbox" name="fee_types[]" value="{{ $f->id }}" @checked(in_array($f->id, old('fee_types', []))) class="h-4 w-4 accent-violet-700"> {{ $f->name }}</label>
                <input type="number" step="0.01" min="0" name="amounts[{{ $f->id }}]" value="{{ old('amounts.' . $f->id, $f->amount) }}" class="w-32 rounded-lg border border-violet-100 px-2 py-1 text-right text-sm"></div>
        @empty<p class="text-sm text-slate-500">No fee types yet - add them under <a class="font-semibold text-violet-700 underline" href="{{ route('admin.school.fee-types.index') }}">Fee types</a>.</p>@endforelse</div>
    <div class="flex gap-3"><button class="btn btn-primary">Create invoices</button><a href="{{ route('admin.school.invoices.index') }}" class="btn btn-outline">Cancel</a></div>
</form>
@endsection
