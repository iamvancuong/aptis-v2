<template x-if="currentQuestion && currentQuestion.skill === 'speaking' && currentQuestion.part === 1">
<div class="space-y-6">
    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r-lg">
        <h3 class="text-lg font-semibold text-indigo-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Personal Information
        </h3>
        <p class="text-sm text-indigo-700 mt-1">Please answer the 3 questions below. You will have 30 seconds for each question.</p>
    </div>

    <!-- Active Question Display -->
    <div class="bg-white p-8 rounded-xl border-2 border-indigo-100 text-center relative overflow-hidden">
        <!-- Progress Bar for Part -->
        <div class="absolute top-0 left-0 h-1 bg-indigo-500 transition-all duration-300" 
             :style="`width: ${((speakingSubIndex + 1) / 3) * 100}%`"></div>
             
        <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full mb-4 uppercase tracking-wide">
            Question <span x-text="speakingSubIndex + 1"></span> of 3
        </span>
        
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8" x-text="currentQuestion.metadata.questions[speakingSubIndex]"></h2>

        <!-- Recording Timer UI -->
        <div class="flex flex-col items-center justify-center space-y-4">
            
            <!-- Playing Audio State -->
            <div x-show="speakingState === 'playing_audio'" class="text-blue-500 flex flex-col items-center">
                <svg class="w-12 h-12 animate-pulse mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                <div class="text-lg font-medium animate-pulse">Playing audio...</div>
            </div>

            <!-- Prep Time State -->
            <div x-show="speakingState === 'prep'" class="text-amber-500 flex flex-col items-center">
                <svg class="w-12 h-12 animate-pulse mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-lg font-medium">Preparation Time</div>
                <div class="text-4xl font-bold" x-text="formatTime(speakingTimer)"></div>
            </div>

            <!-- Recording State -->
            <div x-show="speakingState === 'recording'" class="flex flex-col items-center relative gap-4">
                {{-- Audio Wave Animation Template --}}
                <div class="flex items-center gap-1 h-12">
                    <template x-for="i in 5">
                        <div class="w-1.5 bg-red-500 rounded-full animate-wave" 
                             :style="`height: ${Math.random() * 100}%; animation-delay: ${i * 0.1}s`"></div>
                    </template>
                </div>
                
                {{-- Circular Timer SVG --}}
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
            
            <!-- Idle/Start State -->
            <div x-show="speakingState === 'idle'" class="mt-4">
                <button @click="startSpeakingPart()" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-lg shadow-lg hover:shadow-xl transition-all flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                    Start Recording
                </button>
            </div>
            
            <!-- Checking/Saving State -->
            <div x-show="speakingState === 'saving'" class="text-indigo-600 flex flex-col items-center">
                <svg class="w-10 h-10 animate-spin mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <div class="text-sm font-medium">Processing Audio...</div>
            </div>
        </div>
    </div>
</div>
</div>
</template>
