@extends('layouts.admin')

@section('title', 'Thêm Feedback - Admin')
@section('header', 'Thêm Feedback Mới')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.feedback.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Quay lại danh sách
        </a>
    </div>

    <form action="{{ route('admin.feedback.store') }}" method="POST">
        @csrf
        
        <x-card title="Thông tin Feedback">
            <div class="space-y-6">
                <x-input 
                    name="name" 
                    label="Tên học viên" 
                    value="{{ old('name') }}" 
                    required 
                    placeholder="VD: Nguyễn Văn A"
                    error="{{ $errors->first('name') }}"
                />

                <x-input 
                    type="url"
                    name="avatar" 
                    label="URL Ảnh đại diện (Tuỳ chọn)" 
                    value="{{ old('avatar') }}" 
                    placeholder="https://example.com/avatar.jpg"
                    error="{{ $errors->first('avatar') }}"
                    hint="Bỏ trống hệ thống sẽ tự tạo avatar mặc định dựa trên chữ cái đầu của tên."
                />

                <x-select 
                    name="rating" 
                    label="Đánh giá sao" 
                    required
                >
                    <option value="5" {{ old('rating', 5) == 5 ? 'selected' : '' }}>5 Sao (★★★★★)</option>
                    <option value="4" {{ old('rating') == 4 ? 'selected' : '' }}>4 Sao (★★★★)</option>
                    <option value="3" {{ old('rating') == 3 ? 'selected' : '' }}>3 Sao (★★★)</option>
                    <option value="2" {{ old('rating') == 2 ? 'selected' : '' }}>2 Sao (★★)</option>
                    <option value="1" {{ old('rating') == 1 ? 'selected' : '' }}>1 Sao (★)</option>
                </x-select>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung đánh giá <span class="text-red-500">*</span></label>
                    <x-textarea 
                        name="content" 
                        rows="4" 
                        required 
                        placeholder="Trải nghiệm học tuyệt vời..."
                    >{{ old('content') }}</x-textarea>
                    @error('content')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors cursor-pointer">
                    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                        Hiển thị công khai trên trang chủ
                    </label>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end space-x-4 border-t border-gray-100 pt-6">
                <x-button :href="route('admin.feedback.index')" class="bg-white border-gray-300 text-gray-700 hover:bg-gray-50">
                    Hủy bỏ
                </x-button>
                <x-button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Lưu Feedback
                </x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
