@extends('layouts.app')

@section('title', 'Buổi hướng dẫn học')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">🎓 Buổi hướng dẫn học</h1>
        <p class="text-gray-500 mt-1">Diễn ra vào <strong>{{ $timeLabel }}</strong>. Chọn 1 buổi thứ 7 trong thời hạn tài khoản của bạn — link Zoom sẽ được gửi về email.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    {{-- Lịch hiện tại --}}
    @if($booking)
        <div class="mb-6 rounded-2xl bg-blue-50 border border-blue-200 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">Lịch đã đặt</div>
            <div class="text-lg font-bold text-gray-900">
                Thứ 7 ngày {{ $booking->session_date->format('d/m/Y') }} — {{ config('guidance.time') }}
            </div>
            @if($booking->zoom_link)
                <a href="{{ $booking->zoom_link }}" target="_blank" class="inline-block mt-2 text-sm text-blue-600 font-medium hover:underline">Vào phòng Zoom →</a>
            @endif
            <p class="text-xs text-gray-500 mt-2">Bạn có thể đổi sang buổi khác bên dưới.</p>
        </div>
    @endif

    {{-- Chọn thứ 7 --}}
    @if($saturdays->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
            Hiện chưa có buổi thứ 7 nào trong thời hạn tài khoản của bạn. Vui lòng gia hạn để tiếp tục tham gia.
        </div>
    @else
        <form method="POST" action="{{ route('guidance.store') }}">
            @csrf
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($saturdays as $sat)
                    @php $val = $sat->toDateString(); $isBooked = $booking && $booking->session_date->toDateString() === $val; @endphp
                    <label class="cursor-pointer">
                        <input type="radio" name="session_date" value="{{ $val }}" class="peer sr-only" {{ $isBooked ? 'checked' : '' }}>
                        <div class="rounded-xl border-2 p-3 text-center transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 border-gray-200 hover:border-gray-300">
                            <div class="text-xs text-gray-500">Thứ 7</div>
                            <div class="font-bold text-gray-900">{{ $sat->format('d/m') }}</div>
                            <div class="text-xs text-gray-400">{{ $sat->format('Y') }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('session_date') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror

            <button type="submit" class="mt-5 w-full py-3 px-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                {{ $booking ? 'Đổi buổi & gửi lại email' : 'Đặt lịch & gửi email' }}
            </button>
        </form>
    @endif
</div>
@endsection
