<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">Preview</h4>
    <div class="space-y-3">
        <div x-show="scenario">
            <p class="text-sm text-gray-600 italic" x-text="scenario"></p>
        </div>
        <div x-show="hints" class="mt-2">
            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Hints:</p>
            <p class="text-sm text-gray-600" x-text="hints"></p>
        </div>
        <div class="flex gap-4 text-xs text-gray-500">
            <span>Min: <strong x-text="wordLimitMin"></strong> words</span>
            <span>Max: <strong x-text="wordLimitMax"></strong> words</span>
        </div>
        <div class="mt-3 p-3 bg-white border border-gray-200 rounded min-h-[80px]">
            <span class="text-gray-300 text-sm italic">Student writes email here...</span>
        </div>
        <div x-show="!scenario" class="text-center text-gray-400 italic text-sm">
            Start typing to see preview...
        </div>
    </div>
</div>
