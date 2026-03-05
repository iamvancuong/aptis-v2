<div class="space-y-6">
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
        <p class="text-sm text-blue-700">
            <strong>Note:</strong> Each speaker needs a separate audio file. Upload 4 audio files, one for each speaker.
        </p>
    </div>

    <!-- Speakers Grid with Audio Upload -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Speakers & Audio Files (4 speakers)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-gray-100 transition">
                    <!-- Speaker Name -->
                    <div class="mb-3">
                        <label class="text-xs font-medium text-gray-600">Speaker Name</label>
                        <input 
                            type="text" 
                            x-model="items[index]" 
                            :name="'metadata[items][' + index + ']'" 
                            class="w-full mt-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            :placeholder="'e.g., Speaker ' + String.fromCharCode(65 + index)"
                            required
                        >
                    </div>
                    
                    <!-- Audio Upload -->
                    <div x-data="{ 
                        fileName: '', 
                        previewUrl: '', 
                        get existingAudio() { 
                            const audioFiles = @js($question->metadata['audio_files'] ?? []);
                            return audioFiles[index] || null;
                        }
                    }">
                        <label class="text-xs font-medium text-gray-600">Audio File</label>
                        
                        <!-- Existing Audio Preview (Edit Mode) -->
                        <template x-if="existingAudio">
                            <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 mb-1">Current Audio:</p>
                                <audio :src="'/storage/' + existingAudio" controls class="w-full h-8"></audio>
                            </div>
                        </template>
                        
                        <div class="mt-1 border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-indigo-400 transition">
                            <label :for="'audio_' + index" class="cursor-pointer block">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                </svg>
                                <span class="text-xs text-indigo-600 font-medium" x-text="existingAudio ? 'Change file' : 'Choose file'"></span>
                                <input 
                                    :id="'audio_' + index" 
                                    :name="'speaker_audio[' + index + ']'" 
                                    type="file" 
                                    class="sr-only" 
                                    accept=".mp3,.wav,.ogg,.m4a"
                                    @change="fileName = $event.target.files[0]?.name || ''; previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : '';"
                                >
                            </label>
                            <p x-show="fileName" x-text="fileName" class="text-xs text-gray-600 mt-2 truncate"></p>
                            <audio x-show="previewUrl" :src="previewUrl" controls class="w-full mt-2 h-8"></audio>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Opinion/Statement Choices -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Opinion/Statement Choices (6 choices)</label>
        <div class="space-y-2">
            <template x-for="(choice, index) in choices" :key="index">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 w-6" x-text="(index + 1) + '.'"></span>
                    <input 
                        type="text" 
                        x-model="choices[index]" 
                        :name="'metadata[choices][' + index + ']'" 
                        class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="e.g., Should recycle more"
                        required
                    >
                </div>
            </template>
        </div>
    </div>

    <!-- Correct Answers Mapping -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Match Speakers to Opinions</label>
        <div class="space-y-3">
            <template x-for="(ans, index) in correctAnswers" :key="index">
                <div class="flex items-center gap-3 bg-white p-3 rounded-lg border border-gray-200">
                    <span class="text-sm font-semibold text-gray-700 min-w-[100px]" x-text="items[index] || 'Speaker ' + (index + 1)"></span>
                    <span class="text-gray-400">→</span>
                    <select 
                        x-model="correctAnswers[index]" 
                        :name="'metadata[correct_answers][' + index + ']'" 
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                        <option value="">- Select Opinion -</option>
                        <template x-for="(choice, idx) in choices" :key="idx">
                            <option :value="idx" x-text="'Choice ' + (idx + 1) + ': ' + (choice || '...')" :selected="correctAnswers[index] == idx"></option>
                        </template>
                    </select>
                </div>
            </template>
        </div>
    </div>
</div>
