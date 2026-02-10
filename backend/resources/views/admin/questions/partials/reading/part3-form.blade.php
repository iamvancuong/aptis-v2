<div class="space-y-6">
    <!-- Opinions / Options Section -->
    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase">Section 1: Opinions / Texts</h3>
        <p class="text-xs text-blue-600 mb-4">Enter the 4 short texts or opinions (A, B, C, D) that users will match questions to.</p>
        
        <div class="space-y-3">
            <template x-for="(option, index) in options" :key="index">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-sm" x-text="getChar(index)"></span>
                    <div class="flex-1">
                        <textarea 
                            :name="`metadata[options][${index}]`"
                            x-model="options[index]"
                            rows="7"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :placeholder="`Enter text for Option ${getChar(index)}...`"
                            required
                        ></textarea>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Questions Section -->
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase">Section 2: Questions</h3>
            <button type="button" @click="addQuestion()" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Question
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(question, index) in questions" :key="index">
                <div class="flex items-start gap-4 p-3 bg-white rounded border border-gray-200 shadow-sm relative group">
                    <span class="text-xs font-bold text-gray-400 mt-2" x-text="index + 1"></span>
                    
                    <div class="flex-1 space-y-2">
                        <!-- Question Text -->
                        <input 
                            type="text" 
                            :name="`metadata[questions][${index}]`"
                            x-model="question.text"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Enter question statement..."
                            required
                        >
                        
                        <!-- Correct Answer Selector -->
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-gray-500">Correct Answer:</span>
                            <div class="flex gap-4">
                                <template x-for="(opt, optIndex) in options" :key="optIndex">
                                    <label class="flex items-center cursor-pointer">
                                        <input 
                                            type="radio" 
                                            :name="`metadata[correct_answers][${index}]`"
                                            :value="optIndex"
                                            x-model.number="question.correctIndex"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                            required
                                        >
                                        <span class="ml-1 text-sm text-gray-700 font-medium" x-text="getChar(optIndex)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <button 
                        type="button" 
                        @click="removeQuestion(index)" 
                        class="text-gray-400 hover:text-red-500 transition ml-2"
                        title="Remove Question"
                        x-show="questions.length > 1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
