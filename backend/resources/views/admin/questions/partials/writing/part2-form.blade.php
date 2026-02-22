<div class="space-y-4">
    <p class="text-sm text-gray-500 mb-4">Set up the email writing scenario. Students will write an informal/semi-formal email.</p>

    <!-- Scenario -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Scenario / Prompt</label>
        <textarea name="metadata[scenario]"
            x-model="scenario"
            rows="4"
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="e.g. You recently moved to a new city. Write an email to your friend telling them about your new home..."
            required></textarea>
    </div>

    <!-- Word Limits -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Min Words</label>
            <input type="number" name="metadata[word_limit][min]"
                x-model.number="wordLimitMin"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value="20" min="1">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Words</label>
            <input type="number" name="metadata[word_limit][max]"
                x-model.number="wordLimitMax"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value="30" min="1">
        </div>
    </div>

    <!-- Hints (optional) -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Hints / Bullet Points (optional)</label>
        <textarea name="metadata[hints]"
            x-model="hints"
            rows="3"
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="e.g. Mention: location, size of the apartment, your favourite room"></textarea>
        <p class="mt-1 text-xs text-gray-500">Optional bullet points to guide the student.</p>
    </div>

    <!-- Sample Answer -->
    <div class="border-t border-gray-200 pt-4 mt-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">💡 Sample Answer (Đáp án gợi ý)</h4>
        <p class="text-xs text-gray-500 mb-3">Provide a model email for students to compare their work against in practice mode.</p>
        <textarea name="metadata[sample_answer]"
            x-model="sampleAnswer"
            rows="5"
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="e.g. Dear Tom, I'm writing to tell you about my new apartment..."></textarea>
    </div>
</div>
