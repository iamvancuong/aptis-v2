@props([
    'active' => false,
    'href',
])

@php
$classes = $active 
    ? 'bg-blue-50 text-blue-700 font-medium' 
    : 'text-gray-700 hover:bg-gray-100';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "block px-4 py-2 rounded-lg transition-colors $classes"]) }}>
    {{ $slot }}
</a>
