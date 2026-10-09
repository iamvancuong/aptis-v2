@extends('layouts.app')

@section('title', 'Full Test Aptis - Milaedu')

@php
    $labels = \App\Services\FullTestService::SKILL_LABELS;
    $stages = \App\Models\FullTest::stages();
    $totalMinutes = collect($stages)->sum(fn ($s) => (int) config("aptis.exam_duration.{$s}"));
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Full Test Aptis</h1>
        <p class="mt-2 text-gray-600">Thi liên tục cả 5 phần như kỳ thi thật. Làm xong và chấm xong, bạn nhận bảng điểm theo thang Aptis (0–50 mỗi kỹ năng) kèm trình độ CEFR.</p>
    </div>

    <x-card>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Cấu trúc bài thi</h2>
        <ol class="space-y-2 mb-6">
            @foreach($stages as $i => $skill)
                <li class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-gray-50">
                    <span class="w-8 h-8 rounded-full bg-white border-2 border-indigo-200 flex items-center justify-center text-sm font-bold text-indigo-700">{{ $i + 1 }}</span>
                    <span class="text-sm font-medium text-gray-800">{{ $labels[$skill] }}</span>
                    <span class="ml-auto text-sm text-gray-500">{{ config("aptis.exam_duration.{$skill}") }} phút</span>
                </li>
            @endforeach
        </ol>

        <ul class="text-sm text-gray-600 space-y-1.5 mb-6 list-disc pl-5">
            <li>Tổng thời gian khoảng <strong>{{ intdiv($totalMinutes, 60) }} giờ {{ $totalMinutes % 60 }} phút</strong>. Nên dùng máy tính, có tai nghe và micro.</li>
            <li>Nộp phần nào thì sang phần sau, <strong>không quay lại</strong> phần đã nộp. Hết giờ, hệ thống tự nộp.</li>
            <li>Lỡ tắt trình duyệt: vào lại trang này để làm tiếp đúng phần đang dở (đồng hồ vẫn chạy).</li>
            <li>Bấm “Bắt đầu” là tính 1 lượt, kể cả khi bỏ dở.</li>
        </ul>

        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-xl bg-indigo-50 border border-indigo-100">
            <div class="flex-1">
                <p class="text-sm text-indigo-900">Lượt Full Test còn lại: <strong class="text-lg">{{ $remaining }}</strong> / {{ $quota }}</p>
            </div>
            @if($active)
                <a href="{{ route('full-test.show', $active) }}" class="inline-flex justify-center items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow">
                    ▶ Tiếp tục bài đang làm
                </a>
            @elseif($remaining > 0)
                <form method="POST" action="{{ route('full-test.start') }}" x-data="{ busy: false }" @pageshow.window="busy = false"
                      @submit="if (busy || !confirm('Bắt đầu Full Test? Lượt thi sẽ được tính ngay.')) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <button type="submit" :disabled="busy" class="w-full inline-flex justify-center items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow disabled:opacity-60 disabled:cursor-wait">
                        <span x-show="!busy">Bắt đầu Full Test</span>
                        <span x-show="busy" x-cloak>Đang chuẩn bị đề…</span>
                    </button>
                </form>
            @else
                <p class="text-sm text-red-600 font-medium">Bạn đã dùng hết lượt. Liên hệ admin để được cấp thêm.</p>
            @endif
        </div>
    </x-card>

    @if($history->isNotEmpty())
        <x-card>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Các lượt đã thi</h2>
            <div class="divide-y divide-gray-100">
                @foreach($history as $ft)
                    <div class="flex items-center gap-3 py-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ $ft->code() }}</p>
                            <p class="text-xs text-gray-500">Bắt đầu {{ $ft->started_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($ft->isCompleted())
                            <a href="{{ route('full-test.result', $ft) }}" class="text-sm font-semibold text-indigo-600 hover:underline">Xem kết quả →</a>
                        @else
                            <a href="{{ route('full-test.show', $ft) }}" class="text-sm font-semibold text-amber-600 hover:underline">
                                Đang làm phần {{ min($ft->current_stage + 1, count($stages)) }}/{{ count($stages) }} →
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</div>
@endsection
