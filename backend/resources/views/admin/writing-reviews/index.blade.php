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

        {{-- Filter & Search --}}
        <div class="flex flex-col md:flex-row items-center gap-4">
            <form action="{{ route('admin.writing-reviews.index') }}" method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên/email học sinh..."
                           class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm text-sm">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div x-data="{ exp: '{{ request('expiration') }}' }" class="flex items-center gap-2">
                    <x-select name="expiration" x-model="exp">
                        <option value="">Lọc ngày thi</option>
                        <option value="expired">Đã quá hạn</option>
                        <option value="warning">Sắp thi (7 ngày)</option>
                        <option value="custom">Sắp thi (Tùy chỉnh ngày)</option>
                        <option value="active">Chưa thi</option>
                        <option value="never">Không giới hạn</option>
                    </x-select>
                    <input 
                        x-show="exp === 'custom'"
                        type="number" 
                        name="expire_days" 
                        value="{{ request('expire_days') }}"
                        placeholder="Số ngày..." 
                        class="w-28 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        min="1"
                        x-cloak
                    >
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 whitespace-nowrap text-sm font-medium">Lọc</button>
            </form>

            <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm">
                <a href="{{ route('admin.writing-reviews.index', ['filter' => 'pending', 'search' => $search]) }}"
                   class="px-4 py-2 text-sm font-medium {{ $filter === 'pending' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    ⏳ Chờ chấm
                </a>
                <a href="{{ route('admin.writing-reviews.index', ['filter' => 'graded', 'search' => $search]) }}"
                   class="px-4 py-2 text-sm font-medium border-l border-gray-200 {{ $filter === 'graded' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                    ✅ Đã chấm
                </a>
            </div>
        </div>
    </div>

    {{-- Bulk Approve Form --}}
    <form method="POST" action="{{ route('admin.writing-reviews.bulk-approve') }}" id="bulkForm">
        @csrf

        {{-- Bulk action bar --}}
        <div class="flex items-center gap-3 mb-3" id="bulkBar" style="display:none !important">
            <span class="text-sm text-gray-500"><span id="selectedCount">0</span> bài được chọn</span>
            <button type="submit"
                    class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                🤖 Duyệt AI grade hàng loạt
            </button>
        </div>

        {{-- Datatable Component --}}
        <x-datatable :data="$attempts" :per-page-options="[10, 20, 50]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Học sinh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set / Part</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày yêu cầu</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($attempts as $attempt)
                    @php
                        $allGraded   = $attempt->attemptAnswers->every(fn($a) => $a->grading_status === 'graded');
                        $allAiGraded = $attempt->attemptAnswers->every(fn($a) => $a->grading_status === 'ai_graded');
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4">
                            @if($allAiGraded)
                                <input type="checkbox" name="attempt_ids[]" value="{{ $attempt->id }}"
                                       class="bulk-check rounded border-gray-300 text-indigo-600">
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-sm font-bold">
                                    {{ strtoupper(substr($attempt->user->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $attempt->user->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $attempt->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-900">{{ $attempt->set->title ?? '—' }}</p>
                            <p class="text-xs text-gray-500">Writing Full (4 Parts)</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $attempt->grading_requested_at ? $attempt->grading_requested_at->format('d/m/Y H:i') : $attempt->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($allGraded)
                                <x-badge variant="success">✅ Đã chốt điểm</x-badge>
                            @elseif($allAiGraded)
                                <x-badge variant="warning">🤖 AI đã chấm (Chờ review)</x-badge>
                            @else
                                <x-badge variant="warning">⏳ Chờ chấm</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($allGraded)
                                <span class="font-bold text-indigo-600">{{ round($attempt->score ?? 0, 1) }}%</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.writing-reviews.show', $attempt->id) }}"
                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg {{ $allGraded ? 'text-gray-600 bg-gray-100 hover:bg-gray-200' : 'text-white bg-indigo-600 hover:bg-indigo-700' }} transition-colors">
                                {{ $allGraded ? 'Xem lại' : 'Chấm bài' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
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
        </x-datatable>
    </form>
</div>

<script>
const selectAll = document.getElementById('selectAll');
const bulkBar   = document.getElementById('bulkBar');
const countEl   = document.getElementById('selectedCount');

function updateBar() {
    const checked = document.querySelectorAll('.bulk-check:checked').length;
    if (checked > 0) {
        bulkBar.style.removeProperty('display');
        countEl.textContent = checked;
    } else {
        bulkBar.style.setProperty('display','none','important');
    }
}

selectAll?.addEventListener('change', () => {
    document.querySelectorAll('.bulk-check').forEach(c => c.checked = selectAll.checked);
    updateBar();
});
document.querySelectorAll('.bulk-check').forEach(c => c.addEventListener('change', updateBar));
</script>
@endsection
