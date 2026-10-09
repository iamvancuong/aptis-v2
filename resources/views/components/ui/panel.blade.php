{{-- Khung thẻ nền trắng bo 2xl. Có tiêu đề (tuỳ chọn), slot `actions` cạnh tiêu đề, slot `footer`.
     padded=false khi nội dung tự lo padding (danh sách chia dòng…). --}}
@props(['title' => null, 'subtitle' => null, 'padded' => true])
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-200 overflow-hidden']) }}>
    @if($title || isset($actions))
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="flex-1 min-w-0">
                @if($title)<h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>@endif
                @if($subtitle)<p class="text-xs text-gray-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex flex-wrap items-center gap-1.5">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5' => $padded])>{{ $slot }}</div>
    @isset($footer)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 border-t border-gray-100 text-sm">{{ $footer }}</div>
    @endisset
</div>
