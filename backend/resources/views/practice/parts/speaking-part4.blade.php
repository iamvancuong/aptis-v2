<template x-if="currentQuestion && currentQuestion.skill === 'speaking' && currentQuestion.part === 4">
<div class="space-y-6">
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
        <h3 class="text-lg font-semibold text-red-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
            Extended Discussion
        </h3>
        <p class="text-sm text-red-700 mt-1">Please look at the picture and answer the 3 questions. You have 1 minute to think and 2 minutes to talk.</p>
    </div>

    <!-- Image Display -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 text-center">
        <img :src="`/storage/${currentQuestion.metadata.image_path}`" alt="Speaking Image" class="max-h-64 object-contain mx-auto rounded-lg shadow-sm">
    </div>

    <!-- Active Question Display (All 3 questions shown together) -->
    <div class="bg-white p-6 md:p-8 rounded-xl border-2 border-red-100 relative overflow-hidden">
        <!-- Progress Bar for Part (always 100% since it's a single long response) -->
        <div class="absolute top-0 left-0 h-1 bg-red-500 w-full"></div>
             
        <div class="space-y-4 mb-8 text-left max-w-2xl mx-auto">
            <template x-for="(qText, idx) in currentQuestion.metadata.questions">
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 font-bold text-sm shrink-0 mt-0.5" x-text="idx + 1"></span>
                    <h2 class="text-lg font-bold text-gray-800" x-text="qText"></h2>
                </div>
            </template>
        </div>

        <!-- Recording Timer UI -->
        <div class="flex flex-col items-center justify-center space-y-4 border-t border-gray-100 pt-8 mt-4">
            
            <!-- Playing Audio State -->
            <div x-show="speakingState === 'playing_audio'" class="text-red-500 flex flex-col items-center">
                <svg class="w-12 h-12 animate-pulse mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                <div class="text-lg font-medium animate-pulse">Playing question audio...</div>
            </div>

            <!-- Prep Time State -->
            <div x-show="speakingState === 'prep'" class="text-amber-500 flex flex-col items-center">
                <svg class="w-12 h-12 animate-pulse mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-lg font-medium">Preparation Time (Think about your answers)</div>
                <div class="text-4xl font-bold" x-text="formatTime(speakingTimer)"></div>
            </div>

            <!-- Recording State -->
            <div x-show="speakingState === 'recording'" class="flex flex-col items-center relative gap-4">
                <div class="relative w-32 h-32 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90 absolute" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#fee2e2" stroke-width="8"></circle>
                        <circle cx="50" cy="50" r="45" fill="none" class="stroke-red-500 transition-all duration-1000 linear" stroke-width="8" stroke-dasharray="283" 
                                :stroke-dashoffset="283 - (283 * (speakingTimer / currentQuestion.metadata.total_answer_time))"></circle>
                    </svg>
                    <div class="text-3xl font-bold text-red-600 absolute" x-text="formatTime(speakingTimer)"></div>
                </div>
                <div class="text-sm font-semibold text-red-600 uppercase tracking-widest animate-pulse">Recording (All 3 Questions)...</div>
            </div>
            
            <!-- Idle State -->
            <div x-show="speakingState === 'idle'" class="mt-4">
                <button @click="startSpeakingPart()" class="px-8 py-4 bg-red-600 hover:bg-red-700 text-white rounded-full font-bold text-lg shadow-lg hover:shadow-xl transition-all flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Start Part 4
                </button>
            </div>
            
            <!-- Saving State -->
            <div x-show="speakingState === 'saving'" class="text-red-600 flex flex-col items-center">
                <svg class="w-10 h-10 animate-spin mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <div class="text-sm font-medium">Processing Audio...</div>
            </div>
        </div>
    </div>
</div>
</div>
</template>
