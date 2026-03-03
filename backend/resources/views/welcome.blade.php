<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milaedu - Nền tảng luyện thi chứng chỉ hàng đầu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(0,0,0,0.05) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(0,0,0,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .perspective-1000 {
            perspective: 1000px;
        }
        .tilt-card {
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.6s ease-out;
            transform-style: preserve-3d;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform: translateZ(0); /* Hardware acceleration for cleaner clipping */
        }
        .tilt-card:hover {
            transform: rotateX(7deg) rotateY(7deg) scale(1.03) translateZ(10px);
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.2);
        }
        .glass-morphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateZ(0);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased overflow-x-hidden selection:bg-blue-200 selection:text-blue-900">

    <!-- Navbar -->
    <nav x-data="{ scrolled: false, mobileMenuOpen: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 20)"
         :class="scrolled ? 'bg-white/80 backdrop-blur-md shadow-sm' : 'bg-transparent'"
         class="fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="#" class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/30">
                            A
                        </div>
                        <span class="font-bold text-2xl tracking-tight text-slate-800">Mila<span class="text-blue-600">edu</span></span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#features" class="text-slate-600 hover:text-blue-600 font-medium transition-colors">Tính năng</a>
                    <a href="#how-it-works" class="text-slate-600 hover:text-blue-600 font-medium transition-colors">Cách hoạt động</a>
                    <a href="#pricing" class="text-slate-600 hover:text-blue-600 font-medium transition-colors">Bảng giá</a>
                    
                    <div class="flex items-center gap-4 ml-4 pl-4 border-l border-slate-200">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white transition-all bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-md hover:shadow-lg hover:-translate-y-0.5 hover:from-blue-500 hover:to-indigo-500">
                                Vào học ngay
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-slate-600 hover:text-blue-600 font-semibold transition-colors">Đăng nhập</a>
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-blue-600 transition-all bg-blue-50 rounded-lg hover:bg-blue-100 ring-1 ring-inset ring-blue-600/20">
                                Đăng ký miễn phí
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-slate-600 hover:text-slate-900 focus:outline-none">
                        <svg class="h-6 w-6" x-show="!mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg class="h-6 w-6" x-show="mobileMenuOpen" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden bg-white border-b border-slate-200 shadow-lg">
            <div class="px-4 pt-2 pb-6 space-y-1">
                <a href="#features" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50">Tính năng</a>
                <a href="#how-it-works" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50">Cách hoạt động</a>
                <a href="#pricing" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50">Bảng giá</a>
                <div class="pt-4 mt-2 border-t border-slate-100 space-y-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="block w-full text-center px-4 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700">Vào học ngay</a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 rounded-md text-base font-medium text-blue-600 bg-blue-50 hover:bg-blue-100">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="block w-full text-center px-4 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700">Đăng ký miễn phí</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative min-h-screen pt-32 pb-20 sm:pt-40 sm:pb-24 overflow-hidden flex flex-col justify-center">
        <!-- Áp dụng inset-0 đè toàn bộ min-h-screen thay vì bị cắt -->
        <div class="absolute inset-0 bg-grid-pattern opacity-50 z-[-1]"></div>
        <div class="absolute inset-y-0 right-0 -z-10 w-[200%] md:w-[100%] bg-gradient-to-l from-blue-50/50 to-transparent skew-x-[-15deg] transform origin-top-right"></div>
        
        <!-- Decoration blobs -->
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute top-40 right-40 w-80 h-80 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-20 right-20 w-80 h-80 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center lg:text-left flex flex-col lg:flex-row items-center gap-16">
            
            <div class="lg:w-1/2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-semibold mb-6 ring-1 ring-inset ring-blue-600/20">
                    <span class="flex h-2 w-2 rounded-full bg-blue-600"></span>
                    Hệ thống luyện thi Aptis mới nhất 2026
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                    Chinh phục <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">Aptis</span> dễ dàng hơn bao giờ hết
                </h1>
                <p class="mt-4 text-lg sm:text-xl text-slate-600 mb-10 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    Trải nghiệm thi thử như thật với hệ thống mô phỏng 100% format chuẩn. Tích hợp AI thông minh chấm điểm và tối ưu bài viết (Writing) ngay tức thì.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold text-white transition-all bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 hover:-translate-y-1">
                            Tiếp tục học ngay
                            <svg class="w-5 h-5 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold text-white transition-all bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 hover:-translate-y-1">
                            Bắt đầu thi thử miễn phí
                            <svg class="w-5 h-5 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="#features" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold text-slate-700 transition-all bg-white rounded-xl shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300">
                            Khám phá tính năng
                        </a>
                    @endauth
                </div>
                
                <div class="mt-10 flex items-center justify-center lg:justify-start gap-8 text-sm text-slate-500 font-medium">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        100+ Đề thi chuẩn
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Chấm AI tức thì
                    </div>
                </div>
            </div>

            <!-- Hero Image/Mockup -->
            <div class="lg:w-1/2 relative hidden md:block">
                <!-- Mac mockup -->
                <div class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[8px] rounded-t-xl h-[172px] max-w-[301px] md:h-[294px] md:max-w-[512px] shadow-2xl">
                    <div class="rounded-lg overflow-hidden h-[156px] md:h-[278px] bg-white relative flex items-center justify-center">
                        <div class="absolute inset-0 bg-blue-50 opacity-50"></div>
                        <div class="text-center z-10 p-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-lg shadow-blue-500/40">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800">Your Exam Simulation</h3>
                            <div class="mt-4 flex gap-2 justify-center">
                                <div class="h-2 w-16 bg-slate-200 rounded-full"></div>
                                <div class="h-2 w-8 bg-blue-400 rounded-full"></div>
                            </div>
                            <div class="mt-2 flex gap-2 justify-center">
                                <div class="h-2 w-12 bg-slate-200 rounded-full"></div>
                                <div class="h-2 w-20 bg-slate-200 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative mx-auto bg-gray-900 dark:bg-gray-700 rounded-b-xl rounded-t-sm h-[17px] max-w-[351px] md:h-[21px] md:max-w-[597px]">
                    <div class="absolute left-1/2 top-0 -translate-x-1/2 rounded-b-xl w-[56px] h-[5px] md:w-[96px] md:h-[8px] bg-gray-800"></div>
                </div>
                
                <!-- Floating badges -->
                <div class="absolute -right-6 top-10 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 animate-bounce" style="animation-duration: 3s;">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Writing Score</p>
                            <p class="text-lg font-bold text-slate-800">C1 Achieved!</p>
                        </div>
                    </div>
                </div>
                
                <div class="absolute -left-8 bottom-20 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 animate-bounce" style="animation-duration: 4s; animation-delay: 1s;">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">AI Feedback</p>
                            <p class="text-sm font-bold text-slate-800">"Excellent grammar"</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Tại sao chọn chúng tôi?</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Mọi thứ bạn cần để đạt điểm số mơ ước
                </p>
                <p class="mt-4 max-w-2xl text-xl text-slate-500 mx-auto">
                    Hệ thống được thiết kế tỉ mỉ bởi các chuyên gia luyện thi, kết hợp cùng công nghệ AI tiên tiến để tối ưu thời gian học của bạn.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="relative group bg-slate-50 p-8 rounded-3xl transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 border border-slate-100">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-3xl -z-10"></div>
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Mô phỏng thi thực tế</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Giao diện, thời gian và áp lực làm bài được tái hiện 100% giống với kỳ thi Aptis thật của Hội đồng Anh (British Council).
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="relative group bg-slate-50 p-8 rounded-3xl transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 border border-slate-100">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-purple-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-3xl -z-10"></div>
                    <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Chấm điểm Writing bằng AI</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Nhận điểm số chuẩn CEFR và nhận xét chi tiết từng lỗi ngữ pháp, từ vựng ngay lập tức nhờ công nghệ AI Schema V3 độc quyền.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="relative group bg-slate-50 p-8 rounded-3xl transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 border border-slate-100">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-pink-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-3xl -z-10"></div>
                    <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Lịch sử & Phân tích tiến độ</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Lưu trữ toàn bộ bài làm, theo dõi lộ trình tiến bộ qua từng ngày. Biết chính xác bạn đang mạnh/yếu phần nào để khắc phục.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="py-24 bg-slate-50 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Lộ trình 3 bước</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Bắt đầu dễ dàng trong 1 phút
                </p>
            </div>

            <div class="relative">
                <!-- Connecting line for desktop -->
                <div class="hidden md:block absolute top-12 left-0 w-full h-0.5 bg-slate-200" aria-hidden="true"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    <!-- Step 1 -->
                    <div class="relative text-center">
                        <div class="w-24 h-24 mx-auto bg-white border-4 border-blue-100 rounded-full flex items-center justify-center relative z-10 shadow-sm">
                            <span class="text-3xl font-extrabold text-blue-600">1</span>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-slate-900">Tạo tài khoản miễn phí</h3>
                        <p class="mt-2 text-slate-600">Đăng ký bằng email chỉ trong 30 giây để truy cập hệ thống thi thử.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center">
                        <div class="w-24 h-24 mx-auto bg-white border-4 border-indigo-100 rounded-full flex items-center justify-center relative z-10 shadow-sm">
                            <span class="text-3xl font-extrabold text-indigo-600">2</span>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-slate-900">Thi thử & Nhận Feedback</h3>
                        <p class="mt-2 text-slate-600">Làm bộ đề thực tế, AI chấm điểm ngay và chỉ ra lỗi cần khắc phục chi tiết.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center">
                        <div class="w-24 h-24 mx-auto bg-white border-4 border-emerald-100 rounded-full flex items-center justify-center relative z-10 shadow-sm">
                            <span class="text-3xl font-extrabold text-emerald-600">3</span>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-slate-900">Cải thiện điểm số</h3>
                        <p class="mt-2 text-slate-600">Học theo lộ trình gợi ý, luyện tập các phần còn yếu để đạt target đặt ra.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- High Scores Section (Bảng vàng) -->
    <section class="py-24 bg-white relative overflow-hidden perspective-1000">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-50 rounded-full blur-[100px] opacity-70"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-3xl mx-auto mb-16" x-data="{ shown: false }" x-intersect="shown = true">
                <h2 x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" class="text-base text-emerald-600 font-bold tracking-widest uppercase mb-3">Vinh danh chiến thần</h2>
                <p x-show="shown" x-transition:enter="transition ease-out duration-700 delay-100" x-transition:enter-start="opacity-0 translate-y-4" class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                    Bảng Vàng <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500">Thành Tích</span>
                </p>
                <div x-show="shown" x-transition:enter="transition ease-out duration-1000 delay-300" x-transition:enter-start="scale-x-0" class="h-1.5 w-24 bg-emerald-500 mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                @forelse($highScores as $score)
                <div class="group perspective-1000">
                    <!-- Ensure the actual animated card handles clipping explicitly -->
                    <div class="tilt-card bg-white rounded-[2.5rem] p-4 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.1)] border border-slate-100 relative overflow-hidden isolate">
                        <!-- Card Glow Layer -->
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/20 via-transparent to-teal-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                        
                        <div class="relative z-10">
                            <!-- Image Container with unique mask - Added overflow-hidden fix -->
                            <div class="overflow-hidden rounded-[2rem] mb-5 aspect-[4/5] bg-slate-50 flex items-center justify-center relative shadow-inner isolate" style="transform: translateZ(1px);">
                                @if($score->avatar)
                                    <img src="{{ $score->avatar }}" alt="{{ $score->name }}" class="w-full h-full object-cover transform scale-[1.01] group-hover:scale-110 transition-transform duration-700 ease-in-out">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-emerald-600 bg-emerald-50 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-emerald-100/50 to-emerald-50">
                                        <span class="text-6xl font-black opacity-20 absolute select-none">GOLD</span>
                                        <span class="text-5xl font-bold relative">{{ mb_substr($score->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                
                                <!-- Floating Badge -->
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-2xl shadow-lg border border-white/50 flex items-center gap-1.5 transform translate-z-20 group-hover:translate-y-[-4px] transition-transform">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-[10px] font-bold text-slate-800 uppercase tracking-tighter">Verified</span>
                                </div>
                            </div>

                            <div class="text-center px-2" style="transform: translateZ(5px);">
                                <h4 class="text-lg font-extrabold text-slate-900 mb-1 group-hover:text-emerald-700 transition-colors line-clamp-1">{{ $score->name }}</h4>
                                <div class="inline-block">
                                    <span class="px-5 py-2 rounded-full text-[11px] font-black bg-gradient-to-r from-emerald-600 to-teal-500 text-white shadow-lg shadow-emerald-200 uppercase tracking-widest transform transition-all group-hover:shadow-emerald-300">
                                        {{ $score->certificate }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center">
                    <p class="text-slate-400 font-medium">Đang cập nhật danh sách học viên tiêu biểu...</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-24 bg-slate-50 relative overflow-hidden">
        <!-- Floating 3D Elements background -->
        <div class="absolute top-1/4 right-0 w-64 h-64 bg-blue-100 rounded-full blur-[120px] opacity-40 animate-pulse"></div>
        <div class="absolute bottom-1/4 left-0 w-64 h-64 bg-indigo-100 rounded-full blur-[120px] opacity-40"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative" 
             x-data="{ 
                active: 0,
                step: 420,
                scroll(direction) {
                    const container = this.$refs.slider;
                    const scrollAmount = direction === 'next' ? this.step : -this.step;
                    container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
             }">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-base text-blue-600 font-bold tracking-widest uppercase mb-3">Học viên nói về Milaedu</h2>
                    <p class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900">
                        Chia sẻ từ <span class="relative">
                            <span class="relative z-10">Người thật</span>
                            <span class="absolute bottom-2 left-0 w-full h-4 bg-blue-200/50 -z-10 rounded-lg"></span>
                        </span> việc thật
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <button @click="scroll('prev')" class="w-12 h-12 rounded-2xl bg-white shadow-lg border border-slate-100 flex items-center justify-center text-slate-600 hover:bg-blue-600 hover:text-white transition-all transform active:scale-95 group">
                        <svg class="w-6 h-6 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button @click="scroll('next')" class="w-12 h-12 rounded-2xl bg-white shadow-lg border border-slate-100 flex items-center justify-center text-slate-600 hover:bg-blue-600 hover:text-white transition-all transform active:scale-95 group">
                        <svg class="w-6 h-6 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Carousel Container -->
            <div class="relative">
                <div class="flex overflow-x-auto pb-12 gap-8 snap-x snap-mandatory no-scrollbar scroll-smooth -mx-4 px-4" x-ref="slider">
                    @forelse($feedbacks as $feedback)
                    <div class="flex-none w-[85vw] md:w-[400px] snap-center py-4">
                        <div class="group h-full">
                            <div class="glass-morphism h-full p-8 rounded-[2.5rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.05)] relative transition-all duration-500 hover:shadow-[0_30px_60px_-15px_rgba(59,130,246,0.15)] hover:-translate-y-2 border border-white/50">
                                <!-- Quote Icon Floating -->
                                <div class="absolute -top-6 -right-2 w-12 h-12 bg-white rounded-2xl shadow-xl flex items-center justify-center text-blue-500 transform rotate-12 group-hover:rotate-0 transition-transform duration-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H16.017C14.9124 8 14.017 7.10457 14.017 6V5C14.017 3.89543 14.9124 3 16.017 3H19.017C21.2261 3 23.017 4.79086 23.017 7V15C23.017 17.2091 21.2261 19 19.017 19H17.517C17.2409 19 17.017 19.2239 17.017 19.5V21H14.017ZM3.01697 21L3.01697 18C3.01697 16.8954 3.9124 16 5.01697 16H8.01697C8.56925 16 9.01697 15.5523 9.01697 15V9C9.01697 8.44772 8.56925 8 8.01697 8H5.01697C3.9124 8 3.01697 7.10457 3.01697 6V5C3.01697 3.89543 3.9124 3 5.01697 3H8.01697C10.2261 3 12.017 4.79086 12.017 7V15C12.017 17.2091 10.2261 19 8.01697 19H6.51697C6.24083 19 6.01697 19.2239 6.01697 19.5V21H3.01697Z"/></svg>
                                </div>

                                <div class="flex items-center gap-4 mb-8">
                                    <div class="relative">
                                        @isset($feedback->avatar)
                                            <img src="{{ $feedback->avatar }}" alt="{{ $feedback->name }}" class="w-16 h-16 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                                        @else
                                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl shadow-lg ring-4 ring-white">
                                                {{ mb_substr($feedback->name, 0, 1) }}
                                            </div>
                                        @endisset
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 rounded-full border-2 border-white flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-extrabold text-slate-900 group-hover:text-blue-600 transition-colors uppercase tracking-tight">{{ $feedback->name }}</h4>
                                        <div class="flex text-amber-400 gap-0.5 mt-1">
                                            @for($i = 0; $i < 5; $i++)
                                                <svg class="w-4 h-4 {{ $i < ($feedback->rating ?? 5) ? 'fill-current' : 'text-slate-200 fill-current' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>

                                <div class="relative">
                                    <span class="text-6xl font-serif text-blue-600/10 absolute -top-4 -left-4 select-none">“</span>
                                    <p class="text-slate-600 leading-[1.8] font-medium italic relative z-10 text-lg line-clamp-4 group-hover:line-clamp-none transition-all duration-300">
                                        {{ $feedback->content }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-16 text-center italic text-slate-400">
                        Cảm ơn sự tin tưởng của các bạn học viên...
                    </div>
                    @endforelse
                </div>
                
                <!-- Fade Gradients for visual polish -->
                <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-slate-50 to-transparent pointer-events-none"></div>
                <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-slate-50 to-transparent pointer-events-none"></div>
            </div>
        </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 bg-slate-50 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Bảng giá</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Đầu tư hợp lý cho tương lai
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Free Plan -->
                <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm flex flex-col items-center text-center">
                    <h3 class="text-xl font-bold text-slate-900">Trải nghiệm Miễn phí</h3>
                    <p class="text-slate-500 mt-2 text-sm">Phù hợp để làm quen với format thi</p>
                    <div class="my-6">
                        <span class="text-5xl font-extrabold text-slate-900">0đ</span>
                    </div>
                    <ul class="space-y-4 text-slate-600 text-left w-full mb-8">
                        <li class="flex items-start">
                            <svg class="h-6 w-6 text-emerald-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Thi thử Reading, Listening & Grammar
                        </li>
                        <li class="flex items-start text-slate-400">
                            <svg class="h-6 w-6 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Không bao gồm Writing & Speaking AI
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="w-full mt-auto inline-block py-3 px-4 bg-white text-blue-600 font-semibold rounded-xl border border-blue-200 hover:bg-blue-50 transition-colors">
                        Đăng ký ngay
                    </a>
                </div>

                <!-- Pro Plan -->
                <div class="bg-gradient-to-b from-blue-600 to-indigo-700 rounded-3xl p-8 shadow-2xl shadow-blue-500/30 flex flex-col items-center text-center relative overflow-hidden transform md:-translate-y-4">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                    <span class="absolute top-4 right-4 bg-emerald-400 text-slate-900 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Phổ biến</span>
                    <h3 class="text-xl font-bold text-white">Gói Pro Aptis</h3>
                    <p class="text-blue-100 mt-2 text-sm">Trải nghiệm Full kỹ năng như thật</p>
                    <div class="my-6">
                        <span class="text-5xl font-extrabold text-white">???</span>
                        <span class="text-blue-200">/ 3 tháng</span>
                    </div>
                    <ul class="space-y-4 text-blue-50 text-left w-full mb-8">
                        <li class="flex items-start">
                            <svg class="h-6 w-6 text-emerald-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Thi thử Full 4 kỹ năng & Grammar
                        </li>
                        <li class="flex items-start">
                            <svg class="h-6 w-6 text-emerald-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Chấm điểm Writing AI, Speaking chấm trực tiếp giáo viên & Feedback
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="w-full mt-auto inline-block py-3 px-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-slate-50 shadow-lg transition-transform hover:-translate-y-0.5">
                        Bắt đầu bản Pro
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
        <div class="absolute inset-0 bg-grid-pattern opacity-20"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-6 tracking-tight">Sẵn sàng đạt điểm C1 cùng Milaedu?</h2>
            <p class="text-lg sm:text-xl text-blue-100 mb-10">Đừng để rào cản tiếng Anh cản bước bạn. Luyện tập ngay hôm nay.</p>
            <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-10 py-4 text-lg font-bold text-slate-900 bg-white rounded-xl shadow-xl hover:bg-slate-50 transition-all hover:scale-105 hover:shadow-2xl">
                Tạo tài khoản miễn phí
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">A</div>
                <span class="font-bold text-xl text-slate-800">Milaedu</span>
            </div>
            <p class="text-slate-500 text-sm">&copy; 2026 Milaedu. Tự hào nền tảng luyện thi hàng đầu.</p>
        </div>
    </footer>

</body>
</html>
