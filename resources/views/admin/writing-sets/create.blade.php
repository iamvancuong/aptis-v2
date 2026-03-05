@extends('layouts.admin')

@section('title', 'Tạo Bộ Đề Writing Mới')
@section('header', 'Tạo Bộ Đề Writing Cohesive (Toàn diện)')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.writing-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
    <p class="text-gray-600 mt-2">Nhập toàn bộ thông tin chi tiết cho 4 phần (Parts). Các trường min/max word words và format được thiết lập sẵn theo chuẩn APTIS, nhưng có thể tinh chỉnh nếu muốn.</p>
</div>

<form action="{{ route('admin.writing-sets.store') }}" method="POST" class="space-y-6 max-w-5xl" x-data="{ hasPart1: {{ old('has_part_1', true) ? 'true' : 'false' }}, hasPart2: {{ old('has_part_2', true) ? 'true' : 'false' }} }">
    @csrf

    <x-card title="Thông tin chung" class="mb-6 bg-gray-50/50">
        <div class="grid grid-cols-1 gap-6">
            <x-input 
                name="title" 
                label="Tên Chủ Đề Bộ Đề (VD: Sports Club...)" 
                value="{{ old('title') }}" 
                required 
                autofocus 
                error="{{ $errors->first('title') }}" 
            />

            <div class="space-y-3">
                <label class="inline-flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-600">Công khai (Hiển thị cho học viên)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="has_part_1" value="1" {{ old('has_part_1', true) ? 'checked' : '' }} x-model="hasPart1">
                    <span class="ml-2 text-gray-600">Bộ đề này có bao gồm Part 1 (Form Filling)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="has_part_2" value="1" {{ old('has_part_2', true) ? 'checked' : '' }} x-model="hasPart2">
                    <span class="ml-2 text-gray-600">Bộ đề này có bao gồm Part 2 (Short Text Writing)</span>
                </label>
            </div>
        </div>
    </x-card>

    <!-- Part 1 -->
    <div x-show="hasPart1" x-collapse>
        <x-card title="Part 1: Word-level Writing (Form Filling)" class="mb-6">
        <div class="space-y-4">
            <x-input 
                name="part1_instructions" 
                label="Câu lệnh hướng dẫn" 
                value="{{ old('part1_instructions', 'You want to join a sports club. Fill in the form.') }}" 
                error="{{ $errors->first('part1_instructions') }}" 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input name="part1_f1_label" label="Field 1 Label" value="{{ old('part1_f1_label', 'Full Name') }}" />
                <x-input name="part1_f1_placeholder" label="Field 1 Placeholder" value="{{ old('part1_f1_placeholder', '') }}" />
                
                <x-input name="part1_f2_label" label="Field 2 Label" value="{{ old('part1_f2_label', 'Date of Birth') }}" />
                <x-input name="part1_f2_placeholder" label="Field 2 Placeholder" value="{{ old('part1_f2_placeholder', '') }}" />
                
                <x-input name="part1_f3_label" label="Field 3 Label" value="{{ old('part1_f3_label', 'Interests') }}" />
                <x-input name="part1_f3_placeholder" label="Field 3 Placeholder" value="{{ old('part1_f3_placeholder', '') }}" />
                
                <x-input name="part1_f4_label" label="Field 4 Label" value="{{ old('part1_f4_label', 'How often do you train?') }}" />
                <x-input name="part1_f4_placeholder" label="Field 4 Placeholder" value="{{ old('part1_f4_placeholder', '') }}" />
                
                <x-input name="part1_f5_label" label="Field 5 Label" value="{{ old('part1_f5_label', 'Why do you want to join?') }}" />
                <x-input name="part1_f5_placeholder" label="Field 5 Placeholder" value="{{ old('part1_f5_placeholder', '') }}" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Sample Answer) - Tùy chọn</label>
                <x-textarea name="part1_sample_answer" rows="2">{{ old('part1_sample_answer') }}</x-textarea>
            </div>
        </div>
        </x-card>
    </div>

    <!-- Part 2 -->
    <div x-show="hasPart2" x-collapse>
        <x-card title="Part 2: Short Text Writing" class="mb-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tình huống (Scenario)</label>
                    <x-textarea 
                        name="part2_scenario" 
                        rows="3" 
                    >{{ old('part2_scenario') }}</x-textarea>
                    @error('part2_scenario')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input name="part2_hints" label="Gợi ý mở rộng (Hints)" value="{{ old('part2_hints', 'Write roughly 20-30 words.') }}" />
                    <x-input type="number" name="part2_min" label="Min words" value="{{ old('part2_min', 20) }}" />
                    <x-input type="number" name="part2_max" label="Max words" value="{{ old('part2_max', 30) }}" />
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Sample Answer)</label>
                    <x-textarea name="part2_sample_answer" rows="3">{{ old('part2_sample_answer') }}</x-textarea>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Part 3 -->
    <x-card title="Part 3: Three Written Responses (Social Media)" class="mb-6">
        <div class="space-y-4">
            <x-input 
                name="part3_stem" 
                label="Câu lệnh gốc (Stem)" 
                value="{{ old('part3_stem', 'Respond to the messages in the group.') }}" 
                required 
                error="{{ $errors->first('part3_stem') }}" 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input type="number" name="part3_min" label="Min words/câu" value="{{ old('part3_min', 30) }}" />
                <x-input type="number" name="part3_max" label="Max words/câu" value="{{ old('part3_max', 40) }}" />
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-4">
                <div>
                    <x-input name="part3_prompt_1" label="Prompt 1 (Câu hỏi 1)" value="{{ old('part3_prompt_1') }}" required error="{{ $errors->first('part3_prompt_1') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 1</label>
                        <x-textarea name="part3_sample_1" rows="2">{{ old('part3_sample_1') }}</x-textarea>
                    </div>
                </div>

                <div>
                    <x-input name="part3_prompt_2" label="Prompt 2 (Câu hỏi 2)" value="{{ old('part3_prompt_2') }}" required error="{{ $errors->first('part3_prompt_2') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 2</label>
                        <x-textarea name="part3_sample_2" rows="2">{{ old('part3_sample_2') }}</x-textarea>
                    </div>
                </div>

                <div>
                    <x-input name="part3_prompt_3" label="Prompt 3 (Câu hỏi 3)" value="{{ old('part3_prompt_3') }}" required error="{{ $errors->first('part3_prompt_3') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 3</label>
                        <x-textarea name="part3_sample_3" rows="2">{{ old('part3_sample_3') }}</x-textarea>
                    </div>
                </div>
            </div>
        </div>
    </x-card>

    <!-- Part 4 -->
    <x-card title="Part 4: Formal and Informal Writing (Dual Email)" class="mb-6">
        <div class="space-y-6">
            <x-input 
                name="part4_context" 
                label="Ngữ cảnh chung" 
                value="{{ old('part4_context') }}" 
                required 
                error="{{ $errors->first('part4_context') }}" 
            />

            <!-- Email Setup -->
            <div class="space-y-4 bg-indigo-50/50 p-4 border border-indigo-100 rounded-lg">
                <h4 class="font-semibold text-indigo-800 text-sm">Cấu trúc Email gợi mở</h4>
                <x-input name="part4_email_greeting" label="Lời chào (Greeting)" value="{{ old('part4_email_greeting', 'Dear Member,') }}" />
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung Email của ban quản lý báo tin <span class="text-red-500">*</span></label>
                    <x-textarea name="part4_email_body" rows="3" required>{{ old('part4_email_body') }}</x-textarea>
                    @error('part4_email_body')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lời kết (Sign-off)</label>
                    <x-textarea name="part4_email_sign_off" rows="2">{{ old('part4_email_sign_off', "Best regards,\nThe Management") }}</x-textarea>
                </div>
            </div>

            <!-- Task 1 -->
            <div class="space-y-4 border-t border-gray-100 pt-4">
                <h4 class="font-semibold text-emerald-700 text-sm">Task 1: Informal Email</h4>
                <x-input name="part4_task1_instruction" label="Yêu cầu Task 1" placeholder="VD: Write an email to your friend explaining..." value="{{ old('part4_task1_instruction', 'Write an email to your friend') }}" required error="{{ $errors->first('part4_task1_instruction') }}" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="number" name="part4_task1_min" label="Min words" value="{{ old('part4_task1_min', 40) }}" />
                    <x-input type="number" name="part4_task1_max" label="Max words" value="{{ old('part4_task1_max', 50) }}" />
                </div>

                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Informal)</label>
                    <x-textarea name="part4_task1_sample" rows="3">{{ old('part4_task1_sample') }}</x-textarea>
                </div>
            </div>

            <!-- Task 2 -->
            <div class="space-y-4 border-t border-gray-100 pt-4">
                <h4 class="font-semibold text-amber-700 text-sm">Task 2: Formal Email</h4>
                <x-input name="part4_task2_instruction" label="Yêu cầu Task 2" placeholder="VD: Write an email to the management..." value="{{ old('part4_task2_instruction', 'Email to the manager of the club:') }}" required error="{{ $errors->first('part4_task2_instruction') }}" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="number" name="part4_task2_min" label="Min words" value="{{ old('part4_task2_min', 120) }}" />
                    <x-input type="number" name="part4_task2_max" label="Max words" value="{{ old('part4_task2_max', 150) }}" />
                </div>

                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Formal)</label>
                    <x-textarea name="part4_task2_sample" rows="5">{{ old('part4_task2_sample') }}</x-textarea>
                </div>
            </div>
        </div>
    </x-card>

    <div class="flex items-center justify-end">
        <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
            Lưu Bộ Đề (Tạo 4 Câu Hỏi Đồng Thời)
        </x-button>
    </div>
</form>
@endsection
