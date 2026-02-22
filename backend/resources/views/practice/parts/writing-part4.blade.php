{{-- Writing Part 4: Email Chain (Dual Tasks) --}}
<template x-if="currentQuestion.skill === 'writing' && currentQuestion.part === 4">
    <div class="space-y-8">
        {{-- 1. Shared Context & Incoming Email --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl overflow-hidden shadow-sm">
            <div class="px-5 py-3 bg-blue-600 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <span class="font-bold text-sm uppercase tracking-wider">Incoming Email</span>
                </div>
            </div>
            
            <div class="p-6 space-y-4">
                {{-- Context --}}
                <div class="text-sm text-blue-800 italic bg-blue-100/50 p-3 rounded-lg border border-blue-200" x-text="currentQuestion.metadata.context"></div>
                
                {{-- Email Content --}}
                <div class="bg-white p-5 rounded-lg border border-blue-100 shadow-inner">
                    <div class="text-gray-800 font-bold mb-3" x-text="currentQuestion.metadata.email?.greeting"></div>
                    <div class="text-gray-700 leading-relaxed whitespace-pre-line mb-4" x-text="currentQuestion.metadata.email?.body"></div>
                    <div class="text-gray-800 font-medium" x-text="currentQuestion.metadata.email?.sign_off"></div>
                </div>
            </div>
        </div>

        {{-- 2. Task 1: Informal Email --}}
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold">1</div>
                <h4 class="font-bold text-gray-800">Task 1: Informal Email</h4>
            </div>
            
            <div class="bg-green-50/50 border border-green-100 rounded-lg p-4 text-sm text-green-800" x-text="currentQuestion.metadata.task1?.instruction"></div>
            
            <div class="relative">
                <textarea
                    x-model="writingPart4Answers[0]"
                    :disabled="hasAnswered(currentQuestion.id)"
                    rows="6"
                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-y disabled:bg-gray-100 disabled:text-gray-500"
                    placeholder="Write your informal email here..."></textarea>
                
                <div class="absolute bottom-3 right-3 flex items-center gap-2 px-2 py-1 bg-white/80 backdrop-blur rounded-md border border-gray-200 shadow-sm transition-all pointer-events-none">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Words</span>
                    <span class="text-xs font-mono font-bold"
                        :class="getWordCountClass(writingPart4Answers[0] || '', currentQuestion.metadata.task1?.word_limit)"
                        x-text="countWords(writingPart4Answers[0] || '')"></span>
                    <span class="text-[10px] text-gray-300">/</span>
                    <span class="text-[10px] font-bold text-gray-400" x-text="currentQuestion.metadata.task1?.word_limit?.max || 50"></span>
                </div>
            </div>
        </div>

        <div class="h-px bg-gray-200"></div>

        {{-- 3. Task 2: Formal Email --}}
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold">2</div>
                <h4 class="font-bold text-gray-800">Task 2: Formal Email</h4>
            </div>
            
            <div class="bg-orange-50/50 border border-orange-100 rounded-lg p-4 text-sm text-orange-800" x-text="currentQuestion.metadata.task2?.instruction"></div>
            
            <div class="relative">
                <textarea
                    x-model="writingPart4Answers[1]"
                    :disabled="hasAnswered(currentQuestion.id)"
                    rows="10"
                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-y disabled:bg-gray-100 disabled:text-gray-500"
                    placeholder="Write your formal email here..."></textarea>
                
                <div class="absolute bottom-3 right-3 flex items-center gap-2 px-2 py-1 bg-white/80 backdrop-blur rounded-md border border-gray-200 shadow-sm transition-all pointer-events-none">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Words</span>
                    <span class="text-xs font-mono font-bold"
                        :class="getWordCountClass(writingPart4Answers[1] || '', currentQuestion.metadata.task2?.word_limit)"
                        x-text="countWords(writingPart4Answers[1] || '')"></span>
                    <span class="text-[10px] text-gray-300">/</span>
                    <span class="text-[10px] font-bold text-gray-400" x-text="currentQuestion.metadata.task2?.word_limit?.max || 150"></span>
                </div>
            </div>
        </div>

        {{-- Submitted State & Sample Answer --}}
        <template x-if="hasAnswered(currentQuestion.id)">
            <div class="space-y-6 pt-6 border-t border-gray-100">
                <template x-if="!isFullTest">
                    <div class="space-y-6">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-green-800 font-medium">Đã hoàn thành!</p>
                                <p class="text-green-600 text-sm">Hãy so sánh bài làm của bạn với đáp án gợi ý bên dưới.</p>
                            </div>
                        </div>

                        {{-- Sample Answers --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Task 1 Sample --}}
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
                                <h4 class="font-bold text-indigo-800 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a1 1 0 11-2 0zM13.536 14.95a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM16.243 16.243a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414z" /></svg>
                                    Sample Answer (Task 1)
                                </h4>
                                <div class="text-sm text-indigo-900 whitespace-pre-line leading-relaxed italic" x-text="currentQuestion.metadata.task1?.sample_answer"></div>
                            </div>
                            
                            {{-- Task 2 Sample --}}
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
                                <h4 class="font-bold text-indigo-800 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 112 0v1a1 1 0 11-2 0zM13.536 14.95a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM16.243 16.243a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414z" /></svg>
                                    Sample Answer (Task 2)
                                </h4>
                                <div class="text-sm text-indigo-900 whitespace-pre-line leading-relaxed italic" x-text="currentQuestion.metadata.task2?.sample_answer"></div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Full Test Mode --}}
                <template x-if="isFullTest">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center gap-3">
                        <svg class="w-6 h-6 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="text-blue-800 font-medium">Đã lưu bài làm</p>
                            <p class="text-blue-600 text-sm">Bạn có thể chuyển sang phần tiếp theo.</p>
                        </div>
                    </div>
                </template>
                {{-- AI Feedback --}}
                <template x-if="!isFullTest">
                    @include('practice.parts._ai_feedback')
                </template>
            </div>
        </template>
    </div>
</template>
