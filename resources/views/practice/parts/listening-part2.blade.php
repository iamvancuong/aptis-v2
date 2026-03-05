{{-- Listening Part 2: Conversation (4 Speaker Audio Grid + Dropdown Matching) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 2">
    <div class="space-y-6">
        {{-- Play All Button --}}
        <button @click="playAllSpeakers()" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 text-sm">
            Phát tất cả
        </button>

        {{-- Speaker Audio Grid (2x2) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="(speaker, sIdx) in currentQuestion.metadata.items" :key="sIdx">
                <div class="border border-gray-200 rounded-lg p-4 bg-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-gray-800 text-sm" x-text="speaker || 'Speaker ' + String.fromCharCode(65 + sIdx)"></span>
                        <button @click="$refs['lp2desc_' + sIdx]?.classList.toggle('hidden')" class="text-blue-500 text-xs hover:underline">Xem mô tả</button>
                    </div>
                    
                    {{-- Audio --}}
                    <template x-if="currentQuestion.metadata.audio_files && currentQuestion.metadata.audio_files[sIdx]">
                        <audio :src="'/storage/' + currentQuestion.metadata.audio_files[sIdx]" controls class="w-full h-10" :id="'speaker_audio_' + sIdx"></audio>
                    </template>
                    <template x-if="!currentQuestion.metadata.audio_files || !currentQuestion.metadata.audio_files[sIdx]">
                        <div class="flex items-center gap-2 text-gray-400 py-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                            <span class="text-xs">No audio</span>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Question Stem --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700 text-sm" x-text="currentQuestion.stem"></p>
        </div>

        {{-- Speaker-to-Opinion Dropdowns --}}
        <div class="space-y-4">
            <template x-for="(speaker, sIdx) in currentQuestion.metadata.items" :key="'dd_' + sIdx">
                <div class="border-b border-gray-200 pb-4 last:border-b-0">
                    <p class="font-semibold text-gray-700 text-sm mb-2" x-text="speaker || 'Speaker ' + String.fromCharCode(65 + sIdx)"></p>
                    <select
                        x-model.number="listeningPart2Answers[sIdx]"
                        class="w-full text-sm border rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                        :class="hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400 cursor-pointer'"
                        :style="getLP2SelectStyle(sIdx)"
                        :disabled="hasAnswered(currentQuestion.id)"
                    >
                        <option value="" disabled>- Chọn câu mô tả -</option>
                        <template x-for="(choice, cIdx) in currentQuestion.metadata.choices" :key="cIdx">
                            <option :value="cIdx" x-text="choice"></option>
                        </template>
                    </select>

                    {{-- Per-speaker feedback --}}
                    <template x-if="hasAnswered(currentQuestion.id)">
                        <div class="mt-2 text-sm">
                            <div x-show="listeningPart2Answers[sIdx] == currentQuestion.metadata.correct_answers[sIdx]" class="flex items-center text-green-600 font-bold">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Correct
                            </div>
                            <div x-show="listeningPart2Answers[sIdx] != currentQuestion.metadata.correct_answers[sIdx]" class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span class="font-bold">Incorrect.</span>
                                <span class="ml-1 text-gray-600">Answer:</span>
                                <span class="ml-1 font-bold text-green-600" x-text="currentQuestion.metadata.choices[currentQuestion.metadata.correct_answers[sIdx]]"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Submit --}}


    </div>
</template>
