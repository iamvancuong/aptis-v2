@props([
    'type' => 'info', // success, error, warning, info
])

@php
$classes = match($type) {
    'success' => 'bg-green-50 border-green-200 text-green-800',
    'error' => 'bg-red-50 border-red-200 text-red-800',
    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
    'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    default => 'bg-blue-50 border-blue-200 text-blue-800',
};
@endphp

<div {{ $attributes->merge(['class' => "border rounded-lg p-4 $classes"]) }}>
    {{ $slot }}
</div>
