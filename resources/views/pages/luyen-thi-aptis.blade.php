@extends('layouts.marketing')

@php
    $gv = config('seo.instructor');
    $faqs = [
        ['Aptis gồm những kỹ năng nào?', 'Aptis đánh giá 4 kỹ năng Nghe, Đọc, Viết, Nói cùng phần Ngữ pháp & Từ vựng, quy đổi theo khung CEFR (A1–C).'],
        ['Luyện thi Aptis online có hiệu quả không?', 'Có, nếu bạn luyện đúng format và được chấm chữa. Milaedu cung cấp đề thi thử sát thật và chấm chữa Writing chi tiết để bạn biết cần cải thiện gì.'],
        ['Bao lâu thì thi được Aptis?', 'Tùy trình độ và mục tiêu điểm. Việc luyện đề đều đặn và sửa lỗi theo phản hồi sẽ rút ngắn thời gian đáng kể.'],
        ['Bài Writing được chấm thế nào?', 'Bài Writing được chấm theo tiêu chí Aptis và chữa từng lỗi, kèm gợi ý cải thiện cụ thể.'],
    ];
@endphp

@section('title', 'Luyện thi Aptis online — Đề thi thử & chấm chữa 4 kỹ năng')
@section('meta_description', 'Luyện thi Aptis online cùng Milaedu: đề thi thử sát thật 4 kỹ năng Nghe, Đọc, Viết, Nói và chấm chữa Writing chi tiết. Lộ trình bám sát mục tiêu điểm.')
@section('meta_keywords', 'luyện thi Aptis, luyện thi Aptis online, ôn thi Aptis, thi thử Aptis, Aptis Speaking, Aptis Writing, luyện thi Aptis cùng ' . $gv['name'])
@section('og_type', 'article')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn ($f) => [
        '@type' => 'Question',
        'name'  => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
    ])->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-b from-slate-50 to-white border-b border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-tight">
            Luyện thi Aptis online cùng Milaedu
        </h1>
        <p class="mt-5 text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
            Đề thi thử sát thật cho cả 4 kỹ năng và <strong>chấm chữa Writing</strong> chi tiết —
            luyện đúng cách để tiến bộ thật, bám sát mục tiêu điểm của bạn.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-8 py-4 rounded-xl bg-blue-700 text-white font-bold hover:bg-blue-800 transition-colors">Xem bảng giá &amp; đăng ký</a>
            <a href="{{ route('about') }}" class="inline-flex justify-center items-center px-8 py-4 rounded-xl bg-white text-slate-700 font-bold ring-1 ring-slate-200 hover:bg-slate-50 transition-colors">Về giảng viên</a>
        </div>
    </div>
</section>

{{-- 4 kỹ năng --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-8 text-center">Luyện đủ 4 kỹ năng Aptis</h2>
    <div class="grid sm:grid-cols-2 gap-6">
        @foreach([
            ['Listening (Nghe)', 'Luyện nghe theo đúng dạng câu hỏi Aptis, làm quen tốc độ và giọng đọc thật.'],
            ['Reading (Đọc)', 'Đề đọc sát format, rèn kỹ năng đọc lướt và đọc lấy ý chính trong thời gian giới hạn.'],
            ['Writing (Viết)', 'Luyện 4 phần viết và được chấm chữa từng lỗi ngữ pháp, từ vựng, mạch lạc.'],
            ['Speaking (Nói)', 'Luyện nói theo từng phần để cải thiện phát âm, ý tưởng và độ trôi chảy.'],
        ] as $skill)
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-2">{{ $skill[0] }}</h3>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $skill[1] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- FAQ --}}
<section class="bg-slate-50 border-y border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-8 text-center">Câu hỏi thường gặp</h2>
        <div class="space-y-4" x-data="{ open: 0 }">
            @foreach($faqs as $i => $f)
                <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-slate-900">
                        <span>{{ $f[0] }}</span>
                        <svg class="w-5 h-5 text-slate-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         class="px-5 pb-4 text-slate-600 leading-relaxed">{{ $f[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white px-8 py-12 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold mb-3">Sẵn sàng luyện thi Aptis?</h2>
        <p class="text-blue-100 mb-6 max-w-xl mx-auto">Đăng ký để bắt đầu với đề thi thử sát thật và chấm chữa chi tiết.</p>
        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 rounded-xl bg-white text-blue-700 font-bold hover:bg-blue-50 transition-colors">Đăng ký ngay</a>
    </div>
</section>
@endsection
