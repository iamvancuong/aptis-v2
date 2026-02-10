<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">Preview (Drag to Reorder Logic Test)</h4>
    <div class="flex justify-between items-center mb-4">
        <p class="text-xs text-gray-500">This preview mimics the <strong>Student View</strong> (Randomized Order).</p>
        <button 
            type="button" 
            @click="shuffleSentences()" 
            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
        >
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reshuffle Order
        </button>
    </div>
    
    <ul class="space-y-2">
        <template x-for="(sentence, index) in shuffledSentences" :key="sentence.id">
            <li class="bg-white p-3 rounded shadow-sm border border-gray-200 flex items-center gap-3">
                <span class="text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </span>
                <span class="font-bold text-gray-400 select-none">::</span>
                <span x-text="sentence.text || 'Empty sentence...'" class="text-sm text-gray-700 flex-1"></span>
            </li>
        </template>
    </ul>
</div>
