<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - APTIS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden md:hidden" style="background-color: rgba(0, 0, 0, 0.5);"></div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="admin-sidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-xl font-bold text-blue-600">Admin Panel</h2>
                <!-- Desktop collapse button -->
                <button id="sidebar-toggle" class="hidden md:block p-2 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </button>
            </div>
            <nav class="mt-4 px-2 space-y-1">
                <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="sidebar-text ml-2">Dashboard</span>
                </x-nav-link>
                <x-nav-link href="{{ route('admin.quizzes.index') }}" :active="request()->routeIs('admin.quizzes.*')">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="sidebar-text ml-2">Quizzes</span>
                </x-nav-link>
                <x-nav-link href="{{ route('admin.sets.index') }}" :active="request()->routeIs('admin.sets.*')">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span class="sidebar-text ml-2">Sets</span>
                </x-nav-link>
                <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="sidebar-text ml-2">Users</span>
                </x-nav-link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div id="main-content" class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm z-30">
                <div class="flex justify-between items-center px-4 md:px-8 py-4">
                    <div class="flex items-center space-x-4">
                        <!-- Mobile hamburger menu -->
                        <button id="mobile-menu-toggle" class="md:hidden p-2 rounded hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('admin-sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const overlay = document.getElementById('sidebar-overlay');
            
            // Load saved sidebar state from localStorage (desktop only)
            const sidebarState = localStorage.getItem('sidebarCollapsed');
            if (sidebarState === 'true' && window.innerWidth >= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            // Desktop sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    
                    // Save state to localStorage
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
            
            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    // Toggle Tailwind's translate class instead of custom class
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                });
            }
            
            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
    
    <style>
        /* Sidebar collapsed state (desktop) */
        #admin-sidebar.collapsed {
            width: 4rem;
        }
        
        /* Hide text when collapsed, show only icons */
        #admin-sidebar.collapsed h2,
        #admin-sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        /* Center icons when collapsed */
        #admin-sidebar.collapsed nav a {
            justify-content: center;
        }
        
        /* Main content expanded when sidebar collapsed */
        #main-content.expanded {
            margin-left: 0;
        }
        
        /* Smooth transitions */
        #admin-sidebar {
            transition: all 0.3s ease-in-out;
        }
    </style>
</body>
</html>
