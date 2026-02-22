<div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-6 rounded-lg border border-indigo-100">
    <h4 class="font-semibold text-gray-800 mb-4">Preview - Speaker Matching</h4>
    
    <!-- Audio Players Grid -->
    <div class="mb-6">
        <p class="text-sm font-medium text-gray-600 mb-3">🎧 Speaker Audio Files</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                    <p class="font-medium text-gray-700 text-sm mb-2" x-text="item || 'Speaker ' + (index + 1)"></p>
                    <div class="text-xs text-gray-400 italic">Audio file will be uploaded</div>
                </div>
            </template>
        </div>
    </div>

    <!-- Matching Results -->
    <div>
        <p class="text-sm font-medium text-gray-600 mb-3">✅ Correct Matching</p>
        <div class="space-y-2">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-white p-3 rounded-lg border-l-4 border-green-500 flex items-center justify-between">
                    <span class="font-medium text-gray-700" x-text="item || 'Speaker ' + (index + 1)"></span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">→</span>
                        <span class="text-sm text-green-600 font-medium">
                            Choice <span x-text="parseInt(correctAnswers[index]) + 1"></span>: 
                            <span x-text="choices[correctAnswers[index]] || '...'"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
