{{-- Đầu trang học viên: breadcrumb + icon + tiêu đề + mô tả, slot `aside` bên phải (chip…).
     :crumbs = [['label' => 'Luyện tập', 'url' => ...], ['label' => 'Listening']] (mục cuối không link) --}}
@props(['title', 'subtitle' => null, 'crumbs' => [], 'skill' => null])
@if(count($crumbs))
    <nav class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
        @foreach($crumbs as $crumb)
            @if(!$loop->first)<span class="text-gray-300">/</span>@endif
            @if(!empty($crumb['url']) && !$loop->last)
                <a href="{{ $crumb['url'] }}" class="hover:text-blue-600">{{ $crumb['label'] }}</a>
            @else
                <span class="text-gray-900 font-medium">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
<div {{ $attributes->merge(['class' => 'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6']) }}>
    <div class="flex items-center gap-4 min-w-0">
        @if($skill)
            <x-ui.icon-badge :skill="$skill" size="lg" />
        @endif
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-0.5 text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($aside)
        <div class="flex flex-wrap gap-2">{{ $aside }}</div>
    @endisset
</div>
