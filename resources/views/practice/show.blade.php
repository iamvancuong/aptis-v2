@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col" x-data="practiceSession({{ $set->questions }})">
    {{-- Premium Header --}}
    <header class="bg-white/90 backdrop-blur-md sticky top-0 z-30 border-b border-gray-100 shadow-sm transition-all duration-300">
        <div class="h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 w-full">
            {{-- Progress indicator line --}}
            <div class="h-full bg-white/40 transition-all duration-500 ease-out flex justify-end" 
                 :style="`width: ${100 - ((currentIndex + 1) / questions.length) * 100}%`"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
                {{-- Back Button --}}
                @php
                    $backRoute = $set->quiz->skill === 'grammar' 
                        ? route('grammar.index') 
                        : route('sets.index', ['skill' => $set->quiz->skill, 'part' => $set->quiz->part]);
                @endphp
                <a href="{{ $backRoute }}" 
                   class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-all shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
                
                {{-- Title Area --}}
                <div class="flex flex-col flex-1 min-w-0 text-center sm:text-left md:flex-row md:items-baseline md:justify-center md:gap-3">
                    <h1 class="text-base sm:text-lg lg:text-xl font-bold text-gray-900 truncate tracking-tight">
                        <span class="capitalize" x-text="currentQuestion?.skill || '{{ ucfirst($set->quiz->skill) }}'"></span> 
                        Part <span x-text="currentQuestion?.part || '{{ $set->quiz->part }}'"></span>
                        <span class="hidden md:inline text-gray-300 mx-1">|</span>
                    </h1>
                    <p class="text-sm font-medium text-indigo-600 truncate mt-0.5 md:mt-0">
                        <span x-text="currentQuestion?.title || '{{ $set->name }}'"></span>
                    </p>
                </div>

                {{-- Counter & Progress --}}
                <div class="flex flex-col items-end shrink-0">
                    <div class="flex items-center gap-1.5 bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-full font-semibold text-sm">
                        <span x-text="currentIndex + 1"></span>
                        <span class="text-indigo-300 opacity-60">/</span>
                        <span x-text="questions.length"></span>
                    </div>
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
                        @include('practice.parts.grammar-part1')
                        @include('practice.parts.grammar-part2')
                        @include('practice.parts.speaking-part1')
                        @include('practice.parts.speaking-part2')
                        @include('practice.parts.speaking-part3')
                        @include('practice.parts.speaking-part4')
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
                        <div x-show="showNavMenu" @click.outside="showNavMenu = false" x-transition 
                             class="absolute bottom-full left-0 mb-2 bg-white rounded-lg shadow-xl border border-gray-200 min-w-[280px] sm:min-w-[340px] max-w-[90vw] max-h-[60vh] flex flex-col z-50 overflow-hidden">
                            
                            {{-- Sticky Header with Search --}}
                            <div class="flex items-center justify-between p-3 border-b border-gray-100 bg-gray-50 shrink-0">
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wide">Danh sách câu hỏi</p>
                            </div>
                            <div class="p-2 border-b border-gray-100 bg-white shrink-0">
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    <input type="text" x-model="searchQuery" placeholder="Tìm kiếm nội dung đề bài..." 
                                        class="w-full text-sm pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Scrollable List view --}}
                            <div class="flex-1 overflow-y-auto bg-gray-50 p-2 space-y-2">
                                <template x-if="filteredQuestions().length === 0">
                                    <div class="text-sm text-center text-gray-500 py-4">Không tìm thấy câu hỏi phù hợp</div>
                                </template>
                                <template x-for="q in filteredQuestions()" :key="q.originalIndex">
                                    <button @click="jumpTo(q.originalIndex); showNavMenu = false; searchQuery = ''"
                                        class="w-full text-left bg-white p-3 rounded-lg border shadow-sm hover:border-indigo-300 transition-colors flex items-start gap-3"
                                        :class="currentIndex === q.originalIndex ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'">
                                        
                                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold border-2 shrink-0"
                                            :class="getNavCircleClass(q.originalIndex, q.id)">
                                            <span x-text="q.originalIndex + 1"></span>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-0.5">Part <span x-text="q.part"></span></p>
                                            <p class="text-sm text-gray-800 line-clamp-2" x-text="q.title || q.stem || 'Câu hỏi #' + (q.originalIndex + 1)"></p>
                                        </div>
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
            searchQuery: '', // Search text
            redirectUrl: null,

            // Reading state
            part1Answers: {},
            part2Slots: [],
            part2Pool: [],
            p2DragOverSlot: null,
            p2DraggingPoolIdx: null,
            p2SelectedPoolIdx: null,
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

            // Grammar state
            grammarAnswers: {},
            vocabAnswers: {},

            // Speaking state
            speakingState: 'idle', // idle, prep, recording, saving
            speakingTimer: 0,
            speakingInterval: null,
            speakingSubIndex: 0,
            speakingAnswers: {}, // Will store combined blobs or arrays of blobs per question
            mediaRecorder: null,
            audioChunks: [],
            audioPlayerUrl: null,
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

                // Cleanup on page exit/reload
                window.addEventListener('beforeunload', () => this.cleanup());
            },

            destroy() {
                this.cleanup();
            },

            cleanup() {
                // 1. Stop all intervals
                if (this.speakingInterval) clearInterval(this.speakingInterval);

                // 2. Stop TTS (SpeechSynthesis)
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }

                // 3. Stop MediaRecorder and release Microphone
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    try { this.mediaRecorder.stop(); } catch(e) {}
                }
                if (this.mediaRecorder && this.mediaRecorder.stream) {
                    this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                }

                // 4. Force stop all audio elements
                document.querySelectorAll('audio').forEach(audio => {
                    try {
                        audio.pause();
                        audio.src = '';
                        audio.load(); // This stops the buffering/loading
                    } catch(e) {}
                });
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

                    if (q.part === 2) {
                        // Always reset pool and slots when loading a new question to prevent stale data
                        const sentences = q.metadata.sentences.slice(1).map((text, idx) => ({
                            text, originalIndex: idx + 1
                        }));
                        this.part2Pool = [...sentences].sort(() => Math.random() - 0.5);
                        this.part2Slots = new Array(sentences.length).fill(null);
                        
                        // Clear any previous feedback for this question
                        if (this.feedback[q.id]) {
                            delete this.feedback[q.id];
                        }
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

                if (q.skill === 'grammar') {
                    if (q.part === 2) this.vocabAnswers[q.id] = this.vocabAnswers[q.id] || {};
                }

                if (q.skill === 'speaking') {
                    this.speakingState = 'idle';
                    this.speakingTimer = 0;
                    this.speakingSubIndex = 0;
                    clearInterval(this.speakingInterval);
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    }
                    this.audioChunks = [];
                    this.speakingAnswers[q.id] = this.speakingAnswers[q.id] || [];
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
                    if (userAns[idx] != correctIdx) isAllCorrect = false;
                });

                this.feedback = { ...this.feedback, [qId]: { correct: isAllCorrect } };
            },

            getPart1SelectStyle(pIndex) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';

                const userAns = this.part1Answers[qId][pIndex];
                const correctAns = this.currentQuestion.metadata.correct_answers[pIndex];

                if (userAns == correctAns) {

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
                this.p2SelectedPoolIdx = null;
            },

            selectP2Pool(poolIdx) {
                if (this.hasAnswered(this.currentQuestion.id)) return;
                
                // If already selected, deselect it
                if (this.p2SelectedPoolIdx === poolIdx) {
                    this.p2SelectedPoolIdx = null;
                } else {
                    this.p2SelectedPoolIdx = poolIdx;
                }
            },

            selectP2Slot(slotIdx) {
                if (this.hasAnswered(this.currentQuestion.id)) return;

                // Case 1: Slot has an item -> Move back to pool
                if (this.part2Slots[slotIdx] !== null) {
                    this.removeFromSlot(slotIdx);
                    return;
                }

                // Case 2: Slot is empty and a pool item is selected -> Place it
                if (this.p2SelectedPoolIdx !== null) {
                    const item = this.part2Pool.splice(this.p2SelectedPoolIdx, 1)[0];
                    this.part2Slots[slotIdx] = item;
                    
                    this.part2Slots = [...this.part2Slots];
                    this.part2Pool = [...this.part2Pool];
                    this.p2SelectedPoolIdx = null;
                }
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

                if (userAns == correctAns) {
                    return 'background-color: #dcfce7 !important; border-color: #16a34a !important; color: #166534 !important; border-width: 2px !important;';
                } else {
                    return 'background-color: #fee2e2 !important; border-color: #dc2626 !important; color: #991b1b !important; border-width: 2px !important;';
                }
            },

            getPart3ContainerClass(qIdx) {
                if (!this.feedback[this.currentQuestion.id]) return 'border-gray-200';
                const correctIdx = this.currentQuestion.metadata.correct_answers[qIdx];
                return this.part3Answers[qIdx] == correctIdx ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50';
            },

            submitPart3() {
                if (this.part3Answers.some(a => a === '' || a === null)) {
                    alert('Please answer all questions before checking.');
                    return;
                }
                const qId = this.currentQuestion.id;
                const correctAnswers = this.currentQuestion.metadata.correct_answers;
                const correctCount = this.part3Answers.filter((ans, idx) => ans == correctAnswers[idx]).length;

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
                const correctCount = this.part4Answers.filter((ans, idx) => ans == correctAnswers[idx]).length;

                this.answers = { ...this.answers, [qId]: [...this.part4Answers] };
                this.feedback = { ...this.feedback, [qId]: { correct: correctCount === correctAnswers.length } };
            },

            getPart4SelectStyle(pIdx) {
                const qId = this.currentQuestion.id;
                if (!this.hasAnswered(qId)) return '';

                const userAns = this.part4Answers[pIdx];
                const correctAns = this.currentQuestion.metadata.correct_answers[pIdx];

                if (userAns == correctAns) {
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
                const audios = [];
                
                // Collect and reset all audio elements
                items.forEach((_, idx) => {
                    const el = document.getElementById('speaker_audio_' + idx);
                    if (el) {
                        el.pause();
                        el.currentTime = 0;
                        el.onended = null;
                        audios.push(el);
                    }
                });

                if (audios.length === 0) return;

                let current = 0;
                const playNext = () => {
                    if (current >= audios.length) return;
                    const activeAudio = audios[current];
                    activeAudio.onended = () => {
                        current++;
                        playNext();
                    };
                    activeAudio.play().catch(e => console.error("Error playing audio seq", e));
                };
                
                playNext();
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

            // --- Grammar Methods ---
            submitGrammarPart1() {
                const qId = this.currentQuestion.id;
                const userAns = this.grammarAnswers[qId];
                if (!userAns) { alert('Vui lòng chọn một đáp án.'); return; }
                const isCorrect = userAns == this.currentQuestion.metadata.correct_option;
                this.answers = { ...this.answers, [qId]: userAns };
                this.feedback = { ...this.feedback, [qId]: { correct: isCorrect } };
            },

            setVocabAnswer(qId, pairId, word) {
                if (this.hasAnswered(qId)) return;
                this.vocabAnswers[qId] = this.vocabAnswers[qId] || {};
                this.vocabAnswers[qId][pairId] = word;
            },

            submitGrammarPart2() {
                const qId = this.currentQuestion.id;
                const userAns = this.vocabAnswers[qId] || {};
                const correctAns = this.currentQuestion.metadata.correct_answers || {};
                const totalPairs = Object.keys(correctAns).length;
                
                if (Object.keys(userAns).length < totalPairs || Object.values(userAns).some(v => v === '')) {
                    alert('Vui lòng chọn từ cho tất cả các ô trống.');
                    return;
                }
                
                // Flexible duplicate answer check (to match backend grading rules if any, though frontend just warns or auto-evaluates)
                const isCorrect = Object.entries(correctAns).every(([pid, word]) => userAns[pid] === word);
                
                this.answers = { ...this.answers, [qId]: userAns };
                this.feedback = { ...this.feedback, [qId]: { correct: isCorrect } };
            },

            // --- Speaking Methods ---
            formatTime(seconds) {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            },

            async playTTS(text, onComplete) {
                if (!('speechSynthesis' in window)) {
                    // Fallback if not supported
                    onComplete();
                    return;
                }
                
                // cancel any ongoing speech
                window.speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                utterance.rate = 1.0; // Normal speed
                
                utterance.onend = () => {
                    onComplete();
                };
                
                utterance.onerror = (e) => {
                    console.error("TTS Error", e);
                    onComplete();
                };
                
                window.speechSynthesis.speak(utterance);
            },

            async setupRecording() {
                console.log('--- Speaking: setupRecording START ---');
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.mediaRecorder = new MediaRecorder(stream);
                    console.log('--- Speaking: MediaRecorder created ---', this.mediaRecorder.state);
                    
                    this.mediaRecorder.ondataavailable = (event) => {
                        console.log('--- Speaking: dataavailable ---', event.data.size);
                        if (event.data.size > 0) this.audioChunks.push(event.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        console.log('--- Speaking: onstop fired ---', this.audioChunks.length, "chunks");
                        const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        const qId = this.currentQuestion.id;
                        this.speakingAnswers[qId] = this.speakingAnswers[qId] || [];
                        this.speakingAnswers[qId].push(blob);
                        console.log('--- Speaking: Blob saved to speakingAnswers ---', qId, blob.size);
                    };
                } catch (err) {
                    console.error('--- Speaking: Microphone error ---', err);
                    alert('Không thể truy cập Microphone. Vui lòng cấp quyền.');
                }
            },

            async startSpeakingPart() {
                if (!this.mediaRecorder) {
                    await this.setupRecording();
                    if (!this.mediaRecorder) return; // Permission denied
                }
                
                this.speakingSubIndex = 0;
                this.audioChunks = [];
                this.runSpeakingSubQuestion();
            },

            runSpeakingSubQuestion() {
                const q = this.currentQuestion;
                if (!q) return;

                const meta = q.metadata;
                // If Part 4, total prep and total answer. Otherwise, per question.
                const prepTime = meta.prep_time || 0;
                const totalQs = meta.questions ? meta.questions.length : 1;
                
                // Determine text to read
                let introText = "";
                if (this.speakingSubIndex === 0) {
                    if (q.part === 1) introText = "Personal Information. Please answer the 3 questions below. You will have 30 seconds for each question. ";
                    else if (q.part === 2) introText = "Describe a Picture. Please describe the picture and answer the 2 questions below. You will have 45 seconds for each response. ";
                    else if (q.part === 3) introText = "Compare Two Pictures. Please compare the two pictures and answer the 3 questions below. You will have 45 seconds for each response. ";
                    else if (q.part === 4) introText = "Extended Discussion. Please look at the picture and answer the 3 questions. You have 1 minute to think and 2 minutes to talk. ";
                }

                let textToRead = "";
                if (q.part === 4) {
                    textToRead = introText + meta.questions.join(". ");
                } else {
                    textToRead = introText + meta.questions[this.speakingSubIndex];
                }
                
                if (q.part === 4) {
                    // Part 4 runs once for all 3 questions
                    if (this.speakingSubIndex > 0) return; 
                    
                    this.speakingState = 'playing_audio';
                    this.playTTS(textToRead, () => {
                        this.startTimerState('prep', 61, () => { // +1s offset
                            this.playBeepAndRecord(121, () => { // +1s offset
                                this.finishSpeakingQuestion();
                            });
                        });
                    });
                } else {
                    // Part 1, 2, 3 run sequentially per question
                    this.speakingState = 'playing_audio';
                    this.playTTS(textToRead, () => {
                        const prepTime = 0; // standard speaking parts 1-3 usually have no individual prep unless specified
                        const recordSeconds = q.part === 1 ? 31 : 46; // +1s offset
                        
                        this.playBeepAndRecord(recordSeconds, () => {
                            this.speakingSubIndex++;
                            if (this.speakingSubIndex < totalQs) {
                                this.runSpeakingSubQuestion();
                            } else {
                                this.finishSpeakingQuestion();
                            }
                        });
                    });
                }
            },

            startTimerState(stateName, seconds, onComplete) {
                if (seconds <= 0) {
                    onComplete();
                    return;
                }
                this.speakingState = stateName;
                this.speakingTimer = seconds;
                
                clearInterval(this.speakingInterval);
                this.speakingInterval = setInterval(() => {
                    this.speakingTimer--;
                    if (this.speakingTimer <= 0) {
                        clearInterval(this.speakingInterval);
                        onComplete();
                    }
                }, 1000);
            },

            playBeepAndRecord(recordSeconds, onComplete) {
                // Web Audio API Beep
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const obj = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    obj.type = 'sine';
                    obj.frequency.value = 800;
                    gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    obj.connect(gain);
                    gain.connect(audioCtx.destination);
                    obj.start();
                    setTimeout(() => obj.stop(), 200);
                } catch (e) {
                    console.warn("Web Audio API beep blocked or unsupported");
                }
                
                // Wait for beep to finish (~500ms usually), then record
                setTimeout(() => {
                    this.audioChunks = [];
                    this.speakingState = 'recording';
                    this.speakingTimer = recordSeconds;
                    console.log('--- Speaking: Recording timer started ---', recordSeconds, 's');
                    
                    try {
                        if (this.mediaRecorder.state === 'inactive') {
                            this.mediaRecorder.start();
                            console.log('--- Speaking: mediaRecorder.start() called ---');
                        }
                    } catch(e) { console.error('--- Speaking: mediaRecorder.start() FAILED ---', e); }

                    clearInterval(this.speakingInterval);
                    this.speakingInterval = setInterval(() => {
                        this.speakingTimer--;
                        if (this.speakingTimer <= 0) {
                            clearInterval(this.speakingInterval);
                            console.log('--- Speaking: Recording timer end ---');
                            try {
                                if (this.mediaRecorder.state !== 'inactive') {
                                    this.mediaRecorder.stop();
                                    console.log('--- Speaking: mediaRecorder.stop() called ---');
                                }
                            } catch(e) { console.error('--- Speaking: mediaRecorder.stop() FAILED ---', e); }
                            
                            // Give mediaRecorder.onstop a moment to fire and push chunks
                            setTimeout(() => {
                                console.log('--- Speaking: Calling onComplete ---');
                                onComplete();
                            }, 500); // Increased to 500ms
                        }
                    }, 1000);
                }, 600);
            },

            finishSpeakingQuestion() {
                this.speakingState = 'saving';
                const qId = this.currentQuestion.id;
                
                // Marking as answered for navigation only if blobs exist
                const blobs = this.speakingAnswers[qId] || [];
                if (blobs.length > 0) {
                    this.answers = { ...this.answers, [qId]: 'recorded' };
                    this.feedback = { ...this.feedback, [qId]: { correct: null, pending: true } };
                } else {
                    // Not recorded, leave it to footer action logic to show alert if needed
                    // (Actually in finishSpeakingQuestion it would mean they completed but maybe with errors or short-circuit)
                }
                
                setTimeout(() => {
                    this.speakingState = 'idle';
                    // Auto next or finish
                    if (this.currentIndex < this.questions.length - 1) {
                        this.next();
                    } else {
                        this.finish();
                    }
                }, 1000);
            },

            // --- Word Count Helpers ---
            countWords(text) {
                if (!text || !text.trim()) return 0;
                return text.trim().split(/\s+/).filter(w => w.length > 0).length;
            },

            getWordCountClass(text, limit) {
                const count = this.countWords(text);
                if (!limit) return 'text-gray-400';
                if (count < (limit.min || 0)) return 'text-amber-500';
                if (count > (limit.max || 999)) return 'text-red-500';
                return 'text-green-500';
            },

            enforceWordLimit(text, max) {
                if (!max) return text;
                const words = text.split(/\s+/).filter(w => w.length > 0);
                if (words.length > max) {
                    // Try to preserve trailing spaces if they don't add a new word
                    const isTrailingSpace = /\s$/.test(text);
                    const truncated = words.slice(0, max).join(' ');
                    return isTrailingSpace ? truncated + ' ' : truncated;
                }
                return text;
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
                    } else if (q.skill === 'grammar') {
                        switch (q.part) {
                            case 1: this.submitGrammarPart1(); break;
                            case 2: this.submitGrammarPart2(); break;
                        }
                    } else if (q.skill === 'speaking') {
                        // Speaking doesn't submit instantly on footer click if not answered.
                        // It forces user to press the big Start Recording button in the UI.
                        if (this.speakingState === 'idle') {
                            alert('Vui lòng nhấn Start Recording để thu âm trước khi chuyển tiếp.');
                            return;
                        } else {
                            alert('Đang trong quá trình thu âm, vui lòng đợi.');
                            return;
                        }
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
                    if (q?.skill === 'speaking') return 'Bỏ qua (Chưa thu âm)';
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

            filteredQuestions() {
                const query = this.searchQuery.toLowerCase().trim();
                return this.questions
                    .map((q, index) => ({ ...q, originalIndex: index }))
                    .filter(q => {
                        if (!query) return true;
                        
                        // Check if query matches question number directly
                        if (!isNaN(query) && parseInt(query) === q.originalIndex + 1) return true;

                        // Flexible multi-word search (Title primary, fallback to stem)
                        const searchTarget = ((q.title || '') + ' ' + (q.stem || '')).toLowerCase();
                        const searchWords = query.split(/\s+/);
                        return searchWords.every(word => searchTarget.includes(word));
                    });
            },

            finish() {
                this.step = 'summary';
                window.scrollTo(0, 0);
                this.submitAttempt();
            },

            async submitAttempt() {
                this.isSaving = true;
                try {
                    // For Speaking, we might have File blobs to send via FormData
                    const hasSpeakingBlobs = Object.keys(this.speakingAnswers).some(k => this.speakingAnswers[k].length > 0);
                    
                    if (hasSpeakingBlobs) {
                        // Send as FormData
                        const formData = new FormData();
                        formData.append('answers', JSON.stringify(this.answers));
                        formData.append('duration', parseInt(0));
                        if (this.attemptId) formData.append('attempt_id', this.attemptId);
                        
                        // Append audio files
                        Object.keys(this.speakingAnswers).forEach(qId => {
                            const blobs = this.speakingAnswers[qId];
                            blobs.forEach((blob, idx) => {
                                formData.append(`speaking_audio[${qId}][${idx}]`, blob, `q${qId}_p${idx}.webm`);
                            });
                        });
                        
                        const response = await fetch(`{{ route('practice.store', $set->id) }}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: formData
                        });
                        if (!response.ok) throw new Error('Failed to save attempt with audio');
                        const result = await response.json();
                        this.attemptId = result.attempt_id;
                        this.answerIds = result.answer_ids || {};
                        this.redirectUrl = result.redirect;
                    } else {
                        // Standard JSON check
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
                        this.redirectUrl = result.redirect;
                    }
                } catch (error) {
                    console.error("Submit error:", error);
                    alert("Có lỗi xảy ra khi nộp bài. Vui lòng thử lại.");
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
                this.redirectUrl = null;
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
                // Grammar
                this.grammarAnswers = {};
                this.vocabAnswers = {};
                // Speaking
                this.speakingAnswers = {};
                clearInterval(this.speakingInterval);
                this.speakingTimer = 0;
                this.speakingState = 'idle';
                this.audioChunks = [];
                
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
