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
<p class="-mt-2 mb-3 text-xs text-gray-500">
    Tự tạo phòng trên Google Meet rồi dán link vào đây. Link này <strong>không hiển thị</strong> cho học viên —
    họ chỉ thấy nút “Vào lớp”, hệ thống kiểm tra hạn tài khoản và giờ học rồi mới chuyển sang phòng.
</p>

{{-- Nhắc ngay chỗ dán link: web chỉ chặn được khâu LẤY link, không chặn được
     người đã vào phòng copy link gửi ra ngoài. Phòng chờ là hàng rào thứ hai. --}}
<div class="mb-4 flex gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div class="text-xs text-amber-800">
        <p class="font-semibold mb-1">Nhớ TẮT “Truy cập nhanh” trong phòng Meet</p>
        <p>
            Web chỉ kiểm soát <strong>ai được lấy link</strong>. Học viên đã vào phòng vẫn có thể copy link
            trên thanh địa chỉ gửi cho người ngoài — không code nào chặn được khâu này.
        </p>
        <p class="mt-1">
            Trong phòng Meet: bấm <strong>biểu tượng khiên (Điều khiển của chủ trì)</strong> →
            <strong>TẮT “Truy cập nhanh” (Quick access)</strong>. Khi đó người lạ phải xin vào và bạn duyệt từng người.
        </p>
        <p class="mt-1 text-amber-700">
            Lưu ý: “Phòng chờ” đúng nghĩa là tính năng <strong>trả phí</strong> (Business Standard trở lên).
            Với Gmail thường thì tắt “Truy cập nhanh” là cách tương đương.
        </p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 sm:gap-x-4">
    <x-input
        type="datetime-local"
        name="starts_at"
        label="Bắt đầu (không bắt buộc)"
        :value="$session?->starts_at?->format('Y-m-d\TH:i') ?? ''"
        error="{{ $errors->first('starts_at') }}"
    />
    <x-input
        type="datetime-local"
        name="ends_at"
        label="Kết thúc (không bắt buộc)"
        :value="$session?->ends_at?->format('Y-m-d\TH:i') ?? ''"
        error="{{ $errors->first('ends_at') }}"
    />
</div>
<div class="-mt-2 mb-4 text-xs text-gray-500 space-y-1">
    <p><strong>Để trống cả hai ô là xong</strong> — lớp mở ngay khi bật, đóng khi bạn bỏ tick “Bật buổi học”. Cách này ít thao tác nhất.</p>
    <p>Nếu có điền: nút “Vào lớp” tự bật trước giờ bắt đầu {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút và tự tắt khi qua giờ kết thúc — không cần nhớ tắt tay.</p>
</div>

<div class="flex items-center">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $session?->is_active ?? true) ? 'checked' : '' }}
           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors cursor-pointer">
    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
        Bật buổi học (học viên nhìn thấy và vào được)
    </label>
</div>
