@extends('layouts.app')

@section('title', 'Dashboard - APTIS Practice')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Chọn kỹ năng luyện tập</h1>
    <p class="mt-2 text-gray-600">Chọn một trong ba kỹ năng để bắt đầu luyện tập</p>
</div>

{{-- Writing Graded Notification Banner --}}
@if(($unseenWriting ?? 0) > 0)
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
    <div class="w-9 h-9 bg-green-100 rounded-full flex items-center justify-center shrink-0">
        <span class="relative flex h-3 w-3 absolute -top-1 -right-1">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
    </div>
    <div class="flex-1">
        <p class="text-sm font-semibold text-green-800">
            🎉 Bạn có {{ $unseenWriting }} bài Writing vừa có điểm!
        </p>
        <p class="text-xs text-green-600">Xem ngay kết quả và nhận xét từ giảng viên</p>
    </div>
    <a href="{{ route('writingHistory.index') }}"
       class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
        Xem ngay →
    </a>
</div>
@endif

{{-- Speaking Graded Notification Banner --}}
@if(($unseenSpeaking ?? 0) > 0)
<div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-xl flex items-center gap-3">
    <div class="w-9 h-9 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
        <span class="relative flex h-3 w-3 absolute -top-1 -right-1">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
        </span>
        <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
    </div>
    <div class="flex-1">
        <p class="text-sm font-semibold text-orange-800">
            🎉 Bạn có {{ $unseenSpeaking }} bài Speaking vừa có điểm!
        </p>
        <p class="text-xs text-orange-600">Xem ngay kết quả và nhận xét từ giảng viên</p>
    </div>
    <a href="{{ route('speakingHistory.index') }}"
       class="px-3 py-1.5 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
        Xem ngay →
    </a>
</div>
@endif

