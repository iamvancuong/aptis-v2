{{-- Listening Part 1: Short Audio MCQ (3 radio choices) --}}
<template x-if="currentQuestion.skill === 'listening' && currentQuestion.part === 1">
    <div class="space-y-6">
        {{-- Audio Player --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <template x-if="currentQuestion.audio_path">
                <div class="space-y-3">
                    <audio :src="currentQuestion.audio_url" controls controlslist="nodownload nofullscreen noremoteplayback" oncontextmenu="return false" class="w-full"></audio>
                    <div class="flex justify-end">
                        <button @click="const el = document.getElementById('lp1_audio_desc'); if(el) el.classList.toggle('hidden')" class="text-blue-500 text-xs hover:underline">Xem mô tả</button>
                    </div>
                    <div id="lp1_audio_desc" class="hidden p-3 bg-white border border-gray-200 rounded-lg text-sm ck-content" x-html="currentQuestion.metadata.description || 'Chưa có mô tả'"></div>
                </div>
            </template>
            <template x-if="!currentQuestion.audio_path">
                <div class="flex items-center gap-3 text-gray-400 py-2">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                    <span class="text-sm font-medium">Chưa có file nghe</span>
                </div>
            </template>
        </div>

        {{-- Question --}}
        <div>
            <h4 class="font-bold text-gray-800 mb-1">Câu hỏi</h4>
            <p class="text-gray-700" x-html="currentQuestion.stem"></p>
        </div>

        {{-- Title display near choices --}}
        <div class="mb-4 bg-blue-50/50 p-3 rounded-lg border border-blue-100/50" x-show="currentQuestion.title">
            <h5 class="text-[11px] font-bold text-blue-500 tracking-wide mb-0" x-text="currentQuestion.title"></h5>
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
                <x-ui.verdict ok="listeningPart1Answer == currentQuestion.metadata.correct_answer" answer="currentQuestion.metadata.choices[currentQuestion.metadata.correct_answer]" />
            </div>
        </template>

        {{-- Submit --}}


    </div>
</template>
