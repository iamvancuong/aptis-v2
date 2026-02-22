<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">Preview</h4>
    <div class="space-y-3">
        <template x-for="(item, index) in items" :key="index">
            <div x-show="item.label" class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-600 w-32" x-text="item.label + ':'"></label>
                <div class="flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded text-sm text-gray-400" x-text="item.placeholder || 'Enter here...'"></div>
            </div>
        </template>
        <div x-show="!items[0].label" class="text-center text-gray-400 italic text-sm">
            Start typing to see preview...
        </div>
    </div>
</div>
