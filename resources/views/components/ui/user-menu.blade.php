{{-- Menu tài khoản (avatar + dropdown) dùng chung cho header học viên + admin.
     Slot mặc định = các link <a> riêng của từng layout (tự nhận kiểu dòng menu),
     đặt trên "Đổi mật khẩu" / "Đăng xuất". --}}
@php
    $u = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($u->name)))->filter()->take(-2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }} @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex items-center gap-2 pl-1 pr-2 sm:pr-3 py-1 rounded-full border border-gray-200 hover:bg-gray-50 transition-colors">
        <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-700 text-xs font-bold flex items-center justify-center">{{ $initials ?: 'U' }}</span>
        <span class="hidden sm:inline max-w-[10rem] truncate text-sm font-medium text-gray-700">{{ $u->name }}</span>
        <x-ui.icon name="chevron-down" class="w-4 h-4 text-gray-400" />
    </button>
    <div x-cloak x-show="open" x-transition.origin.top.right
         class="absolute right-0 mt-2 w-60 bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden py-1 z-50">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="text-sm font-semibold text-gray-900 truncate">{{ $u->name }}</div>
            <div class="text-xs text-gray-500 truncate">{{ $u->email }}</div>
        </div>
        <div class="py-1 [&_a]:block [&_a]:px-4 [&_a]:py-2 [&_a]:text-sm [&_a]:text-gray-700 [&_a:hover]:bg-gray-50">
            {{ $slot }}
            <a href="{{ route('password.change') }}">Đổi mật khẩu</a>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 pt-1">
            @csrf
            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Đăng xuất</button>
        </form>
    </div>
</div>
