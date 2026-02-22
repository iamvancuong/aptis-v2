<div class="space-y-4">
    <p class="text-sm text-gray-500 mb-4">Create form fields that students will fill in (e.g., Name, Date of Birth, etc.)</p>

    <template x-for="(item, index) in items" :key="index">
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between items-start mb-3">
                <span class="text-sm font-medium text-gray-500" x-text="`Field ${index + 1}`"></span>
                <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                    class="text-red-400 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Label -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Label</label>
                    <input type="text"
                        :name="'metadata[fields][' + index + '][label]'"
                        x-model="item.label"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="e.g. Full Name"
                        required>
                </div>

                <!-- Placeholder -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Placeholder / Hint</label>
                    <input type="text"
                        :name="'metadata[fields][' + index + '][placeholder]'"
                        x-model="item.placeholder"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="e.g. Enter your full name">
                </div>
            </div>
        </div>
    </template>

    <button type="button" @click="items.push({ label: '', placeholder: '' })"
        class="w-full py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-500 transition-colors">
        + Add Field
    </button>

    <!-- Instructions -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Instructions for student</label>
        <textarea name="metadata[instructions]"
            x-model="instructions"
            rows="2"
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="e.g. Fill in the form using the information given."></textarea>
    </div>

    <!-- Sample Answer -->
    <div class="border-t border-gray-200 pt-4 mt-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">💡 Sample Answer (Đáp án gợi ý)</h4>
        <p class="text-xs text-gray-500 mb-3">Provide suggested answers for each field. These will be shown to students in practice mode.</p>
        <template x-for="(item, index) in items" :key="'sa-'+index">
            <div class="flex items-center gap-3 mb-2" x-show="item.label">
                <label class="text-sm font-medium text-gray-600 w-32 flex-shrink-0" x-text="item.label + ':'"></label>
                <input type="text"
                    :name="'metadata[sample_answer][' + item.label + ']'"
                    x-model="sampleAnswer[item.label]"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Suggested answer...">
            </div>
        </template>
    </div>
</div>
