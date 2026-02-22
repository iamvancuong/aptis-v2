{{-- Listening Part 4: Complex Audio (Topic + 2 MCQ Sub-questions) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 4">
    <div class="space-y-6">
        {{-- Audio Player (optional) --}}
        <template x-if="currentQuestion.audio_path">
            <div class="bg-gray-50 rounded-lg p-4">
                <audio :src="'/storage/' + currentQuestion.audio_path" controls class="w-full"></audio>
            </div>
        </template>

        {{-- Topic/Context --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700" x-text="currentQuestion.metadata.topic || currentQuestion.stem"></p>
        </div>

        {{-- Sub-questions with Radio Choices --}}
        <div class="space-y-6">
            <template x-for="(subQ, qIdx) in currentQuestion.metadata.questions" :key="qIdx">
                <div class="border-l-4 border-indigo-400 bg-white rounded-r-lg p-5">
                    {{-- Sub-question text --}}
                    <p class="font-semibold text-gray-800 mb-4" x-text="subQ.question"></p>

                    {{-- Radio choices --}}
                    <div class="space-y-3">
                        <template x-for="(choice, cIdx) in subQ.choices" :key="cIdx">
                            <label 
                                class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                :class="getLP4RadioClass(qIdx, cIdx)"
                            >
                                <input 
                                    type="radio" 
                                    :name="'lp4_q_' + currentQuestion.id + '_' + qIdx"
                                    :value="cIdx"
                                    x-model.number="listeningPart4Answers[qIdx]"
                                    :disabled="hasAnswered(currentQuestion.id)"
                                    class="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="text-sm" x-text="choice"></span>
                            </label>
                        </template>
                    </div>

                    {{-- Per-question feedback --}}
                    <template x-if="hasAnswered(currentQuestion.id)">
                        <div class="mt-3 text-sm">
                            <div x-show="listeningPart4Answers[qIdx] == currentQuestion.metadata.correct_answers[qIdx]" class="flex items-center text-green-600 font-bold">
                                <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Correct
                            </div>
                            <div x-show="listeningPart4Answers[qIdx] != currentQuestion.metadata.correct_answers[qIdx]" class="flex items-center text-red-600">
                                <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span class="font-bold">Incorrect.</span>
                                <span class="ml-1 text-gray-600">Answer:</span>
                                <span class="ml-1 font-bold text-green-600" x-text="subQ.choices[currentQuestion.metadata.correct_answers[qIdx]]"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Submit --}}


    </div>
</template>
