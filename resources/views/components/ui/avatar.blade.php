{{-- Avatar chữ viết tắt (2 chữ cái cuối của họ tên, an toàn với tiếng Việt có dấu). size: sm | md --}}
@props(['name' => '', 'size' => 'md'])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(-2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    $box = $size === 'sm' ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-sm';
@endphp
<span {{ $attributes->merge(['class' => "$box rounded-full bg-blue-50 text-blue-700 font-bold flex items-center justify-center shrink-0"]) }}>{{ $initials }}</span>
