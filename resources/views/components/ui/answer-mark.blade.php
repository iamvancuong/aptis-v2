{{-- Dấu đúng/sai/chưa trả lời của một câu (thay cho emoji đánh dấu). :correct = true | false | null --}}
@props(['correct' => null])
@php
    $tone = $correct === true ? 'bg-emerald-100 text-emerald-600' : ($correct === false ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400');
@endphp
<span {{ $attributes->merge(['class' => "w-6 h-6 rounded-full flex items-center justify-center shrink-0 $tone"]) }}
      title="{{ $correct === true ? 'Đúng' : ($correct === false ? 'Sai' : 'Chưa trả lời') }}">
    @if($correct === null)
        <span class="w-2 h-0.5 rounded bg-current"></span>
    @else
        <x-ui.icon :name="$correct ? 'check' : 'x'" class="w-3.5 h-3.5" />
    @endif
</span>
