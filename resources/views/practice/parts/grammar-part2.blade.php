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

                    {{-- Prefix and Suffix (shown before the gap) --}}
                    <template x-if="pair.prefix !== undefined">
                        <span class="text-sm text-gray-700" x-text="pair.prefix"></span>
                    </template>
                    <template x-if="pair.suffix !== undefined">
                        <span class="text-sm text-gray-700" x-text="pair.suffix"></span>
                    </template>

                    {{-- Prompt (for non-sentence-completion) --}}
                    <template x-if="pair.prompt !== undefined">
                        <span class="text-sm font-medium text-gray-700 min-w-0" x-text="pair.prompt"></span>
                    </template>

                    {{-- Connector (for non-sentence-completion) --}}
                    <template x-if="pair.prompt !== undefined">
                        <span class="text-gray-300 font-mono text-sm shrink-0"
                              x-text="currentQuestion.metadata.connector || '='"></span>
                    </template>

                    {{-- Select dropdown / Result --}}
                    <div class="flex items-center gap-2" :class="pair.prompt !== undefined ? 'ml-auto' : ''">
                        {{-- Select dropdown --}}
                        <template x-if="!hasAnswered(currentQuestion.id)">
                            <select class="shrink-0 w-32 border border-blue-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400 bg-white"
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
                            <div class="flex items-center gap-2 shrink-0">
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
                                    <span class="text-xs text-green-600 font-medium whitespace-nowrap">
                                        → <span x-text="currentQuestion.metadata.correct_answers[pair.id]"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- After (for sentence_completion) --}}
                    <template x-if="pair.after !== undefined">
                        <span class="text-sm text-gray-700" x-text="pair.after"></span>
                    </template>
                </div>
            </template>
        </div>

        {{-- Explanation --}}
        <template x-if="hasAnswered(currentQuestion.id) && currentQuestion.explanation">
            <div class="mt-4 p-4 bg-purple-50 rounded-xl border border-purple-100 flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-purple-600 text-white rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1">Giải thích</p>
                    <div class="prose prose-sm prose-purple max-w-none text-purple-900 leading-relaxed ck-content" x-html="currentQuestion.explanation"></div>
                </div>
            </div>
        </template>
    </div>
</template>
