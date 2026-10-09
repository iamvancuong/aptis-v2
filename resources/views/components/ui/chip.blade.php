{{-- Chip tóm tắt dạng viên thuốc. tone: gray | green | amber | red | blue --}}
@props(['tone' => 'gray'])
@php
    $tones = [
        'gray'  => 'bg-white border-gray-200 text-gray-600',
        'green' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
        'amber' => 'bg-amber-50 border-amber-200 text-amber-700',
        'red'   => 'bg-red-50 border-red-200 text-red-700',
        'blue'  => 'bg-blue-50 border-blue-200 text-blue-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm ' . ($tones[$tone] ?? $tones['gray'])]) }}>{{ $slot }}</span>
