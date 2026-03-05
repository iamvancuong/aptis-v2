<div class="space-y-6">
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
        <p class="text-sm text-blue-700">
            <strong>Part 3 Format:</strong> Students listen to a conversation and respond to statements using a shared set of options (e.g., "Agree", "Disagree", "Not stated").
        </p>
    </div>

    <!-- Topic/Context -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Topic/Context</label>
        <input 
            type="text" 
            x-model="topic" 
            name="metadata[topic]" 
            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            placeholder="e.g., politics bản 1"
            required
        >
    </div>

    <!-- Shared Dropdown Choices (Fixed) -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Dropdown Choices (Fixed)</label>
        <div class="space-y-2">
            <template x-for="(choice, index) in sharedChoices" :key="index">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 w-6" x-text="(index + 1) + '.'"></span>
                    <input 
                        type="text" 
                        x-model="sharedChoices[index]" 
                        :name="'metadata[shared_choices][' + index + ']'" 
                        class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                </div>
            </template>
        </div>
    </div>

    <!-- Statements (Fixed) -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Statements (4 fixed)</label>
        <div class="space-y-3">
            <template x-for="(statement, index) in statements" :key="index">
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <label class="text-sm font-medium text-gray-700 mb-2 block">
                        <span x-text="(index + 1) + '.'"></span> Statement
                    </label>
                    
                    <input 
                        type="text" 
                        x-model="statements[index]" 
                        :name="'metadata[statements][' + index + ']'" 
                        class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 mb-3"
                        placeholder="e.g., Young people are becoming more interested in politics"
                        required
                    >
                    
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600">Correct Answer:</label>
                        <select 
                            x-model="correctAnswers[index]" 
                            :name="'metadata[correct_answers][' + index + ']'" 
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            required
                        >
                            <option value="">-- Select --</option>
                            <template x-for="(choice, choiceIdx) in sharedChoices" :key="choiceIdx">
                                <option :value="choiceIdx" x-text="choice || 'Choice ' + (choiceIdx + 1)" :selected="correctAnswers[index] == choiceIdx"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
