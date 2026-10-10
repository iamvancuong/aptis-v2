{{-- Reading Part 1: Sentence Gap Fill --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 1">
    <div class="space-y-6">
        <template x-for="(paragraph, pIndex) in currentQuestion.metadata.paragraphs" :key="pIndex">
            <div class="text-lg leading-relaxed bg-white p-4 rounded-lg border border-gray-100 shadow-sm flex items-center flex-wrap gap-2">
                {{-- Paragraph Text with Inline Select --}}
                <template x-for="(segment, sIndex) in paragraph.split('[BLANK]')" :key="sIndex">
                    <span>
                        <span x-html="segment"></span>
                        <template x-if="sIndex < paragraph.split('[BLANK]').length - 1">
                            <select 
                                x-model.number="part1Answers[currentQuestion.id][pIndex]"
                                class="mx-1 py-1 px-3 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 cursor-pointer"
                                :class="[hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400']"
                                :style="getPart1SelectStyle(pIndex)"
                            >
                                <option value="" disabled selected></option>
                                <template x-for="(opt, optIndex) in currentQuestion.metadata.choices[pIndex]" :key="optIndex">
                                    <option :value="optIndex" x-text="opt"></option>
                                </template>
                            </select>
                        </template>
                    </span>
                </template>

                {{-- Row feedback icon & Correct Answer --}}
                <template x-if="hasAnswered(currentQuestion.id)">
                    <div class="ml-auto flex items-center gap-2 flex-shrink-0">
                        <x-ui.verdict ok="part1Answers[currentQuestion.id][pIndex] == currentQuestion.metadata.correct_answers[pIndex]" answer="currentQuestion.metadata.choices[pIndex][currentQuestion.metadata.correct_answers[pIndex]]" />
                    </div>
                </template>
            </div>
        </template>


    </div>
</template>
