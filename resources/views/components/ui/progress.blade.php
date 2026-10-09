{{-- Thanh tiến độ mảnh có nhãn hai bên. value: 0–100. --}}
@props(['value' => 0, 'label' => null, 'hint' => null])
@php $value = max(0, min(100, (int) round($value))); @endphp
<div {{ $attributes }}>
    @if($label || $hint)
        <div class="flex items-center justify-between text-xs mb-1.5">
            <span class="{{ $value > 0 ? 'text-gray-600' : 'text-gray-400' }}">{{ $label }}</span>
            @if($hint)<span class="font-medium text-gray-900">{{ $hint }}</span>@endif
        </div>
    @endif
    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
        <div class="h-full rounded-full bg-blue-500" style="width: {{ $value }}%"></div>
    </div>
</div>
