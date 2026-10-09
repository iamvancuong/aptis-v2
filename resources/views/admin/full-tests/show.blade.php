@extends('layouts.admin')

@section('title', 'Full Test ' . $fullTest->code())

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.full-tests.index') }}" class="text-sm text-indigo-600 hover:underline">← Full Test</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Full Test {{ $fullTest->code() }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $fullTest->user?->name }} · {{ $fullTest->user?->email }} ·
                bắt đầu {{ $fullTest->started_at?->format('d/m/Y H:i') }}
                @if($fullTest->finished_at) · nộp xong {{ $fullTest->finished_at->format('d/m/Y H:i') }} @endif
            </p>
        </div>

        @if($report['finished'] && ! $report['fully_graded'])
            <form method="POST" action="{{ route('admin.full-tests.regrade', $fullTest) }}"
                  onsubmit="return confirm('Gửi chấm lại AI các phần Speaking/Writing còn treo?');">
                @csrf
                <button class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-sm">
                    Chấm lại phần còn treo
                </button>
            </form>
        @endif
    </div>

    @if(! $report['finished'])
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-800">
            Học viên đang làm phần {{ min($fullTest->current_stage + 1, 5) }}/5.
        </div>
    @endif

    {{-- Admin không có link sang trang kết quả từng phần (trang đó chỉ cho chủ bài). --}}
    @include('full-test._report', ['detailLinks' => false])
</div>
@endsection
