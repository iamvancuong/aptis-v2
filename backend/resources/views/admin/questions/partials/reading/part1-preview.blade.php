<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-3">Preview</h4>
    <div class="space-y-6">
        <template x-for="(item, index) in items" :key="index">
            <div class="pb-4 border-b border-gray-200 last:border-0" x-show="item.paragraph">
                <div class="flex gap-2">
                    <span class="font-bold text-gray-700" x-text="index + 1 + '.'"></span>
                    <div class="flex-1">
                        <!-- Paragraph with gap -->
                        <p class="text-gray-800 text-sm leading-relaxed mb-2">
                            <span x-html="item.paragraph.replace(/\[BLANK\]/g, '________')"></span>
                        </p>
                        
                        <!-- Choices -->
                        <div class="grid grid-cols-3 gap-4 mt-2">
                            <template x-for="(choice, cIndex) in item.choices" :key="cIndex">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 border border-gray-300 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 rounded-full" :class="item.correctIndex == cIndex ? 'bg-green-500' : ''"></div>
                                    </div>
                                    <span class="text-sm" :class="item.correctIndex == cIndex ? 'font-medium text-green-700' : 'text-gray-600'" x-text="choice"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div x-show="!items[0].paragraph" class="text-center text-gray-400 italic text-sm">
            Start typing to see preview...
        </div>
    </div>
</div>
