@extends('layouts.admin')

@section('title', 'Bài cần chấm - Writing')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📝 Bài cần chấm</h1>
            <p class="text-sm text-gray-500 mt-1">Xem và chấm bài Writing của học sinh</p>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex rounded-lg border border-gray-200 overflow-hidden">
            <a href="{{ route('admin.writing-reviews.index', ['filter' => 'all']) }}"
               class="px-4 py-2 text-sm font-medium {{ $filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                Tất cả
            </a>
            <a href="{{ route('admin.writing-reviews.index', ['filter' => 'pending']) }}"
               class="px-4 py-2 text-sm font-medium border-l {{ $filter === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                ⏳ Chờ chấm
            </a>
            <a href="{{ route('admin.writing-reviews.index', ['filter' => 'graded']) }}"
               class="px-4 py-2 text-sm font-medium border-l {{ $filter === 'graded' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                ✅ Đã chấm
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Học sinh</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set / Part</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian nộp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($submissions as $submission)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-sm font-bold">
                                        {{ strtoupper(substr($submission->attempt->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $submission->attempt->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $submission->attempt->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-900">{{ $submission->attempt->set->title ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $submission->question->title ?? 'Part ' . $submission->question->part }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $submission->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($submission->grading_status === 'graded')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ Đã chấm
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        ⏳ Chờ chấm
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($submission->writingReview)
                                    <span class="font-bold text-indigo-600">{{ $submission->writingReview->total_score }}/10</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('admin.writing-reviews.show', $submission->attempt_id) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg
                                          {{ $submission->grading_status === 'graded' ? 'text-gray-600 bg-gray-100 hover:bg-gray-200' : 'text-white bg-indigo-600 hover:bg-indigo-700' }} transition-colors">
                                    {{ $submission->grading_status === 'graded' ? 'Xem lại' : 'Chấm bài' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm font-medium">Chưa có bài nộp nào</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($submissions->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $submissions->appends(['filter' => $filter])->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
