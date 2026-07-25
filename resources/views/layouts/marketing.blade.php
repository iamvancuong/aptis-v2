<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <x-seo
        :title="$__env->yieldContent('title') ?: null"
        :description="$__env->yieldContent('meta_description') ?: null"
        :keywords="$__env->yieldContent('meta_keywords') ?: null"
        :type="$__env->yieldContent('og_type') ?: 'website'"
    />

    <x-favicon />

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 antialiased">

    {{-- Header --}}
    <header x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white font-bold flex items-center justify-center">M</span>
                    <span class="font-bold text-xl text-slate-900">Mila<span class="text-blue-700">edu</span></span>
                </a>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                    <a href="{{ route('home') }}" class="hover:text-blue-700 transition-colors">Trang chủ</a>
                    <a href="{{ route('about') }}" class="hover:text-blue-700 transition-colors {{ request()->routeIs('about') ? 'text-blue-700' : '' }}">Giới thiệu</a>
                    <a href="{{ route('aptis') }}" class="hover:text-blue-700 transition-colors {{ request()->routeIs('aptis') ? 'text-blue-700' : '' }}">Luyện thi Aptis</a>
                    <a href="{{ route('home') }}#pricing" class="hover:text-blue-700 transition-colors">Bảng giá</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold text-white bg-blue-700 hover:bg-blue-800 transition-colors" data-turbo="false">Vào học</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-slate-600 hover:text-blue-700" data-turbo="false">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold text-white bg-blue-700 hover:bg-blue-800 transition-colors" data-turbo="false">Đăng ký</a>
                    @endauth
                    <button @click="open = !open" class="md:hidden p-2 text-slate-600" aria-label="Mở menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
            {{-- Menu mobile --}}
            <div x-show="open" x-cloak class="md:hidden pb-4 flex flex-col gap-2 text-sm font-medium text-slate-700">
                <a href="{{ route('home') }}" class="py-2">Trang chủ</a>
                <a href="{{ route('about') }}" class="py-2">Giới thiệu</a>
                <a href="{{ route('aptis') }}" class="py-2">Luyện thi Aptis</a>
                <a href="{{ route('home') }}#pricing" class="py-2">Bảng giá</a>
                <a href="{{ route('login') }}" class="py-2">Đăng nhập</a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-300 mt-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 grid gap-10 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center">M</span>
                    <span class="font-bold text-xl text-white">Milaedu</span>
                </div>
                <p class="text-sm leading-relaxed max-w-md text-slate-400">
                    Nền tảng luyện thi Aptis online: đề thi thử sát thật, chấm chữa Writing &amp; Speaking chi tiết.
                    Đồng hành cùng {{ config('seo.instructor.name') }} — {{ config('seo.instructor.job_title') }}.
                </p>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3 text-sm">Khám phá</h3>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="{{ route('about') }}" class="hover:text-white">Giới thiệu</a></li>
                    <li><a href="{{ route('aptis') }}" class="hover:text-white">Luyện thi Aptis</a></li>
                    <li><a href="{{ route('home') }}#pricing" class="hover:text-white">Bảng giá</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white">Đăng ký</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3 text-sm">Hỗ trợ</h3>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="{{ route('policy.refund') }}" class="hover:text-white">Chính sách hoàn tiền</a></li>
                    @if(config('seo.contact.email'))
                        <li><a href="mailto:{{ config('seo.contact.email') }}" class="hover:text-white">{{ config('seo.contact.email') }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-slate-500">
                © {{ date('Y') }} Milaedu · Luyện thi Aptis online.
            </div>
        </div>
    </footer>

</body>
</html>
