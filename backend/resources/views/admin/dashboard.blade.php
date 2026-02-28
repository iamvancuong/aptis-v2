@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Tổng Quan Hệ Thống</h1>
        <p class="text-gray-500 mt-1">Kiểm soát hoạt động luyện thi APTIS của học viên</p>
    </div>
    <div class="mt-4 md:mt-0 flex gap-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition shadow-sm">
            🖥️ View as Student
        </a>
    </div>
</div>

<!-- TOP STATS -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <x-card class="border-l-4 border-l-blue-500 shadow-sm relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <svg class="w-16 h-16 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Tổng Học Viên</p>
            <p class="text-4xl font-black text-gray-800">{{ $totalUsers }}</p>
        </div>
        <div class="mt-4 text-sm">
            <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                Quản lý users <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </x-card>

    <x-card class="border-l-4 border-l-emerald-500 shadow-sm relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <svg class="w-16 h-16 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Lượt Thi Mock Test</p>
            <p class="text-4xl font-black text-gray-800">{{ $mockTestStats['total'] }}</p>
        </div>
        <div class="mt-4 flex items-center text-sm gap-3">
            <span class="text-emerald-600 font-semibold">{{ $mockTestStats['completed'] }} hoàn thành</span>
            <span class="text-amber-500 font-semibold">{{ $mockTestStats['in_progress'] }} đang thi</span>
        </div>
    </x-card>

    <x-card class="border-l-4 border-l-amber-500 shadow-sm relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <svg class="w-16 h-16 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Bài Viết Chờ Chấm</p>
            <p class="text-4xl font-black {{ $pendingWritings > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $pendingWritings }}</p>
        </div>
        <div class="mt-4 text-sm">
            @if($pendingWritings > 0)
                <a href="{{ route('admin.writing-reviews.index') }}" class="text-amber-600 hover:text-amber-800 font-medium inline-flex items-center">
                    Chấm bài ngay <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </a>
            @else
                <span class="text-gray-400">Không có bài tồn đọng</span>
            @endif
        </div>
    </x-card>

    <x-card class="border-l-4 border-l-rose-500 shadow-sm relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <svg class="w-16 h-16 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Thi Speaking</p>
            <p class="text-4xl font-black {{ $pendingSpeaking > 0 ? 'text-rose-600' : 'text-gray-800' }}">{{ $pendingSpeaking }}</p>
        </div>
        <div class="mt-4 text-sm">
            @if($pendingSpeaking > 0)
                <a href="{{ route('admin.speaking-reviews.index') }}" class="text-rose-600 hover:text-rose-800 font-medium inline-flex items-center">
                    Chấm bài ngay <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </a>
            @else
                <span class="text-gray-400">Không có bài tồn đọng</span>
            @endif
        </div>
    </x-card>

    <x-card class="border-l-4 border-l-purple-500 shadow-sm relative overflow-hidden group">
         <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <svg class="w-16 h-16 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Duyệt AI Hàng Loạt</p>
            <p class="text-4xl font-black {{ $aiGradedWritings > 0 ? 'text-purple-600' : 'text-gray-800' }}">{{ $aiGradedWritings }}</p>
        </div>
        <div class="mt-4 text-sm">
            @if($aiGradedWritings > 0)
                <a href="{{ route('admin.writing-reviews.index') }}" class="text-purple-600 hover:text-purple-800 font-medium inline-flex items-center">
                    Vào duyệt điểm <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </a>
            @else
                <span class="text-gray-400">All clear</span>
            @endif
        </div>
    </x-card>
</div>

