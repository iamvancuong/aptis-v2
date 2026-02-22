<div x-show="hasAnswered(currentQuestion.id)" class="mt-8 space-y-6">
    {{-- AI Action Button --}}
    <div class="flex flex-col items-center justify-center p-6 bg-indigo-50 rounded-2xl border-2 border-dashed border-indigo-200" x-show="!aiFeedback[currentQuestion.id]">
        <div class="text-center space-y-3">
            <div class="flex items-center justify-center gap-2 text-indigo-700 font-bold text-lg">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <span>Nhận xét chi tiết bằng AI</span>
            </div>
            <p class="text-indigo-600/80 text-sm max-w-md">
                Phân tích lỗi ngữ pháp, từ vựng và gợi ý cách diễn đạt hay hơn chuẩn APTIS.
            </p>
            <button 
                @click="getAiFeedback()"
                :disabled="isSaving || isAiLoading[currentQuestion.id] || (aiUsageStatus[currentQuestion.part]?.remaining <= 0 && !{{ auth()->user()->isAdmin() ? 'true' : 'false' }})"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all hover:scale-105 active:scale-95"
            >
                <template x-if="!isSaving && !isAiLoading[currentQuestion.id]">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                        <span>✨ Nhận xét bằng AI</span>
                    </div>
                </template>
                <template x-if="isSaving">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Đang lưu bài...</span>
                    </div>
                </template>
                <template x-if="!isSaving && isAiLoading[currentQuestion.id]">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Đang phân tích...</span>
                    </div>
                </template>
            </button>
            <div class="text-xs text-indigo-500 font-medium mt-2">
                <template x-if="aiUsageStatus[currentQuestion.part]?.remaining === 'unlimited'">
                    <span>Lượt còn lại: Không giới hạn (Admin)</span>
                </template>
                <template x-if="aiUsageStatus[currentQuestion.part]?.remaining !== 'unlimited'">
                    <span x-text="'Lượt còn lại: ' + (aiUsageStatus[currentQuestion.part]?.remaining || 0) + '/' + (aiUsageStatus[currentQuestion.part]?.limit || 10)"></span>
                </template>
            </div>
            
            {{-- Error Message --}}
            <template x-if="aiError[currentQuestion.id]">
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3 text-red-700 text-sm shadow-sm transition-all animate-pulse">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div class="flex-1">
                        <p class="font-bold">Lỗi không thể xử lý</p>
                        <p x-text="aiError[currentQuestion.id]" class="opacity-90"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- AI Feedback Content --}}
    <div x-show="aiFeedback[currentQuestion.id]" x-transition class="space-y-6">
        <div class="bg-white border-2 border-indigo-100 rounded-2xl overflow-hidden shadow-sm">
            {{-- Header --}}
            <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Phân tích từ AI</span>
                </div>
                <div class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold backdrop-blur-sm">
                    Dự đoán: <span x-text="aiFeedback[currentQuestion.id]?.scores?.score_estimate"></span>
                </div>
            </div>

            <div class="p-6 space-y-8">
                {{-- Grammar Feedback --}}
                <div class="space-y-4">
                    <h4 class="flex items-center gap-2 font-bold text-gray-800">
                        <span class="w-1.5 h-6 bg-amber-400 rounded-full"></span>
                        Phân tích Ngữ pháp
                    </h4>
                    <div class="grid gap-4">
                        <template x-for="(err, i) in aiFeedback[currentQuestion.id]?.scores?.grammar" :key="i">
                            <div class="bg-amber-50 rounded-xl p-4 border border-amber-100 space-y-2">
                                <div class="flex items-start gap-2">
                                    <span class="text-red-500 mt-0.5"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg></span>
                                    <div class="text-sm">
                                        <span class="text-gray-500 line-through" x-text="err.original"></span>
                                        <span class="mx-2 text-indigo-400">→</span>
                                        <span class="font-bold text-green-600" x-text="err.correction"></span>
                                    </div>
                                </div>
                                <div class="text-xs text-amber-800 bg-white/50 p-2 rounded-lg italic" x-text="err.explanation"></div>
                            </div>
                        </template>
                        <template x-if="!aiFeedback[currentQuestion.id]?.scores?.grammar?.length">
                            <div class="text-sm text-green-600 bg-green-50 p-4 rounded-xl border border-green-100">
                                Tuyệt vời! AI không phát hiện lỗi ngữ pháp nghiêm trọng nào.
                            </div>
                        </template>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Vocabulary --}}
                    <div class="space-y-3">
                        <h4 class="font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.168.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.168.477-4.5 1.253" /></svg>
                            Từ vựng
                        </h4>
                        <div class="text-sm text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-100" x-text="aiFeedback[currentQuestion.id]?.scores?.vocabulary"></div>
                    </div>

                    {{-- Task Fulfillment --}}
                    <div class="space-y-3">
                        <h4 class="font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            Hoàn thành nhiệm vụ
                        </h4>
                        <div class="text-sm text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-100" x-text="aiFeedback[currentQuestion.id]?.scores?.task_fulfillment"></div>
                    </div>
                </div>

                {{-- Improved Sample --}}
                <div class="pt-6 border-t border-gray-100 space-y-4">
                    <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Bài viết mẫu tối ưu (Nhấn để xem)
                    </h4>
                    <div 
                        x-data="{ expanded: false }" 
                        @click="expanded = !expanded"
                        class="cursor-pointer group relative bg-indigo-50/30 rounded-2xl p-6 border border-indigo-100 transition-all hover:bg-indigo-50"
                    >
                        <div class="text-gray-700 leading-relaxed" :class="expanded ? '' : 'line-clamp-3'" x-text="aiFeedback[currentQuestion.id]?.comment"></div>
                        <div x-show="!expanded" class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-white/80 to-transparent flex items-end justify-center pb-2">
                            <span class="text-indigo-500 font-bold text-xs uppercase tracking-wider group-hover:translate-y-[-2px] transition-transform">Xem thêm</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
