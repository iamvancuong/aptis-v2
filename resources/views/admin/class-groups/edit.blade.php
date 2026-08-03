@extends('layouts.admin')

@section('title', 'Sửa lớp - Admin')
@section('header', 'Sửa lớp: ' . $classGroup->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.class-groups.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Quay lại danh sách lớp
        </a>
        <a href="{{ route('admin.class-groups.members', $classGroup) }}"
           class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            Thành viên ({{ $classGroup->members()->count() }}) →
        </a>
    </div>

    <form action="{{ route('admin.class-groups.update', $classGroup) }}" method="POST">
        @csrf @method('PUT')

        <x-card title="Thông tin lớp">
            @include('admin.class-groups._form')

            <div class="mt-8 flex items-center justify-end space-x-4 border-t border-gray-100 pt-6">
                <x-button :href="route('admin.class-groups.index')" class="bg-white border-gray-300 text-gray-700 hover:bg-gray-50">
                    Hủy bỏ
                </x-button>
                <x-button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    Lưu thay đổi
                </x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
