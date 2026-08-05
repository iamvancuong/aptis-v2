@extends('layouts.admin')

@section('title', 'Lớp online - Admin')
@section('header', 'Lớp học online')

@section('content')

<div class="mb-6 flex justify-end">
    <a href="{{ route('admin.class-tools.index') }}"
       class="px-4 py-2 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg transition-colors">
        🔍 Kiểm tra lớp online
    </a>
</div>

{{-- Danh sách mời qua Google Calendar. Đây là cách DUY NHẤT hiện có để người
     ngoài không vào thẳng được: mời đích danh học viên còn hạn, đặt phòng ở mức
     hạn chế. Không mời ai mà tắt "Truy cập nhanh" thì CẢ LỚP phải xin duyệt tay. --}}
<x-card class="mb-6">
    <div x-data="{ copied: false, list: @js(implode(', ', $guestEmails)) }">
        {{-- 🔴 Danh sách này gom TOÀN TRƯỜNG. Từ khi có lớp, dán nó vào sự kiện
             Calendar của một lớp là mời cả người ngoài lớp đó vào — phá đúng thứ
             việc chia lớp dựng lên, mà Google không báo lỗi gì. Có lớp thì đẩy
             admin sang danh sách riêng của từng lớp trước khi họ kịp bấm Copy. --}}
        @if($lopHoc->isNotEmpty())
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm font-semibold text-amber-900 mb-1">
                    Đã chia lớp — hãy dùng danh sách mời CỦA TỪNG LỚP
                </p>
                <p class="text-xs text-amber-800 mb-2">
                    Danh sách bên dưới gồm <strong>mọi học viên còn hạn của cả trường</strong>. Dán nó vào
                    sự kiện Calendar của một lớp là mời luôn cả người không thuộc lớp đó vào thẳng phòng.
                    Chỉ dùng nó cho <strong>buổi không gắn lớp</strong> (workshop, buổi mở cho tất cả).
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($lopHoc as $l)
                        <a href="{{ route('admin.class-groups.members', $l) }}"
                           class="px-3 py-2 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">
                            Danh sách mời: {{ $l->name }} ({{ $l->members_count }})
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Danh sách mời TOÀN TRƯỜNG (chỉ cho buổi không gắn lớp)
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong>{{ count($guestEmails) }}</strong> địa chỉ dùng được.
                    @if($nonGmailCount > 0)
                        <span class="text-amber-700">{{ $nonGmailCount }} địa chỉ không phải @gmail.com — nếu ai đó không vào thẳng được, nhắc họ khai Gmail ở trang “Lớp học”.</span>
                    @endif
                    @if($sapHetHan > 0)
                        <span class="block mt-1">{{ $sapHetHan }} người hết hạn trong 7 ngày tới — danh sách này là <strong>ảnh chụp lúc copy</strong>, nhớ cập nhật lại lời mời sau đó.</span>
                    @endif
                </p>
            </div>
            @if(count($guestEmails) > 0)
                <button type="button"
                        @click="navigator.clipboard.writeText(list); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
                    <span x-show="!copied">Copy {{ count($guestEmails) }} địa chỉ</span>
                    <span x-show="copied" x-cloak>✓ Đã copy</span>
                </button>
            @endif
        </div>

        @if(count($guestEmails) > 0)
            <textarea readonly rows="2" x-text="list"
                      class="w-full px-3 py-2 text-xs font-mono border border-gray-200 rounded-lg bg-gray-50 text-gray-600"></textarea>
        @else
            <p class="text-sm text-gray-500 italic">Chưa học viên nào khai Gmail. Nhắc học viên điền ở trang “Lớp học”.</p>
        @endif

        @if($emailHong->isNotEmpty())
            {{-- Địa chỉ gõ nhầm tên miền: hợp lệ về cú pháp nhưng không tồn tại.
                 Đã loại khỏi danh sách copy, hiện ra đây để admin liên hệ sửa. --}}
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-xs font-semibold text-red-800 mb-2">
                    {{ $emailHong->count() }} địa chỉ sai — đã loại khỏi danh sách mời
                </p>
                <p class="text-xs text-red-700 mb-2">
                    Các email này gõ nhầm tên miền nên <strong>không tồn tại</strong>. Mời vào cũng vô ích:
                    những bạn này buổi nào cũng phải xin duyệt. Liên hệ để họ sửa lại email tài khoản,
                    hoặc bảo họ khai Gmail đúng ở trang “Lớp học”.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <tbody class="divide-y divide-gray-200">
                            @foreach($emailHong as $r)
                                <tr>
                                    <td class="py-1.5 pr-4 font-medium text-gray-800 whitespace-nowrap">{{ $r['user']->name }}</td>
                                    <td class="py-1.5 pr-4 font-mono text-red-700 whitespace-nowrap">{{ $r['email'] }}</td>
                                    <td class="py-1.5 text-gray-600 whitespace-nowrap">
                                        @if($r['goi_y'])
                                            có phải là <span class="font-mono text-green-700">{{ $r['goi_y'] }}</span> ?
                                        @else
                                            sai định dạng
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($canGoBo->isNotEmpty())
            {{-- Người hết hạn nhưng vẫn nằm trong lời mời Calendar cũ: họ có link
                 trong lịch nên vào Meet thẳng, KHÔNG qua cổng web. Phải gỡ tay. --}}
            <div class="mt-3 p-3 bg-orange-50 border border-orange-200 rounded-lg"
                 x-data="{ copied: false, list: @js($canGoBo->map->classInviteEmail()->unique()->values()->implode(', ')) }">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-2">
                    <div>
                        <p class="text-xs font-bold text-orange-800">
                            {{ $canGoBo->count() }} người đã hết hạn — cần GỠ khỏi lời mời Calendar
                        </p>
                        <p class="text-xs text-orange-800 mt-1">
                            Những người này vẫn còn lời mời trong lịch của họ, nên <strong>vào thẳng phòng Meet được
                            mà không đi qua website</strong> — hệ thống không chặn được. Mở sự kiện Calendar, xoá các
                            địa chỉ dưới đây khỏi ô Khách mời.
                        </p>
                    </div>
                    <button type="button"
                            @click="navigator.clipboard.writeText(list); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors shrink-0">
                        <span x-show="!copied">Copy danh sách cần gỡ</span>
                        <span x-show="copied" x-cloak>✓ Đã copy</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <tbody class="divide-y divide-gray-200">
                            @foreach($canGoBo as $u)
                                <tr>
                                    <td class="py-1.5 pr-4 font-medium text-gray-800 whitespace-nowrap">{{ $u->name }}</td>
                                    <td class="py-1.5 pr-4 font-mono text-orange-800 whitespace-nowrap">{{ $u->classInviteEmail() }}</td>
                                    <td class="py-1.5 text-gray-600 whitespace-nowrap" style="font-variant-numeric: tabular-nums">hết hạn {{ $u->expires_at->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('admin.class-sessions.invite-synced') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-orange-800 underline hover:no-underline">
                        Tôi đã gỡ xong — ẩn danh sách này đi
                    </button>
                    <span class="text-xs text-orange-700 ml-2">
                        @if($lanDongBo)
                            (lần cuối xác nhận: {{ \Illuminate\Support\Carbon::parse($lanDongBo)->format('H:i d/m/Y') }})
                        @else
                            (đang tính trong 60 ngày gần nhất)
                        @endif
                    </span>
                </form>
            </div>
        @endif

        {{-- Hướng dẫn phải mô tả quy trình ĐANG chạy, không phải quy trình lúc
             viết ra nó. Bản cũ ("làm 1 lần cho mỗi buổi") viết khi chưa có sự
             kiện lặp và chưa có buổi tự sinh — nay cả hai đều cố định, nên việc
             lặp lại duy nhất còn lại là cập nhật ô Khách mời. --}}
        <div class="mt-3 flex gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-xs text-blue-900">
                <p class="font-semibold mb-1">Cách dùng — lịch đã cố định, không phải dựng lại mỗi tuần</p>
                <ul class="list-disc ml-4 space-y-0.5">
                    <li><strong>Sự kiện Calendar lặp lại</strong> + <strong>buổi trên web tự sinh hằng tuần</strong> → hằng tuần không phải làm gì.</li>
                    <li><strong>Việc lặp lại duy nhất:</strong> mỗi khi thành viên lớp đổi (có người mới, có người hết hạn, nhóm thi sang tuần mới) → copy danh sách mời <strong>của lớp đó</strong> rồi <strong>dán đè</strong> vào ô Khách của sự kiện tương ứng.</li>
                    <li><strong>Làm 1 lần cho mỗi phòng mới:</strong> tắt “Truy cập nhanh”, bỏ tick “Mời những người khác” và “Xem danh sách khách mời”.</li>
                </ul>
                <p class="mt-1.5">Kết quả: người được mời <strong>vào thẳng</strong>, người ngoài dù có link vẫn phải xin duyệt.</p>
            </div>
        </div>
    </div>
</x-card>

<x-card>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Danh sách buổi học</h2>
            <p class="text-sm text-gray-500 mt-1">
                Tạo phòng trên Google Meet rồi dán link vào buổi học. Học viên còn hạn sẽ thấy nút “Vào lớp” trong khung giờ.
            </p>
        </div>
        <x-button :href="route('admin.class-sessions.create')" class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm buổi học
        </x-button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buổi học</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link phòng</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sessions as $session)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $session->title }}</div>
                            @if($session->classGroup)
                                <div class="text-xs text-blue-700">Lớp: {{ $session->classGroup->name }}</div>
                            @else
                                <div class="text-xs text-amber-700">Mở cho MỌI học viên còn hạn</div>
                            @endif
                            @if($session->extra_members_count)
                                <div class="text-xs text-gray-500">+{{ $session->extra_members_count }} khách mời riêng</div>
                            @endif
                            @if($session->description)
                                <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $session->description }}">{{ $session->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @if($session->isAlwaysOpen())
                                <span class="text-gray-500 italic">Mở tự do</span>
                            @else
                                <div>{{ $session->starts_at?->format('d/m/Y H:i') ?? 'Mở ngay' }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $session->ends_at ? 'đến ' . $session->ends_at->format('H:i') : 'không tự đóng' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $variant = match(true) {
                                    !$session->is_active => 'default',
                                    $session->hasEnded() => 'default',
                                    $session->isLive()   => 'success',
                                    default              => 'warning',
                                };
                            @endphp
                            <x-badge :variant="$variant">{{ $session->statusLabel() }}</x-badge>
                            @if($session->is_active && $session->isUpcoming())
                                {{-- Nói rõ giờ CỬA MỞ, không phải giờ bắt đầu: hai giờ này
                                     lệch nhau {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút
                                     và học viên kêu "không vào được" chính là ở khoảng giữa. --}}
                                <span class="block mt-1 text-xs text-amber-700">
                                    Mở lúc {{ $session->joinOpensAt()->format('H:i d/m') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php $link = $session->effectiveMeetLink(); @endphp
                            @if($link)
                                <a href="{{ $link }}" target="_blank" rel="noopener"
                                   class="text-sm text-blue-600 hover:text-blue-800 underline max-w-[14rem] truncate inline-block align-bottom"
                                   title="{{ $link }}">{{ $link }}</a>
                                @if(! $session->meet_link)
                                    <span class="block text-xs text-gray-500">kế thừa từ lớp</span>
                                @endif
                            @else
                                {{-- Không có link ở cả buổi lẫn lớp: `isJoinable()` trả false nên
                                     học viên không thấy nút. Nói rõ để admin biết mà dán link. --}}
                                <span class="text-sm text-red-600">Chưa có link — học viên không vào được</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('admin.class-sessions.joins', $session) }}" class="text-slate-600 hover:text-slate-900 bg-slate-100 px-2 py-1.5 rounded-md hover:bg-slate-200 transition-colors text-xs font-medium" title="Nhật ký vào lớp">
                                    Nhật ký ({{ $session->joins_count }})
                                </a>
                                <a href="{{ route('admin.class-sessions.edit', $session) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1.5 rounded-md hover:bg-indigo-100 transition-colors" title="Chỉnh sửa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form action="{{ route('admin.class-sessions.destroy', $session) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá buổi học này? Hành động không thể hoàn tác.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1.5 rounded-md hover:bg-red-100 transition-colors" title="Xóa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <p class="text-sm font-medium">Chưa có buổi học nào.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    @endif
</x-card>
@endsection
