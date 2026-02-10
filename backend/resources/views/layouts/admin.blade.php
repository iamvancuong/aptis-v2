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
<body class="bg-gray-100" x-data="{ 
    isMobileOpen: false,
    isDesktopCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    toggleDesktop() {
        this.isDesktopCollapsed = !this.isDesktopCollapsed;
        localStorage.setItem('sidebarCollapsed', this.isDesktopCollapsed);
    }
}">
    <!-- Mobile Overlay -->
    <div x-show="isMobileOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isMobileOpen = false"
         class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
         x-cloak>
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside :class="isDesktopCollapsed ? 'w-20' : 'w-64'"
               class="fixed md:static inset-y-0 left-0 z-50 bg-white shadow-lg transition-all duration-300 ease-in-out flex flex-col"
               :class="{'translate-x-0': isMobileOpen, '-translate-x-full': !isMobileOpen, 'md:translate-x-0': true}"
               x-cloak>
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-17 px-4 shadow-[0_1px_0_rgba(0,0,0,0.08)]">
                <h2 class="text-xl font-bold text-blue-600 overflow-hidden whitespace-nowrap transition-all duration-300"
                    :class="{'w-0 opacity-0': isDesktopCollapsed, 'w-auto opacity-100': !isDesktopCollapsed}">
                    Admin Panel
                </h2>
                <!-- Desktop collapse button -->
                <button @click="toggleDesktop()" class="hidden md:block p-2 rounded hover:bg-gray-100 focus:outline-none">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" x-show="isDesktopCollapsed"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" x-show="!isDesktopCollapsed"></path>
                    </svg>
                </button>
                <!-- Mobile close button -->
                <button @click="isMobileOpen = false" class="md:hidden p-2 rounded hover:bg-gray-100 focus:outline-none">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
                <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" class="flex items-center" x-bind:class="{'justify-center px-0': isDesktopCollapsed}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                          :class="{'opacity-0 hidden': isDesktopCollapsed, 'opacity-100 block': !isDesktopCollapsed}">
                        Dashboard
                    </span>
                </x-nav-link>

                <x-nav-link href="{{ route('admin.quizzes.index') }}" :active="request()->routeIs('admin.quizzes.*')" class="flex items-center" x-bind:class="{'justify-center px-0': isDesktopCollapsed}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                          :class="{'opacity-0 hidden': isDesktopCollapsed, 'opacity-100 block': !isDesktopCollapsed}">
                        Quizzes
                    </span>
                </x-nav-link>

                <x-nav-link href="{{ route('admin.sets.index') }}" :active="request()->routeIs('admin.sets.*')" class="flex items-center" x-bind:class="{'justify-center px-0': isDesktopCollapsed}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                          :class="{'opacity-0 hidden': isDesktopCollapsed, 'opacity-100 block': !isDesktopCollapsed}">
                        Sets
                    </span>
                </x-nav-link>

                <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')" class="flex items-center" x-bind:class="{'justify-center px-0': isDesktopCollapsed}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                          :class="{'opacity-0 hidden': isDesktopCollapsed, 'opacity-100 block': !isDesktopCollapsed}">
                        Users
                    </span>
                </x-nav-link>

                <x-nav-link href="{{ route('admin.questions.index') }}" :active="request()->routeIs('admin.questions.*')" class="flex items-center" x-bind:class="{'justify-center px-0': isDesktopCollapsed}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="ml-3 font-medium whitespace-nowrap transition-opacity duration-300"
                          :class="{'opacity-0 hidden': isDesktopCollapsed, 'opacity-100 block': !isDesktopCollapsed}">
                        Questions
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
                        <span class="text-xs md:text-sm text-gray-700">{{ auth()->user()->name }}</span>
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
</body>
</html>
