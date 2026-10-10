{{-- Tiêu đề nhóm trong sidebar admin; khi thu gọn thì thành đường kẻ mảnh. --}}
@props(['label'])
<p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400" x-show="!isDesktopCollapsed || isMobile">{{ $label }}</p>
<div class="mx-3 my-3 border-t border-gray-100" x-show="isDesktopCollapsed && !isMobile"></div>
