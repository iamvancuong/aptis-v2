<div class="space-y-4">
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Enter the sentences in their <strong>CORRECT order</strong>. The first sentence is usually fixed.
                </p>
            </div>
        </div>
    </div>

    <template x-for="(sentence, index) in sentences" :key="sentence.id">
        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="pt-3">
                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-indigo-100 text-sm font-medium text-indigo-800" x-text="index + 1"></span>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="index === 0 ? 'Sentence 1 (Fixed Start)' : `Sentence ${index + 1}`"></label>
                <textarea 
                    x-model="sentence.text"
                    rows="2"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    :placeholder="index === 0 ? 'Enter the first sentence...' : 'Enter sentence...'"
                ></textarea>
            </div>
            
            <!-- Drag Handle Icon (Visual only for now, drag logic in preview or reorder buttons if needed) -->
            <!-- For simple input, we might just let them type. Drag reordering inputs is also possible -->
        </div>
    </template>
    
    <!-- Hidden input to store metadata for form submission -->
    <template x-for="(sentence, index) in sentences" :key="index">
        <input type="hidden" :name="`metadata[sentences][${index}]`" :value="sentence.text">
    </template>
</div>
