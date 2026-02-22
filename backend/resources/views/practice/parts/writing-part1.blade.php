{{-- Writing Part 1: Form Filling --}}
<template x-if="currentQuestion.skill === 'writing' && currentQuestion.part === 1">
    <div class="space-y-6">
        {{-- Instructions --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="font-bold text-blue-800 mb-1">📝 Form Filling</h4>
            <p class="text-sm text-blue-700" x-text="currentQuestion.metadata?.instructions || 'Fill in the form below.'"></p>
        </div>

        {{-- Question Stem --}}
        <div>
            <p class="text-gray-700 font-medium" x-text="currentQuestion.stem"></p>
        </div>

        {{-- Form Fields --}}
        <div class="space-y-4">
            <template x-for="(field, idx) in currentQuestion.metadata.fields" :key="idx">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" x-text="field.label"></label>
                    <input type="text"
                        x-model="writingPart1Answers[idx]"
                        :placeholder="field.placeholder || ''"
                        :disabled="hasAnswered(currentQuestion.id)"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-500">
                </div>
            </template>
        </div>

        {{-- Submitted State & Sample Answer --}}
        <template x-if="hasAnswered(currentQuestion.id)">
            <div class="space-y-4">
                {{-- Practice Mode: Completed + Sample Answer --}}
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
                                <div class="text-sm text-indigo-900 space-y-2">
                                     <template x-for="(value, key) in currentQuestion.metadata.sample_answer" :key="key">
                                        <div>
                                            <span class="font-semibold" x-text="key + ':'"></span>
                                            <span x-text="value"></span>
                                        </div>
                                     </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Full Test Mode: Saved --}}
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
