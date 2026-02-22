{{-- Reading Part 4: Long Text Comprehension (Heading Matching - Single Column) --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 4">
    <div class="space-y-6">
        <template x-for="(para, pIdx) in currentQuestion.metadata.paragraphs" :key="pIdx">
            <div class="border-b border-gray-200 pb-6 last:border-b-0">
                {{-- Paragraph Number --}}
                <div class="flex items-center gap-3 mb-3">
                    <span class="font-bold text-gray-500 text-sm" x-text="(pIdx + 1) + '.'"></span>
                    
                    {{-- Dropdown --}}
                    <select 
                        x-model.number="part4Answers[pIdx]"
                        class="flex-1 text-sm border rounded-md py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"
                        :class="hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-indigo-400 cursor-pointer'"
                        :style="getPart4SelectStyle(pIdx)"
                        :disabled="hasAnswered(currentQuestion.id)"
                    >
                        <option value="" disabled>- Select -</option>
                        <template x-for="(h, hIdx) in currentQuestion.metadata.headings" :key="hIdx">
                            <option :value="hIdx" x-text="h"></option>
                        </template>
                    </select>
                </div>

                {{-- Paragraph Text --}}
                <p class="text-gray-700 leading-relaxed text-sm" x-text="para"></p>

                {{-- Per-paragraph feedback --}}
                <template x-if="hasAnswered(currentQuestion.id)">
                    <div class="mt-3 flex items-center gap-2 text-sm">
                        {{-- Correct --}}
                        <div x-show="part4Answers[pIdx] === currentQuestion.metadata.correct_answers[pIdx]" class="flex items-center text-green-600 font-bold">
                            <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Correct
                        </div>
                        {{-- Incorrect --}}
                        <div x-show="part4Answers[pIdx] !== currentQuestion.metadata.correct_answers[pIdx]" class="flex items-center text-red-600">
                            <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            <span class="font-bold">Incorrect.</span>
                            <span class="ml-1 text-gray-600">Answer:</span>
                            <span class="ml-1 font-bold text-green-600" x-text="currentQuestion.metadata.headings[currentQuestion.metadata.correct_answers[pIdx]]"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>


    </div>
</template>

