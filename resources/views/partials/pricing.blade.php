@php
    $features = [
        'Thi thử full 4 kỹ năng + Grammar',
        'Chấm chữa Writing & Speaking chi tiết',
        'Chấm Writing bằng AI ngay tức thì',
        'Mua nhiều — cộng dồn thời hạn',
    ];
@endphp
<section id="pricing" class="py-20 sm:py-24 bg-slate-50 border-y border-slate-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <p class="text-sm font-bold tracking-widest uppercase text-blue-700 mb-3">Bảng giá</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900">Chọn gói phù hợp với bạn</h2>
            <p class="mt-4 text-slate-600">Giá tuyến tính, minh bạch. Mua nhiều gói được cộng dồn thời hạn, không ràng buộc.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-6 max-w-3xl mx-auto items-stretch">
            @foreach(config('pricing.packages') as $key => $p)
                @php $pop = $p['popular'] ?? false; @endphp
                <div class="relative rounded-2xl bg-white p-8 flex flex-col {{ $pop ? 'border-2 border-blue-600 shadow-xl' : 'border border-slate-200 shadow-sm' }}">
                    @if($pop)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-700 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Phổ biến</span>
                    @endif

                    <h3 class="font-bold text-slate-900 text-lg">{{ $p['label'] }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $p['days'] }} ngày học</p>

                    <div class="mt-5 mb-6">
                        <span class="text-4xl font-extrabold text-slate-900">{{ number_format($p['price'], 0, ',', '.') }}đ</span>
                        <span class="text-slate-500">/ {{ $p['unit'] }}</span>
                    </div>

                    <ul class="space-y-3 text-sm text-slate-600 mb-8">
                        @foreach($features as $f)
                            <li class="flex items-start gap-2.5">
                                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('register', ['goi' => $key]) }}" data-turbo="false"
                       class="mt-auto text-center py-3 rounded-xl font-bold transition-colors {{ $pop ? 'bg-blue-700 text-white hover:bg-blue-800' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' }}">
                        Chọn gói này
                    </a>
                </div>
            @endforeach
        </div>

        <p class="text-center text-xs text-slate-400 mt-8">
            <a href="{{ route('policy.refund') }}" class="underline hover:text-blue-700">Chính sách không hoàn tiền</a>
        </p>
    </div>
</section>
