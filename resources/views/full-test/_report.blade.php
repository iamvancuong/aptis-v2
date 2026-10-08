{{--
    Kết quả một lượt Full Test — dùng chung cho học viên và admin.
    Biến: $fullTest, $report (FullTestService::report), $pdfRoute,
          $detailLinks (bool, mặc định true — link sang kết quả chi tiết từng phần).
--}}
@php $detailLinks = $detailLinks ?? true; @endphp

{{-- Điểm từng phần --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-4 py-3">Phần thi</th>
                <th class="text-right px-4 py-3">Điểm Aptis</th>
                <th class="text-center px-4 py-3">CEFR</th>
                <th class="text-right px-4 py-3">Trạng thái</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($report['skills'] as $row)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800">
                        {{ $row['label'] }}
                        @if($detailLinks && $row['mock'] && $row['submitted'] && $row['skill'] !== 'grammar')
                            <a href="{{ route('mock-test.result', $row['mock']) }}" class="ml-2 text-xs text-indigo-600 hover:underline">chi tiết</a>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">
                        {{ $row['submitted'] && $row['graded'] ? $row['scale'] . '/50' : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($row['level'] && $row['graded'])
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ \App\Support\AptisScale::color($row['level'])['badge'] }}">{{ $row['level'] }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-xs">
                        @if(! $row['submitted'])
                            <span class="text-gray-400">Chưa làm</span>
                        @elseif($row['graded'])
                            <span class="text-green-600 font-medium">✓ Đã chấm</span>
                        @elseif($row['failed'] > 0)
                            <span class="text-red-600 font-medium">Lỗi chấm {{ $row['failed'] }} phần</span>
                        @else
                            <span class="text-amber-600 font-medium">⏳ Đang chấm {{ $row['pending'] }} phần</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($report['fully_graded'])
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-gray-700">
            Tổng 4 kỹ năng: <strong>{{ $report['total'] }}/200</strong> ·
            Trình độ ước tính: <strong class="text-indigo-700">{{ $report['overall'] }}</strong>
        </p>
        <a href="{{ $pdfRoute }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow">
            ⬇ Tải bảng điểm (PDF)
        </a>
    </div>

    <div class="overflow-x-auto">
        <div style="min-width: 640px; max-width: 820px; margin: 0 auto;">
            @include('full-test._certificate')
        </div>
    </div>
@else
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800 text-sm">
        Bảng điểm sẽ có khi chấm xong cả 5 phần. Speaking và Writing được AI chấm, thường mất vài phút — tải lại trang sau ít phút.
    </div>
@endif
