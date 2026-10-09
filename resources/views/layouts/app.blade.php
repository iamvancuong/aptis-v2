<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Milaedu')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Restore Tailwind's reset styles for CKEditor content */
        .ck-content strong { font-weight: bold; }
        .ck-content em { font-style: italic; }
        .ck-content u { text-decoration: underline; }
        .ck-content s { text-decoration: line-through; }
        .ck-content ul { list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .ck-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .ck-content h1 { font-size: 2em; font-weight: bold; margin-top: 0.67em; margin-bottom: 0.67em; }
        .ck-content h2 { font-size: 1.5em; font-weight: bold; margin-top: 0.83em; margin-bottom: 0.83em; }
        .ck-content h3 { font-size: 1.17em; font-weight: bold; margin-top: 1em; margin-bottom: 1em; }
        .ck-content p { margin-bottom: 0.5em; }
    </style>
</head>
<body class="bg-slate-50 text-gray-900 antialiased">
    @php
        $u = auth()->user();
        $navItems = [
            ['label' => 'Luyện tập', 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard', 'skills.*', 'sets.*', 'practice.*', 'mock-test.*', 'full-test.*', 'grammar.*', 'history.*', 'writingHistory.*', 'speakingHistory.*', 'leaderboard.*')],
        ];
        if (config('aptis.vocab.enabled')) {
            $navItems[] = ['label' => 'Từ vựng', 'url' => route('vocab.index'), 'active' => request()->routeIs('vocab.*'), 'badge' => $vocabDueCount ?? 0];
        }
        if (config('aptis.classes_enabled')) {
            $navItems[] = ['label' => 'Lớp học', 'url' => route('classes.index'), 'active' => request()->routeIs('classes.*')];
        }
        $navItems[] = ['label' => 'Hướng dẫn', 'url' => route('instructions.index'), 'active' => request()->routeIs('instructions.*')];
        $thongBao = $headerNotifications ?? [];
    @endphp
    {{-- Header học viên: menu dạng tab, gom thông báo vào chuông, gom Đổi mật khẩu /
         Đăng xuất vào menu tài khoản (trước đây nằm ngang hàng, rối mắt). --}}
    <nav x-data="{ mobile: false }" class="relative z-40 bg-white border-b border-gray-200">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16 gap-4">
                <x-ui.brand />

                {{-- Menu chính (desktop) --}}
                <div class="hidden md:flex items-center gap-1 ml-4">
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="relative inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ $item['active'] ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            {{ $item['label'] }}
                            @if(($item['badge'] ?? 0) > 0)
                                <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-white text-[11px] font-bold leading-5 text-center" title="{{ $item['badge'] }} từ đến hạn ôn">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @if($u->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50">Quản trị</a>
                    @endif

                    {{-- Chuông thông báo --}}
                    <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" aria-label="Thông báo"
                                class="relative w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors">
                            <x-ui.icon name="bell" class="w-5 h-5" />
                            @if(count($thongBao) > 0)
                                <span class="absolute top-1 right-1 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold leading-[1.1rem] text-center ring-2 ring-white">{{ count($thongBao) }}</span>
                            @endif
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right
                             class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Thông báo</div>
                            @forelse($thongBao as $tb)
                                <a href="{{ $tb['url'] }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $tb['tone'] === 'amber' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                                        <x-ui.icon :name="$tb['icon'] === 'flame' ? 'flame' : 'check'" class="w-4 h-4" />
                                    </span>
                                    <span class="flex-1 text-sm text-gray-700">{{ $tb['text'] }}</span>
                                    <span class="text-gray-300">&rsaquo;</span>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-gray-500">Bạn đã xem hết thông báo.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Menu tài khoản --}}
                    <x-ui.user-menu>
                        @if($u->isAdmin())
                            <a href="{{ route('admin.dashboard') }}">Quản trị</a>
                        @endif
                        <a href="{{ route('history.index') }}">Lịch sử làm bài</a>
                    </x-ui.user-menu>

                    {{-- Nút menu (mobile) --}}
                    <button type="button" @click="mobile = !mobile" aria-label="Mở menu"
                            class="md:hidden w-10 h-10 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100">
                        <x-ui.icon name="menu" x-show="!mobile" class="w-6 h-6" />
                        <x-ui.icon name="x" x-cloak x-show="mobile" class="w-6 h-6" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Menu chính (mobile) --}}
        <div x-cloak x-show="mobile" x-transition class="md:hidden border-t border-gray-100 bg-white px-4 py-2">
            @foreach($navItems as $item)
                <a href="{{ $item['url'] }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium {{ $item['active'] ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    {{ $item['label'] }}
                    @if(($item['badge'] ?? 0) > 0)
                        <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-white text-[11px] font-bold leading-5 text-center">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif
        
        @if(session('warning'))
            <x-alert type="warning" class="border-yellow-200 bg-yellow-50 text-yellow-800 mb-4">{{ session('warning') }}</x-alert>
        @endif

        @if(session('error'))
            <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
        @endif

        @if(session('info'))
            <x-alert type="info" class="border-blue-200 bg-blue-50 text-blue-800 mb-4">{{ session('info') }}</x-alert>
        @endif

        @yield('content')
    </main>

    @include('partials.devtools-guard')

    @stack('scripts')
</body>
</html>
