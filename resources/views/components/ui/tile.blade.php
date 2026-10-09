{{-- Thẻ trong lưới (kỹ năng, Part, đề). Có `href` → cả thẻ bấm được + mũi tên góc phải;
     không có `href` → thẻ tĩnh (dùng khi bên trong có nhiều nút riêng). --}}
@props(['href' => null])
@php
    $base = 'group relative flex flex-col bg-white rounded-2xl border border-gray-200 p-5 transition-all';
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base . ' hover:border-blue-300 hover:shadow-md hover:-translate-y-0.5']) }}>
        <span class="absolute top-5 right-5 text-gray-300 group-hover:text-blue-500 transition-colors" aria-hidden="true">→</span>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $base . ' hover:border-gray-300']) }}>{{ $slot }}</div>
@endif
