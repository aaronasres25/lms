{{-- Single photo layer. Vars: $i (which picture, wraps), $class, $style. Falls back to the bookshelf pattern. --}}
@php $__u = \App\Support\SitePhotos::pick($i ?? 0); @endphp
@if ($__u)
    <div class="{{ $class ?? 'absolute inset-0 bg-cover bg-center opacity-70' }}" style="background-image: url('{{ $__u }}'); {{ $style ?? '' }}"></div>
@else
    @include('partials.bookshelf', ['class' => 'absolute inset-0 h-full w-full opacity-40 blur-[2px]'])
@endif
