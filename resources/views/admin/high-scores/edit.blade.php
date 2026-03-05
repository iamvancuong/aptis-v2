@extends('layouts.admin')

@section('title', 'Chỉnh sửa Thành tích - Admin')
@section('header', 'Chỉnh sửa Học viên Bảng vàng')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.high-scores.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Quay lại danh sách
        </a>
    </div>

    <form action="{{ route('admin.high-scores.update', $highScore) }}" method="POST">
        @csrf
        @method('PUT')
        
        <x-card title="Thông tin Thành tích">
            <div class="space-y-6">
                <x-input 
                    name="name" 
                    label="Tên học viên" 
                    value="{{ old('name', $highScore->name) }}" 
                    required 
                    placeholder="VD: Phan Văn A"
                    error="{{ $errors->first('name') }}"
                />

                <x-input 
                    name="certificate" 
                    label="Chứng chỉ / Điểm số" 
                    value="{{ old('certificate', $highScore->certificate) }}" 
                    required 
                    placeholder="VD: Aptis C hoặc 198/200"
                    error="{{ $errors->first('certificate') }}"
                />

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">URL Ảnh đại diện (Tuỳ chọn)</label>
                        @if($highScore->avatar)
                            <img src="{{ $highScore->avatar }}" alt="Current Avatar" class="w-8 h-8 rounded-full object-cover border border-gray-200">
                        @endif
                    </div>
                    <x-input 
                        type="url"
                        name="avatar" 
                        value="{{ old('avatar', $highScore->avatar) }}" 
                        placeholder="https://example.com/student.jpg"
                        error="{{ $errors->first('avatar') }}"
                    />
                    <p class="mt-1.5 text-xs text-gray-500">Dùng ảnh chụp chứng chỉ hoặc ảnh chân dung học viên.</p>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $highScore->is_active) ? 'checked' : '' }}
                           class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded transition-colors cursor-pointer">
                    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                        Hiển thị công khai trên Bảng vàng
                    </label>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end space-x-4 border-t border-gray-100 pt-6">
                <x-button :href="route('admin.high-scores.index')" class="bg-white border-gray-300 text-gray-700 hover:bg-gray-50">
                    Hủy bỏ
                </x-button>
                <x-button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Cập nhật Thành tích
                </x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
