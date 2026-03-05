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
        },
        expandSidebar() {
            this.isDesktopCollapsed = false;
            localStorage.setItem('sidebarCollapsed', false);
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
                <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto custom-scrollbar" x-data="{ 
                    activeMenu: @if(request()->routeIs('admin.sets.*', 'admin.questions.*')) 'rl' 
                                @elseif(request()->routeIs('admin.writing-sets.*', 'admin.writing-reviews.*')) 'writing'
                                @elseif(request()->routeIs('admin.speaking-sets.*', 'admin.speaking-reviews.*')) 'speaking'
                                @elseif(request()->routeIs('admin.grammar-sets.*')) 'grammar'
                                @elseif(request()->routeIs('admin.mock-tests.*', 'admin.reports.*')) 'reports'
                                @elseif(request()->routeIs('admin.feedback.*', 'admin.high-scores.*')) 'interface'
                                @else null @endif
                }">
                    {{-- System Group --}}
                    <div class="mb-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2" x-show="!isDesktopCollapsed || isMobile">Hệ thống</p>
                        <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300" :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">Dashboard</span>
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')" class="flex items-center mt-1" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300" :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">Học viên</span>
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.settings.index') }}" :active="request()->routeIs('admin.settings.*')" class="flex items-center mt-1" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300" :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">Cài đặt</span>
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.instructions.index') }}" :active="request()->routeIs('admin.instructions.*')" class="flex items-center mt-1" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300" :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">Hướng dẫn</span>
                        </x-nav-link>
                    </div>

                    {{-- Reading & Listening Group --}}
                    <div class="mb-2" x-data="{ open: activeMenu === 'rl' }">
                        <button @click="open = !open; if(open) expandSidebar()" class="w-full flex items-center justify-between px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors group" :class="{'justify-center px-0': (isDesktopCollapsed && !isMobile), 'bg-gray-50': activeMenu === 'rl'}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <span class="ml-3 font-semibold whitespace-nowrap" x-show="!isDesktopCollapsed || isMobile">R & L Skill</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" x-show="!isDesktopCollapsed || isMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open && (!isDesktopCollapsed || isMobile)" x-transition class="mt-1 space-y-1 ml-4 border-l-2 border-blue-100 pl-2" x-cloak>
                            <x-nav-link href="{{ route('admin.sets.index') }}" :active="request()->routeIs('admin.sets.*')" class="text-sm py-1.5">Bộ đề R&L</x-nav-link>
                            <x-nav-link href="{{ route('admin.questions.reading') }}" :active="request()->routeIs('admin.questions.reading')" class="text-sm py-1.5">Câu hỏi Reading</x-nav-link>
                            <x-nav-link href="{{ route('admin.questions.listening') }}" :active="request()->routeIs('admin.questions.listening')" class="text-sm py-1.5">Câu hỏi Listening</x-nav-link>
                        </div>
                    </div>

                    {{-- Writing Group --}}
                    <div class="mb-2" x-data="{ open: activeMenu === 'writing' }">
                        <button @click="open = !open; if(open) expandSidebar()" class="w-full flex items-center justify-between px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors group" :class="{'justify-center px-0': (isDesktopCollapsed && !isMobile), 'bg-gray-50': activeMenu === 'writing'}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                                <span class="ml-3 font-semibold whitespace-nowrap" x-show="!isDesktopCollapsed || isMobile">Writing Skill</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" x-show="!isDesktopCollapsed || isMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open && (!isDesktopCollapsed || isMobile)" x-transition class="mt-1 space-y-1 ml-4 border-l-2 border-emerald-100 pl-2" x-cloak>
                            <x-nav-link href="{{ route('admin.writing-sets.index') }}" :active="request()->routeIs('admin.writing-sets.*')" class="text-sm py-1.5">Bộ đề Writing</x-nav-link>
                            <x-nav-link href="{{ route('admin.writing-reviews.index') }}" :active="request()->routeIs('admin.writing-reviews.*')" class="text-sm py-1.5">Bài chờ chấm</x-nav-link>
                        </div>
                    </div>

                    {{-- Speaking Group --}}
                    <div class="mb-2" x-data="{ open: activeMenu === 'speaking' }">
                        <button @click="open = !open; if(open) expandSidebar()" class="w-full flex items-center justify-between px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors group" :class="{'justify-center px-0': (isDesktopCollapsed && !isMobile), 'bg-gray-50': activeMenu === 'speaking'}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                                <span class="ml-3 font-semibold whitespace-nowrap" x-show="!isDesktopCollapsed || isMobile">Speaking Skill</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" x-show="!isDesktopCollapsed || isMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open && (!isDesktopCollapsed || isMobile)" x-transition class="mt-1 space-y-1 ml-4 border-l-2 border-rose-100 pl-2" x-cloak>
                            <x-nav-link href="{{ route('admin.speaking-sets.index') }}" :active="request()->routeIs('admin.speaking-sets.*')" class="text-sm py-1.5">Bộ đề Speaking</x-nav-link>
                            <x-nav-link href="{{ route('admin.speaking-reviews.index') }}" :active="request()->routeIs('admin.speaking-reviews.*')" class="text-sm py-1.5">Chấm Speaking</x-nav-link>
                        </div>
                    </div>

                    {{-- Grammar Group --}}
                    <div class="mb-2">
                        <x-nav-link href="{{ route('admin.grammar-sets.index') }}" :active="request()->routeIs('admin.grammar-sets.*')" class="flex items-center" x-bind:class="{'justify-center px-0': (isDesktopCollapsed && !isMobile)}">
                            <svg class="w-5 h-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="ml-3 font-semibold whitespace-nowrap transition-opacity duration-300" :class="{'opacity-0 hidden': (isDesktopCollapsed && !isMobile), 'opacity-100 block': !(isDesktopCollapsed && !isMobile)}">Grammar Skill</span>
                        </x-nav-link>
                    </div>

                    {{-- Reports Group --}}
                    <div class="mb-2 pt-4 border-t border-gray-100" x-data="{ open: activeMenu === 'reports' }">
                        <button @click="open = !open; if(open) expandSidebar()" class="w-full flex items-center justify-between px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors group" :class="{'justify-center px-0': (isDesktopCollapsed && !isMobile), 'bg-gray-50': activeMenu === 'reports'}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="ml-3 font-semibold whitespace-nowrap" x-show="!isDesktopCollapsed || isMobile">Báo cáo</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" x-show="!isDesktopCollapsed || isMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open && (!isDesktopCollapsed || isMobile)" x-transition class="mt-1 space-y-1 ml-4 border-l-2 border-indigo-100 pl-2" x-cloak>
                            <x-nav-link href="{{ route('admin.mock-tests.index') }}" :active="request()->routeIs('admin.mock-tests.*')" class="text-sm py-1.5">Mock Tests</x-nav-link>
                            <x-nav-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')" class="text-sm py-1.5">Tổng quan</x-nav-link>
                        </div>
                    </div>

                    {{-- Interface Group --}}
                    <div class="mb-2 pt-4 border-t border-gray-100" x-data="{ open: activeMenu === 'interface' }">
                        <button @click="open = !open; if(open) expandSidebar()" class="w-full flex items-center justify-between px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors group" :class="{'justify-center px-0': (isDesktopCollapsed && !isMobile), 'bg-gray-50': activeMenu === 'interface'}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="ml-3 font-semibold whitespace-nowrap" x-show="!isDesktopCollapsed || isMobile">Giao diện</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" x-show="!isDesktopCollapsed || isMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open && (!isDesktopCollapsed || isMobile)" x-transition class="mt-1 space-y-1 ml-4 border-l-2 border-pink-100 pl-2" x-cloak>
                            <x-nav-link href="{{ route('admin.feedback.index') }}" :active="request()->routeIs('admin.feedback.*')" class="text-sm py-1.5">Feedback</x-nav-link>
                            <x-nav-link href="{{ route('admin.high-scores.index') }}" :active="request()->routeIs('admin.high-scores.*')" class="text-sm py-1.5">Bảng vàng</x-nav-link>
                        </div>
                    </div>
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
    <!-- Bulk Delete Script -->
    <script>
        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('.bulk-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
            toggleBulkDeleteBtn();
        }

        function toggleBulkDeleteBtn() {
            const btn = document.getElementById('bulk-delete-btn');
            if (!btn) return;
            const checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
            if (checkedCount > 0) {
                btn.style.display = 'inline-flex';
                btn.querySelector('.count').innerText = checkedCount;
            } else {
                btn.style.display = 'none';
            }
        }

        // Add event listeners to individual checkboxes
        document.addEventListener('change', function(e) {
            if(e.target && e.target.classList.contains('bulk-checkbox')) {
                toggleBulkDeleteBtn();
                
                // Update Select All checkbox state
                const selectAllCb = document.getElementById('selectAllCheckbox');
                if(selectAllCb) {
                    const total = document.querySelectorAll('.bulk-checkbox').length;
                    const checked = document.querySelectorAll('.bulk-checkbox:checked').length;
                    selectAllCb.checked = (total > 0 && total === checked);
                }
            }
        });

        async function bulkDelete() {
            const checkboxes = document.querySelectorAll('.bulk-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Vui lòng chọn ít nhất một mục để xoá.');
                return;
            }

            if (!confirm(`Bạn có chắc muốn xoá vĩnh viễn ${checkboxes.length} mục đã chọn? Hành động này không thể hoàn tác.`)) {
                return;
            }

            const btn = document.getElementById('bulk-delete-btn');
            if(btn) {
                btn.disabled = true;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = `<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Đang xoá...`;
            }

            for (let cb of checkboxes) {
                const id = cb.value;
                const form = document.getElementById(`delete-form-${id}`);
                if (form) {
                    try {
                        const formData = new FormData(form);
                        await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                    } catch (e) {
                        console.error('Lỗi khi xoá ID ' + id, e);
                    }
                }
            }
            
            window.location.reload();
        }
    </script>
    @stack('scripts')
</body>
</html>