{{-- Quick Stats Row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    {{-- Total Attempts --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="text-xl font-black text-gray-800">{{ $totalAttempts }}</div>
            <div class="text-xs text-gray-500 font-medium">Bài đã làm</div>
        </div>
    </div>

    {{-- Avg Score --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <div>
            <div class="text-xl font-black text-gray-800">
                {{ $avgScore !== null ? $avgScore . '%' : '—' }}
            </div>
            <div class="text-xs text-gray-500 font-medium">Điểm trung bình</div>
        </div>
    </div>

    {{-- AI Remaining --}}
    @php
        $isAiUnlimited = $totalAiLimit === -1;
        $aiPct = ($totalAiLimit > 0 && !$isAiUnlimited) ? round($aiRemaining / $totalAiLimit * 100) : ($isAiUnlimited ? 100 : 0);
        $aiColor = $isAiUnlimited ? 'text-green-600 bg-green-100' : ($aiRemaining > $totalAiLimit * 0.5 ? 'text-green-600 bg-green-100' : ($aiRemaining > 0 ? 'text-amber-600 bg-amber-100' : 'text-red-600 bg-red-100'));
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 {{ $aiColor }} rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            @if ($isAiUnlimited)
                <div class="text-xl font-black text-gray-800">∞<span class="text-sm font-medium text-gray-400">/∞</span></div>
                <div class="text-xs text-gray-500 font-medium">AI Writing không giới hạn</div>
            @else
                <div class="text-xl font-black text-gray-800">{{ $aiRemaining }}<span class="text-sm font-medium text-gray-400">/{{ $totalAiLimit }}</span></div>
                <div class="text-xs text-gray-500 font-medium">AI Writing còn lại</div>
            @endif
        </div>
    </div>

    {{-- Account Expiry --}}
    @php
        $expiryColor = match($expirationStatus) {
            'expired' => 'text-red-600 bg-red-100',
            'warning' => 'text-amber-600 bg-amber-100',
            'active'  => 'text-green-600 bg-green-100',
            default   => 'text-gray-500 bg-gray-100',
        };
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 {{ $expiryColor }} rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            @if($expirationStatus === 'never')
                <div class="text-xl font-black text-gray-600">∞</div>
                <div class="text-xs text-gray-500 font-medium">Không giới hạn</div>
            @elseif($expirationStatus === 'expired')
                <div class="text-base font-black text-red-600">Đã hết hạn</div>
                <div class="text-xs text-gray-500 font-medium">{{ $expiresAt->format('d/m/Y') }}</div>
            @else
                <div class="text-xl font-black text-gray-800">{{ $daysUntilExpiry }}d</div>
                <div class="text-xs text-gray-500 font-medium">Còn {{ $expiresAt->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
</div>


<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
    <!-- Reading -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Reading</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng đọc hiểu</p>
            <x-button href="{{ route('skills.show', 'reading') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Listening -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Listening</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng nghe</p>
            <x-button href="{{ route('skills.show', 'listening') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Writing -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Writing</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng viết</p>
            <x-button href="{{ route('skills.show', 'writing') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Grammar & Vocabulary -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Grammar</h3>
            <p class="text-gray-600 text-sm mb-4">Ngữ pháp & Từ vựng</p>
            <x-button href="{{ route('grammar.index') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Speaking -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Speaking</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng nói</p>
            <x-button href="{{ route('skills.show', 'speaking') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>
</div>

<div class="mt-8 mb-8">
    <x-card title="Thống kê tiến độ luyện tập">
        <p class="text-gray-600 mb-6">Theo dõi sự tiến bộ điểm số của bạn qua các lần luyện tập và thi thử</p>
        <div class="relative h-80 w-full">
            <canvas id="progressChart"></canvas>
        </div>
    </x-card>
</div>

<div class="mt-8">
    <x-card title="Lịch sử làm bài">
        <p class="text-gray-600 mb-4">Xem lịch sử các bài thi và luyện tập của bạn theo từng kỹ năng</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-button href="{{ route('history.index') }}" variant="secondary" class="flex-1 justify-center flex items-center gap-2">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Trắc nghiệm & Ngữ pháp
            </x-button>
            <x-button href="{{ route('writingHistory.index') }}" variant="secondary" class="flex-1 justify-center flex items-center gap-2">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Tự luận (Writing)
            </x-button>
            <x-button href="{{ route('leaderboard.index') }}" variant="secondary" class="flex-1 justify-center flex items-center gap-2">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                🏆 Leaderboard
            </x-button>
        </div>
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Use injected statistics data directly
        const result = @json($statisticsData);
        
        try {

            // Extract all unique dates for the X-axis labels to align the different datasets
            const allDates = new Set();
            ['reading', 'listening', 'writing', 'grammar', 'speaking', 'mock_test'].forEach(skill => {
                if (result[skill]) {
                    result[skill].forEach(dataPoint => allDates.add(dataPoint.date));
                }
            });

            // Sort dates chronologically (assuming DD/MM format)
            const sortedDates = Array.from(allDates).sort((a, b) => {
                // a and b are like '23/02'
                try {
                    const [dayA, monthA] = a.split('/');
                    const [dayB, monthB] = b.split('/');
                    
                    const valA = new Date(`2024-${monthA}-${dayA}T00:00:00`);
                    const valB = new Date(`2024-${monthB}-${dayB}T00:00:00`);
                    return valA - valB;
                } catch (e) {
                    return 0; // fallback if parsing fails
                }
            });

            if (sortedDates.length === 0) {
                document.getElementById('progressChart').parentNode.innerHTML = '<p class="text-center text-gray-500 mt-20">Chưa có dữ liệu thống kê. Hãy bắt đầu luyện tập!</p>';
                return;
            }

            // Helper to map sparse data to aligned dates
            const mapDataToDates = (skillData) => {
                if (!skillData) return sortedDates.map(() => null);
                return sortedDates.map(date => {
                    const found = skillData.find(d => d.date === date);
                    return found ? found.score : null; // null keeps the line continuous in Chart.js using spanGaps
                });
            };

            const ctx = document.getElementById('progressChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: sortedDates,
                    datasets: [
                        {
                            label: 'Reading Practice',
                            data: mapDataToDates(result.reading),
                            borderColor: '#3b82f6', // blue-500
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true
                        },
                        {
                            label: 'Listening Practice',
                            data: mapDataToDates(result.listening),
                            borderColor: '#10b981', // green-500
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true
                        },
                        {
                            label: 'Writing Practice',
                            data: mapDataToDates(result.writing),
                            borderColor: '#8b5cf6', // purple-500
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true
                        },
                        {
                            label: 'Grammar Practice',
                            data: mapDataToDates(result.grammar),
                            borderColor: '#ec4899', // pink-500
                            backgroundColor: 'rgba(236, 72, 153, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true
                        },
                        {
                            label: 'Speaking Practice',
                            data: mapDataToDates(result.speaking),
                            borderColor: '#f97316', // orange-500
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            spanGaps: true
                        },
                        {
                            label: 'Mock Test (Full)',
                            data: mapDataToDates(result.mock_test),
                            borderColor: '#f59e0b', // amber-500
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            tension: 0.3,
                            spanGaps: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += Math.round(context.parsed.y) + '%';
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    // Find the raw data object to get the parts information
                                    const datasetIndex = context.datasetIndex;
                                    const dataIndex = context.dataIndex;
                                    const dateLabel = context.chart.data.labels[dataIndex];
                                    
                                    // Determine which skill array to look up
                                    let skillKey = '';
                                    if (datasetIndex === 0) skillKey = 'reading';
                                    else if (datasetIndex === 1) skillKey = 'listening';
                                    else if (datasetIndex === 2) skillKey = 'writing';
                                    else if (datasetIndex === 3) skillKey = 'grammar';
                                    else if (datasetIndex === 4) skillKey = 'speaking';
                                    else if (datasetIndex === 5) skillKey = 'mock_test';
                                    
                                    const skillData = result[skillKey] || [];
                                    const originalPoint = skillData.find(d => d.date === dateLabel);
                                    
                                    if (originalPoint && originalPoint.parts && Object.keys(originalPoint.parts).length > 0) {
                                        let partsText = [];
                                        partsText.push('--- Chi tiết ---');
                                        for (const [part, score] of Object.entries(originalPoint.parts)) {
                                            partsText.push(`Part ${part}: ${score}% đúng`);
                                        }
                                        return partsText;
                                    }
                                    return '';
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    });
</script>
@endpush
