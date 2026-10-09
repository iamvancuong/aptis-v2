{{-- Tiêu đề nhỏ phía trên một lưới thẻ, `meta` là chú thích mờ bên phải. --}}
@props(['title', 'meta' => null])
<div {{ $attributes->merge(['class' => 'flex items-baseline justify-between gap-3 mb-3']) }}>
    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
    @if($meta)<span class="text-xs text-gray-400">{{ $meta }}</span>@endif
</div>
