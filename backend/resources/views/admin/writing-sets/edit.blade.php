@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Bộ Đề Writing')
@section('header', 'Chỉnh Sửa Bộ Đề Writing Cohesive (Toàn diện)')

@php
    // Bóc tách dữ liệu từ 4 questions của set
    $q1 = $writing_set->questions->where('part', 1)->first();
    $q2 = $writing_set->questions->where('part', 2)->first();
    $q3 = $writing_set->questions->where('part', 3)->first();
    $q4 = $writing_set->questions->where('part', 4)->first();

    // Part 1
    $part1_instructions = optional($q1)->metadata['instructions'] ?? '';
    $fields = optional($q1)->metadata['fields'] ?? [];
    $part1_f1 = $fields[0] ?? ['label' => '', 'placeholder' => ''];
    $part1_f2 = $fields[1] ?? ['label' => '', 'placeholder' => ''];
    $part1_f3 = $fields[2] ?? ['label' => '', 'placeholder' => ''];
    $part1_f4 = $fields[3] ?? ['label' => '', 'placeholder' => ''];
    $part1_f5 = $fields[4] ?? ['label' => '', 'placeholder' => ''];
    $part1_sample_answer = optional($q1)->metadata['sample_answer'] ?? '';
    
    // Part 2
    $part2_scenario = optional($q2)->metadata['scenario'] ?? '';
    $part2_hints = optional($q2)->metadata['hints'] ?? '';
    $part2_min = optional($q2)->metadata['word_limit']['min'] ?? 20;
    $part2_max = optional($q2)->metadata['word_limit']['max'] ?? 30;
    $part2_sample_answer = optional($q2)->metadata['sample_answer'] ?? '';

    // Part 3
    $part3_stem = optional($q3)->stem ?? '';
    $part3_prompts = optional($q3)->metadata['questions'] ?? [];
    $part3_p1 = $part3_prompts[0]['prompt'] ?? '';
    $part3_p2 = $part3_prompts[1]['prompt'] ?? '';
    $part3_p3 = $part3_prompts[2]['prompt'] ?? '';
    $part3_min = $part3_prompts[0]['word_limit']['min'] ?? 30;
    $part3_max = $part3_prompts[0]['word_limit']['max'] ?? 40;
    $part3_s1 = $part3_prompts[0]['sample_answer'] ?? '';
    $part3_s2 = $part3_prompts[1]['sample_answer'] ?? '';
    $part3_s3 = $part3_prompts[2]['sample_answer'] ?? '';

    // Part 4
    $part4_context = optional($q4)->metadata['context'] ?? '';
    $p4e = optional($q4)->metadata['email'] ?? [];
    $part4_email_greeting = $p4e['greeting'] ?? '';
    $part4_email_body = $p4e['body'] ?? '';
    $part4_email_sign_off = $p4e['sign_off'] ?? '';
    
    $p4t1 = optional($q4)->metadata['task1'] ?? [];
    $part4_task1_instruction = $p4t1['instruction'] ?? '';
    $part4_t1_min = $p4t1['word_limit']['min'] ?? 40;
    $part4_t1_max = $p4t1['word_limit']['max'] ?? 50;
    $part4_task1_sample = $p4t1['sample_answer'] ?? '';

    $p4t2 = optional($q4)->metadata['task2'] ?? [];
    $part4_task2_instruction = $p4t2['instruction'] ?? '';
    $part4_t2_min = $p4t2['word_limit']['min'] ?? 120;
    $part4_t2_max = $p4t2['word_limit']['max'] ?? 150;
    $part4_task2_sample = $p4t2['sample_answer'] ?? '';
@endphp

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <a href="{{ route('admin.writing-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
        <p class="text-gray-600 mt-2">Dữ liệu được bóc tách từ 4 câu hỏi. Khi cập nhật sẽ đồng bộ lại vào cơ sở dữ liệu.</p>
    </div>
    
    @if($writing_set->questions->count() < 4)
        <div class="px-4 py-2 bg-red-100 text-red-800 rounded">
            ⚠️ Chú ý: Bộ đề này hiện không đủ 4 phần (chỉ có {{ $writing_set->questions->count() }}). Thao tác cập nhật sẽ bị vô hiệu để bảo vệ dữ liệu.
        </div>
    @endif
</div>

