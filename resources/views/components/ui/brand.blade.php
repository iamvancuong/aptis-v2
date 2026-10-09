{{-- Logo Milaedu dùng chung cho header học viên + admin. `tag` = nhãn nhỏ bên cạnh (vd. "Admin"). --}}
@props(['href' => route('dashboard'), 'tag' => null])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2 shrink-0']) }}>
    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center shrink-0">M</span>
    <span class="text-lg font-bold text-gray-900 whitespace-nowrap">Mila<span class="text-blue-600">edu</span></span>
    @if($tag)
        <span class="px-1.5 py-0.5 rounded-md bg-gray-100 text-gray-600 text-[11px] font-semibold uppercase tracking-wide">{{ $tag }}</span>
    @endif
</a>
