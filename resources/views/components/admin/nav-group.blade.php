{{-- Nhóm xổ xuống trong sidebar admin; tự mở khi đang ở một trang con ($active).
     Bấm khi sidebar đang thu gọn → mở rộng sidebar (expandSidebar của layout). --}}
@props(['label', 'icon', 'active' => false])
<div x-data="{ open: @js($active) }">
    <button type="button" @click="open = !open; if (open) expandSidebar()" title="{{ $label }}"
            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $active ? 'text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
            :class="(isDesktopCollapsed && !isMobile) ? 'justify-center' : ''">
        <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0 {{ $active ? 'text-blue-600' : 'text-gray-400' }}" />
        <span class="flex-1 text-left truncate" x-show="!isDesktopCollapsed || isMobile">{{ $label }}</span>
        <x-ui.icon name="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" x-show="!isDesktopCollapsed || isMobile" ::class="open ? 'rotate-180' : ''" />
    </button>
    <div x-show="open && (!isDesktopCollapsed || isMobile)" x-cloak x-transition class="mt-1 ml-5 pl-3 border-l border-gray-200 space-y-0.5">
        {{ $slot }}
    </div>
</div>
