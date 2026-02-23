<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - APTIS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div x-data="{ 
        isMobileOpen: false,
        isDesktopCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        isMobile: window.innerWidth < 768,
        init() {
            this.isMobile = window.innerWidth < 768;
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
            });
        },
        toggleDesktop() {
            this.isDesktopCollapsed = !this.isDesktopCollapsed;
            localStorage.setItem('sidebarCollapsed', this.isDesktopCollapsed);
        }
    }" x-init="init()">
        
        <!-- Mobile Overlay -->
        <div x-show="isMobileOpen" 
             @click="isMobileOpen = false"
             class="fixed inset-0 z-[90] bg-transparent md:hidden"
             x-cloak>
        </div>

        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="fixed md:static inset-y-0 left-0 z-[100] bg-white shadow-lg transition-all duration-300 ease-in-out flex flex-col transform max-w-[80vw]"
                   :class="[
                       (isDesktopCollapsed && !isMobile) ? 'w-20' : 'w-64',
                       isMobileOpen ? 'translate-x-0' : '-translate-x-full',
                       'md:translate-x-0'
                   ]"
                   x-cloak>
                
                <!-- Sidebar Header -->
                <div class="flex items-center justify-between h-16 px-4 shadow-[0_1px_0_rgba(0,0,0,0.08)]">
                    <h2 class="text-xl font-bold text-blue-600 overflow-hidden whitespace-nowrap transition-all duration-300"
                        :class="{'w-0 opacity-0': (isDesktopCollapsed && !isMobile), 'w-auto opacity-100': !(isDesktopCollapsed && !isMobile)}">
                        Admin Panel
                    </h2>
                    <!-- Desktop collapse button -->
                    <button type="button" @click="toggleDesktop()" class="hidden md:block p-2 rounded hover:bg-gray-100 focus:outline-none">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" x-show="isDesktopCollapsed"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" x-show="!isDesktopCollapsed"></path>
                        </svg>
                    </button>
                    <!-- Mobile close button -->
                    <button type="button" @click.stop="isMobileOpen = false" class="md:hidden p-2 rounded hover:bg-gray-100 focus:outline-none z-50">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
                    <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Dashboard
                        </span>
                    </x-nav-link>

                    <!-- Quizzes link removed as they are seeded -->

                    <x-nav-link href="{{ route('admin.sets.index') }}" :active="request()->routeIs('admin.sets.*')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Sets (R & L)
                        </span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.writing-sets.index') }}" :active="request()->routeIs('admin.writing-sets.*')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Writing Sets
                        </span>
                    </x-nav-link>

                     <x-nav-link href="{{ route('admin.questions.reading') }}" :active="request()->routeIs('admin.questions.reading')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Questions (Reading)
                        </span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.questions.listening') }}" :active="request()->routeIs('admin.questions.listening')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Questions (Listening)
                        </span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.writing-reviews.index') }}" :active="request()->routeIs('admin.writing-reviews.*')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Bài cần chấm
                        </span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                              :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">
                            Users
                        </span>
                    </x-nav-link>
                   
                </nav>
            </aside>
     
            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300">
                <header class="bg-white shadow-sm z-30">
                    <div class="flex justify-between items-center px-4 md:px-8 py-4">
                        <div class="flex items-center space-x-4">
                            <!-- Mobile hamburger menu -->
                            <button @click="isMobileOpen = !isMobileOpen" class="md:hidden p-2 rounded hover:bg-gray-100 focus:outline-none">
                                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h1 class="text-xl md:text-2xl font-semibold">@yield('header', 'Dashboard')</h1>
                        </div>
                        <div class="flex items-center space-x-2 md:space-x-4">
                            <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 hover:text-emerald-800 transition-colors border border-emerald-200">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Về Giao diện Học viên
                            </a>
                            <div class="hidden sm:block h-5 w-px bg-gray-300 mx-2"></div>
                            
                            <span class="text-xs md:text-sm text-gray-700 font-medium">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-button type="submit" variant="secondary" class="text-xs md:text-sm">Đăng xuất</x-button>
                            </form>
                        </div>
                    </div>
                </header>
     
                <main class="flex-1 overflow-y-auto p-4 md:p-8">
                    @if(session('success'))
                        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
                    @endif
                    
                    @if(session('error'))
                        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
                    @endif
     
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
