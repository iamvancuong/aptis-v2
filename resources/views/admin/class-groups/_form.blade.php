@php
    // create.blade.php không truyền $classGroup — dùng chung 1 form cho cả tạo/sửa.
    $group = $classGroup ?? null;
@endphp

<x-input
    name="name"
    label="Tên lớp"
    :value="old('name', $group?->name ?? '')"
    required
    placeholder="VD: Lớp B1 — tối thứ 7"
    error="{{ $errors->first('name') }}"
/>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú về lớp (tuỳ chọn)</label>
    <x-textarea name="description" rows="2" placeholder="VD: Lớp cấp tốc 8 buổi, khai giảng 10/08.">{{ old('description', $group?->description) }}</x-textarea>
    @error('description')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Lọc mặc định khi chọn thành viên (tuỳ chọn)</label>
    <x-select name="source_filter">
        <option value="">Không lọc — hiện tất cả học viên</option>
        @foreach(\App\Models\User::SOURCE_LABELS as $key => $label)
            <option value="{{ $key }}" {{ old('source_filter', $group?->source_filter) === $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </x-select>
    <p class="mt-1 text-xs text-gray-500">
        Chỉ là <strong>bộ lọc cho tiện</strong> ở màn chọn thành viên — không phải luật.
        Ai được vào lớp hoàn toàn do danh sách thành viên bạn chọn quyết định, nên bạn vẫn
        thêm được học viên tạo tay vào lớp mua chuyển khoản và ngược lại.
    </p>
    @error('source_filter')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<x-input
    type="url"
    name="meet_link"
    label="Link phòng Meet của lớp (tuỳ chọn)"
    :value="old('meet_link', $group?->meet_link ?? '')"
    placeholder="https://meet.google.com/abc-defg-hij"
    error="{{ $errors->first('meet_link') }}"
/>
<p class="-mt-2 mb-4 text-xs text-gray-500">
    Dán <strong>một lần</strong> ở đây thì mọi buổi của lớp tự dùng link này — khỏi phải dán lại từng buổi.
    Cách làm ít thao tác nhất: tạo <strong>một sự kiện Google Calendar lặp lại</strong> cho lớp
    (“19h30 thứ 7 hàng tuần”), Google giữ nguyên một link Meet cho cả chuỗi, dán link đó vào đây.
    Buổi nào cần phòng khác thì dán link riêng ở buổi đó, nó sẽ ghi đè link lớp.
</p>

<div class="flex items-center">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $group?->is_active ?? true) ? 'checked' : '' }}
           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors cursor-pointer">
    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
        Bật lớp — tắt lớp là <strong>đóng luôn mọi buổi</strong> của lớp, kể cả với khách mời riêng
    </label>
</div>
