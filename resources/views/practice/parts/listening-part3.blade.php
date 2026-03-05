{{-- Listening Part 3: Monologue (Audio + Statements + Shared Dropdowns) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 3">
    <div class="space-y-6">
        {{-- Audio Player --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <template x-if="currentQuestion.audio_path">
                <audio :src="'/storage/' + currentQuestion.audio_path" controls class="w-full"></audio>
            </template>
            <template x-if="!currentQuestion.audio_path">
                <div class="flex items-center gap-3 text-gray-400 py-2">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                    <span class="text-sm font-medium">No audio available</span>
                </div>
            </template>
        </div>

        {{-- Topic/Question --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700 text-sm" x-text="'Topic: ' + (currentQuestion.metadata.topic || '')"></p>
            <p class="text-gray-700" x-show="currentQuestion.stem" x-text="currentQuestion.stem"></p>
        </div>

        {{-- Statements with Shared Dropdown --}}
        <div class="space-y-4">
            <template x-for="(statement, sIdx) in currentQuestion.metadata.statements" :key="sIdx">
                <div class="border-l-4 border-blue-400 bg-white rounded-r-lg p-4">
                    <p class="font-medium text-gray-800 text-sm mb-3">
                        <span class="text-blue-600" x-text="(sIdx + 1) + '. '"></span>
                        <span x-text="statement"></span>
                    </p>

                    <select
                        x-model.number="listeningPart3Answers[sIdx]"
                        class="w-full text-sm border rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                        :class="hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400 cursor-pointer'"
                        :style="getLP3SelectStyle(sIdx)"
                        :disabled="hasAnswered(currentQuestion.id)"
                    >
                        <option value="" disabled>-- Chọn --</option>
                        <template x-for="(choice, cIdx) in currentQuestion.metadata.shared_choices" :key="cIdx">
                            <option :value="cIdx" x-text="choice"></option>
                        </template>
                    </select>

                    {{-- Per-statement feedback --}}
                    <template x-if="hasAnswered(currentQuestion.id)">
                        <div class="mt-2 text-sm">
                            <div x-show="listeningPart3Answers[sIdx] == currentQuestion.metadata.correct_answers[sIdx]" class="flex items-center text-green-600 font-bold">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Correct
                            </div>
                            <div x-show="listeningPart3Answers[sIdx] != currentQuestion.metadata.correct_answers[sIdx]" class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span class="font-bold">Incorrect.</span>
                                <span class="ml-1 text-gray-600">Answer:</span>
                                <span class="ml-1 font-bold text-green-600" x-text="currentQuestion.metadata.shared_choices[currentQuestion.metadata.correct_answers[sIdx]]"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Submit --}}


    </div>
</template>
