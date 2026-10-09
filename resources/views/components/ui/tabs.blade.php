{{-- Thanh tab dạng link. variant: underline (chuyển trang ngang hàng) | pill (bộ lọc).
     Đường kẻ dưới là inset shadow (không phải border) để vừa cuộn ngang được trên
     mobile vừa không bị cắt mất gạch chân của tab đang chọn.
     :items = [['label' => 'Writing', 'url' => ..., 'active' => true], ...] --}}
@props(['items', 'variant' => 'underline'])
@if($variant === 'pill')
    <div {{ $attributes->merge(['class' => 'inline-flex p-1 rounded-xl bg-gray-100']) }}>
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" @if($item['active'] ?? false) aria-current="page" @endif
               class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors {{ ($item['active'] ?? false) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex gap-1 overflow-x-auto overflow-y-hidden shadow-[inset_0_-1px_0_#e5e7eb]']) }}>
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" @if($item['active'] ?? false) aria-current="page" @endif
               class="px-4 py-2.5 border-b-2 text-sm font-medium whitespace-nowrap transition-colors {{ ($item['active'] ?? false) ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@endif
