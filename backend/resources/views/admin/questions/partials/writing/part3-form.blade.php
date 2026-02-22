<div class="space-y-4">
    <p class="text-sm text-gray-500 mb-4">Create 3 social media prompts. Students will write a short response (30-40 words) to each.</p>

    <template x-for="(item, index) in items" :key="index">
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-start mb-3">
                <span class="text-sm font-medium text-gray-500" x-text="`Prompt ${index + 1}`"></span>
            </div>

            <!-- Prompt Text -->
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Social Media Post / Question</label>
                <textarea
                    :name="'metadata[questions][' + index + '][prompt]'"
                    x-model="item.prompt"
                    rows="3"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="e.g. A friend posted: 'Just got promoted at work! 🎉' — Write a comment congratulating them."
                    required></textarea>
            </div>

            <!-- Word limits per prompt -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Words</label>
                    <input type="number"
                        :name="'metadata[questions][' + index + '][word_limit][min]'"
                        x-model.number="item.wordLimitMin"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        value="30" min="1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Words</label>
                    <input type="number"
                        :name="'metadata[questions][' + index + '][word_limit][max]'"
                        x-model.number="item.wordLimitMax"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        value="40" min="1">
                </div>
            </div>
        </div>
    </template>

    <button type="button" @click="items.push({ prompt: '', wordLimitMin: 30, wordLimitMax: 40 }); sampleAnswers.push('');"
        class="w-full py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-500 transition-colors">
        + Add Prompt
    </button>

    <!-- Sample Answers -->
    <div class="border-t border-gray-200 pt-4 mt-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">💡 Sample Answers (Đáp án gợi ý)</h4>
        <p class="text-xs text-gray-500 mb-3">Provide a model response for each prompt. These will be shown in practice mode.</p>
        <template x-for="(item, index) in items" :key="'sa-'+index">
            <div class="mb-3" x-show="item.prompt">
                <label class="block text-sm font-medium text-gray-600 mb-1" x-text="'Response ' + (index + 1) + ':'"></label>
                <textarea
                    :name="'metadata[sample_answer][' + index + ']'"
                    x-model="sampleAnswers[index]"
                    rows="2"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Suggested response..."></textarea>
            </div>
        </template>
    </div>
</div>
