@extends('layouts.marketing')

@section('title', 'Luyện thi Aptis online có chấm chữa Writing & Speaking')
@section('meta_description', 'Luyện thi Aptis online cùng Milaedu: đề thi thử sát thật 4 kỹ năng, chấm chữa Writing & Speaking chi tiết, lộ trình bám sát mục tiêu điểm. Học mọi lúc, mọi nơi.')

@push('head')
    @include('partials.structured-data')
@endpush

@section('content')
{{-- ===================== HERO ===================== --}}
<section class="relative overflow-hidden bg-gradient-to-b from-slate-50 to-white lg:landscape:min-h-[calc(100vh-4rem)] flex items-center">
    <div class="absolute top-0 right-0 -mt-24 -mr-24 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob" aria-hidden="true"></div>
    <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000" aria-hidden="true"></div>

    <div class="relative w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            {{-- Trái --}}
            <div class="text-center lg:text-left">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold mb-6 ring-1 ring-inset ring-blue-600/20">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    Luyện thi Aptis online
                </span>
                <h1 class="text-4xl sm:text-5xl font-extrabold text-slate-900 leading-[1.1] tracking-tight">
                    Chinh phục <span class="text-blue-700">Aptis</span> với đề thi thử sát thật &amp; chấm chữa
                </h1>
                <p class="mt-5 text-lg text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Luyện đủ 4 kỹ năng và được chấm chữa <strong>Writing &amp; Speaking</strong> chi tiết để tiến bộ thật, bám sát mục tiêu điểm của bạn.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ route('dashboard') }}" data-turbo="false" class="inline-flex justify-center items-center px-7 py-3.5 rounded-xl bg-blue-700 text-white font-bold hover:bg-blue-800 transition-colors">Tiếp tục học</a>
                    @else
                        <a href="{{ route('register') }}" data-turbo="false" class="inline-flex justify-center items-center px-7 py-3.5 rounded-xl bg-blue-700 text-white font-bold hover:bg-blue-800 transition-colors">Xem bảng giá &amp; đăng ký</a>
                    @endauth
                    <a href="{{ route('aptis') }}" class="inline-flex justify-center items-center px-7 py-3.5 rounded-xl bg-white text-slate-700 font-bold ring-1 ring-slate-200 hover:bg-slate-50 transition-colors">Tìm hiểu Aptis</a>
                </div>
                <div class="mt-8 flex items-center justify-center lg:justify-start gap-6 text-sm text-slate-500 font-medium">
                    <span class="flex items-center gap-2"><svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Đề thi thử sát thật</span>
                    <span class="flex items-center gap-2"><svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Chấm chữa chi tiết</span>
                </div>
            </div>

            {{-- Phải: mockup bảng chấm Writing --}}
            <div class="relative hidden md:block">
                <div class="relative mx-auto max-w-[480px] rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border-b border-slate-100">
                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="ml-3 text-xs font-semibold text-slate-500">Milaedu · Chấm Writing với AI</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Kết quả tổng</p>
                                <p class="text-3xl font-extrabold text-slate-900 mt-1">Band C1</p>
                                <p class="text-sm text-slate-500 mt-1">Writing · Task 4</p>
                            </div>
                            <div class="relative w-20 h-20 shrink-0">
                                <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#e2e8f0" stroke-width="3.5"></circle>
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#2563eb" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="86 100"></circle>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-lg font-extrabold text-slate-800">8.6</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            @foreach(['Ngữ pháp' => 90, 'Từ vựng' => 86, 'Mạch lạc' => 82, 'Hoàn thành yêu cầu' => 88] as $label => $pct)
                                <div>
                                    <div class="flex justify-between text-xs font-medium text-slate-500 mb-1">
                                        <span>{{ $label }}</span><span>{{ $pct }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 rounded-xl bg-blue-50/70 ring-1 ring-blue-100 p-4">
                            <p class="text-xs font-bold text-blue-700 mb-1">Nhận xét của giáo viên · AI</p>
                            <p class="text-sm text-slate-600 leading-relaxed">Bài viết mạch lạc, từ vựng học thuật tốt. Cần chú ý thì của động từ ở đoạn 2 và đa dạng hoá cấu trúc câu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== VÌ SAO CHỌN MILAEDU ===================== --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <p class="text-sm font-bold tracking-widest uppercase text-blue-700 mb-3">Vì sao chọn Milaedu</p>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900">Luyện đúng cách, tiến bộ thật</h2>
    </div>
    <div class="grid sm:grid-cols-3 gap-6">
        @php
            $features = [
                ['d' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title' => 'Đề thi thử sát thật', 'desc' => 'Mô phỏng đúng format Aptis 4 kỹ năng — làm bài như thi thật, quen áp lực thời gian.'],
                ['d' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'title' => 'Chấm chữa chi tiết', 'desc' => 'Writing & Speaking được chấm và chữa từng lỗi, kèm gợi ý cải thiện cụ thể.'],
                ['d' => 'M13 10V3L4 14h7v7l9-11h-7z', 'title' => 'Chấm Writing bằng AI tức thì', 'desc' => 'Nộp bài Writing, nhận điểm và gợi ý cải thiện từ AI ngay lập tức — luyện đến đâu biết đến đó.'],
            ];
        @endphp
        @foreach($features as $f)
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['d'] }}"/></svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ===================== VỀ GIẢNG VIÊN (kín đáo) ===================== --}}
<section class="bg-slate-50 border-y border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <a href="{{ route('about') }}" class="flex flex-col sm:flex-row items-center gap-6 text-center sm:text-left group">
            <span class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white text-3xl font-extrabold flex items-center justify-center shrink-0">
                {{ \Illuminate\Support\Str::upper(mb_substr(\Illuminate\Support\Str::afterLast(trim(config('seo.instructor.name')), ' '), 0, 1)) }}
            </span>
            <div class="flex-1">
                <h2 class="text-xl font-extrabold text-slate-900">Luyện thi Aptis cùng {{ config('seo.instructor.name') }}</h2>
                <p class="text-slate-600 mt-1">{{ config('seo.instructor.bio') }}</p>
                <span class="inline-block mt-2 text-sm font-semibold text-blue-700 group-hover:underline">Tìm hiểu thêm về giảng viên →</span>
            </div>
        </a>
    </div>
