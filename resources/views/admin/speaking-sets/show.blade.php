@extends('layouts.admin')

@section('title', 'Chi tiết Bộ Đề Speaking')
@section('header', 'Chi tiết Bộ Đề: ' . $speaking_set->title)

@section('content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('admin.speaking-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
    <a href="{{ route('admin.speaking-sets.edit', $speaking_set) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors">
        Chỉnh sửa Bộ Đề
    </a>
</div>

@php
    $questions = $speaking_set->questions->keyBy('part');
    $p1 = $questions[1] ?? null;
    $p2 = $questions[2] ?? null;
    $p3 = $questions[3] ?? null;
    $p4 = $questions[4] ?? null;
@endphp

<div class="space-y-6 max-w-5xl">
    <x-card title="Thông tin chung" class="mb-6 bg-gray-50/50">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Tên Bộ Đề</label>
                <div class="text-lg font-semibold text-gray-900">{{ $speaking_set->title }}</div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Trạng thái</label>
                <x-badge :variant="$speaking_set->is_public ? 'success' : 'default'">
                    {{ $speaking_set->is_public ? 'Công khai' : 'Bản nháp / Riêng tư' }}
                </x-badge>
            </div>
        </div>
    </x-card>

    <!-- Part 1 -->
    <x-card title="Part 1: Personal Information" class="mb-6 border-l-4 border-indigo-500">
        <div class="space-y-4">
            @foreach($p1->metadata['questions'] ?? [] as $idx => $q)
                <div>
                    <label class="block text-xs font-bold text-indigo-600 uppercase mb-1">Câu hỏi {{ $idx + 1 }}</label>
                    <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 text-gray-800">{{ $q }}</div>
                </div>
            @endforeach

            @if(!empty($p1->metadata['sample_answer']))
                <div class="mt-2 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                    <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Đáp án tham khảo (P1)</label>
                    <div class="text-sm text-emerald-800 italic whitespace-pre-wrap">{{ $p1->metadata['sample_answer'] }}</div>
                </div>
            @endif
        </div>
    </x-card>

    <!-- Part 2 -->
    <x-card title="Part 2: Describe a picture" class="mb-6 border-l-4 border-emerald-500">
        <div class="space-y-4">
            @if(!empty($p2->metadata['image_path']))
                <div>
                    <label class="block text-xs font-bold text-emerald-600 uppercase mb-2">Hình ảnh</label>
                    <img src="{{ asset('storage/' . $p2->metadata['image_path']) }}" class="max-h-64 rounded-lg shadow-sm border border-emerald-100">
                </div>
            @endif

            <div class="space-y-4 pt-4">
                @foreach($p2->metadata['questions'] ?? [] as $idx => $q)
                    <div>
                        <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">
                            @if($idx === 0) Câu hỏi 1 (Mô tả ảnh) @elseif($idx === 1) Câu hỏi 2 (Mở rộng) @else Câu hỏi 3 @endif
                        </label>
                        <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-100 text-gray-800">{{ $q }}</div>
                    </div>
                @endforeach
            </div>

            @if(!empty($p2->metadata['sample_answer']))
                <div class="mt-2 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                    <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Đáp án tham khảo (P2)</label>
                    <div class="text-sm text-emerald-800 italic whitespace-pre-wrap">{{ $p2->metadata['sample_answer'] }}</div>
                </div>
            @endif
        </div>
    </x-card>

    <!-- Part 3 -->
    <x-card title="Part 3: Compare two pictures" class="mb-6 border-l-4 border-amber-500">
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($p3->metadata['image_paths'] ?? [] as $idx => $path)
                    <div>
                        <label class="block text-xs font-bold text-amber-600 uppercase mb-2">Ảnh {{ $idx + 1 }}</label>
                        <img src="{{ asset('storage/' . $path) }}" class="h-64 w-full object-contain mx-auto rounded-lg shadow-sm border border-amber-100">
                    </div>
                @endforeach
            </div>

            <div class="space-y-4 pt-4">
                @foreach($p3->metadata['questions'] ?? [] as $idx => $q)
                    <div>
                        <label class="block text-xs font-bold text-amber-600 uppercase mb-1">
                            @if($idx === 0) Câu hỏi 1 (So sánh) @elseif($idx === 1) Câu hỏi 2 @else Câu hỏi 3 @endif
                        </label>
                        <div class="bg-amber-50 p-3 rounded-lg border border-amber-100 text-gray-800">{{ $q }}</div>
                    </div>
                @endforeach
            </div>

            @if(!empty($p3->metadata['sample_answer']))
                <div class="mt-2 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                    <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Đáp án tham khảo (P3)</label>
                    <div class="text-sm text-emerald-800 italic whitespace-pre-wrap">{{ $p3->metadata['sample_answer'] }}</div>
                </div>
            @endif
        </div>
    </x-card>

    <!-- Part 4 -->
    <x-card title="Part 4: Extended Discussion (Abstract Level)" class="mb-6 border-l-4 border-red-500">
        <div class="space-y-4">
            @if(!empty($p4->metadata['image_path']))
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase mb-2">Hình ảnh</label>
                    <img src="{{ asset('storage/' . $p4->metadata['image_path']) }}" class="max-h-64 rounded-lg shadow-sm border border-red-100">
                </div>
            @endif

            <div class="space-y-4 pt-4">
                @foreach($p4->metadata['questions'] ?? [] as $idx => $q)
                    <div>
                        <label class="block text-xs font-bold text-red-600 uppercase mb-1">Câu hỏi {{ $idx + 1 }}</label>
                        <div class="bg-red-50 p-3 rounded-lg border border-red-100 text-gray-800">{{ $q }}</div>
                    </div>
                @endforeach
            </div>

            @if(!empty($p4->metadata['sample_answer']))
                <div class="mt-2 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                    <label class="block text-xs font-bold text-emerald-600 uppercase mb-1">Đáp án tham khảo (P4)</label>
                    <div class="text-sm text-emerald-800 italic whitespace-pre-wrap">{{ $p4->metadata['sample_answer'] }}</div>
                </div>
            @endif

            <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <div><span class="font-bold">Thời gian chuẩn bị:</span> 60 giây</div>
                    <div class="text-gray-300">|</div>
                    <div><span class="font-bold">Tổng thời gian nói:</span> 120 giây</div>
                </div>
            </div>
        </div>
    </x-card>
</div>
@endsection
