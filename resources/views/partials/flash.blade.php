@if (session('success'))
    <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