</section>

{{-- ===================== CỘNG ĐỒNG ===================== --}}
@if(config('seo.contact.facebook'))
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-50 via-white to-indigo-50 ring-1 ring-slate-200/70 px-6 sm:px-12 py-14">
        <div class="relative grid md:grid-cols-2 gap-8 md:gap-10 items-center">
            <div>
                <p class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 mb-4">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>Cộng đồng Milaedu
                </p>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">Bạn không luyện thi một mình</h2>
                <p class="mt-4 text-slate-600 leading-relaxed max-w-xl">
                    Tham gia cộng đồng Milaedu trên Facebook — nơi những người đang ngày ngày cố gắng vì mục tiêu Aptis
                    cùng chia sẻ tài liệu, hỏi đáp, động viên nhau và ăn mừng từng cột mốc tiến bộ.
                    Có người đồng hành, hành trình ôn thi nhẹ nhàng hơn rất nhiều.
                </p>
                <a href="{{ config('seo.contact.facebook') }}" target="_blank" rel="noopener noreferrer"
                   class="mt-7 inline-flex items-center gap-2.5 px-7 py-3.5 rounded-xl bg-blue-600 text-white font-semibold shadow-sm hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Tham gia cộng đồng Facebook
                </a>
            </div>
            <ul class="space-y-3">
                @foreach([
                    ['Chia sẻ tài liệu & kinh nghiệm', 'Tài liệu, mẹo làm bài và lộ trình ôn được chia sẻ mỗi ngày.'],
                    ['Hỏi đáp cùng người học khác', 'Vướng ở đâu cứ hỏi — luôn có người sẵn sàng giải đáp.'],
                    ['Động viên nhau mỗi ngày', 'Cùng giữ động lực, không ai bỏ cuộc giữa chừng.'],
                ] as $item)
                    <li class="flex items-start gap-4 rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm p-4">
                        <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div>
                            <p class="font-bold text-slate-900">{{ $item[0] }}</p>
                            <p class="text-sm text-slate-500 mt-0.5">{{ $item[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
@endif

{{-- ===================== BẢNG GIÁ ===================== --}}
@include('partials.pricing')

{{-- ===================== CẢM NHẬN HỌC VIÊN ===================== --}}
@if($feedbacks->count())
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <p class="text-sm font-bold tracking-widest uppercase text-blue-700 mb-3">Cảm nhận học viên</p>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900">
            Chia sẻ từ <span class="relative inline-block">
                <span class="absolute inset-x-0 bottom-1 h-3 bg-blue-200/60 rounded"></span>
                <span class="relative">người thật</span>
            </span> việc thật
        </h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($feedbacks as $feedback)
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col">
                <div class="flex text-amber-400 gap-0.5 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 {{ $i < ($feedback->rating ?? 5) ? 'fill-current' : 'fill-current text-slate-200' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-slate-600 leading-relaxed italic flex-1">"{{ $feedback->content }}"</p>
                <div class="flex items-center gap-3 mt-5 pt-5 border-t border-slate-100">
                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center shrink-0">{{ mb_substr($feedback->name, 0, 1) }}</span>
                    <span class="font-bold text-slate-900">{{ $feedback->name }}</span>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ===================== CTA ===================== --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white px-8 py-14 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold mb-3">Sẵn sàng chinh phục Aptis?</h2>
        <p class="text-blue-100 mb-7 max-w-xl mx-auto">Bắt đầu với đề thi thử sát thật và chấm chữa chi tiết ngay hôm nay.</p>
        <a href="{{ route('register') }}" data-turbo="false" class="inline-flex items-center px-8 py-4 rounded-xl bg-white text-blue-700 font-bold hover:bg-blue-50 transition-colors">
            Xem bảng giá &amp; đăng ký
        </a>
    </div>
</section>
@endsection
