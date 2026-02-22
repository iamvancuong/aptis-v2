{{-- Reading Part 1: Sentence Gap Fill --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 1">
    <div class="space-y-6">
        <template x-for="(paragraph, pIndex) in currentQuestion.metadata.paragraphs" :key="pIndex">
            <div class="text-lg leading-relaxed bg-white p-4 rounded-lg border border-gray-100 shadow-sm flex items-center flex-wrap gap-2">
                {{-- Paragraph Text with Inline Select --}}
                <template x-for="(segment, sIndex) in paragraph.split('[BLANK]')" :key="sIndex">
                    <span>
                        <span x-text="segment"></span>
                        <template x-if="sIndex < paragraph.split('[BLANK]').length - 1">
                            <select 
                                x-model.number="part1Answers[currentQuestion.id][pIndex]"
                                class="mx-1 py-1 px-3 border rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 cursor-pointer"
                                :class="[hasAnswered(currentQuestion.id) ? 'pointer-events-none font-bold' : 'bg-white border-gray-300 hover:border-blue-400']"
                                :style="getPart1SelectStyle(pIndex)"
                            >
                                <option value="" disabled selected>???</option>
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
                        {{-- Correct --}}
                        <div x-show="part1Answers[currentQuestion.id][pIndex] === currentQuestion.metadata.correct_answers[pIndex]" class="flex items-center text-green-600 font-bold text-sm">
                            <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Correct</span>
                        </div>

                        {{-- Incorrect --}}
                        <div x-show="part1Answers[currentQuestion.id][pIndex] !== currentQuestion.metadata.correct_answers[pIndex]" class="flex items-center text-red-600 text-sm">
                            <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="font-bold mr-1">Incorrect.</span>
                            <span class="text-gray-600">Answer: </span>
                            <span class="font-bold ml-1 text-green-600" x-text="currentQuestion.metadata.choices[pIndex][currentQuestion.metadata.correct_answers[pIndex]]"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>


    </div>
</template>
