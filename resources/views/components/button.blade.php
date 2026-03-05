@props([
    'variant' => 'primary', // primary, secondary, danger
    'type' => 'button',
    'href' => null,
])

@php
$classes = match($variant) {
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    default => 'bg-blue-600 hover:bg-blue-700 text-white',
};
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors $classes"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors $classes"]) }}>
        {{ $slot }}
    </button>
@endif
