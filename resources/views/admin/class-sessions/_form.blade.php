@php
    // create.blade.php không truyền $classSession — dùng chung 1 form cho cả tạo/sửa.
    $session = $classSession ?? null;
@endphp

<x-input
    name="title"
    label="Tên buổi học"
    :value="$session?->title ?? ''"
    required
    placeholder="VD: Buổi 5 — Chữa Writing Task 1"
    error="{{ $errors->first('title') }}"
/>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung buổi học (tuỳ chọn)</label>
    <x-textarea name="description" rows="3" placeholder="VD: Chữa đề tuần trước, luyện viết email trang trọng.">{{ old('description', $session?->description) }}</x-textarea>
    @error('description')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<x-input
    type="url"
    name="meet_link"
    label="Link phòng học (Google Meet)"
    :value="$session?->meet_link ?? ''"
    required
    placeholder="https://meet.google.com/abc-defg-hij"
    error="{{ $errors->first('meet_link') }}"
/>
<p class="-mt-2 mb-4 text-xs text-gray-500">
    Tự tạo phòng trên Google Meet rồi dán link vào đây. Link này <strong>không hiển thị</strong> cho học viên —
    họ chỉ thấy nút “Vào lớp”, hệ thống kiểm tra hạn tài khoản và giờ học rồi mới chuyển sang phòng.
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 sm:gap-x-4">
    <x-input
        type="datetime-local"
        name="starts_at"
        label="Bắt đầu"
        :value="$session?->starts_at?->format('Y-m-d\TH:i') ?? ''"
        required
        error="{{ $errors->first('starts_at') }}"
    />
    <x-input
        type="datetime-local"
        name="ends_at"
        label="Kết thúc"
        :value="$session?->ends_at?->format('Y-m-d\TH:i') ?? ''"
        required
        error="{{ $errors->first('ends_at') }}"
    />
</div>
<p class="-mt-2 mb-4 text-xs text-gray-500">
    Nút “Vào lớp” tự bật trước giờ bắt đầu {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút, và tắt khi qua giờ kết thúc.
</p>

<div class="flex items-center">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $session?->is_active ?? true) ? 'checked' : '' }}
           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors cursor-pointer">
    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
        Bật buổi học (học viên nhìn thấy và vào được)
    </label>
</div>
