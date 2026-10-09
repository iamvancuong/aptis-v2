@extends('layouts.admin')

@section('title', 'Full Test')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Full Test</h1>
            <p class="text-sm text-gray-500 mt-1">Các lượt thi liên tục 5 phần của học viên</p>
        </div>
        <a href="{{ route('admin.full-tests.quotas') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
            Cấp lượt Full Test
        </a>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm">
            @foreach(['all' => 'Tất cả', 'completed' => 'Đã thi xong', 'in_progress' => 'Đang làm'] as $val => $label)
                <a href="{{ route('admin.full-tests.index', array_merge(request()->only('q'), ['status' => $val])) }}"
                   class="px-3 py-2 text-xs font-medium {{ $status === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-200' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Tìm email / tên học viên"
                   class="px-3 py-2 text-sm border border-gray-200 rounded-lg w-64">
            <button class="px-3 py-2 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50">Tìm</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3">Mã</th>
                    <th class="text-left px-4 py-3">Học viên</th>
                    <th class="text-left px-4 py-3">Bắt đầu</th>
                    <th class="text-left px-4 py-3">Tiến độ</th>
                    <th class="text-right px-4 py-3">Tổng /200</th>
                    <th class="text-center px-4 py-3">CEFR</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($fullTests as $ft)
                    @php $r = $reports[$ft->id]; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $ft->code() }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $ft->user?->name }}</div>
                            <div class="text-xs text-gray-500">{{ $ft->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $ft->started_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if(! $r['finished'])
                                <span class="text-amber-600 font-medium">Đang làm phần {{ min($ft->current_stage + 1, 5) }}/5</span>
                            @elseif($r['fully_graded'])
                                <span class="text-green-600 font-medium">Có bảng điểm</span>
                            @else
                                <span class="text-blue-600 font-medium">Chờ chấm AI</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $r['fully_graded'] ? $r['total'] : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($r['fully_graded'])
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ \App\Support\AptisScale::color($r['overall'])['badge'] }}">{{ $r['overall'] }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.full-tests.show', $ft) }}" class="text-indigo-600 hover:underline text-xs font-semibold">Xem →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Chưa có lượt Full Test nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $fullTests->links() }}
</div>
@endsection
