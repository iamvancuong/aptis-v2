{{-- Ô số liệu: số to + nhãn nhỏ. tone: gray | blue | green | amber | red
     bind = biểu thức Alpine cho số động (thay cho value). --}}
@props(['value' => null, 'label', 'tone' => 'gray', 'bind' => null])
@php
    $tones = ['gray' => 'text-gray-900', 'blue' => 'text-blue-600', 'green' => 'text-emerald-600', 'amber' => 'text-amber-600', 'red' => 'text-red-600'];
@endphp
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-200 px-4 py-3']) }}>
    <p class="text-2xl font-bold {{ $tones[$tone] ?? $tones['gray'] }}" @if($bind) x-text="{{ $bind }}" @endif>{{ $value }}</p>
    <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $label }}</p>
</div>
