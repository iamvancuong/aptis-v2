<div class="space-y-8">
    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <div class="mb-6 border-b border-gray-100 pb-4">
            <h3 class="text-lg font-bold text-gray-800">Long Text Comprehension</h3>
            <p class="text-sm text-gray-500">Read the passage below and match each paragraph to the correct heading.</p>
        </div>

        <!-- Headings List -->
        <div class="mb-8 p-4 bg-purple-50 rounded-lg border border-purple-100">
            <h4 class="text-sm font-bold text-purple-800 mb-3 uppercase tracking-wide">Headings</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4">
                <template x-for="(heading, index) in headings" :key="index">
                    <div class="flex items-start text-sm">
                        <span class="font-bold text-purple-600 w-8 flex-shrink-0" x-text="index + 1"></span>
                        <span class="text-gray-800" x-text="heading || '[Empty Heading]'"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Paragraphs -->
        <div class="space-y-6">
            <template x-for="(paragraph, index) in paragraphs" :key="index">
                <div class="prose max-w-none">
                    <div class="flex items-center gap-3 mb-2">
                        <h4 class="text-md font-bold text-gray-700 m-0" x-text="`Paragraph ${index + 1}`"></h4>
                        <!-- Answer Slot -->
                        <div class="flex items-center">
                            <div class="h-8 min-w-[150px] px-3 border-b-2 border-gray-300 bg-gray-50 flex items-center justify-between text-sm text-gray-600">
                                <span x-text="matches[index] !== null ? (matches[index] + 1) : 'Choose Heading...'"></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 leading-relaxed text-sm whitespace-pre-wrap" x-text="paragraph || '[Empty Paragraph Content]'"></p>
                </div>
            </template>
        </div>
    </div>
</div>
