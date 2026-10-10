<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - APTIS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-gray-900 antialiased">
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
            <aside class="fixed md:static inset-y-0 left-0 z-[100] bg-white border-r border-gray-200 transition-all duration-300 ease-in-out flex flex-col transform max-w-[80vw]"
                   :class="[
                       (isDesktopCollapsed && !isMobile) ? 'w-20' : 'w-64',
                       isMobileOpen ? 'translate-x-0' : '-translate-x-full',
                       'md:translate-x-0'
                   ]"
                   x-cloak>
                
                <!-- Sidebar Header -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                    <div class="overflow-hidden transition-all duration-300"
                         :class="{'w-0 opacity-0': (isDesktopCollapsed && !isMobile), 'w-auto opacity-100': !(isDesktopCollapsed && !isMobile)}">
                        <x-ui.brand :href="route('admin.dashboard')" tag="Admin" />
                    </div>
                    <!-- Desktop collapse button -->
                    <button type="button" @click="toggleDesktop()" aria-label="Thu gọn / mở rộng menu"
                            class="hidden md:flex w-9 h-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                        <x-ui.icon name="menu" class="w-5 h-5" x-show="isDesktopCollapsed" />
                        <x-ui.icon name="chevron-left2" class="w-5 h-5" x-show="!isDesktopCollapsed" />
                    </button>
                    <!-- Mobile close button -->
                    <button type="button" @click.stop="isMobileOpen = false" class="md:hidden p-2 rounded hover:bg-gray-100 focus:outline-none z-50">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation: gom theo công việc (Tổng quan → Học viên → Chấm bài → Ngân hàng đề → Báo cáo & hệ thống) -->
                @php
                    $is = fn (...$p) => request()->routeIs(...$p);
                    $pending = $pendingReviews ?? ['writing' => 0, 'speaking' => 0];
                @endphp
                <nav class="flex-1 px-3 py-3 overflow-y-auto custom-scrollbar space-y-0.5">
                    <x-admin.nav-item :href="route('admin.dashboard')" icon="home" label="Tổng quan" :active="$is('admin.dashboard')" />
                    <x-admin.nav-item :href="route('admin.revenue.index')" icon="coin" label="Doanh số" :active="$is('admin.revenue.*')" />

                    <x-admin.nav-section label="Học viên" />
                    <x-admin.nav-item :href="route('admin.users.index')" icon="users" label="Tài khoản học viên" :active="$is('admin.users.*')" />
                    <x-admin.nav-item :href="route('admin.full-tests.index')" icon="flag" label="Full Test & cấp lượt" :active="$is('admin.full-tests.*')" />
                    <x-admin.nav-item :href="route('admin.security-flags.index')" icon="shield" label="Cảnh báo bảo mật" :active="$is('admin.security-flags.*')" />
                    {{-- Lớp online / Lớp học: ẩn khi tính năng lớp đang hoãn (CLASSES_ENABLED=false), giống menu học viên. --}}
                    @if(config('aptis.classes_enabled'))
                        <x-admin.nav-item :href="route('admin.class-sessions.index')" icon="video" label="Lớp online" :active="$is('admin.class-sessions.*')" />
                        <x-admin.nav-item :href="route('admin.class-groups.index')" icon="users" label="Lớp học" :active="$is('admin.class-groups.*')" />
                    @endif

                    <x-admin.nav-section label="Chấm bài" />
                    <x-admin.nav-item :href="route('admin.writing-reviews.index')" icon="pencil" label="Writing chờ chấm" :active="$is('admin.writing-reviews.*')" :badge="$pending['writing']" />
                    <x-admin.nav-item :href="route('admin.speaking-reviews.index')" icon="mic" label="Speaking chờ chấm" :active="$is('admin.speaking-reviews.*')" :badge="$pending['speaking']" />

                    <x-admin.nav-section label="Ngân hàng đề" />
                    <x-admin.nav-group icon="book" label="Reading & Listening" :active="$is('admin.sets.*', 'admin.questions.*')">
                        <x-admin.nav-item sub :href="route('admin.sets.index')" label="Bộ đề R&L" :active="$is('admin.sets.*')" />
                        <x-admin.nav-item sub :href="route('admin.questions.reading')" label="Câu hỏi Reading" :active="$is('admin.questions.reading')" />
                        <x-admin.nav-item sub :href="route('admin.questions.listening')" label="Câu hỏi Listening" :active="$is('admin.questions.listening')" />
                    </x-admin.nav-group>
                    <x-admin.nav-item :href="route('admin.writing-sets.index')" icon="pencil" label="Bộ đề Writing" :active="$is('admin.writing-sets.*')" />
                    <x-admin.nav-item :href="route('admin.speaking-sets.index')" icon="mic" label="Bộ đề Speaking" :active="$is('admin.speaking-sets.*')" />
                    <x-admin.nav-item :href="route('admin.grammar-sets.index')" icon="document" label="Grammar" :active="$is('admin.grammar-sets.*')" />

                    <x-admin.nav-section label="Báo cáo & hệ thống" />
                    <x-admin.nav-item :href="route('admin.mock-tests.index')" icon="chart" label="Lượt thi thử" :active="$is('admin.mock-tests.*')" />
                    <x-admin.nav-item :href="route('admin.reports.index')" icon="list" label="Báo cáo toàn lớp" :active="$is('admin.reports.*')" />
                    <x-admin.nav-item :href="route('admin.settings.index')" icon="cog" label="Cài đặt" :active="$is('admin.settings.*')" />
                    <x-admin.nav-group icon="layout" label="Trang chủ" :active="$is('admin.feedback.*', 'admin.high-scores.*', 'admin.instructions.*')">
                        <x-admin.nav-item sub :href="route('admin.feedback.index')" label="Phản hồi học viên" :active="$is('admin.feedback.*')" />
                        <x-admin.nav-item sub :href="route('admin.high-scores.index')" label="Bảng vàng" :active="$is('admin.high-scores.*')" />
                        <x-admin.nav-item sub :href="route('admin.instructions.index')" label="Hướng dẫn" :active="$is('admin.instructions.*')" />
                    </x-admin.nav-group>
                </nav>
            </aside>
     
            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden transition-all duration-300">
                {{-- Header admin: cùng ngôn ngữ với header học viên (logo/menu tài khoản dùng chung x-ui.*). --}}
                <header class="bg-white border-b border-gray-200 z-30">
                    <div class="flex items-center gap-3 h-16 px-4 md:px-8">
                        <button type="button" @click="isMobileOpen = !isMobileOpen" aria-label="Mở menu"
                                class="md:hidden w-10 h-10 -ml-2 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100">
                            <x-ui.icon name="menu" class="w-6 h-6" />
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400 leading-none mb-1">Quản trị</p>
                            <h1 class="text-lg font-semibold text-gray-900 truncate leading-tight">@yield('header', 'Dashboard')</h1>
                        </div>
                        <div class="ml-auto flex items-center gap-2">
                            <a href="{{ route('dashboard') }}"
                               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <x-ui.icon name="arrow-left" class="w-4 h-4" />
                                Giao diện học viên
                            </a>
                            <x-ui.user-menu>
                                <a href="{{ route('dashboard') }}">Giao diện học viên</a>
                                <a href="{{ route('admin.settings.index') }}">Cài đặt hệ thống</a>
                            </x-ui.user-menu>
                        </div>
                    </div>
                </header>
     
                <main class="flex-1 overflow-y-auto p-4 md:p-8">
                    @if(session('success'))
                        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
                    @endif
                    
                    @if(session('warning'))
                        <x-alert type="warning" class="mb-4">{{ session('warning') }}</x-alert>
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
