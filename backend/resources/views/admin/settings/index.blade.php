@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống')

@section('content')
<div class="max-w-4xl mx-auto pb-12">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Cài đặt hệ thống
        </h1>
        <p class="text-sm text-gray-500 mt-1">Quản lý các thông số cấu hình chung của ứng dụng.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg shadow-sm flex items-center justify-between" x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-green-800 font-medium">{{ session('success') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-green-600 hover:text-green-800 text-xl font-bold p-1 leading-none">&times;</button>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">Liên hệ hỗ trợ / Đăng ký</h2>
        </div>
        
        <form action="{{ route('admin.settings.update') }}" method="POST" class="p-6">
            @csrf
            
            <div class="mb-6">
                <label for="zalo_contact_number" class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại Zalo (Admin) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                    </div>
                    <input type="text" id="zalo_contact_number" name="zalo_contact_number" 
                        value="{{ old('zalo_contact_number', $zaloSetting->value ?? '') }}" 
                        class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="VD: 0886160515" required>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Học sinh khi ấn "Đăng ký" trên web sẽ tự động chuyển hướng sang mục liên hệ qua ứng dụng Zalo tới số điện thoại này.
                </p>
                @error('zalo_contact_number')
                    <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Grading Limits Section -->
            <div class="px-6 py-4 bg-gray-50 border-y border-gray-200 -mx-6 mb-6 mt-8">
                <h2 class="text-lg font-bold text-gray-800">Giới hạn Yêu cầu Chấm điểm</h2>
                <p class="text-sm text-gray-500 mt-1">Cấu hình số lần tối đa học viên được gửi bài cho Giáo viên chấm điểm đối với từng Kỹ năng.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Writing Limit -->
                <div>
                    <label for="writing_grading_limit" class="block text-sm font-semibold text-gray-700 mb-2">Giới hạn gửi bài Writing (lần) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <input type="number" id="writing_grading_limit" name="writing_grading_limit" 
                            value="{{ old('writing_grading_limit', $writingLimitSetting->value ?? 2) }}" min="1" max="100"
                            class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            required>
                    </div>
                    @error('writing_grading_limit')
                        <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Speaking Limit -->
                <div>
                    <label for="speaking_grading_limit" class="block text-sm font-semibold text-gray-700 mb-2">Giới hạn gửi bài Speaking (lần) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </div>
                        <input type="number" id="speaking_grading_limit" name="speaking_grading_limit" 
                            value="{{ old('speaking_grading_limit', $speakingLimitSetting->value ?? 2) }}" min="1" max="100"
                            class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            required>
                    </div>
                    @error('speaking_grading_limit')
                        <p class="text-red-500 text-sm mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-sm transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Lưu cài đặt
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
