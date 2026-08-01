@extends('layouts.app')

@section('title', 'Lớp học online - Milaedu')

@section('content')
{{-- Không cần nhánh "tài khoản hết hạn": middleware CheckAccountExpiration (áp
     cho mọi route web) đã logout người hết hạn trước khi tới được trang này. --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Lớp học online</h1>
    <p class="mt-2 text-gray-600">Nút “Vào lớp” bật trước giờ học {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút.</p>
</div>

<div class="space-y-4">
    @forelse($sessions as $session)
        <div class="bg-white rounded-xl shadow-sm border {{ $session->isLive() ? 'border-green-300' : 'border-gray-200' }} p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $session->title }}</h2>
                        @if($session->isLive())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 shrink-0">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                Đang diễn ra
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }},
                        {{ $session->starts_at->format('d/m/Y') }}
                    </p>
                    @if($session->description)
                        <p class="mt-2 text-sm text-gray-500">{{ $session->description }}</p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if($session->isJoinable())
                        {{-- Link trỏ tới route join, KHÔNG phải link Meet: hệ thống kiểm tra rồi mới chuyển. --}}
                        <a href="{{ route('classes.join', $session) }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Vào lớp
                        </a>
                    @else
                        <span class="inline-block px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg">
                            Mở lúc {{ $session->joinOpensAt()->format('H:i d/m') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <svg class="w-12 h-12 text-gray-300 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <p class="text-sm font-medium text-gray-500">Chưa có buổi học nào sắp diễn ra.</p>
            <p class="text-xs text-gray-400 mt-1">Lịch học sẽ hiện ở đây khi giảng viên mở lớp.</p>
        </div>
    @endforelse
</div>
@endsection