<!-- MAIN CONTENT GRID -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- LATEST MOCK TESTS -->
    <div class="lg:col-span-2">
        <x-card class="h-full">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Mock Test Vừa Nộp
                </h3>
                <a href="{{ route('admin.mock-tests.index') }}" class="text-sm font-medium text-blue-600 hover:underline">Xem tất cả</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="pb-3 px-2 font-semibold">Tài khoản</th>
                            <th class="pb-3 px-2 font-semibold">Skill</th>
                            <th class="pb-3 px-2 font-semibold text-right">Điểm</th>
                            <th class="pb-3 px-2 font-semibold text-right">Lúc nộp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentMockTests as $mock)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-2 font-medium text-gray-800">
                                    {{ $mock->user->name ?? 'Unknown' }}
                                    <div class="text-xs text-gray-400 font-normal">{{ $mock->user->email ?? '' }}</div>
                                </td>
                                <td class="py-3 px-2">
                                    <span class="px-2 py-1 bg-{{ $mock->skill === 'reading' ? 'blue' : ($mock->skill === 'listening' ? 'amber' : 'fuchsia') }}-100 text-{{ $mock->skill === 'reading' ? 'blue' : ($mock->skill === 'listening' ? 'amber' : 'fuchsia') }}-800 rounded text-xs font-semibold uppercase tracking-wider">
                                        {{ $mock->skill }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-right">
                                    @php
                                        $totalArr = config("aptis.mock_test.{$mock->skill}");
                                        $totalQ = $totalArr ? array_sum(array_column($totalArr['parts'], 'questions')) : 0;
                                        $scores = $mock->section_scores ?? [];
                                        $earned = array_sum(array_column($scores, 'score'));
                                        $scorePct = $totalQ > 0 ? round(($earned / $totalQ) * 100) : 0;
                                    @endphp
                                    <span class="font-bold text-gray-900">{{ number_format($scorePct) }}%</span>
                                </td>
                                <td class="py-3 px-2 text-right text-xs text-gray-500">
                                    {{ $mock->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-400">Chưa có bài thi nào hoàn thành.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- EXPIRING USERS & QUICK LINKS -->
    <div class="flex flex-col gap-6">
        <x-card>
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Tài Khoản Sắp Hết Hạn
            </h3>
            <div class="space-y-3">
                @forelse($expiringUsers as $user)
                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-100">
                        <div>
                            <p class="font-medium text-sm text-gray-800">{{ $user->name }}</p>
                            <p class="text-xs text-red-600 font-semibold mt-0.5">Còn {{ $user->daysUntilExpiration() }} ngày</p>
                        </div>
                        <form action="{{ route('admin.users.extend-expiration', $user) }}" method="POST">
                            @csrf
                            <input type="hidden" name="days" value="30">
                            <button type="submit" class="px-2 py-1 bg-white border border-red-200 text-red-700 hover:bg-red-100 rounded text-xs font-semibold shadow-sm transition">
                                +30d
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-sm text-gray-400 py-4">Tất cả tài khoản đều ổn định.</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
             <h3 class="text-lg font-bold text-gray-800 mb-4">Các Chức Năng Chính</h3>
             <div class="grid grid-cols-2 gap-3">
                 <a href="{{ route('admin.reports.index') }}" class="p-3 border border-gray-200 rounded-lg text-center hover:bg-gray-50 hover:border-blue-300 transition group">
                     <svg class="w-6 h-6 mx-auto text-gray-400 group-hover:text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                     <span class="text-xs font-semibold text-gray-600 group-hover:text-blue-700">Xem Báo Cáo Lớp</span>
                 </a>
                 <a href="{{ route('admin.writing-sets.index') }}" class="p-3 border border-gray-200 rounded-lg text-center hover:bg-gray-50 hover:border-amber-300 transition group">
                     <svg class="w-6 h-6 mx-auto text-gray-400 group-hover:text-amber-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                     <span class="text-xs font-semibold text-gray-600 group-hover:text-amber-700">Đề Thi Writing</span>
                 </a>
                  <a href="{{ route('admin.sets.index') }}" class="p-3 border border-gray-200 rounded-lg text-center hover:bg-gray-50 hover:border-cyan-300 transition group">
                     <svg class="w-6 h-6 mx-auto text-gray-400 group-hover:text-cyan-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                     <span class="text-xs font-semibold text-gray-600 group-hover:text-cyan-700">Đề Reading / Listening</span>
                 </a>
                 <a href="{{ route('admin.users.index') }}" class="p-3 border border-gray-200 rounded-lg text-center hover:bg-gray-50 hover:border-emerald-300 transition group">
                     <svg class="w-6 h-6 mx-auto text-gray-400 group-hover:text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                     <span class="text-xs font-semibold text-gray-600 group-hover:text-emerald-700">Quản lý Tài Khoản</span>
                 </a>
             </div>
        </x-card>
    </div>
</div>
@endsection
