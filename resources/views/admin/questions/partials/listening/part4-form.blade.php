<div class="space-y-6">
    <div class="bg-purple-50 border-l-4 border-purple-400 p-4 rounded">
        <p class="text-sm text-purple-700">
            <strong>Part 4 Format:</strong> Students listen to a monologue and answer 2 multiple-choice questions, each with 3 options.
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
            placeholder="e.g., Regional Development Planning"
            required
        >
    </div>

    <!-- Questions (Fixed 2) -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Questions (2 fixed)</label>
        <div class="space-y-4">
            <template x-for="(q, qIndex) in questions" :key="qIndex">
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Question <span x-text="qIndex + 1"></span>
                    </label>
                    
                    <input 
                        type="text" 
                        x-model="questions[qIndex].question" 
                        :name="'metadata[questions][' + qIndex + '][question]'" 
                        class="w-full px-4 py-2 mb-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="e.g., What is one of the main criticisms of the Regional Development Plan?"
                        required
                    >
                    
                    <label class="block text-xs font-medium text-gray-600 mb-2">Choices (3 fixed)</label>
                    <template x-for="(choice, cIndex) in questions[qIndex].choices" :key="cIndex">
                        <div class="flex items-center gap-2 mb-2">
                            <input 
                                type="radio" 
                                :name="'question_' + qIndex + '_preview'" 
                                :checked="correctAnswers[qIndex] == cIndex"
                                @click="correctAnswers[qIndex] = cIndex"
                                class="text-indigo-600 focus:ring-indigo-500"
                            >
                            <input 
                                type="text" 
                                x-model="questions[qIndex].choices[cIndex]" 
                                :name="'metadata[questions][' + qIndex + '][choices][' + cIndex + ']'" 
                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                :placeholder="'Choice ' + (cIndex + 1)"
                                required
                            >
                        </div>
                    </template>
                    
                    <!-- Hidden input for correct answer -->
                    <input 
                        type="hidden" 
                        :name="'metadata[correct_answers][' + qIndex + ']'" 
                        :value="correctAnswers[qIndex]"
                    >
                </div>
            </template>
        </div>
    </div>
</div>
