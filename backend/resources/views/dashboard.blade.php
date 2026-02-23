@extends('layouts.app')

@section('title', 'Dashboard - APTIS Practice')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Chọn kỹ năng luyện tập</h1>
    <p class="mt-2 text-gray-600">Chọn một trong ba kỹ năng để bắt đầu luyện tập</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                Trắc nghiệm (Reading / Listening)
            </x-button>
            <x-button href="{{ route('writingHistory.index') }}" variant="secondary" class="flex-1 justify-center flex items-center gap-2">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Tự luận (Writing)
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
            ['reading', 'listening', 'writing', 'mock_test'].forEach(skill => {
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
                                    else if (datasetIndex === 3) skillKey = 'mock_test';
                                    
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
