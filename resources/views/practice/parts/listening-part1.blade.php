{{-- Listening Part 1: Short Audio MCQ (3 radio choices) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 1">
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

        {{-- Question --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700" x-text="currentQuestion.stem"></p>
        </div>

        {{-- Radio Choices --}}
        <div class="space-y-3">
            <template x-for="(choice, cIdx) in currentQuestion.metadata.choices" :key="cIdx">
                <label 
                    class="flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all"
                    :class="getLP1RadioClass(cIdx)"
                >
                    <input 
                        type="radio" 
                        :name="'lp1_q_' + currentQuestion.id" 
                        :value="cIdx"
                        x-model.number="listeningPart1Answer"
                        :disabled="hasAnswered(currentQuestion.id)"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="text-sm" x-text="choice"></span>
                </label>
            </template>
        </div>

        {{-- Feedback --}}
        <template x-if="hasAnswered(currentQuestion.id)">
            <div class="text-sm">
                <div x-show="listeningPart1Answer === currentQuestion.metadata.correct_answer" class="flex items-center text-green-600 font-bold">
                    <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Correct!
                </div>
                <div x-show="listeningPart1Answer !== currentQuestion.metadata.correct_answer" class="flex items-center text-red-600">
                    <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    <span class="font-bold">Incorrect.</span>
                    <span class="ml-1 text-gray-600">Answer:</span>
                    <span class="ml-1 font-bold text-green-600" x-text="currentQuestion.metadata.choices[currentQuestion.metadata.correct_answer]"></span>
                </div>
            </div>
        </template>

        {{-- Submit --}}


    </div>
</template>
