@extends('layouts.app')

@section('title', ucfirst($mockTest->skill) . ' - Thi thử')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="min-h-screen bg-gray-50 flex flex-col" x-data="mockTestExam()">

    {{-- Timer Bar (Sticky) --}}
    <div class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <h1 class="text-lg font-bold text-gray-800">
                    {{ ucfirst($mockTest->skill) }} — Thi thử
                </h1>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full"
                         :class="timeRemaining <= 300 ? 'bg-red-100 text-red-700' : 'bg-blue-50 text-blue-700'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-mono font-bold text-lg" x-text="formatTime(timeRemaining)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section Tabs --}}
    <div class="bg-white border-b border-gray-200 sticky top-14 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-1 overflow-x-auto py-2">
                @foreach($sectionsWithSets as $index => $section)
                    <button @click="switchSection({{ $index }})"
                        class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="currentSectionIndex === {{ $index }}
                            ? 'bg-blue-600 text-white shadow-md'
                            : (sectionAnswered({{ $index }}) ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')">
                        <span class="flex items-center gap-2">
                            <span>{{ $index + 1 }}. Part {{ $section['part'] }}</span>
                            <template x-if="sectionAnswered({{ $index }})">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </template>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Main Content Area --}}
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 pb-32">

        {{-- Within-section question navigation (for sections with >1 question, e.g. Listening Part 1 = 13 MCQs) --}}
        <div x-show="questions.length > 1" class="mb-4 bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <button @click="prevQuestion()" :disabled="currentIndex === 0"
                    class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    ← Câu trước
                </button>
                <div class="flex items-center gap-1.5 flex-wrap justify-center">
                    <template x-for="(q, qi) in questions" :key="q.id">
                        <button @click="goToQuestion(qi)"
                            class="w-8 h-8 rounded-full text-xs font-bold transition-all"
                            :class="currentIndex === qi
                                ? 'bg-blue-600 text-white ring-2 ring-blue-300 scale-110'
                                : (answers[q.id] !== undefined ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200')"
                            x-text="qi + 1"></button>
                    </template>
                </div>
                <button @click="nextQuestion()" :disabled="currentIndex >= questions.length - 1"
                    class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    Câu sau →
                </button>
            </div>
            <div class="text-center text-xs text-gray-400 mt-2">
                Câu <span x-text="currentIndex + 1"></span> / <span x-text="questions.length"></span>
            </div>
        </div>

        {{-- Single question view (driven by Alpine's currentQuestion) --}}
        <template x-if="currentQuestion">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                {{-- Question Header --}}
                <div class="p-6 border-b border-gray-100 bg-blue-50">
                    <div class="max-w-3xl mx-auto">
                        <div class="text-sm text-gray-500 mb-2">
                            Section <span x-text="currentSectionIndex + 1"></span> — Part <span x-text="sections[currentSectionIndex].part"></span>
                            <template x-if="questions.length > 1">
                                <span> — Câu <span x-text="currentIndex + 1"></span>/<span x-text="questions.length"></span></span>
                            </template>
                        </div>
                        <div class="text-base md:text-lg font-medium" x-text="currentQuestion.stem"></div>
                    </div>
                </div>

                {{-- Question Content — REUSE PRACTICE PARTIALS --}}
                <div class="p-6">
                    @include('practice.parts.reading-part1')
                    @include('practice.parts.reading-part2')
                    @include('practice.parts.reading-part3')
                    @include('practice.parts.reading-part4')
                    @include('practice.parts.listening-part1')
                    @include('practice.parts.listening-part2')
                    @include('practice.parts.listening-part3')
                    @include('practice.parts.listening-part4')
                    @include('practice.parts.writing-part1')
                    @include('practice.parts.writing-part2')
                    @include('practice.parts.writing-part3')
                    @include('practice.parts.writing-part4')
                </div>
            </div>
        </template>
    </main>

    {{-- Footer Navigation --}}
    <footer class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-3">
                {{-- Previous --}}
                <button @click="prevSection()" :disabled="currentSectionIndex === 0"
                    class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    ← Trước
                </button>

                {{-- Progress --}}
                <div class="flex items-center gap-1">
                    @foreach($sectionsWithSets as $i => $s)
                        <div @click="switchSection({{ $i }})"
                            class="w-3 h-3 rounded-full cursor-pointer transition-all"
                            :class="currentSectionIndex === {{ $i }}
                                ? 'bg-blue-600 scale-125'
                                : (sectionAnswered({{ $i }}) ? 'bg-green-400' : 'bg-gray-300')">
                        </div>
                    @endforeach
                </div>

                {{-- Next / Submit --}}
                <template x-if="currentSectionIndex < sections.length - 1">
                    <button @click="nextSection()"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-colors text-sm">
                        Tiếp theo →
                    </button>
                </template>
                <template x-if="currentSectionIndex >= sections.length - 1">
                    <button @click="confirmSubmit()"
                        class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors text-sm">
                        🏁 Nộp bài
                    </button>
                </template>
            </div>
        </div>
    </footer>

    {{-- Submit Confirmation Modal --}}
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4" @click.outside="showConfirmModal = false">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Xác nhận nộp bài?</h3>
            <p class="text-gray-600 mb-2">Bạn đã hoàn thành:</p>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-500">Sections đã làm:</span>
                    <span class="font-bold" x-text="answeredCount + '/' + sections.length"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all" :style="'width:' + (answeredCount/sections.length*100) + '%'"></div>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="showConfirmModal = false"
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">
                    Quay lại
                </button>
                <button @click="submitTest()"
                    class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700"
                    :disabled="submitting">
                    <span x-show="!submitting">Nộp bài</span>
                    <span x-show="submitting">Đang nộp...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function mockTestExam() {
    // Build sections data from server
    const sectionsData = @json($sectionsJson);

    return {
        // Core state
        sections: sectionsData,
        currentSectionIndex: 0,
        timeRemaining: {{ $mockTest->duration_minutes }} * 60,
        timerInterval: null,
        showConfirmModal: false,
        submitting: false,

        // Per-section answers: { sectionIndex: { questionId: answer } }
        sectionAnswers: {},

        // Practice-compatible state (shared across all sections, reset on switch)
        questions: [],
        currentIndex: 0,
        answers: {},
        feedback: {},
        showNavMenu: false,

        // Reading state
        part1Answers: {},
        part2Slots: [],
        part2Pool: [],
        p2DragOverSlot: null,
        p2DraggingPoolIdx: null,
        part3Answers: [],
        part4Answers: [],

        // Listening state
        listeningPart1Answer: null,
        listeningPart2Answers: [],
        listeningPart3Answers: [],
        listeningPart4Answers: [],

        // Writing state
        writingPart1Answers: [],
        writingPart2Answer: '',
        writingPart3Answers: [],
        writingPart4Answer: '',

        init() {
            // Start timer
            this.timerInterval = setInterval(() => {
                if (this.timeRemaining > 0) {
                    this.timeRemaining--;
                } else {
                    this.autoSubmit();
                }
            }, 1000);

            // Load first section
            this.loadSection(0);
        },

        destroy() {
            if (this.timerInterval) clearInterval(this.timerInterval);
        },

        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },

        // --- Section Management ---
        loadSection(sectionIndex) {
            // Save current section answers before switching
            this.saveSectionState();

            this.currentSectionIndex = sectionIndex;
            const section = this.sections[sectionIndex];
            this.questions = section.questions;
            this.currentIndex = 0;

            // Reset state
            this.answers = {};
            this.feedback = {};

            // Restore if previously answered
            if (this.sectionAnswers[sectionIndex]) {
                this.answers = { ...this.sectionAnswers[sectionIndex] };
            }

            this.loadQuestionState();
        },

        saveSectionState() {
            if (this.sections.length === 0) return;
            // Save answers for current section
            this.collectCurrentAnswers();
            this.sectionAnswers[this.currentSectionIndex] = { ...this.answers };
        },

        collectCurrentAnswers() {
            // Auto-collect current input state into answers
            const q = this.currentQuestion;
            if (!q) return;

            if (q.skill === 'reading') {
                switch (q.part) {
                    case 1:
                        if (Object.keys(this.part1Answers[q.id] || {}).length > 0) {
                            this.answers[q.id] = { ...this.part1Answers[q.id] };
                        }
                        break;
                    case 2:
                        if (this.part2Slots.some(s => s !== null)) {
                            this.answers[q.id] = [...this.part2Slots];
                        }
                        break;
                    case 3:
                        if (this.part3Answers.some(a => a !== '' && a !== null)) {
                            this.answers[q.id] = [...this.part3Answers];
                        }
                        break;
                    case 4:
                        if (this.part4Answers.some(a => a !== '' && a !== null)) {
                            this.answers[q.id] = [...this.part4Answers];
                        }
                        break;
                }
            } else if (q.skill === 'listening') {
                switch (q.part) {
                    case 1:
                        if (this.listeningPart1Answer !== null) this.answers[q.id] = this.listeningPart1Answer;
                        break;
                    case 2:
                        if (this.listeningPart2Answers.some(a => a !== '')) this.answers[q.id] = [...this.listeningPart2Answers];
                        break;
                    case 3:
                        if (this.listeningPart3Answers.some(a => a !== '')) this.answers[q.id] = [...this.listeningPart3Answers];
                        break;
                    case 4:
                        if (this.listeningPart4Answers.some(a => a !== null)) this.answers[q.id] = [...this.listeningPart4Answers];
                        break;
                }
            } else if (q.skill === 'writing') {
                switch (q.part) {
                    case 1:
                        if (this.writingPart1Answers.some(a => (a || '').trim())) this.answers[q.id] = [...this.writingPart1Answers];
                        break;
                    case 2:
                        if (this.writingPart2Answer.trim()) this.answers[q.id] = this.writingPart2Answer;
                        break;
                    case 3:
                        if (this.writingPart3Answers.some(a => (a || '').trim())) this.answers[q.id] = [...this.writingPart3Answers];
                        break;
                    case 4:
                        if ((this.writingPart4Answer || '').trim()) this.answers[q.id] = this.writingPart4Answer;
                        break;
                }
            }
        },

        switchSection(index) {
            if (index === this.currentSectionIndex) return;
            this.loadSection(index);
            window.scrollTo(0, 0);
        },

        nextSection() {
            if (this.currentSectionIndex < this.sections.length - 1) {
                this.switchSection(this.currentSectionIndex + 1);
            }
        },

        prevSection() {
            if (this.currentSectionIndex > 0) {
                this.switchSection(this.currentSectionIndex - 1);
            }
        },

        // --- Within-section question navigation ---
        goToQuestion(qi) {
            if (qi === this.currentIndex) return;
            this.collectCurrentAnswers();
            this.currentIndex = qi;
            this.loadQuestionState();
            window.scrollTo(0, 0);
        },

        nextQuestion() {
            if (this.currentIndex < this.questions.length - 1) {
                this.goToQuestion(this.currentIndex + 1);
            }
        },

        prevQuestion() {
            if (this.currentIndex > 0) {
                this.goToQuestion(this.currentIndex - 1);
            }
        },

        sectionAnswered(index) {
            return this.sectionAnswers[index] && Object.keys(this.sectionAnswers[index]).length > 0;
        },

        get answeredCount() {
            return Object.keys(this.sectionAnswers).filter(k => {
                return this.sectionAnswers[k] && Object.keys(this.sectionAnswers[k]).length > 0;
            }).length;
        },

        get currentQuestion() {
            if (!this.questions || this.questions.length === 0) return null;
            return this.questions[this.currentIndex];
        },

        // --- Practice-compatible methods (reused by partials) ---
        loadQuestionState() {
            const q = this.currentQuestion;
            if (!q) return;
            const saved = this.answers[q.id]; // Restore previously saved answer

            if (q.skill === 'reading') {
                this.part1Answers[q.id] = this.part1Answers[q.id] || {};
                this.part3Answers = saved && Array.isArray(saved) && q.part === 3
                    ? [...saved] : new Array(q.metadata.questions?.length || 0).fill('');
                this.part4Answers = saved && Array.isArray(saved) && q.part === 4
                    ? [...saved] : new Array(q.metadata.paragraphs?.length || 0).fill('');

                if (q.part === 2) {
                    const sentences = q.metadata.sentences.slice(1).map((text, idx) => ({
                        text, originalIndex: idx + 1
                    }));
                    this.part2Pool = sentences.sort(() => Math.random() - 0.5);
                    this.part2Slots = new Array(sentences.length).fill(null);
                }
            }

            if (q.skill === 'listening') {
                if (q.part === 1) this.listeningPart1Answer = (saved !== undefined) ? saved : null;
                if (q.part === 2) this.listeningPart2Answers = saved && Array.isArray(saved)
                    ? [...saved] : new Array(q.metadata.items?.length || 0).fill('');
                if (q.part === 3) this.listeningPart3Answers = saved && Array.isArray(saved)
                    ? [...saved] : new Array(q.metadata.statements?.length || 0).fill('');
                if (q.part === 4) this.listeningPart4Answers = saved && Array.isArray(saved)
                    ? [...saved] : new Array(q.metadata.questions?.length || 0).fill(null);
            }

            if (q.skill === 'writing') {
                if (q.part === 1) this.writingPart1Answers = saved && Array.isArray(saved)
                    ? [...saved] : new Array(q.metadata.fields?.length || 0).fill('');
                if (q.part === 2) this.writingPart2Answer = (saved && typeof saved === 'string') ? saved : '';
                if (q.part === 3) this.writingPart3Answers = saved && Array.isArray(saved)
                    ? [...saved] : new Array(q.metadata.questions?.length || 0).fill('');
                if (q.part === 4) this.writingPart4Answer = (saved && typeof saved === 'string') ? saved : '';
            }
        },

        hasAnswered(qId) {
            // In mock test: NEVER lock answers or show feedback.
            // Grading only happens server-side on final submit.
            return false;
        },

        // Reading Part 1
        submitPart1() {
            const qId = this.currentQuestion.id;
            const userAns = this.part1Answers[qId];
            this.answers = { ...this.answers, [qId]: { ...userAns } };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getPart1SelectStyle(pIndex) { return ''; },

        // Reading Part 2
        p2PoolDragStart(event, poolIdx) {
            if (this.hasAnswered(this.currentQuestion.id)) { event.preventDefault(); return; }
            this.p2DraggingPoolIdx = poolIdx;
            event.dataTransfer.effectAllowed = 'move';
        },
        dropToSlot(slotIdx) {
            if (this.hasAnswered(this.currentQuestion.id)) return;
            this.p2DragOverSlot = null;
            if (this.p2DraggingPoolIdx === null) return;
            if (this.part2Slots[slotIdx] !== null) this.part2Pool.push(this.part2Slots[slotIdx]);
            const item = this.part2Pool.splice(this.p2DraggingPoolIdx, 1)[0];
            this.part2Slots[slotIdx] = item;
            this.part2Slots = [...this.part2Slots];
            this.part2Pool = [...this.part2Pool];
            this.p2DraggingPoolIdx = null;
        },
        removeFromSlot(slotIdx) {
            if (this.hasAnswered(this.currentQuestion.id)) return;
            if (this.part2Slots[slotIdx] === null) return;
            this.part2Pool.push(this.part2Slots[slotIdx]);
            this.part2Slots[slotIdx] = null;
            this.part2Slots = [...this.part2Slots];
            this.part2Pool = [...this.part2Pool];
        },
        submitPart2() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.part2Slots] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },

        // Reading Part 3
        selectPart3(qIdx, oIdx) {
            if (this.hasAnswered(this.currentQuestion.id)) return;
            const newAnswers = [...this.part3Answers];
            newAnswers[qIdx] = oIdx;
            this.part3Answers = newAnswers;
        },
        getPart3SelectStyle(qIdx) { return ''; },
        getPart3ContainerClass(qIdx) { return 'border-gray-200'; },
        submitPart3() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.part3Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },

        // Reading Part 4
        submitPart4() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.part4Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getPart4SelectStyle(pIdx) { return ''; },

        // Listening Part 1
        submitListeningPart1() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: this.listeningPart1Answer };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getLP1RadioClass(cIdx) {
            const isSelected = this.listeningPart1Answer === cIdx;
            return isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50';
        },

        // Listening Part 2
        submitListeningPart2() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.listeningPart2Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getLP2SelectStyle(sIdx) { return ''; },
        playAllSpeakers() {
            const items = this.currentQuestion.metadata.items || [];
            items.forEach((_, idx) => {
                const el = document.getElementById('speaker_audio_' + idx);
                if (el) { el.currentTime = 0; el.play(); }
            });
        },

        // Listening Part 3
        submitListeningPart3() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.listeningPart3Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getLP3SelectStyle(sIdx) { return ''; },

        // Listening Part 4
        submitListeningPart4() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.listeningPart4Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null } };
        },
        getLP4RadioClass(qIdx, cIdx) {
            const isSelected = this.listeningPart4Answers[qIdx] === cIdx;
            return isSelected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50';
        },

        // Writing
        submitWritingPart1() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.writingPart1Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
        },
        submitWritingPart2() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: this.writingPart2Answer };
            this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
        },
        submitWritingPart3() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: [...this.writingPart3Answers] };
            this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
        },
        submitWritingPart4() {
            const qId = this.currentQuestion.id;
            this.answers = { ...this.answers, [qId]: this.writingPart4Answer };
            this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
        },

        // Word count helpers
        countWords(text) {
            if (!text || !text.trim()) return 0;
            return text.trim().split(/\s+/).length;
        },
        getWordCountClass(text, limit) {
            const count = this.countWords(text);
            if (!limit) return 'text-gray-400';
            if (count < (limit.min || 0)) return 'text-amber-500';
            if (count > (limit.max || 999)) return 'text-red-500';
            return 'text-green-500';
        },

        // Footer action (not used in mock test — no per-question submit)
        handleFooterAction() {},
        getFooterButtonText() { return ''; },
        next() {},
        prev() {},
        jumpTo(index) {},
        finish() {},
        getNavCircleClass() { return ''; },
        resetPractice() {},
        calculateScore() { return 0; },

        // --- Submit ---
        confirmSubmit() {
            this.saveSectionState();
            this.showConfirmModal = true;
        },

        autoSubmit() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.saveSectionState();
            this.submitTest();
        },

        async submitTest() {
            this.submitting = true;

            // Build answers per section: { sectionIndex: { questionId: answer } }
            const allAnswers = {};
            for (let i = 0; i < this.sections.length; i++) {
                allAnswers[i] = this.sectionAnswers[i] || {};
            }

            try {
                const response = await fetch('{{ route("mock-test.submit", $mockTest) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ answers: allAnswers })
                });

                const data = await response.json();

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Có lỗi xảy ra.');
                    this.submitting = false;
                }
            } catch (error) {
                console.error('Submit error:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
                this.submitting = false;
            }
        }
    };
}
</script>
@endsection
