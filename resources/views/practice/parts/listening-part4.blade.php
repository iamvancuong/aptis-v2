{{-- Listening Part 4: Complex Audio (Topic + 2 MCQ Sub-questions) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 4">
    <div class="space-y-6">
        {{-- Audio Player (optional) --}}
        <template x-if="currentQuestion.audio_path">
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                <audio :src="currentQuestion.audio_url" controls controlslist="nodownload nofullscreen noremoteplayback" oncontextmenu="return false" class="w-full"></audio>
                <div class="flex justify-end">
                    <button @click="const el = document.getElementById('lp4_audio_desc'); if(el) el.classList.toggle('hidden')" class="text-blue-500 text-xs hover:underline">Xem mô tả</button>
                </div>
                <div id="lp4_audio_desc" class="hidden p-3 bg-white border border-gray-200 rounded-lg text-sm ck-content" x-html="currentQuestion.metadata.description || 'Chưa có mô tả'"></div>
            </div>
        </template>

        {{-- Topic/Context --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700" x-html="currentQuestion.metadata.topic || currentQuestion.stem"></p>
        </div>
        
        {{-- Sub-questions with Radio Choices --}}
        <div class="space-y-6">
            <template x-for="(subQ, qIdx) in currentQuestion.metadata.questions" :key="qIdx">
                <div class="border-l-4 border-blue-400 bg-white rounded-r-lg p-5">
                    {{-- Sub-question text --}}
                    <p class="font-semibold text-gray-800 mb-4" x-html="subQ.question"></p>

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
                                    class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm" x-text="choice"></span>
                            </label>
                        </template>
                    </div>

                    {{-- Per-question feedback --}}
                    <template x-if="hasAnswered(currentQuestion.id)">
                        <div class="mt-3 text-sm">
                            <x-ui.verdict ok="listeningPart4Answers[qIdx] == currentQuestion.metadata.correct_answers[qIdx]" answer="subQ.choices[currentQuestion.metadata.correct_answers[qIdx]]" />
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Submit --}}


    </div>
</template>
