@extends('layouts.app')

@section('title', 'Full Test ' . $fullTest->code() . ' - Milaedu')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('full-test.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm">← Full Test</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Full Test {{ $fullTest->code() }}</h1>
    </div>

    {{-- Tiến trình 5 phần --}}
    <x-card>
        <ol class="grid grid-cols-5 gap-2">
            @foreach($stages as $i => $skill)
                @php
                    $done = $i < $fullTest->current_stage;
                    $current = $i === $fullTest->current_stage;
                @endphp
                <li class="text-center">
                    <div class="mx-auto w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $done ? 'bg-green-500 text-white' : ($current ? 'bg-indigo-600 text-white ring-4 ring-indigo-100' : 'bg-gray-100 text-gray-400') }}">
                        {{ $done ? '✓' : $i + 1 }}
                    </div>
                    <p class="mt-2 text-xs font-medium {{ $current ? 'text-indigo-700' : 'text-gray-500' }}">{{ \Illuminate\Support\Str::before($labels[$skill], ' and') }}</p>
                </li>
            @endforeach
        </ol>
    </x-card>

    {{-- Phần kế tiếp --}}
    <x-card>
        <div class="text-center py-4">
            @if($fullTest->current_stage > 0 && ! $currentMock)
                <p class="text-green-600 font-semibold mb-2">✓ Đã nộp phần {{ $labels[$stages[$fullTest->current_stage - 1]] }}</p>
            @endif

            <p class="text-sm text-gray-500 uppercase tracking-wider">Phần {{ $fullTest->current_stage + 1 }}/{{ count($stages) }}</p>
            <h2 class="text-3xl font-bold text-gray-900 mt-1">{{ $labels[$currentSkill] }}</h2>
            <p class="text-gray-600 mt-2">Thời gian: <strong>{{ config("aptis.exam_duration.{$currentSkill}") }} phút</strong></p>

            @if($currentSkill === 'speaking')
                <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-4 py-2 inline-block">
                    🎙️ Phần nói cần micro — trình duyệt sẽ hỏi quyền, hãy bấm “Cho phép”.
                </p>
            @endif

            <form method="POST" action="{{ route('full-test.next', $fullTest) }}" class="mt-6"
                  x-data="{ busy: false }" @pageshow.window="busy = false" @submit="if (busy) { $event.preventDefault(); return; } busy = true">
                @csrf
                <button type="submit" :disabled="busy" class="inline-flex items-center px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white text-lg font-semibold rounded-xl shadow-lg disabled:opacity-60 disabled:cursor-wait">
                    <span x-show="busy" x-cloak>Đang mở đề…</span>
                    <span x-show="!busy">
                    @if($currentMock && $currentMock->status === 'in_progress')
                        ▶ Tiếp tục phần đang làm
                    @else
                        Bắt đầu phần {{ $labels[$currentSkill] }} →
                    @endif
                    </span>
                </button>
            </form>
            @if($currentMock && $currentMock->status === 'in_progress')
                <p class="mt-3 text-xs text-gray-500">Đồng hồ của phần này vẫn đang chạy.</p>
            @else
                <p class="mt-3 text-xs text-gray-500">Đồng hồ bắt đầu chạy khi bạn bấm nút.</p>
            @endif
        </div>
    </x-card>
</div>
@endsection
