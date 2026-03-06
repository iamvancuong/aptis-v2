{{-- Grammar Part 2: Vocabulary Dropdown (Q26–30) --}}
<template x-if="currentQuestion.skill === 'grammar' && currentQuestion.part === 2">
    <div class="space-y-5">
        {{-- Instruction --}}
        <p class="text-sm text-gray-600 italic" x-text="currentQuestion.stem"></p>

        {{-- Example (if available) --}}
        <template x-if="currentQuestion.metadata.example">
            <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 border border-gray-100 rounded-lg px-4 py-2.5">
                <span class="font-medium text-gray-400 uppercase text-xs tracking-wide w-16 shrink-0">Example</span>
                <span class="font-semibold text-gray-700" x-text="currentQuestion.metadata.example.left"></span>
                <span class="text-gray-300 font-mono mx-1" x-text="currentQuestion.metadata.connector || '='"></span>
                <span class="font-semibold text-indigo-600" x-text="currentQuestion.metadata.example.right"></span>
            </div>
        </template>

        {{-- 5 Pairs --}}
        <div class="space-y-2">
            <template x-for="(pair, idx) in currentQuestion.metadata.pairs" :key="pair.id">
                <div class="flex items-center gap-3 rounded-xl border px-4 py-3 transition-all"
                     :class="{
                        'border-gray-200 bg-white': !hasAnswered(currentQuestion.id),
                        'border-green-300 bg-green-50':
                            hasAnswered(currentQuestion.id) &&
                            vocabAnswers[currentQuestion.id] &&
                            vocabAnswers[currentQuestion.id][pair.id] == currentQuestion.metadata.correct_answers[pair.id],
                        'border-red-200 bg-red-50':
                            hasAnswered(currentQuestion.id) &&
                            vocabAnswers[currentQuestion.id] &&
                            vocabAnswers[currentQuestion.id][pair.id] != currentQuestion.metadata.correct_answers[pair.id],
                     }">
                    {{-- Number --}}
                    <span class="w-5 text-xs font-semibold text-gray-400 shrink-0 text-center" x-text="pair.id"></span>

                    {{-- Prompt or prefix/suffix --}}
                    <template x-if="pair.prompt !== undefined">
                        <span class="text-sm font-medium text-gray-700 min-w-0 shrink-0 w-28 truncate" x-text="pair.prompt"></span>
                    </template>
                    <template x-if="pair.prefix !== undefined">
                        <span class="text-sm text-gray-700 flex-1 min-w-0 truncate" x-text="pair.prefix"></span>
                    </template>

                    {{-- Connector --}}
                    <template x-if="pair.prompt !== undefined">
                        <span class="text-gray-300 font-mono text-sm shrink-0"
                              x-text="currentQuestion.metadata.connector || '='"></span>
                    </template>
                    <template x-if="pair.prefix !== undefined">
                        <span class="text-gray-300 text-xs font-mono shrink-0">[___]</span>
                    </template>

                    {{-- Suffix (sentence_completion) --}}
                    <template x-if="pair.suffix !== undefined">
                        <span class="text-sm text-gray-700 flex-1 min-w-0 truncate" x-text="pair.suffix"></span>
                    </template>

                    {{-- Select dropdown --}}
                    <template x-if="!hasAnswered(currentQuestion.id)">
                        <select class="ml-auto shrink-0 w-32 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-white"
                                @change="setVocabAnswer(currentQuestion.id, pair.id, $event.target.value)">
                            <option value="">Chọn...</option>
                            <template x-for="word in currentQuestion.metadata.dropdown_pool" :key="word">
                                <option :value="word"
                                        :selected="vocabAnswers[currentQuestion.id] && vocabAnswers[currentQuestion.id][pair.id] == word"
                                        x-text="word"></option>
                            </template>
                        </select>
                    </template>

                    {{-- After submit: show result --}}
                    <template x-if="hasAnswered(currentQuestion.id)">
                        <div class="ml-auto flex items-center gap-2 shrink-0">
                            <span class="font-semibold text-sm"
                                  :class="{
                                    'text-green-600':
                                        vocabAnswers[currentQuestion.id] &&
                                        vocabAnswers[currentQuestion.id][pair.id] == currentQuestion.metadata.correct_answers[pair.id],
                                    'text-red-500':
                                        !vocabAnswers[currentQuestion.id] ||
                                        vocabAnswers[currentQuestion.id][pair.id] != currentQuestion.metadata.correct_answers[pair.id],
                                  }"
                                  x-text="vocabAnswers[currentQuestion.id] ? (vocabAnswers[currentQuestion.id][pair.id] || '—') : '—'"></span>
                            <template x-if="vocabAnswers[currentQuestion.id] && vocabAnswers[currentQuestion.id][pair.id] != currentQuestion.metadata.correct_answers[pair.id]">
                                <span class="text-xs text-green-600 font-medium">
                                    → <span x-text="currentQuestion.metadata.correct_answers[pair.id]"></span>
                                </span>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</template>
