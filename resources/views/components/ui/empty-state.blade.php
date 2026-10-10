{{-- Trạng thái trống: lời mời làm gì tiếp, không phải lời xin lỗi. --}}
@props(['title', 'icon' => 'info'])
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-200 px-5 py-10 text-center']) }}>
    <x-ui.icon-badge :icon="$icon" tone="bg-gray-100 text-gray-400" size="md" class="mx-auto mb-3" />
    <p class="text-sm font-medium text-gray-700">{{ $title }}</p>
    @if(trim($slot))<div class="text-sm text-gray-500 mt-1 max-w-md mx-auto leading-relaxed">{{ $slot }}</div>@endif
</div>
