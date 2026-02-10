<div class="space-y-6">
    <!-- Headings Section -->
    <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase">Section 1: Headings</h3>
            <button type="button" @click="addHeading()" class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded hover:bg-purple-700 transition flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Heading
            </button>
        </div>
        <p class="text-xs text-purple-600 mb-4">Enter the list of headings. You usually have 8 headings for 7 paragraphs (one extra).</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="(heading, index) in headings" :key="index">
                <div class="flex items-center gap-2">
                    <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-purple-200 text-purple-800 font-bold text-xs" x-text="index + 1"></span>
                    <div class="flex-1 relative">
                        <input 
                            type="text" 
                            :name="`metadata[headings][${index}]`"
                            x-model="headings[index]"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500"
                            :placeholder="`Heading ${index + 1}`"
                        >
                        <button 
                            type="button" 
                            @click="removeHeading(index)" 
                            class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500 transition"
                            title="Remove Heading"
                            x-show="headings.length > 2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Paragraphs Section -->
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-700 uppercase">Section 2: Paragraphs & Matches</h3>
            <button type="button" @click="addParagraph()" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Paragraph
            </button>
        </div>

        <div class="space-y-6">
            <template x-for="(paragraph, index) in paragraphs" :key="index">
                <div class="bg-white rounded border border-gray-200 shadow-sm p-4 relative group">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider" x-text="`Paragraph ${index + 1}`"></span>
                        <button 
                            type="button" 
                            @click="removeParagraph(index)" 
                            class="text-gray-400 hover:text-red-500 transition"
                            title="Remove Paragraph"
                            x-show="paragraphs.length > 1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Paragraph Text -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Content</label>
                            <textarea 
                                :name="`metadata[paragraphs][${index}]`"
                                x-model="paragraphs[index]"
                                rows="5"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                placeholder="Enter paragraph content..."
                            ></textarea>
                        </div>
                        
                        <!-- Matching Heading -->
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Correct Heading</label>
                            <div class="relative">
                                <select 
                                    :name="`metadata[correct_answers][${index}]`" 
                                    x-model.number="matches[index]"
                                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 appearance-none bg-white"
                                >
                                    <option value="">Select Heading...</option>
                                    <template x-for="(heading, hIndex) in headings" :key="hIndex">
                                        <option :value="hIndex" x-text="`${hIndex + 1}. ${heading.substring(0, 30)}${heading.length > 30 ? '...' : ''}`"></option>
                                    </template>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Select the heading that matches this paragraph.</p>
                            
                            <!-- Display selected heading full text validation -->
                            <div x-show="matches[index] !== null && headings[matches[index]]" class="mt-3 p-2 bg-purple-50 text-xs text-purple-700 rounded border border-purple-100">
                                <strong>Selected:</strong> <span x-text="headings[matches[index]]"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
