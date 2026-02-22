@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col" x-data="practiceSession({{ $set->questions }})">
    {{-- Header --}}
    <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('sets.index', ['skill' => $set->quiz->skill, 'part' => $set->quiz->part]) }}" class="text-gray-500 hover:text-gray-800 flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
                <h1 class="text-lg font-bold text-gray-800">
                    Reading Part <span x-text="currentQuestion?.part"></span>: <span x-text="currentQuestion?.title || 'Multiple Choice'"></span> - {{ $set->name }}
                </h1>
                <div class="text-sm text-gray-600">
                    <span x-text="currentIndex + 1"></span>/<span x-text="questions.length"></span>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 pb-32">
        
        {{-- Practice Step --}}
        <div x-show="step === 'practice'" class="mx-auto">
            <template x-if="currentQuestion">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    {{-- Question Header --}}
                    <div class="p-6 border-b border-gray-100 bg-blue-50">
                        <div class="max-w-3xl mx-auto">
                            <div class="text-base md:text-lg font-medium" x-text="currentQuestion.stem"></div>
                        </div>
                    </div>

                    {{-- Question Content --}}
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

                    {{-- Feedback Footer --}}
                    @include('practice.parts._feedback')
                </div>
            </template>
        </div>

        {{-- Summary --}}
        @include('practice.parts._summary')

    </main>

    {{-- Footer Navigation --}}
    <footer x-show="step === 'practice'" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-3">
                {{-- Left: Menu + Clock + Notes --}}
                <div class="flex items-center gap-2">
                    {{-- Hamburger Menu (Question Nav) --}}
                    <div class="relative">
                        <button @click="showNavMenu = !showNavMenu" class="p-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        {{-- Dropdown Nav Panel --}}
                        <div x-show="showNavMenu" @click.outside="showNavMenu = false" x-transition class="absolute bottom-full left-0 mb-2 bg-white rounded-lg shadow-xl border border-gray-200 p-3 min-w-[200px] z-50">
                            <p class="text-xs text-gray-500 uppercase font-bold mb-2 tracking-wide">Questions</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(q, index) in questions" :key="q.id">
                                    <button @click="jumpTo(index); showNavMenu = false"
                                        class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-medium transition-all border-2"
                                        :class="getNavCircleClass(index, q.id)">
                                        <span x-text="index + 1"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Clock --}}
                    <button class="p-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>

                    {{-- Notes / Translate --}}
                    <span class="text-sm text-gray-500 hidden sm:inline">Ghi chú / Dịch</span>
                </div>

                {{-- Right: Unified Action Button --}}
                <div class="flex items-center gap-3">
                    <button @click="handleFooterAction()"
                        class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-semibold rounded-lg shadow-md transition-colors flex items-center gap-2">
                        <span x-text="getFooterButtonText()"></span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
    function practiceSession(questions) {
        return {
            step: 'practice',
            questions: questions,
            isFullTest: false,
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
            writingPart4Answers: [],

            // State
            attemptId: null,

            // AI State
            aiFeedback: {},
            isAiLoading: {},
            isSaving: false,
            aiError: {},
            aiUsageStatus: {},
            answerIds: {},

            getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            },

            // --- Lifecycle ---
            init() {
                this.loadQuestionState();
                this.loadAiUsageStatus();
                this.$watch('currentIndex', () => this.loadQuestionState());
            },

            get currentQuestion() {
                return this.questions[this.currentIndex];
            },

            loadQuestionState() {
                const q = this.currentQuestion;
                if (!q) return;

                if (q.skill === 'reading') {
                    this.part1Answers[q.id] = this.part1Answers[q.id] || {};
                    this.part3Answers = new Array(q.metadata.questions?.length || 0).fill('');
                    this.part4Answers = new Array(q.metadata.paragraphs?.length || 0).fill('');

                    if (q.part === 2 && !this.part2Pool.length && !this.part2Slots.some(s => s !== null)) {
                        const sentences = q.metadata.sentences.slice(1).map((text, idx) => ({
                            text, originalIndex: idx + 1
                        }));
                        this.part2Pool = sentences.sort(() => Math.random() - 0.5);
                        this.part2Slots = new Array(sentences.length).fill(null);
                    }
                }

                if (q.skill === 'listening') {
                    if (q.part === 1) this.listeningPart1Answer = null;
                    if (q.part === 2) this.listeningPart2Answers = new Array(q.metadata.items?.length || 0).fill('');
                    if (q.part === 3) this.listeningPart3Answers = new Array(q.metadata.statements?.length || 0).fill('');
                    if (q.part === 4) this.listeningPart4Answers = new Array(q.metadata.questions?.length || 0).fill(null);
                }

                if (q.skill === 'writing') {
                    if (q.part === 1) this.writingPart1Answers = new Array(q.metadata.fields?.length || 0).fill('');
                    if (q.part === 2) this.writingPart2Answer = '';
                    if (q.part === 3) this.writingPart3Answers = new Array(q.metadata.questions?.length || 0).fill('');
                    if (q.part === 4) this.writingPart4Answers = new Array(2).fill('');
                }
            },

            hasAnswered(qId) {
                return this.answers.hasOwnProperty(qId);
            },

            // --- Part 1: Gap Fill ---
            submitPart1() {
                const qId = this.currentQuestion.id;
                const userAns = this.part1Answers[qId];
                const totalQuestions = this.currentQuestion.metadata.paragraphs.length;
                
                if (Object.keys(userAns).length < totalQuestions) {
                    alert("Please select an option for all questions.");
                    return;
                }

                const correctAns = this.currentQuestion.metadata.correct_answers;

                this.answers = { ...this.answers, [qId]: { ...userAns } };
                
                let isAllCorrect = true;
                correctAns.forEach((correctIdx, idx) => {
                    if (userAns[idx] !== correctIdx) isAllCorrect = false;
                });

                this.feedback = { ...this.feedback, [qId]: { correct: isAllCorrect } };
            },

            getPart1SelectStyle(pIndex) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';

                const userAns = this.part1Answers[qId][pIndex];
                const correctAns = this.currentQuestion.metadata.correct_answers[pIndex];

                if (userAns === correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;'; 
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            // --- Part 2: Ordering (Pool-to-Slot) ---
            p2PoolDragStart(event, poolIdx) {
                if (this.hasAnswered(this.currentQuestion.id)) { event.preventDefault(); return; }
                this.p2DraggingPoolIdx = poolIdx;
                event.dataTransfer.effectAllowed = 'move';
            },

            dropToSlot(slotIdx) {
                if (this.hasAnswered(this.currentQuestion.id)) return;
                this.p2DragOverSlot = null;
                if (this.p2DraggingPoolIdx === null) return;

                if (this.part2Slots[slotIdx] !== null) {
                    this.part2Pool.push(this.part2Slots[slotIdx]);
                }

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
                
                let isCorrect = true;
                this.part2Slots.forEach((item, idx) => {
                    if (!item || item.originalIndex !== idx + 1) isCorrect = false;
                });

                this.answers = { ...this.answers, [qId]: [...this.part2Slots] };
                this.feedback = { ...this.feedback, [qId]: { correct: isCorrect } };
                this.part2Slots = [...this.part2Slots];
            },

            // --- Part 3: Matching (Dropdown) ---
            selectPart3(qIdx, oIdx) {
                if (this.hasAnswered(this.currentQuestion.id)) return;
                const newAnswers = [...this.part3Answers];
                newAnswers[qIdx] = oIdx;
                this.part3Answers = newAnswers;
            },

            getPart3SelectStyle(qIdx) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';

                const userAns = this.part3Answers[qIdx];
                const correctAns = this.currentQuestion.metadata.correct_answers[qIdx];

                if (userAns === correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;';
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            getPart3ContainerClass(qIdx) {
                if (!this.feedback[this.currentQuestion.id]) return 'border-gray-200';
                const correctIdx = this.currentQuestion.metadata.correct_answers[qIdx];
                return this.part3Answers[qIdx] === correctIdx ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50';
            },

            submitPart3() {
                if (this.part3Answers.some(a => a === '' || a === null)) {
                    alert('Please answer all questions before checking.');
                    return;
                }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.part3Answers.filter((ans, idx) => ans === correctAnswers[idx]).length;

                this.answers = { ...this.answers, [qId]: [...this.part3Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            // --- Part 4: Headings ---
            submitPart4() {
                if (this.part4Answers.some(a => a === '' || a === null)) {
                    alert('Please select a heading for all paragraphs.');
                    return;
                }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.part4Answers.filter((ans, idx) => ans === correctAnswers[idx]).length;

                this.answers = { ...this.answers, [qId]: [...this.part4Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            getPart4SelectStyle(pIdx) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';

                const userAns = this.part4Answers[pIdx];
                const correctAns = this.currentQuestion.metadata.correct_answers[pIdx];

                if (userAns === correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;';
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            // --- Listening Part 1: Short Audio MCQ ---
            submitListeningPart1() {
                if (this.listeningPart1Answer === null) { alert('Please select an answer.'); return; }
                const qId = this.currentQuestion.id;
                const isCorrect = this.listeningPart1Answer === parseInt(this.currentQuestion.metadata.correct_answer);
                this.answers = { ...this.answers, [qId]: this.listeningPart1Answer };
                this.feedback = { ...this.feedback, [qId]: { correct: isCorrect } };
            },

            getLP1RadioClass(cIdx) {
                const qId = this.currentQuestion.id;
                const isSelected = this.listeningPart1Answer === cIdx;
                if (!this.hasAnswered(qId)) {
                    return isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50';
                }
                const correctIdx = parseInt(this.currentQuestion.metadata.correct_answer);
                if (cIdx === correctIdx) return 'border-green-500 bg-green-50';
                if (isSelected && cIdx !== correctIdx) return 'border-red-500 bg-red-50';
                return 'border-gray-200 bg-gray-50 opacity-50';
            },

            // --- Listening Part 2: Conversation (Speaker Matching) ---
            submitListeningPart2() {
                if (this.listeningPart2Answers.some(a => a === '' || a === null)) { alert('Please select an opinion for all speakers.'); return; }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.listeningPart2Answers.filter((ans, idx) => ans == correctAnswers[idx]).length;
                this.answers = { ...this.answers, [qId]: [...this.listeningPart2Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            getLP2SelectStyle(sIdx) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';
                const userAns = this.listeningPart2Answers[sIdx];
                const correctAns = this.currentQuestion.metadata.correct_answers[sIdx];
                if (userAns == correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;';
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            playAllSpeakers() {
                const items = this.currentQuestion.metadata.items || [];
                items.forEach((_, idx) => {
                    const el = document.getElementById('speaker_audio_' + idx);
                    if (el) { el.currentTime = 0; el.play(); }
                });
            },

            // --- Listening Part 3: Monologue (Shared Dropdown) ---
            submitListeningPart3() {
                if (this.listeningPart3Answers.some(a => a === '' || a === null)) { alert('Please answer all statements.'); return; }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.listeningPart3Answers.filter((ans, idx) => ans == correctAnswers[idx]).length;
                this.answers = { ...this.answers, [qId]: [...this.listeningPart3Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            getLP3SelectStyle(sIdx) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';
                const userAns = this.listeningPart3Answers[sIdx];
                const correctAns = this.currentQuestion.metadata.correct_answers[sIdx];
                if (userAns == correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;';
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            // --- Listening Part 4: Complex Audio (2 MCQ) ---
            submitListeningPart4() {
                if (this.listeningPart4Answers.some(a => a === null)) { alert('Please answer all questions.'); return; }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.listeningPart4Answers.filter((ans, idx) => ans == correctAnswers[idx]).length;
                this.answers = { ...this.answers, [qId]: [...this.listeningPart4Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            getLP4RadioClass(qIdx, cIdx) {
                const qId = this.currentQuestion.id;
                const isSelected = this.listeningPart4Answers[qIdx] === cIdx;
                if (!this.hasAnswered(qId)) {
                    return isSelected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50';
                }
                const correctIdx = parseInt(this.currentQuestion.metadata.correct_answers[qIdx]);
                if (cIdx === correctIdx) return 'border-green-500 bg-green-50';
                if (isSelected && cIdx !== correctIdx) return 'border-red-500 bg-red-50';
                return 'border-gray-200 bg-gray-50 opacity-50';
            },

            // --- Writing Submit Methods ---
            submitWritingPart1() {
                const qId = this.currentQuestion.id;
                if (this.writingPart1Answers.every(a => !a.trim())) { alert('Vui lòng điền ít nhất một trường.'); return; }
                this.answers = { ...this.answers, [qId]: [...this.writingPart1Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
            },

            submitWritingPart2() {
                const qId = this.currentQuestion.id;
                if (!this.writingPart2Answer.trim()) { alert('Vui lòng viết bài trước khi nộp.'); return; }
                this.answers = { ...this.answers, [qId]: this.writingPart2Answer };
                this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
            },

            submitWritingPart3() {
                const qId = this.currentQuestion.id;
                if (this.writingPart3Answers.every(a => !(a || '').trim())) { alert('Vui lòng viết ít nhất một phản hồi.'); return; }
                this.answers = { ...this.answers, [qId]: [...this.writingPart3Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
            },

            submitWritingPart4() {
                const qId = this.currentQuestion.id;
                if (this.writingPart4Answers.every(a => !(a || '').trim())) { alert('Vui lòng hoàn thành cả hai nhiệm vụ.'); return; }
                this.answers = { ...this.answers, [qId]: [...this.writingPart4Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
            },

            // --- Word Count Helpers ---
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

            // --- Unified Footer Action ---
            async handleFooterAction() {
                const q = this.currentQuestion;
                const qId = q.id;

                // Step 1: If not answered yet, submit/check
                if (!this.hasAnswered(qId)) {
                    if (q.skill === 'reading') {
                        switch (q.part) {
                            case 1: this.submitPart1(); break;
                            case 2: this.submitPart2(); break;
                            case 3: this.submitPart3(); break;
                            case 4: this.submitPart4(); break;
                        }
                    } else if (q.skill === 'listening') {
                        switch (q.part) {
                            case 1: this.submitListeningPart1(); break;
                            case 2: this.submitListeningPart2(); break;
                            case 3: this.submitListeningPart3(); break;
                            case 4: this.submitListeningPart4(); break;
                        }
                    } else if (q.skill === 'writing') {
                        switch (q.part) {
                            case 1: this.submitWritingPart1(); break;
                            case 2: this.submitWritingPart2(); break;
                            case 3: this.submitWritingPart3(); break;
                            case 4: this.submitWritingPart4(); break;
                        }
                        // For Writing: auto-save immediately to enable AI feedback
                        await this.submitAttempt();
                    }

                    return; // Stop here — show feedback first
                }

                // Step 2: Already answered → next or finish
                if (this.currentIndex < this.questions.length - 1) {
                    this.next();
                } else {
                    this.finish();
                }
            },

            getFooterButtonText() {
                const q = this.currentQuestion;
                const qId = q?.id;
                if (!this.hasAnswered(qId)) {
                    return q?.skill === 'writing' ? 'Nộp bài' : 'Kiểm tra';
                }
                if (this.currentIndex < this.questions.length - 1) return 'Tiếp theo';
                return 'Hoàn thành';
            },

            // --- Navigation ---
            next() {
                if (this.currentIndex < this.questions.length - 1) {
                    this.currentIndex++;
                } else {
                    this.finish();
                }
            },

            prev() {
                if (this.currentIndex > 0) this.currentIndex--;
            },

            jumpTo(index) {
                this.currentIndex = index;
            },

            finish() {
                this.step = 'summary';
                window.scrollTo(0, 0);
                this.submitAttempt();
            },

            async submitAttempt() {
                this.isSaving = true;
                try {
                    const response = await fetch(`{{ route('practice.store', $set->id) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        },
                        body: JSON.stringify({
                            answers: this.answers,
                            duration: 0,
                            attempt_id: this.attemptId
                        })
                    });
                    if (!response.ok) throw new Error('Failed to save attempt');
                    const result = await response.json();
                    this.attemptId = result.attempt_id;
                    this.answerIds = result.answer_ids || {};
                } finally {
                    this.isSaving = false;
                }
            },

            async loadAiUsageStatus() {
                try {
                    const response = await fetch(`{{ route('ai.usage-status') }}`);
                    if (response.ok) {
                        this.aiUsageStatus = await response.json();
                    }
                } catch (error) {
                    console.error('Error loading AI usage status:', error);
                }
            },

            async getAiFeedback() {
                const qId = this.currentQuestion.id;
                const ansId = this.answerIds[qId];

                if (!ansId) {
                    alert('Bạn cần nộp bài trước khi nhận xét bằng AI.');
                    return;
                }

                this.isAiLoading[qId] = true;
                this.aiError[qId] = null;

                try {
                    const response = await fetch(`/ai/grade-writing/${ansId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        }
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        this.aiError[qId] = result.message || 'Lỗi khi gọi AI';
                        return;
                    }

                    this.aiFeedback[qId] = result.review;
                    await this.loadAiUsageStatus();
                } catch (error) {
                    console.error('Error fetching AI feedback:', error);
                    this.aiError[qId] = 'Đã có lỗi xảy ra khi kết nối với AI. Vui lòng thử lại sau.';
                } finally {
                    this.isAiLoading[qId] = false;
                }
            },

            resetPractice() {
                this.answers = {};
                this.feedback = {};
                this.attemptId = null;
                // Reading
                this.part1Answers = {};
                this.part2Slots = [];
                this.part2Pool = [];
                this.part3Answers = [];
                this.part4Answers = [];
                // Listening
                this.listeningPart1Answer = null;
                this.listeningPart2Answers = [];
                this.listeningPart3Answers = [];
                this.listeningPart4Answers = [];
                // Writing
                this.writingPart1Answers = [];
                this.writingPart2Answer = '';
                this.writingPart3Answers = [];
                this.writingPart4Answers = [];
                this.currentIndex = 0;
                this.step = 'practice';
                this.loadQuestionState();
                window.scrollTo(0, 0);
            },

            calculateScore() {
                let correct = Object.values(this.feedback).filter(f => f.correct).length;
                let total = this.questions.length;
                return total === 0 ? 0 : Math.round((correct / total) * 100);
            },

            getNavCircleClass(index, qId) {
                const isCurrent = this.currentIndex === index;
                const answered = this.hasAnswered(qId);
                const fb = this.feedback[qId];

                let base = "";
                if (isCurrent) base += " ring-2 ring-offset-2 ring-blue-500 transform scale-110";

                if (!answered) return base + " bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200";
                if (fb && fb.pending) return base + " bg-amber-400 text-white border-amber-500";
                if (fb && fb.correct) return base + " bg-green-500 text-white border-green-600";
                if (fb && fb.correct === false) return base + " bg-red-500 text-white border-red-600";
                // Self-check (graded but null correct/incorrect)
                return base + " bg-indigo-500 text-white border-indigo-600";
            }
        };
    }
</script>
@endsection
