@extends('layouts.app')

@section('title', 'Dashboard - Milaedu')

@use('App\Support\SkillMeta')

@php
    $tenGoi = collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->last() ?: auth()->user()->name;

    // Chip tóm tắt: màu chỉ bật lên khi cần chú ý.
    $isAiUnlimited = $totalAiLimit === -1;
    $aiTone = $isAiUnlimited || $aiRemaining > $totalAiLimit * 0.5 ? 'green' : ($aiRemaining > 0 ? 'amber' : 'red');
    $expiryTone = match($expirationStatus) { 'expired' => 'red', 'warning' => 'amber', default => 'gray' };

    // Khối "Việc hôm nay": gom các thông báo trước đây là 4 thanh màu riêng.
    $viecHomNay = [];
    if (($unseenWriting ?? 0) > 0) {
        $viecHomNay[] = ['icon' => 'check', 'tone' => 'bg-emerald-50 text-emerald-600', 'text' => $unseenWriting . ' bài Writing vừa có điểm', 'sub' => 'Xem điểm và nhận xét chi tiết', 'cta' => 'Xem kết quả', 'url' => route('writingHistory.index')];
    }
    if (($unseenSpeaking ?? 0) > 0) {
        $viecHomNay[] = ['icon' => 'mic', 'tone' => 'bg-emerald-50 text-emerald-600', 'text' => $unseenSpeaking . ' bài Speaking vừa có điểm', 'sub' => 'Xem điểm và nhận xét chi tiết', 'cta' => 'Xem kết quả', 'url' => route('speakingHistory.index')];
    }
    if ($vocabToday ?? null) {
        $chuoi = $vocabToday['streak'] > 0 ? 'Chuỗi ' . $vocabToday['streak'] . ' ngày' . ($vocabToday['reviewed'] === 0 ? ' — ôn 1 từ để giữ chuỗi' : '') : 'Ôn mỗi ngày để bắt đầu chuỗi';
        $sub = $chuoi . ' · ' . $vocabToday['total'] . ' từ trong sổ tay';
        $viecHomNay[] = $vocabToday['due'] > 0
            ? ['icon' => 'flame', 'tone' => 'bg-amber-50 text-amber-600', 'text' => 'Hôm nay có ' . $vocabToday['due'] . ' từ cần ôn', 'sub' => $sub, 'cta' => 'Ôn ngay', 'url' => route('vocab.review')]
            : ['icon' => 'flame', 'tone' => 'bg-gray-100 text-gray-500', 'text' => 'Đã ôn xong từ vựng hôm nay', 'sub' => $sub, 'cta' => 'Xem sổ tay', 'url' => route('vocab.index')];
    }
    if ($nextClass ?? null) {
        $dangHoc = $nextClass->isJoinable();
        $viecHomNay[] = ['icon' => 'video', 'tone' => $dangHoc ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600', 'text' => ($dangHoc ? 'Lớp đang diễn ra: ' : 'Lớp sắp tới: ') . $nextClass->title, 'sub' => $nextClass->timeLabel(), 'cta' => $dangHoc ? 'Vào lớp' : 'Xem lịch', 'url' => $dangHoc ? route('classes.join', $nextClass) : route('classes.index')];
    }

    $statusTone = ['amber' => 'text-amber-600', 'gray' => 'text-gray-500', 'muted' => 'text-gray-400'];

    // Nhãn + màu đường biểu đồ lấy từ SkillMeta để khớp màu icon kỹ năng.
    $chartMeta = collect(SkillMeta::ORDER)
        ->mapWithKeys(fn ($k) => [$k => ['label' => SkillMeta::get($k)['name'], 'color' => SkillMeta::get($k)['color']]])
        ->put('mock_test', ['label' => 'Thi thử', 'color' => '#94a3b8']);
@endphp

@section('content')
<x-ui.page-header :title="'Chào ' . $tenGoi" subtitle="Hôm nay bạn muốn luyện kỹ năng nào?" class="lg:items-end">
    <x-slot:aside>
        <x-ui.chip><span class="font-semibold text-gray-900">{{ $totalAttempts }}</span> bài đã làm</x-ui.chip>
        <x-ui.chip>Điểm TB <span class="font-semibold text-gray-900">{{ $avgScore !== null ? $avgScore . '%' : '—' }}</span></x-ui.chip>
        <x-ui.chip :tone="$aiTone" title="Số lượt AI chấm Writing còn lại">
            AI Writing <span class="font-semibold">{{ $isAiUnlimited ? '∞' : $aiRemaining . '/' . $totalAiLimit }}</span>
        </x-ui.chip>
        <x-ui.chip :tone="$expiryTone">
            @if($expirationStatus === 'never')
                Hạn dùng <span class="font-semibold">không giới hạn</span>
            @elseif($expirationStatus === 'expired')
                <span class="font-semibold">Đã hết hạn</span> {{ $expiresAt->format('d/m/Y') }}
            @else
                Còn <span class="font-semibold">{{ $daysUntilExpiry }} ngày</span> · {{ $expiresAt->format('d/m/Y') }}
            @endif
        </x-ui.chip>
    </x-slot:aside>
</x-ui.page-header>

@if(count($viecHomNay) > 0)
    <x-ui.panel title="Việc hôm nay" :padded="false" class="mb-6">
        <x-slot:actions><span class="text-xs text-gray-400">{{ count($viecHomNay) }} mục</span></x-slot:actions>
        <div class="divide-y divide-gray-100">
            @foreach($viecHomNay as $viec)
                <a href="{{ $viec['url'] }}" class="group flex items-center gap-3 sm:gap-4 px-5 py-3 hover:bg-gray-50 transition-colors">
                    <x-ui.icon-badge :icon="$viec['icon']" :tone="$viec['tone']" size="sm" />
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-gray-900 truncate">{{ $viec['text'] }}</span>
                        <span class="block text-xs text-gray-500 truncate">{{ $viec['sub'] }}</span>
                    </span>
                    <span class="shrink-0 text-sm font-medium text-blue-600 group-hover:text-blue-700">{{ $viec['cta'] }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </x-ui.panel>
@endif

<x-ui.cta-card :href="route('full-test.index')" icon="flag" title="Full Test Aptis" :badge="'Còn ' . $fullTestRemaining . ' lượt'" action="Vào thi">
    Thi liên tục 5 phần như thi thật · nhận bảng điểm thang Aptis và trình độ CEFR
</x-ui.cta-card>

<x-ui.section-title title="Luyện từng kỹ năng" meta="Theo thứ tự đề thi Aptis" />
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
    @foreach(SkillMeta::ORDER as $skill)
        @php $meta = SkillMeta::get($skill); @endphp
        <x-ui.tile :href="$skill === 'grammar' ? route('grammar.index') : route('skills.show', $skill)">
            <x-ui.icon-badge :skill="$skill" />
            <span class="block mt-4 text-base font-semibold text-gray-900">{{ $meta['name'] }}</span>
            <span class="block text-xs text-gray-500">{{ $meta['desc'] }}</span>
            <span class="block mt-3 pt-3 border-t border-gray-100 text-xs font-medium {{ $statusTone[$skillStatus[$skill]['tone']] }}">
                {{ $skillStatus[$skill]['text'] }}
            </span>
        </x-ui.tile>
    @endforeach
</div>

<x-ui.panel title="Tiến độ luyện tập" subtitle="Điểm trung bình mỗi tuần · 6 tháng gần nhất" :padded="false">
    <x-slot:actions><div id="chartLegend" class="flex flex-wrap gap-1.5"></div></x-slot:actions>
    <div class="px-3 sm:px-5 pt-4 pb-2">
        <div class="relative h-56 sm:h-64 w-full">
            <canvas id="progressChart"></canvas>
        </div>
    </div>
    <x-slot:footer>
        <span class="text-gray-400">Lịch sử:</span>
        <a href="{{ route('history.index') }}" class="text-gray-600 hover:text-blue-600">Trắc nghiệm</a>
        <a href="{{ route('writingHistory.index') }}" class="text-gray-600 hover:text-blue-600">Writing</a>
        <a href="{{ route('speakingHistory.index') }}" class="text-gray-600 hover:text-blue-600">Speaking</a>
        <a href="{{ route('leaderboard.index') }}" class="text-gray-600 hover:text-blue-600">Bảng xếp hạng</a>
    </x-slot:footer>
</x-ui.panel>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const data = @json($statisticsData);
        const canvas = document.getElementById('progressChart');
        const legend = document.getElementById('chartLegend');

        // Nhãn + màu từ SkillMeta (server) → khớp màu icon kỹ năng phía trên.
        const meta = @json($chartMeta);

        const keys = Object.keys(meta).filter(k => data.series && data.series[k]);
        if (!data.labels || data.labels.length === 0 || keys.length === 0) {
            canvas.parentNode.innerHTML = '<div class="h-full flex flex-col items-center justify-center text-center text-sm text-gray-400">'
                + '<span class="text-gray-500 font-medium">Chưa có dữ liệu trong 6 tháng gần đây</span>'
                + '<span>Làm bài luyện tập để thấy tiến độ của bạn ở đây.</span></div>';
            return;
        }

        const chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: keys.map(k => ({
                    key: k,
                    label: meta[k].label,
                    data: data.series[k],
                    borderColor: meta[k].color,
                    backgroundColor: meta[k].color,
                    borderWidth: 2.5,
                    borderDash: k === 'mock_test' ? [6, 4] : [],
                    tension: 0.4,
                    cubicInterpolationMode: 'monotone',
                    // Ẩn chấm cho gọn, TRỪ mốc đứng một mình (không có mốc kề để
                    // nối thành đường) — không có chấm thì mốc đó biến mất hẳn.
                    pointRadius: ctx => {
                        const d = ctx.dataset.data, i = ctx.dataIndex;
                        const prev = d.slice(0, i).some(v => v !== null);
                        const next = d.slice(i + 1).some(v => v !== null);
                        return (!prev && !next) ? 4 : 0;
                    },
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHoverBorderColor: '#fff',
                    spanGaps: true,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                layout: { padding: { top: 6 } },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                    },
                    y: {
                        min: 0, max: 100,
                        border: { display: false },
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 25, callback: v => v + '%' },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#111827',
                        bodyColor: '#4b5563',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        boxWidth: 8, boxHeight: 8, boxPadding: 4,
                        usePointStyle: true,
                        callbacks: {
                            title: items => 'Tuần ' + items[0].label,
                            label: ctx => ' ' + ctx.dataset.label + ': ' + Math.round(ctx.parsed.y) + '%',
                        },
                        filter: item => item.parsed.y !== null,
                    },
                },
            },
        });

        // Chú thích: chip bấm được để ẩn/hiện từng đường.
        keys.forEach((k, i) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 transition-opacity';
            chip.innerHTML = '<span class="w-2 h-2 rounded-full" style="background:' + meta[k].color + '"></span>' + meta[k].label;
            chip.addEventListener('click', () => {
                const visible = chart.isDatasetVisible(i);
                chart.setDatasetVisibility(i, !visible);
                chip.classList.toggle('opacity-40', visible);
                chart.update();
            });
            legend.appendChild(chip);
        });
    });
</script>
@endpush
