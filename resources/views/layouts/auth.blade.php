<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <x-seo
        :title="$__env->yieldContent('title') ?: null"
        :description="$__env->yieldContent('meta_description') ?: null"
        :noindex="$__env->yieldContent('noindex') ? true : false"
    />
    <x-favicon />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen flex flex-col">

        {{-- Header --}}
        <header class="px-4 sm:px-6">
            <div class="max-w-6xl mx-auto h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white font-bold flex items-center justify-center">M</span>
                    <span class="font-bold text-xl text-slate-900">Mila<span class="text-blue-700">edu</span></span>
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Về trang chủ
                </a>
            </div>
        </header>

        {{-- Nội dung căn giữa --}}
        <main class="flex-1 flex justify-center @yield('form_align', 'items-center') px-4 py-8 sm:px-6">
            <div class="w-full @yield('form_width', 'max-w-md')">
                @if(session('success'))
                    <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
                @endif
                @if(session('error'))
                    <x-alert type="error" class="mb-5">{{ session('error') }}</x-alert>
                @endif

                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="py-6 text-center text-xs text-slate-400">
            © {{ date('Y') }} Milaedu · Luyện thi Aptis
        </footer>

    </div>
</body>
</html>
