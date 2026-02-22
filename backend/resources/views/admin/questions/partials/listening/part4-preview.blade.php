<div class="bg-gradient-to-br from-purple-50 to-pink-50 p-6 rounded-lg border border-purple-100">
    <h4 class="font-semibold text-gray-800 mb-4">Preview - Monologue Questions</h4>
    
    <!-- Topic Display -->
    <div class="mb-4 bg-white p-3 rounded-lg border-l-4 border-purple-500">
        <p class="text-sm font-medium text-gray-600">Topic:</p>
        <p class="text-base font-semibold text-gray-800" x-text="topic || 'No topic specified'"></p>
    </div>

    <!-- Questions Preview -->
    <div class="space-y-4">
        <template x-for="(q, qIndex) in questions" :key="qIndex">
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <p class="font-medium text-gray-800 mb-3">
                    <span x-text="(qIndex + 1) + '.'"></span>
                    <span x-text="q.question || 'Question...'"></span>
                </p>
                
                <div class="space-y-2 ml-4">
                    <template x-for="(choice, cIndex) in q.choices" :key="cIndex">
                        <div class="flex items-center gap-2">
                            <input 
                                type="radio" 
                                :name="'preview_q' + qIndex" 
                                :checked="correctAnswers[qIndex] === cIndex"
                                disabled
                                class="text-green-600"
                            >
                            <span 
                                class="text-sm"
                                :class="correctAnswers[qIndex] === cIndex ? 'text-green-600 font-semibold' : 'text-gray-700'"
                                x-text="choice || 'Choice ' + (cIndex + 1)"
                            ></span>
                            <span 
                                x-show="correctAnswers[qIndex] === cIndex" 
                                class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded"
                            >
                                ✓ Correct
                            </span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
