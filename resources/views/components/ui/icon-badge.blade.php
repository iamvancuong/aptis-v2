{{-- Ô vuông bo góc chứa icon, tô theo màu kỹ năng hoặc tone tuỳ chọn.
     <x-ui.icon-badge skill="listening" size="lg" />  ·  <x-ui.icon-badge icon="flag" tone="bg-blue-50 text-blue-600" /> --}}
@props(['skill' => null, 'icon' => null, 'tone' => null, 'size' => 'md'])
@php
    $meta = $skill ? \App\Support\SkillMeta::get($skill) : null;
    $sizes = [
        'sm' => ['box' => 'w-9 h-9 rounded-full', 'icon' => 'w-5 h-5'],
        'md' => ['box' => 'w-11 h-11 rounded-xl', 'icon' => 'w-6 h-6'],
        'lg' => ['box' => 'w-14 h-14 rounded-2xl', 'icon' => 'w-7 h-7'],
    ][$size];
@endphp
<span {{ $attributes->merge(['class' => "{$sizes['box']} flex items-center justify-center shrink-0 " . ($tone ?? $meta['tone'] ?? 'bg-gray-100 text-gray-500')]) }}>
    <x-ui.icon :name="$icon ?? $meta['icon'] ?? 'info'" :class="$sizes['icon']" />
</span>
