<div class="space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-sm font-medium text-gray-500 mb-4">Preview (Opinion Matching):</p>
        
        <!-- Options Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <template x-for="(option, index) in options" :key="index">
                <div class="p-3 bg-white border border-gray-200 rounded shadow-sm">
                    <div class="text-xs font-bold text-gray-400 mb-1" x-text="'Option ' + getChar(index)"></div>
                    <div class="text-sm text-gray-800 italic" x-text="option || '[Empty Option]'"></div>
                </div>
            </template>
        </div>

        <!-- Questions List -->
        <div class="space-y-2">
            <template x-for="(question, index) in questions" :key="index">
                <div class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-gray-500 rounded-full bg-gray-100 w-6 h-6 flex items-center justify-center" x-text="index + 1"></span>
                        <span class="text-sm text-gray-800" x-text="question.text || '[Empty Question]'"></span>
                    </div>
                    <!-- Correct Answer Badge -->
                    <div x-show="question.correctIndex !== null" class="flex-shrink-0">
                         <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            Answer: <span x-text="getChar(question.correctIndex)"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
