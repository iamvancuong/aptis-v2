{{-- Reading Part 3: Short Text Comprehension (Matching with Dropdowns) --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 3">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- LEFT: Texts (A - D) --}}
        <div>
            <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Texts <span class="text-gray-400 normal-case">(A - <span x-text="String.fromCharCode(64 + currentQuestion.metadata.options.length)"></span>)</span></h4>
            <div class="space-y-4">
                <template x-for="(opt, idx) in currentQuestion.metadata.options" :key="idx">
                    <div class="flex rounded-lg border border-gray-200 bg-white overflow-hidden shadow-sm">
                        {{-- Color bar + Letter --}}
                        <div class="w-12 flex-shrink-0 flex items-start justify-center pt-4"
                             :class="['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'][idx] || 'bg-gray-500'">
                            <span class="text-white font-bold text-lg" x-text="String.fromCharCode(65 + idx)"></span>
                        </div>
                        {{-- Text content --}}
                        <div class="p-4 flex-1">
                            <p class="text-sm text-gray-700 leading-relaxed" x-text="opt"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT: Questions with Dropdowns --}}
        <div>
            <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3">Questions</h4>
            <div class="space-y-3">
                <template x-for="(q, qIdx) in currentQuestion.metadata.questions" :key="qIdx">
                    <div class="p-4 bg-white rounded-lg border transition-all"
                         :class="getPart3ContainerClass(qIdx)">
                        {{-- Question text --}}
                        <div class="flex items-start gap-3 mb-3">
                            <span class="font-bold text-gray-500 text-sm flex-shrink-0 mt-0.5" x-text="(qIdx + 1) + '.'"></span>
                            <p class="text-sm font-medium text-gray-800" x-text="q"></p>
                        </div>
                        
                        {{-- Dropdown --}}
                        <select 
                            x-model.number="part3Answers[qIdx]"
                            @change="selectPart3(qIdx, part3Answers[qIdx])"
                            class="w-full sm:w-auto text-sm border rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                            :class="hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400 cursor-pointer'"
                            :style="getPart3SelectStyle(qIdx)"
                            :disabled="hasAnswered(currentQuestion.id)"
                        >
                            <option value="" disabled>- Select person -</option>
                            <template x-for="(opt, oIdx) in currentQuestion.metadata.options" :key="oIdx">
                                <option :value="oIdx" x-text="'Person ' + String.fromCharCode(65 + oIdx)"></option>
                            </template>
                        </select>

                        {{-- Feedback --}}
                        <template x-if="hasAnswered(currentQuestion.id)">
                            <div class="mt-2 flex items-center gap-2 text-sm">
                                {{-- Correct --}}
                                <div x-show="part3Answers[qIdx] === currentQuestion.metadata.correct_answers[qIdx]" class="flex items-center text-green-600 font-bold">
                                    <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Correct
                                </div>
                                {{-- Incorrect --}}
                                <div x-show="part3Answers[qIdx] !== currentQuestion.metadata.correct_answers[qIdx]" class="flex items-center text-red-600">
                                    <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    <span class="font-bold">Incorrect.</span>
                                    <span class="ml-1 text-gray-600">Answer:</span>
                                    <span class="ml-1 font-bold text-green-600" x-text="'Person ' + String.fromCharCode(65 + currentQuestion.metadata.correct_answers[qIdx])"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                


            </div>
        </div>
    </div>
</template>
