{{-- Nút dùng chung (học viên + admin). href → thẻ <a>, không có → <button>.
     variant: primary | secondary | ghost | danger · size: sm | md · icon: tên trong x-ui.icon --}}
@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'size' => 'md',
    'icon' => null,
])

@php
$classes = match($variant) {
    'secondary' => 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50',
    'ghost'     => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
    'danger'    => 'bg-red-600 hover:bg-red-700 text-white',
    default     => 'bg-blue-600 hover:bg-blue-700 text-white',
};
$sizes = $size === 'sm' ? 'px-3 py-1.5 text-sm rounded-lg' : 'px-4 py-2.5 text-sm rounded-xl';
$base = "inline-flex items-center justify-center gap-1.5 font-semibold transition-colors disabled:opacity-50 $sizes $classes";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        @if($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>
        @if($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif
        {{ $slot }}
    </button>
@endif
