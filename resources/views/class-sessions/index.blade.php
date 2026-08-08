@extends('layouts.app')

@section('title', 'Lớp học online - Milaedu')

@section('content')
{{-- Không cần nhánh "tài khoản hết hạn": middleware CheckAccountExpiration (áp
     cho mọi route web) đã logout người hết hạn trước khi tới được trang này. --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Lớp học online</h1>
    <p class="mt-2 text-gray-600">Nút “Vào lớp” bật trước giờ học {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút.</p>
    <p class="mt-1 text-gray-600">Bạn sắp xếp vào lớp đúng giờ và tham gia học đầy đủ để sớm đạt kết quả cao nhé.</p>
</div>

{{-- Địa chỉ mời vào lớp. Mặc định là email tài khoản (96% học viên đã là Gmail),
     ô này chỉ để ghi đè khi vào Meet bằng tài khoản Google khác. Mặc định đóng
     để không làm rối trang — ai cần mới mở. --}}
@php $u = auth()->user(); @endphp
<div class="mb-6 p-4 bg-white border border-gray-200 rounded-xl" x-data="{ open: {{ $errors->has('google_email') ? 'true' : 'false' }} }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <p class="text-sm text-gray-700">
            Giảng viên sẽ mời <strong class="text-gray-900">{{ $u->classInviteEmail() }}</strong> vào buổi học.
        </p>
        <button type="button" @click="open = !open"
                class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline shrink-0 text-left sm:text-right">
            <span x-show="!open">Tôi vào Meet bằng Gmail khác</span>
            <span x-show="open" x-cloak>Đóng</span>
        </button>
    </div>

    <div x-show="open" x-cloak class="mt-4 pt-4 border-t border-gray-100">
        <form action="{{ route('classes.google-email') }}" method="POST" class="flex flex-col sm:flex-row sm:items-start gap-3">
            @csrf
            <div class="flex-1">
                <label for="google_email" class="block text-sm font-semibold text-gray-800 mb-1">Gmail dùng để vào lớp</label>
                <p class="text-xs text-gray-500 mb-2">
                    Chỉ điền nếu tài khoản Google bạn đăng nhập trên máy học <strong>khác</strong> email đăng ký Milaedu.
                    Để trống thì hệ thống dùng email tài khoản. Điền đúng thì bạn <strong>vào thẳng</strong> lớp,
                    sai thì phải bấm “Yêu cầu tham gia” và chờ duyệt.
                </p>
                <input type="email" name="google_email" id="google_email"
                       value="{{ old('google_email', $u->google_email) }}"
                       placeholder="{{ $u->email }}"
                       class="w-full px-4 py-2.5 text-sm border {{ $errors->has('google_email') ? 'border-red-500' : 'border-slate-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent">
                @error('google_email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0 sm:mt-6">
                Lưu
            </button>
        </form>
    </div>
</div>

{{-- Nội quy. Chỉ nêu những điều hệ thống LÀM THẬT — học viên thử một lần là
     biết ngay lời nào bịa, mất hết tác dụng răn đe. --}}
<div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div class="text-sm text-amber-900">
            <p class="font-semibold mb-2">Nội quy lớp học online</p>
            <ul class="space-y-1.5 list-disc ml-4">
                <li><strong>Link lớp học chỉ dành riêng cho bạn.</strong> Không chia sẻ cho người khác dưới bất kỳ hình thức nào.</li>
                <li>
                    <strong>Mỗi lần vào lớp đều được ghi lại</strong>: thời gian, thiết bị và địa chỉ mạng.
                    Giảng viên xem được toàn bộ nhật ký này.
                </li>
                <li>
                    Tài khoản đăng nhập vượt quá số thiết bị cho phép sẽ bị <strong>ghi nhận vi phạm</strong>;
                    tái phạm nhiều lần thì <strong>tài khoản bị khoá</strong>.
                </li>
                <li>
                    Người không có trong danh sách lớp khi bấm link sẽ phải xin phép và
                    <strong>giảng viên có quyền từ chối hoặc mời ra khỏi phòng</strong>.
                </li>
            </ul>
            <p class="mt-2 text-xs text-amber-800">
                Học phí của bạn trả cho công sức giảng viên. Chia sẻ link là lấy đi phần đó của cô.
            </p>
        </div>
    </div>
</div>

<div class="space-y-4">
    @forelse($sessions as $session)
        <div class="bg-white rounded-xl shadow-sm border {{ $session->isLive() ? 'border-green-300' : 'border-gray-200' }} p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $session->title }}</h2>
                        @if($session->isLive())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 shrink-0">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                Đang diễn ra
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ $session->timeLabel() }}
                        @if($session->classGroup)
                            <span class="text-gray-400">·</span>
                            <span class="text-gray-700">{{ $session->classGroup->name }}</span>
                        @endif
                    </p>
                    @if($session->description)
                        <p class="mt-2 text-sm text-gray-500">{{ $session->description }}</p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if($session->isJoinable())
                        {{-- Link trỏ tới route join, KHÔNG phải link Meet: hệ thống kiểm tra rồi mới chuyển. --}}
                        <a href="{{ route('classes.join', $session) }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Vào lớp
                        </a>
                    @else
                        {{-- `isJoinable()` false vì NHIỀU lý do, không chỉ "chưa tới giờ".
                             Bản trước in thẳng "Mở lúc {joinOpensAt}" — buổi thiếu link
                             hoặc buổi mở tự do đều có `joinOpensAt` = null nên học viên
                             đọc được đúng chữ "Mở lúc" trống trơn. Phải nói lý do thật. --}}
                        <span class="inline-block px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg">
                            @if(! $session->hasMeetLink())
                                Giảng viên chưa mở phòng
                            @elseif($session->isUpcoming())
                                Mở lúc {{ $session->joinOpensAt()->format('H:i d/m') }}
                            @else
                                Chưa mở
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <svg class="w-12 h-12 text-gray-300 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <p class="text-sm font-medium text-gray-500">Chưa có buổi học nào sắp diễn ra.</p>
            <p class="text-xs text-gray-400 mt-1">Lịch học sẽ hiện ở đây khi giảng viên mở lớp.</p>
        </div>
    @endforelse
</div>
@endsection
