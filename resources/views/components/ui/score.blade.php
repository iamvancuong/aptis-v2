{{-- Ô điểm phần trăm tô màu theo mức: ≥80 xanh, ≥50 vàng, còn lại đỏ; null = chờ chấm. --}}
@props(['value' => null, 'empty' => 'Chờ chấm'])
@php
    $v = $value === null ? null : (float) $value;
    $tone = $v === null ? 'bg-gray-100 text-gray-500' : ($v >= 80 ? 'bg-emerald-50 text-emerald-700' : ($v >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'));
@endphp
<span {{ $attributes->merge(['class' => "min-w-[3.5rem] text-center px-2.5 py-1 rounded-lg text-sm font-bold shrink-0 $tone"]) }}>{{ $v === null ? $empty : number_format($v, 0) . '%' }}</span>
