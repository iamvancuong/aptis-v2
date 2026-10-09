{{-- Thẻ hành động CHÍNH của trang (Full Test, Thi thử kỹ năng…). Mỗi trang tối đa một thẻ.
     <x-ui.cta-card :href="..." icon="flag" title="Full Test Aptis" badge="Còn 3 lượt" action="Vào thi">mô tả</x-ui.cta-card> --}}
@props(['href', 'icon' => 'flag', 'title', 'badge' => null, 'action' => 'Bắt đầu'])
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'group flex flex-col sm:flex-row sm:items-center gap-4 mb-8 p-5 rounded-2xl bg-white border-2 border-blue-200 hover:border-blue-400 hover:shadow-md transition-all']) }}>
    <x-ui.icon-badge :icon="$icon" tone="bg-blue-50 text-blue-600" size="md" />
    <span class="flex-1">
        <span class="flex items-center gap-2 flex-wrap">
            <span class="text-lg font-bold text-gray-900">{{ $title }}</span>
            @if($badge)
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">{{ $badge }}</span>
            @endif
        </span>
        <span class="block text-sm text-gray-500 mt-0.5">{{ $slot }}</span>
    </span>
    <span class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-blue-600 group-hover:bg-blue-700 text-white font-semibold text-sm transition-colors">{{ $action }} →</span>
</a>
