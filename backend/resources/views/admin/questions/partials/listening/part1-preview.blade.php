<div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-6 rounded-lg border border-indigo-100">
    <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        Preview
    </h4>
    <div class="space-y-2">
        <template x-for="(choice, index) in choices" :key="index">
            <label 
                class="flex items-center gap-3 p-3 bg-white rounded-lg border-2 transition-all duration-200 cursor-pointer hover:border-indigo-300"
                :class="index == correctAnswer ? 'border-green-500 bg-green-50' : 'border-gray-200'"
            >
                <input 
                    type="radio" 
                    :checked="index == correctAnswer" 
                    disabled
                    class="text-indigo-600"
                >
                <span 
                    class="flex-1"
                    :class="index == correctAnswer ? 'font-semibold text-green-700' : 'text-gray-700'"
                    x-text="choice || 'Choice ' + (index + 1)"
                ></span>
                <span 
                    x-show="index == correctAnswer" 
                    class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full"
                >
                    ✓ Correct
                </span>
            </label>
        </template>
    </div>
</div>
