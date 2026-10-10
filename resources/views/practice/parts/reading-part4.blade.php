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
                        class="flex-1 text-sm border rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                        :class="hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400 cursor-pointer'"
                        :style="getPart4SelectStyle(pIdx)"
                        :disabled="hasAnswered(currentQuestion.id)"
                    >
                        <option value="" disabled>- Chọn -</option>
                        <template x-for="(h, hIdx) in currentQuestion.metadata.headings" :key="hIdx">
                            <option :value="hIdx" x-text="h"></option>
                        </template>
                    </select>
                </div>

                {{-- Paragraph Text --}}
                <div class="text-gray-700 leading-relaxed text-sm" x-html="para"></div>

                {{-- Per-paragraph feedback --}}
                <template x-if="hasAnswered(currentQuestion.id)">
                    <div class="mt-3 flex items-center gap-2 text-sm">
                        <x-ui.verdict ok="part4Answers[pIdx] == currentQuestion.metadata.correct_answers[pIdx]" answer="currentQuestion.metadata.headings[currentQuestion.metadata.correct_answers[pIdx]]" />
                    </div>
                </template>
            </div>
        </template>


    </div>
</template>

