<div class="space-y-4">
    <template x-for="(item, index) in items" :key="index">
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-start mb-4">
                <span class="text-sm font-medium text-gray-500" x-text="`Question ${index + 1}`"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Paragraph -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paragraph Text</label>
                    <textarea 
                        name="metadata[paragraphs][]"
                        x-model="item.paragraph"
                        rows="3"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Enter paragraph text with [BLANK]..."
                        required
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">Use [BLANK] for the missing word location.</p>
                </div>

                <!-- Choices -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Choices</label>
                    <div class="space-y-2">
                        <template x-for="(choice, choiceIndex) in item.choices" :key="choiceIndex">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 w-4" x-text="String.fromCharCode(65 + choiceIndex)"></span>
                                <input 
                                    type="text" 
                                    :name="`metadata[choices][${index}][]`"
                                    x-model="item.choices[choiceIndex]"
                                    class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    :placeholder="`Option ${String.fromCharCode(65 + choiceIndex)}`"
                                    required
                                >
                                <input 
                                    type="radio" 
                                    :name="`metadata[correct_answers][${index}]`"
                                    :value="choiceIndex"
                                    x-model.number="item.correctIndex"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                    title="Mark as correct answer"
                                    required
                                >
                            </div>
                        </template>
                    </div>
                    <div class="mt-2 text-xs text-red-500" x-show="item.correctIndex === null">
                        Please select the correct answer.
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