<form action="{{ route('admin.writing-sets.update', $writing_set) }}" method="POST" class="space-y-6 max-w-5xl">
    @csrf
    @method('PUT')

    <x-card title="Thông tin chung" class="mb-6 bg-gray-50/50">
        <div class="grid grid-cols-1 gap-6">
            <x-input 
                name="title" 
                label="Tên Chủ Đề Bộ Đề" 
                value="{{ old('title', $writing_set->title) }}" 
                required 
                autofocus 
                error="{{ $errors->first('title') }}" 
            />

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="is_public" value="1" {{ old('is_public', $writing_set->is_public) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-600">Công khai (Hiển thị cho học viên)</span>
                </label>
            </div>
        </div>
    </x-card>

    <!-- Part 1 -->
    <x-card title="Part 1: Word-level Writing (Form Filling)" class="mb-6">
        <div class="space-y-4">
            <x-input 
                name="part1_instructions" 
                label="Câu lệnh hướng dẫn" 
                value="{{ old('part1_instructions', $part1_instructions) }}" 
                required 
                error="{{ $errors->first('part1_instructions') }}" 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input name="part1_f1_label" label="Field 1 Label" value="{{ old('part1_f1_label', $part1_f1['label']) }}" required />
                <x-input name="part1_f1_placeholder" label="Field 1 Placeholder" value="{{ old('part1_f1_placeholder', $part1_f1['placeholder']) }}" />
                
                <x-input name="part1_f2_label" label="Field 2 Label" value="{{ old('part1_f2_label', $part1_f2['label']) }}" required />
                <x-input name="part1_f2_placeholder" label="Field 2 Placeholder" value="{{ old('part1_f2_placeholder', $part1_f2['placeholder']) }}" />
                
                <x-input name="part1_f3_label" label="Field 3 Label" value="{{ old('part1_f3_label', $part1_f3['label']) }}" required />
                <x-input name="part1_f3_placeholder" label="Field 3 Placeholder" value="{{ old('part1_f3_placeholder', $part1_f3['placeholder']) }}" />
                
                <x-input name="part1_f4_label" label="Field 4 Label" value="{{ old('part1_f4_label', $part1_f4['label']) }}" required />
                <x-input name="part1_f4_placeholder" label="Field 4 Placeholder" value="{{ old('part1_f4_placeholder', $part1_f4['placeholder']) }}" />
                
                <x-input name="part1_f5_label" label="Field 5 Label" value="{{ old('part1_f5_label', $part1_f5['label']) }}" required />
                <x-input name="part1_f5_placeholder" label="Field 5 Placeholder" value="{{ old('part1_f5_placeholder', $part1_f5['placeholder']) }}" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Sample Answer) - Tùy chọn</label>
                <x-textarea name="part1_sample_answer" rows="2">{{ old('part1_sample_answer', $part1_sample_answer) }}</x-textarea>
            </div>
        </div>
    </x-card>

    <!-- Part 2 -->
    <x-card title="Part 2: Short Text Writing" class="mb-6">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tình huống (Scenario) <span class="text-red-500">*</span></label>
                <x-textarea 
                    name="part2_scenario" 
                    rows="3" 
                    required
                >{{ old('part2_scenario', $part2_scenario) }}</x-textarea>
                @error('part2_scenario')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input name="part2_hints" label="Gợi ý mở rộng (Hints)" value="{{ old('part2_hints', $part2_hints) }}" />
                <x-input type="number" name="part2_min" label="Min words" value="{{ old('part2_min', $part2_min) }}" />
                <x-input type="number" name="part2_max" label="Max words" value="{{ old('part2_max', $part2_max) }}" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Sample Answer)</label>
                <x-textarea name="part2_sample_answer" rows="3">{{ old('part2_sample_answer', $part2_sample_answer) }}</x-textarea>
            </div>
        </div>
    </x-card>

    <!-- Part 3 -->
    <x-card title="Part 3: Three Written Responses (Social Media)" class="mb-6">
        <div class="space-y-4">
            <x-input 
                name="part3_stem" 
                label="Câu lệnh gốc (Stem)" 
                value="{{ old('part3_stem', $part3_stem) }}" 
                required 
                error="{{ $errors->first('part3_stem') }}" 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input type="number" name="part3_min" label="Min words/câu" value="{{ old('part3_min', $part3_min) }}" />
                <x-input type="number" name="part3_max" label="Max words/câu" value="{{ old('part3_max', $part3_max) }}" />
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-4">
                <div>
                    <x-input name="part3_prompt_1" label="Prompt 1 (Câu hỏi 1)" value="{{ old('part3_prompt_1', $part3_p1) }}" required error="{{ $errors->first('part3_prompt_1') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 1</label>
                        <x-textarea name="part3_sample_1" rows="2">{{ old('part3_sample_1', $part3_s1) }}</x-textarea>
                    </div>
                </div>

                <div>
                    <x-input name="part3_prompt_2" label="Prompt 2 (Câu hỏi 2)" value="{{ old('part3_prompt_2', $part3_p2) }}" required error="{{ $errors->first('part3_prompt_2') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 2</label>
                        <x-textarea name="part3_sample_2" rows="2">{{ old('part3_sample_2', $part3_s2) }}</x-textarea>
                    </div>
                </div>

                <div>
                    <x-input name="part3_prompt_3" label="Prompt 3 (Câu hỏi 3)" value="{{ old('part3_prompt_3', $part3_p3) }}" required error="{{ $errors->first('part3_prompt_3') }}" />
                    <div class="mt-2 pl-4 border-l-2 border-indigo-100">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đáp án tham khảo cho Câu 3</label>
                        <x-textarea name="part3_sample_3" rows="2">{{ old('part3_sample_3', $part3_s3) }}</x-textarea>
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
                value="{{ old('part4_context', $part4_context) }}" 
                required 
                error="{{ $errors->first('part4_context') }}" 
            />

            <!-- Email Setup -->
            <div class="space-y-4 bg-indigo-50/50 p-4 border border-indigo-100 rounded-lg">
                <h4 class="font-semibold text-indigo-800 text-sm">Cấu trúc Email gợi mở</h4>
                <x-input name="part4_email_greeting" label="Lời chào (Greeting)" value="{{ old('part4_email_greeting', $part4_email_greeting) }}" />
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung Email của ban quản lý báo tin <span class="text-red-500">*</span></label>
                    <x-textarea name="part4_email_body" rows="3" required>{{ old('part4_email_body', $part4_email_body) }}</x-textarea>
                    @error('part4_email_body')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lời kết (Sign-off)</label>
                    <x-textarea name="part4_email_sign_off" rows="2">{{ old('part4_email_sign_off', $part4_email_sign_off) }}</x-textarea>
                </div>
            </div>

            <!-- Task 1 -->
            <div class="space-y-4 border-t border-gray-100 pt-4">
                <h4 class="font-semibold text-emerald-700 text-sm">Task 1: Informal Email</h4>
                <x-input name="part4_task1_instruction" label="Yêu cầu Task 1" value="{{ old('part4_task1_instruction', $part4_task1_instruction) }}" required error="{{ $errors->first('part4_task1_instruction') }}" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="number" name="part4_task1_min" label="Min words" value="{{ old('part4_task1_min', $part4_t1_min) }}" />
                    <x-input type="number" name="part4_task1_max" label="Max words" value="{{ old('part4_task1_max', $part4_t1_max) }}" />
                </div>

                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Informal)</label>
                    <x-textarea name="part4_task1_sample" rows="3">{{ old('part4_task1_sample', $part4_task1_sample) }}</x-textarea>
                </div>
            </div>

            <!-- Task 2 -->
            <div class="space-y-4 border-t border-gray-100 pt-4">
                <h4 class="font-semibold text-amber-700 text-sm">Task 2: Formal Email</h4>
                <x-input name="part4_task2_instruction" label="Yêu cầu Task 2" value="{{ old('part4_task2_instruction', $part4_task2_instruction) }}" required error="{{ $errors->first('part4_task2_instruction') }}" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="number" name="part4_task2_min" label="Min words" value="{{ old('part4_task2_min', $part4_t2_min) }}" />
                    <x-input type="number" name="part4_task2_max" label="Max words" value="{{ old('part4_task2_max', $part4_t2_max) }}" />
                </div>

                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đáp án tham khảo (Formal)</label>
                    <x-textarea name="part4_task2_sample" rows="5">{{ old('part4_task2_sample', $part4_task2_sample) }}</x-textarea>
                </div>
            </div>
        </div>
    </x-card>

    <div class="flex items-center justify-end">
        @if($writing_set->questions->count() == 4)
            <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">Lưu Cập Nhật</x-button>
        @else
            <x-button type="button" class="bg-gray-400 cursor-not-allowed" disabled>Vô hiệu hoá cập nhật do không đủ 4 câu hỏi gốc</x-button>
        @endif
    </div>
</form>
@endsection
