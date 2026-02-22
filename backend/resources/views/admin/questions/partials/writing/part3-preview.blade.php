<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">Preview</h4>
    <div class="space-y-4">
        <template x-for="(item, index) in items" :key="index">
            <div x-show="item.prompt" class="p-3 bg-white rounded border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase" x-text="'Prompt ' + (index + 1)"></span>
                    <span class="text-xs text-gray-400" x-text="item.wordLimitMin + '-' + item.wordLimitMax + ' words'"></span>
                </div>
                <p class="text-sm text-gray-700" x-text="item.prompt"></p>
                <div class="mt-2 p-2 bg-gray-50 border border-dashed border-gray-200 rounded min-h-[40px]">
                    <span class="text-gray-300 text-xs italic">Student response...</span>
                </div>
            </div>
        </template>
        <div x-show="!items[0].prompt" class="text-center text-gray-400 italic text-sm">
            Start typing to see preview...
        </div>
    </div>
</div>
