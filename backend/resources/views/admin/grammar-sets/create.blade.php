@extends('layouts.admin')
@section('title', 'Tạo Bộ Đề Grammar Mới')
@section('header', 'Tạo Bộ Đề Grammar Mới')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.grammar-sets.index') }}" class="text-blue-600 hover:text-blue-700">← Quay lại danh sách</a>
    <p class="text-gray-600 mt-2">Đặt tên cho bộ đề. Sau khi tạo, bạn sẽ được chuyển đến trang nhập câu hỏi.</p>
</div>

<form action="{{ route('admin.grammar-sets.store') }}" method="POST" class="space-y-6 max-w-2xl">
    @csrf
    <input type="hidden" name="action" value="draft">

    <x-card title="Thông tin bộ đề">
        <x-input
            name="title"
            label="Tên bộ đề (VD: Grammar Test 01 – Tháng 3/2026)"
            value="{{ old('title') }}"
            required
            autofocus
            error="{{ $errors->first('title') }}"
        />
    </x-card>

    <div class="flex items-center gap-4">
        <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
            Tạo & Bắt đầu nhập câu hỏi →
        </x-button>
        <a href="{{ route('admin.grammar-sets.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">Hủy</a>
    </div>
</form>
@endsection
