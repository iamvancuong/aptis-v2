<div class="space-y-4">
    <p class="text-sm text-gray-600">Create 3 choices for the listening question. Mark the correct answer.</p>
    
    <!-- Choices -->
    <template x-for="(choice, index) in choices" :key="index">
        <div>
            <label :for="'choice_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                Choice <span x-text="index + 1"></span>
            </label>
            <input 
                type="text" 
                x-model="choices[index]" 
                :name="'metadata[choices][' + index + ']'" 
                :id="'choice_' + index"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200" 
                placeholder="Enter choice text"
                required
            >
        </div>
    </template>
    
    <!-- Correct Answer -->
    <div>
        <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-1">Correct Answer</label>
        <select 
            x-model="correctAnswer" 
            name="metadata[correct_answer]" 
            id="correct_answer"
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
            required
        >
            <option value="0">Choice 1</option>
            <option value="1">Choice 2</option>
            <option value="2">Choice 3</option>
        </select>
    </div>
</div>
