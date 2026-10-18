@extends('layouts.admin')

@section('title', 'Cấp lượt học viên')

@section('content')
@php
    $vocabDaily = \App\Models\Setting::int('vocab_daily_limit', (int) config('aptis.vocab.daily_limit', 60));
@endphp
<div class="space-y-6 max-w-5xl">
    <div>
        <a href="{{ route('admin.full-tests.index') }}" class="text-sm text-indigo-600 hover:underline">← Full Test</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Cấp lượt học viên</h1>
        <p class="text-sm text-gray-500 mt-1">
            <strong>Full Test:</strong> tổng số lượt được cấp (đã tính cả lượt đã dùng); tài khoản mới nhận
            {{ \App\Models\Setting::int('default_full_test_quota', (int) config('aptis.full_test.default_quota', 3)) }} lượt.
            <strong>Tra từ:</strong> mọi học viên có {{ $vocabDaily }} lượt/ngày; ô bên dưới là số lượt cấp <em>thêm</em> mỗi ngày.
            Đổi mức chung ở <a href="{{ route('admin.settings.index') }}" class="text-indigo-600 hover:underline">Cài đặt</a>.
        </p>
    </div>

    <form method="GET" class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Tìm email / tên học viên"
               class="px-3 py-2 text-sm border border-gray-200 rounded-lg w-72">
        <button class="px-3 py-2 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50">Tìm</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3">Học viên</th>
                    <th class="text-center px-4 py-3">Full Test đã dùng</th>
                    <th class="text-left px-4 py-3">Lượt Full Test được cấp</th>
                    <th class="text-left px-4 py-3">Tra từ thêm/ngày</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $u->name }}</div>
                            <div class="text-xs text-gray-500">{{ $u->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="{{ $u->full_tests_count >= $u->full_test_quota ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                {{ $u->full_tests_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.full-tests.quotas.update', $u) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="number" name="full_test_quota" min="0" max="1000" value="{{ $u->full_test_quota }}"
                                       class="w-20 px-2 py-1.5 text-sm border border-gray-200 rounded-lg">
                                <button class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Lưu</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.full-tests.quotas.vocab', $u) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <span class="text-gray-400">+</span>
                                <input type="number" name="vocab_extra_daily" min="0" max="10000" value="{{ $u->vocab_extra_daily }}"
                                       class="w-20 px-2 py-1.5 text-sm border border-gray-200 rounded-lg">
                                <button class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Lưu</button>
                                <span class="text-xs text-gray-500 whitespace-nowrap">= {{ $vocabDaily + (int) $u->vocab_extra_daily }}/ngày</span>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">Không tìm thấy học viên.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
