{{-- Một mục sidebar admin. Dựa vào biến Alpine `isDesktopCollapsed` / `isMobile` của layout:
     thu gọn trên desktop thì chỉ còn icon (tên hiện ở tooltip), số đếm thành chấm đỏ.
     sub = mục con trong nhóm (không icon, chữ nhỏ). --}}
@props(['href', 'label', 'icon' => null, 'active' => false, 'badge' => 0, 'sub' => false])
@php
    $tone = $active ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';
@endphp
<a href="{{ $href }}" title="{{ $label }}" @if($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => "relative flex items-center gap-3 rounded-lg transition-colors $tone " . ($sub ? 'px-3 py-1.5 text-[13px]' : 'px-3 py-2 text-sm')]) }}
   :class="(isDesktopCollapsed && !isMobile) ? 'justify-center' : ''">
    @if($icon)
        <x-ui.icon :name="$icon" class="w-5 h-5 shrink-0 {{ $active ? 'text-blue-600' : 'text-gray-400' }}" />
    @endif
    <span class="flex-1 truncate" x-show="!isDesktopCollapsed || isMobile">{{ $label }}</span>
    @if($badge > 0)
        <span x-show="!isDesktopCollapsed || isMobile" class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-white text-[11px] font-bold leading-5 text-center">{{ $badge > 99 ? '99+' : $badge }}</span>
        <span x-show="isDesktopCollapsed && !isMobile" class="absolute top-1.5 right-2 w-2 h-2 rounded-full bg-red-500"></span>
    @endif
</a>
