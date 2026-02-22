<div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-6 rounded-lg border border-indigo-100">
    <h4 class="font-semibold text-gray-800 mb-4">Preview - Conversation Statements</h4>
    
    <!-- Topic Display -->
    <div class="mb-4 bg-white p-3 rounded-lg border-l-4 border-indigo-500">
        <p class="text-sm font-medium text-gray-600">Topic:</p>
        <p class="text-base font-semibold text-gray-800" x-text="topic || 'No topic specified'"></p>
    </div>

    <!-- Shared Choices Info -->
    <div class="mb-4">
        <p class="text-sm font-medium text-gray-600 mb-2">Available Choices:</p>
        <div class="flex flex-wrap gap-2">
            <template x-for="(choice, idx) in sharedChoices" :key="idx">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium" x-text="choice || 'Choice ' + (idx + 1)"></span>
            </template>
        </div>
    </div>

    <!-- Statements Preview -->
    <div>
        <p class="text-sm font-medium text-gray-600 mb-3">Statements & Correct Answers:</p>
        <div class="space-y-2">
            <template x-for="(statement, index) in statements" :key="index">
                <div class="bg-white p-3 rounded-lg border border-gray-200 hover:border-green-300 transition">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm text-gray-700 flex-1">
                            <span class="font-semibold" x-text="(index + 1) + '.'"></span>
                            <span x-text="statement || 'Statement...'"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">→</span>
                            <span 
                                class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium whitespace-nowrap"
                                x-text="sharedChoices[correctAnswers[index]] || 'Not selected'"
                            ></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
