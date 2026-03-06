{{-- Writing Part 3: Social Response (3 prompts) --}}
<template x-if="currentQuestion.skill === 'writing' && currentQuestion.part === 3">
    <div class="space-y-6">
        {{-- Instructions --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="font-bold text-blue-800 mb-1">💬 Viết câu trả lời của bạn vào đây</h4>
            <p class="text-blue-700" x-text="currentQuestion.stem || 'Read each social media post and write a response.'"></p>
        </div>

        {{-- Prompts --}}
        <div class="space-y-6">
            <template x-for="(item, idx) in currentQuestion.metadata?.questions || []" :key="idx">
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    {{-- Social Post --}}
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                            x-text="idx + 1"></div>
                        <p class="text-sm text-gray-700 font-medium" x-text="typeof item === 'string' ? item : (item.prompt || '')"></p>
                    </div>

                    {{-- Word limit --}}
                    <div class="text-xs text-gray-400 ml-11">
                        Viết từ
                        <strong x-text="item.word_limit?.min || 30"></strong> đến
                        <strong x-text="item.word_limit?.max || 40"></strong> từ
                    </div>

                    {{-- Response textarea --}}
                    <div class="ml-11">
                        <textarea
                            x-model="writingPart3Answers[idx]"
                            @input="writingPart3Answers[idx] = enforceWordLimit($event.target.value, item.word_limit?.max || 40)"
                            @paste.prevent
                            :disabled="hasAnswered(currentQuestion.id)"
                            rows="4"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y disabled:bg-gray-100 disabled:text-gray-500"
                            placeholder="Viết phản hồi..."></textarea>
                        <div class="flex justify-end mt-1">
                            <span class="text-xs"
                                :class="getWordCountClass(writingPart3Answers[idx] || '', item.word_limit)"
                                x-text="countWords(writingPart3Answers[idx] || '') + ' từ'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Submitted State & Sample Answer --}}
        <template x-if="hasAnswered(currentQuestion.id)">
            <div class="space-y-4">
                {{-- Practice Mode --}}
                <template x-if="!isFullTest">
                    <div class="space-y-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-green-800 font-medium">Đã hoàn thành!</p>
                                <p class="text-green-600 text-sm">Hãy so sánh bài làm của bạn với đáp án gợi ý bên dưới.</p>
                            </div>
                        </div>

                        {{-- Sample Answer --}}
                        <template x-if="currentQuestion.metadata?.sample_answer">
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                                <h4 class="font-bold text-indigo-800 mb-2">💡 Đáp án gợi ý (Sample Answer)</h4>
                                <div class="space-y-4">
                                    <template x-for="(ans, idx) in currentQuestion.metadata?.sample_answer || []" :key="idx">
                                        <div>
                                            <div class="text-xs font-bold text-indigo-400 mb-1" x-text="'Response ' + (idx + 1)"></div>
                                            <div class="text-sm text-indigo-900 whitespace-pre-line leading-relaxed" x-text="ans"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
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
