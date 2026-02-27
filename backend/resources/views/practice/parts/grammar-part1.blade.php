{{-- Grammar Part 1: MCQ 3 options A/B/C (Q1–25) --}}
<template x-if="currentQuestion.skill === 'grammar' && currentQuestion.part === 1">
    <div class="space-y-4">
        {{-- Instruction --}}
        <p class="text-sm text-gray-500 italic">Choose the correct option (A, B or C) to complete each sentence.</p>

        {{-- Options A / B / C --}}
        <div class="space-y-2.5">
            <template x-for="opt in currentQuestion.metadata.options" :key="opt.id">
                <label
                    class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all select-none"
                    :class="{
                        'border-indigo-500 bg-indigo-50':
                            !hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] === opt.id,
                        'border-gray-200 bg-white hover:border-indigo-200':
                            !hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] !== opt.id,
                        'border-green-400 bg-green-50':
                            hasAnswered(currentQuestion.id) && opt.id === currentQuestion.metadata.correct_option,
                        'border-red-300 bg-red-50':
                            hasAnswered(currentQuestion.id) &&
                            grammarAnswers[currentQuestion.id] === opt.id &&
                            opt.id !== currentQuestion.metadata.correct_option,
                        'border-gray-100 bg-gray-50 opacity-60':
                            hasAnswered(currentQuestion.id) &&
                            grammarAnswers[currentQuestion.id] !== opt.id &&
                            opt.id !== currentQuestion.metadata.correct_option,
                    }">
                    {{-- Option badge --}}
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-all"
                          :class="{
                            'bg-indigo-600 text-white': !hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] === opt.id,
                            'bg-gray-100 text-gray-500': !hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] !== opt.id,
                            'bg-green-500 text-white': hasAnswered(currentQuestion.id) && opt.id === currentQuestion.metadata.correct_option,
                            'bg-red-400 text-white': hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] === opt.id && opt.id !== currentQuestion.metadata.correct_option,
                            'bg-gray-100 text-gray-400': hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] !== opt.id && opt.id !== currentQuestion.metadata.correct_option,
                          }"
                          x-text="opt.id"></span>

                    {{-- Option text --}}
                    <span class="text-sm font-medium flex-1" x-text="opt.text"></span>

                    {{-- Correct / Wrong icon after submit --}}
                    <template x-if="hasAnswered(currentQuestion.id) && opt.id === currentQuestion.metadata.correct_option">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="hasAnswered(currentQuestion.id) && grammarAnswers[currentQuestion.id] === opt.id && opt.id !== currentQuestion.metadata.correct_option">
                        <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </template>

                    {{-- Hidden radio --}}
                    <input type="radio"
                           class="sr-only"
                           :name="'q_' + currentQuestion.id"
                           :value="opt.id"
                           :disabled="hasAnswered(currentQuestion.id)"
                           x-model="grammarAnswers[currentQuestion.id]">
                </label>
            </template>
        </div>
    </div>
</template>
