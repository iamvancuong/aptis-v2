{{-- Generic Feedback Footer (Reusable across skills) --}}
<div x-show="hasAnswered(currentQuestion.id)" 
     class="px-6 pb-6 bg-gray-50 border-t border-gray-100 pt-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <span class="flex items-center justify-center h-8 w-8 rounded-full"
                  :class="feedback[currentQuestion.id]?.correct ? 'bg-green-100' : 'bg-red-100'">
                <svg class="w-5 h-5" :class="feedback[currentQuestion.id]?.correct ? 'text-green-600' : 'text-red-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path x-show="feedback[currentQuestion.id]?.correct" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    <path x-show="!feedback[currentQuestion.id]?.correct" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </span>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium" :class="feedback[currentQuestion.id]?.correct ? 'text-green-800' : 'text-red-800'">
                <span x-text="feedback[currentQuestion.id]?.correct ? 'Well done!' : 'Review your answers above.'"></span>
            </h3>
            <p class="mt-1 text-sm text-gray-600" x-show="currentQuestion.metadata.explanation">
                <span class="font-bold">Explanation:</span> <span x-text="currentQuestion.metadata.explanation"></span>
            </p>
        </div>
    </div>
</div>
