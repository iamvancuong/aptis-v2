{{-- Writing Part 4: Essay --}}
<template x-if="currentQuestion.skill === 'writing' && currentQuestion.part === 4">
    <div class="space-y-6">
        {{-- Topic --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="font-bold text-blue-800 mb-1">📄 Essay</h4>
            <p class="text-sm text-blue-700 font-medium" x-text="currentQuestion.metadata?.topic || currentQuestion.stem"></p>
        </div>

        {{-- Instructions --}}
        <template x-if="currentQuestion.metadata?.instructions">
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
                <p x-text="currentQuestion.metadata.instructions"></p>
            </div>
        </template>

        {{-- Word Limit Info --}}
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Viết từ
                <strong x-text="currentQuestion.metadata?.word_limit?.min || 200"></strong> đến
                <strong x-text="currentQuestion.metadata?.word_limit?.max || 250"></strong> từ
            </span>
        </div>

        {{-- Textarea --}}
        <div>
            <textarea
                x-model="writingPart4Answer"
                :disabled="hasAnswered(currentQuestion.id)"
                rows="14"
                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y disabled:bg-gray-100 disabled:text-gray-500"
                placeholder="Viết bài luận của bạn ở đây..."></textarea>

            {{-- Word Counter --}}
            <div class="flex justify-end mt-1">
                <span class="text-xs"
                    :class="getWordCountClass(writingPart4Answer, currentQuestion.metadata?.word_limit)"
                    x-text="countWords(writingPart4Answer) + ' từ'"></span>
            </div>
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
                                <div class="text-sm text-indigo-900 whitespace-pre-line leading-relaxed italic" x-text="currentQuestion.metadata.sample_answer"></div>
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
            </div>
        </template>
    </div>
</template>
