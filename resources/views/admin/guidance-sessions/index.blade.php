@extends('layouts.admin')

@section('title', 'Buổi hướng dẫn')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">🎓 Buổi hướng dẫn (Zoom)</h1>
        <p class="text-sm text-gray-500 mt-1">
            Các buổi thứ 7 sắp tới có học viên đăng ký. Bấm "Tạo phòng &amp; gửi" để tạo phòng Zoom
            và gửi link cho học viên + email admin. Cron cũng tự gửi trước buổi
            {{ config('guidance.send_before_hours') }} giờ.
        </p>
        @if($fake)
            <div class="mt-3 rounded-lg bg-purple-50 border border-purple-200 p-3 text-sm text-purple-800">
                🧪 Đang ở <strong>chế độ giả lập Zoom</strong> (ZOOM_FAKE=true): phòng tạo ra là link giả để test.
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        @if($rows->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">Chưa có buổi nào có người đăng ký.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Buổi (Thứ 7)</th>
                            <th class="px-5 py-3 font-medium">Số đăng ký</th>
                            <th class="px-5 py-3 font-medium">Trạng thái</th>
                            <th class="px-5 py-3 font-medium text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $key = \Illuminate\Support\Carbon::parse($row->session_date)->toDateString();
                                $s = $sessions[$key] ?? null;
                            @endphp
                            <tr class="border-b border-gray-50">
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ \Illuminate\Support\Carbon::parse($row->session_date)->format('d/m/Y') }} — {{ config('guidance.time') }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center justify-center min-w-7 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold">{{ $row->cnt }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($s && $s->sent_at)
                                        <span class="text-green-700">✓ Đã gửi {{ $s->sent_at->format('d/m H:i') }}</span>
                                    @elseif($s && $s->hasRoom())
                                        <span class="text-amber-600">Đã tạo phòng, chưa gửi</span>
                                    @else
                                        <span class="text-gray-400">Chưa tạo phòng</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <form action="{{ route('admin.guidance-sessions.activate') }}" method="POST" class="inline-block"
                                          onsubmit="return confirm('Tạo phòng và gửi link cho buổi {{ \Illuminate\Support\Carbon::parse($row->session_date)->format('d/m/Y') }}?')">
                                        @csrf
                                        <input type="hidden" name="session_date" value="{{ $key }}">
                                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                                            {{ $s && $s->sent_at ? 'Gửi lại' : 'Tạo phòng & gửi' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
