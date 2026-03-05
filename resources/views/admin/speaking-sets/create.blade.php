@extends('layouts.admin')

@section('title', 'Tạo Bộ Đề Speaking Mới')
@section('header', 'Tạo Bộ Đề Speaking (4 Parts)')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.speaking-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
    <p class="text-gray-600 mt-2">Nhập toàn bộ thông tin chi tiết cho 4 phần (Parts) của bài thi Speaking. Hệ thống sẽ tự động cấu hình Prep Time và Answer Time theo chuẩn APTIS mới nhất.</p>
</div>

<form action="{{ route('admin.speaking-sets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-5xl" x-data="{ hasPart1: {{ old('has_part_1', 'true') ? 'true' : 'false' }}, hasPart2: {{ old('has_part_2', 'true') ? 'true' : 'false' }}, hasPart3: {{ old('has_part_3', 'true') ? 'true' : 'false' }} }">
    @csrf

    <x-card title="Thông tin chung" class="mb-6 bg-gray-50/50">
        <div class="grid grid-cols-1 gap-6">
            <x-input 
                name="title" 
                label="Tên Bộ Đề (VD: Speaking Test 01 - Family & Friends)" 
                value="{{ old('title') }}" 
                required 
                autofocus 
                error="{{ $errors->first('title') }}" 
            />

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-600">Công khai (Hiển thị cho học viên)</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-6 pt-4 border-t border-gray-100">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="has_part_1" value="1" x-model="hasPart1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700 font-medium">Bật Part 1 (Personal Info)</span>
                </label>
                
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="has_part_2" value="1" x-model="hasPart2" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                    <span class="ml-2 text-sm text-gray-700 font-medium">Bật Part 2 (Describe Picture)</span>
                </label>
                
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="has_part_3" value="1" x-model="hasPart3" class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500">
                    <span class="ml-2 text-sm text-gray-700 font-medium">Bật Part 3 (Compare Pictures)</span>
                </label>
            </div>
        </div>
    </x-card>

    <!-- Part 1 -->
    <div x-show="hasPart1" x-collapse>
        <x-card title="Part 1: Personal Information (Tùy chọn)" class="mb-6 border-l-4 border-indigo-500">
            <div class="space-y-4">
                <p class="text-sm text-gray-500 mb-4 bg-indigo-50 p-3 rounded-lg"><span class="font-bold">Luồng thi:</span> Trả lời 3 câu hỏi ngắn về bản thân. Không có thời gian chuẩn bị. Trả lời 30 giây / câu. <br><strong>Lưu ý:</strong> Để trống toàn bộ 3 câu hỏi nếu muốn bỏ qua phần này.</p>
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part1_q1" label="Câu hỏi 1" value="{{ old('part1_q1') }}" error="{{ $errors->first('part1_q1') }}" placeholder="VD: Please tell me about your family." :required="false" />
                    <x-input name="part1_q2" label="Câu hỏi 2" value="{{ old('part1_q2') }}" error="{{ $errors->first('part1_q2') }}" placeholder="VD: What do you like to do in your free time?" :required="false" />
                    <x-input name="part1_q3" label="Câu hỏi 3" value="{{ old('part1_q3') }}" error="{{ $errors->first('part1_q3') }}" placeholder="VD: Tell me about your typical day." :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part1_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho 3 câu hỏi trên..." rows="4">{{ old('part1_sample_answer') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 2 -->
    <div x-show="hasPart2" x-collapse>
        <x-card title="Part 2: Describe a picture" class="mb-6 border-l-4 border-emerald-500">
            <div class="space-y-4">
                <p class="text-sm text-gray-500 mb-4 bg-emerald-50 p-3 rounded-lg"><span class="font-bold">Luồng thi:</span> Hiển thị 1 bức ảnh. Mô tả và trả lời 2 câu hỏi. Không có thời gian chuẩn bị. Trả lời 45 giây / câu.</p>
                
                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700">Hình ảnh Part 2 (Tùy chọn)</label>
                    <input type="file" name="part2_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 mb-2">
                    @error('part2_image')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>

                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part2_q1" label="Câu hỏi 1 (Thường là mô tả ảnh)" value="{{ old('part2_q1') }}" error="{{ $errors->first('part2_q1') }}" placeholder="VD: Describe the picture." :required="false" />
                    <x-input name="part2_q2" label="Câu hỏi 2 (Mở rộng)" value="{{ old('part2_q2') }}" error="{{ $errors->first('part2_q2') }}" placeholder="VD: Why do you think the people in the picture are happy?" :required="false" />
                    <x-input name="part2_q3" label="Câu hỏi 3 (Trải nghiệm cá nhân)" value="{{ old('part2_q3') }}" error="{{ $errors->first('part2_q3') }}" placeholder="VD: Tell me about a time you experienced something similar." :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part2_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho các câu hỏi trên..." rows="4">{{ old('part2_sample_answer') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 3 -->
    <div x-show="hasPart3" x-collapse>
        <x-card title="Part 3: Compare two pictures" class="mb-6 border-l-4 border-amber-500">
            <div class="space-y-4">
                <p class="text-sm text-gray-500 mb-4 bg-amber-50 p-3 rounded-lg"><span class="font-bold">Luồng thi:</span> So sánh 2 bức ảnh. Trả lời 3 câu hỏi (so sánh và mở rộng). Không có thời gian chuẩn bị. Trả lời 45 giây / câu.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hình ảnh 1 (Tùy chọn)</label>
                        <input type="file" name="part3_image1" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 mb-2">
                        @error('part3_image1')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hình ảnh 2 (Tùy chọn)</label>
                        <input type="file" name="part3_image2" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 mb-2">
                        @error('part3_image2')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part3_q1" label="Câu hỏi 1 (So sánh)" value="{{ old('part3_q1') }}" error="{{ $errors->first('part3_q1') }}" placeholder="VD: Compare the two pictures." :required="false" />
                    <x-input name="part3_q2" label="Câu hỏi 2 (Mở rộng/Ý kiến)" value="{{ old('part3_q2') }}" error="{{ $errors->first('part3_q2') }}" placeholder="VD: Which of these two places would you prefer to visit and why?" :required="false" />
                    <x-input name="part3_q3" label="Câu hỏi 3 (Vấn đề xã hội)" value="{{ old('part3_q3') }}" error="{{ $errors->first('part3_q3') }}" placeholder="VD: How do you think tourism affects these kinds of places?" :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part3_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho các câu hỏi trên..." rows="4">{{ old('part3_sample_answer') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 4 -->
    <x-card title="Part 4: Extended Discussion (Abstract Level)" class="mb-6 border-l-4 border-red-500">
        <div class="space-y-4">
            <p class="text-sm text-gray-500 mb-4 bg-red-50 p-3 rounded-lg"><span class="font-bold">Luồng thi:</span> Xem 1 bức ảnh. Cùng lúc hiển thị 3 câu hỏi. Có 1 phút chuẩn bị (được note). Trả lời liên tục 3 câu hỏi trong 2 phút.</p>
            
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700">Hình ảnh Part 4 (Tùy chọn)</label>
                <input type="file" name="part4_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 mb-2">
                @error('part4_image')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-4">
                <x-input name="part4_q1" label="Câu hỏi 1" value="{{ old('part4_q1') }}" required error="{{ $errors->first('part4_q1') }}" placeholder="VD: Tell me about a time when you were on your own." />
                <x-input name="part4_q2" label="Câu hỏi 2" value="{{ old('part4_q2') }}" required error="{{ $errors->first('part4_q2') }}" placeholder="VD: How did you feel about it?" />
                <x-input name="part4_q3" label="Câu hỏi 3" value="{{ old('part4_q3') }}" required error="{{ $errors->first('part4_q3') }}" placeholder="VD: What are some of the ways of passing the time on your own?" />
            </div>
            <div class="mt-4">
                <x-textarea name="part4_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho cả 3 câu hỏi (bài nói kéo dài 2 phút)..." rows="6">{{ old('part4_sample_answer') }}</x-textarea>
            </div>
        </div>
    </x-card>

    <div class="flex items-center justify-end">
        <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
            Lưu Bộ Đề Speaking (Tạo 4 Phần)
        </x-button>
    </div>
</form>
@endsection
