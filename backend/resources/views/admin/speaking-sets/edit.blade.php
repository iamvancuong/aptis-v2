@extends('layouts.admin')

@section('title', 'Chỉnh sửa Bộ Đề Speaking')
@section('header', 'Chỉnh sửa Bộ Đề: ' . $speaking_set->title)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.speaking-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
</div>

@php
    $questions = $speaking_set->questions->keyBy('part');
    $p1 = $questions[1] ?? null;
    $p2 = $questions[2] ?? null;
    $p3 = $questions[3] ?? null;
    $p4 = $questions[4] ?? null;
@endphp

<form action="{{ route('admin.speaking-sets.update', $speaking_set) }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-5xl" x-data="{ hasPart1: {{ $p1 ? 'true' : 'false' }}, hasPart2: {{ $p2 ? 'true' : 'false' }}, hasPart3: {{ $p3 ? 'true' : 'false' }} }">
    @csrf
    @method('PUT')

    <x-card title="Thông tin chung" class="mb-6 bg-gray-50/50">
        <div class="grid grid-cols-1 gap-6">
            <x-input 
                name="title" 
                label="Tên Bộ Đề" 
                value="{{ old('title', $speaking_set->title) }}" 
                required 
                autofocus 
                error="{{ $errors->first('title') }}" 
            />

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="is_public" value="1" {{ old('is_public', $speaking_set->is_public) ? 'checked' : '' }}>
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
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part1_q1" label="Câu hỏi 1" value="{{ old('part1_q1', $p1->metadata['questions'][0] ?? '') }}" error="{{ $errors->first('part1_q1') }}" :required="false" />
                    <x-input name="part1_q2" label="Câu hỏi 2" value="{{ old('part1_q2', $p1->metadata['questions'][1] ?? '') }}" error="{{ $errors->first('part1_q2') }}" :required="false" />
                    <x-input name="part1_q3" label="Câu hỏi 3" value="{{ old('part1_q3', $p1->metadata['questions'][2] ?? '') }}" error="{{ $errors->first('part1_q3') }}" :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part1_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho 3 câu hỏi trên..." rows="4">{{ old('part1_sample_answer', $p1->metadata['sample_answer'] ?? '') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 2 -->
    <div x-show="hasPart2" x-collapse>
        <x-card title="Part 2: Describe a picture" class="mb-6 border-l-4 border-emerald-500">
            <div class="space-y-4">
                
                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700">Hình ảnh Part 2 mới (bỏ trống nếu muốn giữ nguyên)</label>
                    <input type="file" name="part2_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 mb-2">
                    @if(!empty($p2->metadata['image_path']))
                        <div class="mt-2 flex items-center gap-4 bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <span class="text-sm text-gray-500 italic">Ảnh hiện tại:</span>
                            <a href="{{ asset('storage/' . $p2->metadata['image_path']) }}" target="_blank" class="text-blue-500 hover:underline text-sm font-medium">Xem ảnh</a>
                            <label class="flex items-center gap-2 cursor-pointer ml-auto bg-red-50 px-2 py-1 rounded text-red-600 hover:bg-red-100 transition-colors">
                                <input type="checkbox" name="delete_part2_image" value="1" class="rounded border-red-300 text-red-600 focus:ring-red-500">
                                <span class="text-xs font-bold uppercase">Xóa hình này</span>
                            </label>
                        </div>
                    @endif
                    @error('part2_image')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>

                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part2_q1" label="Câu hỏi 1 (Thường là mô tả ảnh)" value="{{ old('part2_q1', $p2->metadata['questions'][0] ?? '') }}" error="{{ $errors->first('part2_q1') }}" :required="false" />
                    <x-input name="part2_q2" label="Câu hỏi 2 (Mở rộng)" value="{{ old('part2_q2', $p2->metadata['questions'][1] ?? '') }}" error="{{ $errors->first('part2_q2') }}" :required="false" />
                    <x-input name="part2_q3" label="Câu hỏi 3 (Trải nghiệm cá nhân)" value="{{ old('part2_q3', $p2->metadata['questions'][2] ?? '') }}" error="{{ $errors->first('part2_q3') }}" :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part2_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho các câu hỏi trên..." rows="4">{{ old('part2_sample_answer', $p2->metadata['sample_answer'] ?? '') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 3 -->
    <div x-show="hasPart3" x-collapse>
        <x-card title="Part 3: Compare two pictures" class="mb-6 border-l-4 border-amber-500">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hình ảnh 1 mới (bỏ trống nếu muốn giữ nguyên)</label>
                        <input type="file" name="part3_image1" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 mb-2">
                        @if(!empty($p3->metadata['image_paths'][0]))
                            <div class="mt-2 flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <a href="{{ asset('storage/' . $p3->metadata['image_paths'][0]) }}" target="_blank" class="text-blue-500 hover:underline text-xs font-medium">Xem ảnh 1</a>
                                <label class="flex items-center gap-1.5 cursor-pointer ml-auto text-red-600">
                                    <input type="checkbox" name="delete_part3_image1" value="1" class="w-3.5 h-3.5 rounded border-red-300 text-red-600 focus:ring-red-500">
                                    <span class="text-[10px] font-bold uppercase">Xóa</span>
                                </label>
                            </div>
                        @endif
                        @error('part3_image1')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hình ảnh 2 mới (bỏ trống nếu muốn giữ nguyên)</label>
                        <input type="file" name="part3_image2" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 mb-2">
                        @if(!empty($p3->metadata['image_paths'][1]))
                            <div class="mt-2 flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <a href="{{ asset('storage/' . $p3->metadata['image_paths'][1]) }}" target="_blank" class="text-blue-500 hover:underline text-xs font-medium">Xem ảnh 2</a>
                                <label class="flex items-center gap-1.5 cursor-pointer ml-auto text-red-600">
                                    <input type="checkbox" name="delete_part3_image2" value="1" class="w-3.5 h-3.5 rounded border-red-300 text-red-600 focus:ring-red-500">
                                    <span class="text-[10px] font-bold uppercase">Xóa</span>
                                </label>
                            </div>
                        @endif
                        @error('part3_image2')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <x-input name="part3_q1" label="Câu hỏi 1 (So sánh)" value="{{ old('part3_q1', $p3->metadata['questions'][0] ?? '') }}" error="{{ $errors->first('part3_q1') }}" :required="false" />
                    <x-input name="part3_q2" label="Câu hỏi 2 (Mở rộng/Ý kiến)" value="{{ old('part3_q2', $p3->metadata['questions'][1] ?? '') }}" error="{{ $errors->first('part3_q2') }}" :required="false" />
                    <x-input name="part3_q3" label="Câu hỏi 3 (Vấn đề xã hội)" value="{{ old('part3_q3', $p3->metadata['questions'][2] ?? '') }}" error="{{ $errors->first('part3_q3') }}" :required="false" />
                </div>
                <div class="mt-4">
                    <x-textarea name="part3_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho các câu hỏi trên..." rows="4">{{ old('part3_sample_answer', $p3->metadata['sample_answer'] ?? '') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 4 -->
    <x-card title="Part 4: Extended Discussion (Abstract Level)" class="mb-6 border-l-4 border-red-500">
        <div class="space-y-4">
            
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700">Hình ảnh Part 4 mới (bỏ trống nếu muốn giữ nguyên)</label>
                <input type="file" name="part4_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 mb-2">
                @if(!empty($p4->metadata['image_path']))
                    <div class="mt-2 flex items-center gap-4 bg-gray-50 p-2 rounded-lg border border-gray-100">
                        <span class="text-sm text-gray-500 italic">Ảnh hiện tại:</span>
                        <a href="{{ asset('storage/' . $p4->metadata['image_path']) }}" target="_blank" class="text-blue-500 hover:underline text-sm font-medium">Xem ảnh</a>
                        <label class="flex items-center gap-2 cursor-pointer ml-auto bg-red-50 px-2 py-1 rounded text-red-600 hover:bg-red-100 transition-colors">
                            <input type="checkbox" name="delete_part4_image" value="1" class="rounded border-red-300 text-red-600 focus:ring-red-500">
                            <span class="text-xs font-bold uppercase">Xóa hình này</span>
                        </label>
                    </div>
                @endif
                @error('part4_image')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-4">
                <x-input name="part4_q1" label="Câu hỏi 1" value="{{ old('part4_q1', $p4->metadata['questions'][0] ?? '') }}" required error="{{ $errors->first('part4_q1') }}" />
                <x-input name="part4_q2" label="Câu hỏi 2" value="{{ old('part4_q2', $p4->metadata['questions'][1] ?? '') }}" required error="{{ $errors->first('part4_q2') }}" />
                <x-input name="part4_q3" label="Câu hỏi 3" value="{{ old('part4_q3', $p4->metadata['questions'][2] ?? '') }}" required error="{{ $errors->first('part4_q3') }}" />
            </div>
            <div class="mt-4">
                <x-textarea name="part4_sample_answer" label="Đáp án tham khảo (Admin)" placeholder="Nhập câu trả lời mẫu cho cả 3 câu hỏi (bài nói kéo dài 2 phút)..." rows="6">{{ old('part4_sample_answer', $p4->metadata['sample_answer'] ?? '') }}</x-textarea>
            </div>
        </div>
    </x-card>

    <div class="flex items-center justify-end">
        <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
            Cập nhật Bộ Đề Speaking
        </x-button>
    </div>
</form>
@endsection
