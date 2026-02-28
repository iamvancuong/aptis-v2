<template x-if="currentQuestion && currentQuestion.skill === 'speaking' && currentQuestion.part === 3">
<div class="space-y-6">
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg">
        <h3 class="text-lg font-semibold text-amber-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            Compare Two Pictures
        </h3>
        <p class="text-sm text-amber-700 mt-1">Please compare the two pictures and answer the 3 questions below. You will have 45 seconds for each response.</p>
    </div>

    <!-- Images Display -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-3 rounded-xl border border-gray-200 text-center">
            <img :src="`/storage/${currentQuestion.metadata.image_paths[0]}`" alt="Image 1" class="h-48 md:h-56 object-contain mx-auto rounded-lg shadow-sm">
        </div>
        <div class="bg-white p-3 rounded-xl border border-gray-200 text-center">
            <img :src="`/storage/${currentQuestion.metadata.image_paths[1]}`" alt="Image 2" class="h-48 md:h-56 object-contain mx-auto rounded-lg shadow-sm">
        </div>
    </div>

    <!-- Active Question Display -->
    <div class="bg-white p-6 md:p-8 rounded-xl border-2 border-amber-100 text-center relative overflow-hidden">
        <!-- Progress Bar for Part -->
        <div class="absolute top-0 left-0 h-1 bg-amber-500 transition-all duration-300" 
             :style="`width: ${((speakingSubIndex + 1) / 3) * 100}%`"></div>
             
        <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full mb-4 uppercase tracking-wide">
            Question <span x-text="speakingSubIndex + 1"></span> of 3
        </span>
        
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-8" x-text="currentQuestion.metadata.questions[speakingSubIndex]"></h2>

        <!-- Recording Timer UI -->
        <div class="flex flex-col items-center justify-center space-y-4">
            <!-- Playing Audio State -->
            <div x-show="speakingState === 'playing_audio'" class="text-amber-500 flex flex-col items-center">
                <svg class="w-12 h-12 animate-pulse mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                <div class="text-lg font-medium animate-pulse">Playing audio...</div>
            </div>

            <!-- Recording State -->
            <div x-show="speakingState === 'recording'" class="flex flex-col items-center relative gap-4">
                <div class="relative w-32 h-32 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90 absolute" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#fee2e2" stroke-width="8"></circle>
                        <circle cx="50" cy="50" r="45" fill="none" class="stroke-red-500 transition-all duration-1000 linear" stroke-width="8" stroke-dasharray="283" 
                                :stroke-dashoffset="283 - (283 * (speakingTimer / currentQuestion.metadata.answer_time_per_question))"></circle>
                    </svg>
                    <div class="text-3xl font-bold text-red-600 absolute" x-text="formatTime(speakingTimer)"></div>
                </div>
                <div class="text-sm font-semibold text-red-600 uppercase tracking-widest animate-pulse">Recording...</div>
            </div>
            
            <!-- Idle State -->
            <div x-show="speakingState === 'idle'" class="mt-4">
                <button @click="startSpeakingPart()" class="px-8 py-4 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-bold text-lg shadow-lg hover:shadow-xl transition-all flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                    Start Recording
                </button>
            </div>
            
            <!-- Saving State -->
            <div x-show="speakingState === 'saving'" class="text-amber-600 flex flex-col items-center">
                <svg class="w-10 h-10 animate-spin mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <div class="text-sm font-medium">Processing Audio...</div>
            </div>
        </div>
    </div>
</div>
</div>
</template>
