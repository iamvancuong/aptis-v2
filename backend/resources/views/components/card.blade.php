@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-md p-6']) }}>
    @if($title)
        <h3 class="text-lg font-semibold mb-4">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
