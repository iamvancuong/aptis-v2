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
                    Điểm: <span x-text="aiFeedback[currentQuestion.id]?.scores?.overall_score"></span>
                </div>
            </div>

            <div class="p-6 space-y-8">
                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="criteria in ['grammar', 'vocabulary', 'coherence', 'task_fulfillment']" :key="criteria">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3">
                            <h4 class="font-bold text-gray-800 capitalize flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span x-text="criteria.replace('_', ' ')"></span>
                                </span>
                                <span class="px-2 py-1 bg-white text-indigo-700 font-bold rounded-lg text-sm shadow-sm border border-indigo-100" x-text="(aiFeedback[currentQuestion.id]?.scores?.scores[criteria] ?? 0) + '/5'"></span>
                            </h4>
                            <p class="text-sm text-gray-700 leading-relaxed" x-text="aiFeedback[currentQuestion.id]?.scores?.feedback[criteria]"></p>
                        </div>
                    </template>
                </div>
                
                {{-- Key Mistakes & Suggestions --}}
                <div class="grid md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                    <div class="space-y-3">
                        <h4 class="font-bold text-red-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Lỗi phổ biến
                        </h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <template x-for="mistake in aiFeedback[currentQuestion.id]?.scores?.key_mistakes || []" :key="mistake">
                                <li x-text="mistake"></li>
                            </template>
                        </ul>
                    </div>
                    
                    <div class="space-y-3">
                        <h4 class="font-bold text-green-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Gợi ý cải thiện
                        </h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <template x-for="suggestion in aiFeedback[currentQuestion.id]?.scores?.suggestions || []" :key="suggestion">
                                <li x-text="suggestion"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                {{-- Schema v3: Part-Specific Responses --}}
                <div x-show="aiFeedback[currentQuestion.id]?.scores?.schema_version >= 3 && aiFeedback[currentQuestion.id]?.scores?.part_responses" class="pt-6 border-t border-gray-100 space-y-6">
                    <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Phân tích & Bài mẫu chi tiết từng phần
                    </h4>
                    
                    <div class="space-y-6">
                        <template x-for="(response, idx) in aiFeedback[currentQuestion.id]?.scores?.part_responses" :key="idx">
                            <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                                
                                {{-- Target Label --}}
                                <div class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs" x-text="idx + 1"></span>
                                    <span x-text="response.label || ('Phần ' + (idx + 1))"></span>
                                </div>
                                
                                {{-- Detailed Corrections for this part --}}
                                <template x-if="response.detailed_corrections?.length > 0">
                                    <div class="mb-5 space-y-3">
                                        <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Sửa lỗi chuyên sâu</div>
                                        <template x-for="correction in response.detailed_corrections" :key="correction.original">
                                            <div class="bg-amber-50 rounded-lg p-3 border border-amber-100 text-sm">
                                                <div class="flex items-start gap-2 mb-2">
                                                    <span class="text-red-500 mt-0.5"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></span>
                                                    <div>
                                                        <span class="text-gray-500 line-through" x-text="correction.original"></span>
                                                        <span class="mx-2 text-indigo-400">→</span>
                                                        <span class="font-bold text-green-600" x-text="correction.corrected"></span>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-amber-800 bg-white/60 p-2 rounded italic" x-text="correction.explanation"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- The Improved Sample --}}
                                <div>
                                    <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">Mẫu tối ưu</div>
                                    <div class="bg-indigo-50/50 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed border border-indigo-100" x-text="response.improved_sample"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Fallback for Schema v1/v2 (Legacy Improved Sample) --}}
                <div x-show="!aiFeedback[currentQuestion.id]?.scores?.schema_version || aiFeedback[currentQuestion.id]?.scores?.schema_version < 3" class="pt-6 border-t border-gray-100 space-y-4">
                    <h4 class="font-bold text-indigo-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Bài viết mẫu tối ưu (Nhấn để xem)
                    </h4>
                    <div 
                        x-data="{ expanded: false }" 
                        @click="expanded = !expanded"
                        class="cursor-pointer group relative bg-indigo-50/30 rounded-2xl p-6 border border-indigo-100 transition-all hover:bg-indigo-50"
                    >
                        <div class="text-gray-700 leading-relaxed whitespace-pre-wrap" :class="expanded ? '' : 'line-clamp-3'" x-text="aiFeedback[currentQuestion.id]?.scores?.improved_sample"></div>
                        <div x-show="!expanded" class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-white/80 to-transparent flex items-end justify-center pb-2">
                            <span class="text-indigo-500 font-bold text-xs uppercase tracking-wider group-hover:translate-y-[-2px] transition-transform">Xem thêm</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
