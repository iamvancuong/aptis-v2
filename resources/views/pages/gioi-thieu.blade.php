@extends('layouts.marketing')

@php $gv = config('seo.instructor'); @endphp

@section('title', 'Giới thiệu — Luyện thi Aptis cùng ' . $gv['name'])
@section('meta_description', 'Tìm hiểu về Milaedu và ' . $gv['name'] . ' — ' . $gv['job_title'] . '. Luyện thi Aptis online với đề thi thử sát thật và chấm chữa Writing, Speaking chi tiết.')
@section('meta_keywords', $gv['name'] . ' Aptis, luyện thi Aptis cùng ' . $gv['name'] . ', giảng viên Aptis, luyện thi Aptis online, Milaedu')
@section('og_type', 'article')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'AboutPage',
    'name'     => 'Giới thiệu Milaedu',
    'url'      => rtrim(config('app.url'), '/') . '/gioi-thieu',
    'about'    => [
        '@type'       => 'Person',
        'name'        => $gv['name'],
        'jobTitle'    => $gv['job_title'],
        'description' => $gv['bio'],
        'knowsAbout'  => ['Aptis', 'Aptis Speaking', 'Aptis Writing', 'Luyện thi Aptis'],
    ],
    'breadcrumb' => [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => rtrim(config('app.url'), '/') . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Giới thiệu', 'item' => rtrim(config('app.url'), '/') . '/gioi-thieu'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-b from-slate-50 to-white border-b border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
        <p class="text-sm font-bold tracking-widest uppercase text-blue-700 mb-3">Về chúng tôi</p>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-tight">
            Luyện thi Aptis cùng {{ $gv['name'] }} tại Milaedu
        </h1>
        <p class="mt-5 text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
            Milaedu là nền tảng luyện thi Aptis online, tập trung vào điều quan trọng nhất với người học:
            luyện đúng format và được <strong>chấm chữa Writing &amp; Speaking</strong> chi tiết để tiến bộ thật.
        </p>
    </div>
</section>

{{-- Về giảng viên --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid md:grid-cols-3 gap-10 items-start">
        <div class="md:col-span-1">
            <div class="aspect-square rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white flex items-center justify-center text-6xl font-extrabold shadow-lg">
                {{ \Illuminate\Support\Str::upper(mb_substr(\Illuminate\Support\Str::afterLast(trim($gv['name']), ' '), 0, 1)) }}
            </div>
            <p class="mt-4 text-center font-bold text-slate-900 text-lg">{{ $gv['name'] }}</p>
            <p class="text-center text-sm text-slate-500">{{ $gv['job_title'] }}</p>
        </div>
        <div class="md:col-span-2">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-4">Về giảng viên</h2>
            <p class="text-slate-600 leading-relaxed mb-6">{{ $gv['bio'] }}</p>
            <ul class="space-y-3">
                @foreach([
                    'Trực tiếp chấm chữa bài Writing &amp; Speaking, chỉ ra lỗi và cách sửa cụ thể.',
                    'Bám sát format Aptis 4 kỹ năng: Reading, Listening, Writing, Speaking.',
                    'Lộ trình theo mục tiêu điểm của từng học viên.',
                ] as $item)
                    <li class="flex items-start gap-3 text-slate-700">
                        <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{!! $item !!}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- Về Milaedu --}}
<section class="bg-slate-50 border-y border-slate-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-8 text-center">Milaedu giúp bạn luyện Aptis thế nào?</h2>
        <div class="grid sm:grid-cols-3 gap-6">
            @foreach([
                ['Đề thi thử sát thật', 'Mô phỏng đúng format Aptis 4 kỹ năng, làm bài như thi thật.'],
                ['Chấm chữa chi tiết', 'Writing &amp; Speaking được chấm và chữa từng lỗi, kèm gợi ý cải thiện.'],
                ['Học mọi lúc, mọi nơi', 'Luyện tập online trên máy tính hoặc điện thoại, theo dõi tiến độ.'],
            ] as $card)
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-2">{{ $card[0] }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{!! $card[1] !!}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Aptis là gì --}}
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-4">Kỳ thi Aptis là gì?</h2>
    <p class="text-slate-600 leading-relaxed mb-4">
        Aptis là bài thi đánh giá năng lực tiếng Anh của Hội đồng Anh (British Council), gồm bốn kỹ năng
        <strong>Nghe, Đọc, Viết, Nói</strong> cùng phần Ngữ pháp &amp; Từ vựng. Kết quả quy đổi theo khung
        tham chiếu châu Âu (CEFR) từ A1 đến C. Aptis được nhiều trường và cơ quan sử dụng để xét chuẩn đầu ra tiếng Anh.
    </p>
    <p class="text-slate-600 leading-relaxed">
        Với Milaedu, bạn luyện đúng dạng bài của từng kỹ năng và được chấm chữa để biết mình đang ở đâu và cần cải thiện gì.
    </p>
</section>

{{-- CTA --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white px-8 py-12 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold mb-3">Bắt đầu luyện thi Aptis hôm nay</h2>
        <p class="text-blue-100 mb-6 max-w-xl mx-auto">Chọn gói phù hợp và luyện tập cùng đề thi thử sát thật, có chấm chữa.</p>
        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 rounded-xl bg-white text-blue-700 font-bold hover:bg-blue-50 transition-colors">
            Xem bảng giá &amp; đăng ký
        </a>
    </div>
</section>
@endsection
