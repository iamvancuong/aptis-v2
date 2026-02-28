@extends('layouts.admin')

@section('title', 'Chấm Bài Speaking')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
        </svg>
        Chấm Bài Speaking
    </h1>
    
    <div class="flex flex-col md:flex-row items-center gap-4">
        <form action="{{ route('admin.speaking-reviews.index') }}" method="GET" class="relative w-full md:w-64">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên/email học sinh..."
                   class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm text-sm">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </form>

        <div class="flex gap-2 bg-white p-1 rounded-lg border border-gray-200">
            <a href="{{ route('admin.speaking-reviews.index', ['status' => 'pending', 'search' => $search]) }}" class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $filter === 'pending' ? 'bg-amber-100 text-amber-700' : 'text-gray-500 hover:bg-gray-50' }}">
                Chờ chấm
            </a>
            <a href="{{ route('admin.speaking-reviews.index', ['status' => 'graded', 'search' => $search]) }}" class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $filter === 'graded' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:bg-gray-50' }}">
                Đã chấm
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50">
            <tr>
                <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-widest border-b">Học viên</th>
                <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-widest border-b">Bộ đề</th>
                <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-widest border-b">Ngày yêu cầu</th>
                <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-widest border-b">Trạng thái</th>
                <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-widest border-b text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($attempts as $attempt)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                            {{ substr($attempt->user->name ?? 'A', 0, 1) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-800">{{ $attempt->user->name ?? 'Unknown User' }}</div>
                            <div class="text-xs text-gray-500">{{ $attempt->user->email ?? '' }}</div>
                        </div>
                    </div>
                </td>
                <td class="p-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $attempt->set->title ?? 'Không rõ' }}
                    </span>
                </td>
                <td class="p-4 text-sm text-gray-600">
                    {{ $attempt->grading_requested_at ? $attempt->grading_requested_at->format('d/m/Y H:i') : ($attempt->finished_at ? $attempt->finished_at->format('d/m/Y H:i') : 'N/A') }}
                </td>
                <td class="p-4">
                    @if($filter === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            Chờ chấm
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Đã chấm ({{ $attempt->score }}/100)
                        </span>
                    @endif
                </td>
                <td class="p-4 text-right">
                    <a href="{{ route('admin.speaking-reviews.show', $attempt) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm font-medium text-gray-700 transition-colors">
                        @if($filter === 'pending')
                            Chấm bài
                        @else
                            Xem lại
                        @endif
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-sm">Không có bài thi nào trong danh sách này.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($attempts->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
        {{ $attempts->links() }}
    </div>
    @endif
</div>
@endsection
