@extends('layouts.admin')

@section('content')
    <div class="min-h-screen" x-data="questionForm">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Create New Question</h1>
            <p class="text-sm text-gray-500 mt-1">Select a Quiz and Set to get started</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were {{ $errors->count() }} errors with your submission</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.questions.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf

            <!-- Left Column: Settings -->
            <div class="lg:col-span-1 border-r border-gray-200 pr-8">
                <div class="sticky top-8 space-y-6">
                    <!-- Basic Info Card -->
                    <x-card class="bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="space-y-5">
                            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Configuration</h3>

                            <!-- Quiz Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Quiz
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="quiz_id" 
                                        id="quiz_id"
                                        required 
                                        x-model="selectedQuizId"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">-- Chọn Quiz --</option>
                                    @foreach($quizzes as $quiz)
                                        <option value="{{ $quiz->id }}" 
                                                data-skill="{{ $quiz->skill }}" 
                                                data-part="{{ $quiz->part }}">
                                            {{ $quiz->name }} ({{ ucfirst($quiz->skill) }} - Part {{ $quiz->part }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Title (Optional) -->
                            <x-input 
                                name="title"
                                label="Title (Optional)"
                                placeholder="Nhập tiêu đề câu hỏi (nếu có)"
                            />
                            <p class="text-sm text-gray-500 -mt-3 mb-4">Tiêu đề giúp học viên dễ nhận biết câu hỏi</p>

                            <!-- Set Selection -->
                            <div>
                                <label for="set_id" class="block text-sm font-medium text-gray-700 mb-1">Set</label>
                                <select 
                                    name="set_id" 
                                    id="set_id" 
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white disabled:bg-gray-100 disabled:text-gray-400" 
                                    required
                                    x-model="selectedSetId"
                                    :disabled="!selectedQuizId || isLoadingSets"
                                >
                                    <option value="">Select Set</option>
                                    <template x-for="set in sets" :key="set.id">
                                        <option :value="set.id" x-text="set.title"></option>
                                    </template>
                                </select>
                                <div x-show="isLoadingSets" class="text-xs text-indigo-500 mt-1 flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading sets...
                                </div>
                                <div x-show="sets.length === 0 && selectedQuizId && !isLoadingSets" class="text-xs text-amber-500 mt-1">
                                    No sets found for this quiz. Please create a set first.
                                </div>
                            </div>

                             <!-- Hidden Fields for Skill/Part logic (populated via JS) -->
                            <input type="hidden" name="skill" :value="quizMetadata.skill">
                            <input type="hidden" name="part" :value="quizMetadata.part">
                            <input type="hidden" name="type" :value="questionType"> <!-- Based on logic -->

                            <!-- Metadata Info -->
                            <div x-show="selectedQuizId" class="p-3 bg-indigo-50 rounded-lg text-xs text-indigo-700 border border-indigo-100">
                                <p><strong>Skill:</strong> <span x-text="quizMetadata.skill"></span></p>
                                <p><strong>Part:</strong> <span x-text="quizMetadata.part"></span></p>
                                <p class="mt-1 italic" x-text="questionDescription"></p>
                            </div>
                            
                            <!-- Audio Upload (For Listening) -->
                            <div x-show="quizMetadata.skill === 'listening'" x-data="{ audioFileName: '', audioPreviewUrl: '' }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Audio File (Optional)</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition-colors duration-200">
                                    <div class="space-y-1 text-center w-full">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="audio" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Upload audio file</span>
                                                <input 
                                                    id="audio" 
                                                    name="audio" 
                                                    type="file" 
                                                    class="sr-only" 
                                                    accept=".mp3,.wav,.ogg,.m4a"
                                                    @change="audioFileName = $event.target.files[0]?.name || ''; audioPreviewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : '';"
                                                >
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">MP3, WAV, OGG, M4A up to 10MB</p>
                                        
                                        <!-- Audio Preview -->
                                        <div x-show="audioFileName" class="pt-3">
                                            <p class="text-sm font-medium text-gray-700 mb-2" x-text="'📁 ' + audioFileName"></p>
                                            <audio x-show="audioPreviewUrl" :src="audioPreviewUrl" controls class="mx-auto max-w-full"></audio>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <!-- Points -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Points</label>
                                <input type="number" name="point" value="1" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200" required>
                            </div>
                        </div>
                    </x-card>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <x-button type="submit" class="flex-1 justify-center bg-indigo-600 hover:bg-indigo-700 text-white shadow-md hover:shadow-lg transition-all duration-200">
                            Create Question
                        </x-button>
                        <a href="{{ route('admin.questions.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Question Content -->
            <div class="lg:col-span-2 space-y-8">
               
               <!-- Loading State -->
               <div x-show="isLoadingSets" class="text-center py-12">
                    <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Fetching quiz context...</p>
               </div>

               <!-- Forms Container (hidden during loading) -->
               <div x-show="!isLoadingSets" x-cloak>
               
               <!-- PART 1: READING (Fill in Blanks) -->
               <template x-if="isReadingPart1">
                    <div x-data="readingPart1">
                        <x-card title="Part 1: Sentence Completion" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 2: READING (Sentence Ordering) -->
               <template x-if="isReadingPart2">
                    <div x-data="readingPart2">
                        <x-card title="Part 2: Sentence Ordering" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 3: READING (Opinion Matching) -->
               <template x-if="isReadingPart3">
                    <div x-data="readingPart3">
                        <x-card title="Part 3: Opinion Matching" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 4: READING (Heading Matching) -->
               <template x-if="isReadingPart4">
                    <div x-data="readingPart4">
                        <x-card title="Part 4: Heading Matching" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 1: Single MCQ -->
               <template x-if="isListeningPart1">
                    <div x-data="listeningPart1">
                        <x-card title="Listening Part 1: Information Recognition" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 2: Speaker Matching -->
               <template x-if="isListeningPart2">
                    <div x-data="listeningPart2">
                        <x-card title="Listening Part 2: Speaker Matching" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 3: Conversation -->
               <template x-if="isListeningPart3">
                    <div x-data="listeningPart3">
                        <x-card title="Listening Part 3: Conversation Questions" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 4: Monologue -->
               <template x-if="isListeningPart4">
                    <div x-data="listeningPart4">
                        <x-card title="Listening Part 4: Monologue Questions" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 1: Form Filling -->
               <template x-if="isWritingPart1">
                    <div x-data="writingPart1">
                        <x-card title="Writing Part 1: Form Filling" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 2: Email -->
               <template x-if="isWritingPart2">
                    <div x-data="writingPart2">
                        <x-card title="Writing Part 2: Email Writing" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 3: Social Responses -->
               <template x-if="isWritingPart3">
                    <div x-data="writingPart3">
                        <x-card title="Writing Part 3: Social Responses" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 4: Email Writing (Task 1 + Task 2) -->
               <template x-if="isWritingPart4">
                    <div x-data="writingPart4">
                        <x-card title="Writing Part 4 & 5: Email Writing" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- Fallback -->
               <div x-show="selectedQuizId && !isReadingPart1 && !isReadingPart2 && !isReadingPart3 && !isReadingPart4 && !isListeningPart1 && !isListeningPart2 && !isListeningPart3 && !isListeningPart4 && !isWritingPart1 && !isWritingPart2 && !isWritingPart3 && !isWritingPart4" class="p-8 text-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Form Not Available</h3>
                    <p class="mt-1 text-sm text-gray-500">The form for <span class="font-bold" x-text="quizMetadata.skill + ' Part ' + quizMetadata.part"></span> is under development.</p>
               </div>
               
               <div x-show="!selectedQuizId" class="p-12 text-center bg-white rounded-lg shadow-sm">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 mb-4">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Start Creating</h3>
                    <p class="mt-2 text-gray-500 max-w-sm mx-auto">Select a Quiz from the configuration panel to load the appropriate question form.</p>
               </div>

            </div>
        </form>
    </div>

    <!-- SortableJS (Removed as per user request) -->
    
    <!-- Alpine.js Logic -->
    <script src="{{ asset('admin/js/questions/reading-part1.js') }}"></script>
    <script src="{{ asset('admin/js/questions/reading-part2.js') }}"></script>
    <script src="{{ asset('admin/js/questions/reading-part3.js') }}"></script>
    <script src="{{ asset('admin/js/questions/reading-part4.js') }}"></script>
    <script src="{{ asset('admin/js/questions/listening-part1.js') }}"></script>
    <script src="{{ asset('admin/js/questions/listening-part2.js') }}"></script>
    <script src="{{ asset('admin/js/questions/listening-part3.js') }}"></script>
    <script src="{{ asset('admin/js/questions/listening-part4.js') }}"></script>
    <script src="{{ asset('admin/js/questions/writing-part1.js') }}"></script>
    <script src="{{ asset('admin/js/questions/writing-part2.js') }}"></script>
    <script src="{{ asset('admin/js/questions/writing-part3.js') }}"></script>
    <script src="{{ asset('admin/js/questions/writing-part4.js') }}"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('questionForm', () => ({
                selectedQuizId: '',
                selectedSetId: '',
                isLoadingSets: false,
                sets: [],
                quizMetadata: { skill: '', part: '' },

                init() {
                    this.$watch('selectedQuizId', (value) => {
                        if (value) {
                            this.fetchSets(value);
                            this.updateQuizMetadata();
                        } else {
                            this.sets = [];
                            this.selectedSetId = '';
                            this.quizMetadata = { skill: '', part: '' };
                        }
                    });
                },

                updateQuizMetadata() {
                    const select = document.getElementById('quiz_id');
                    const option = select.options[select.selectedIndex];
                    this.quizMetadata.skill = option.dataset.skill;
                    this.quizMetadata.part = parseInt(option.dataset.part);
                },

                fetchSets(quizId) {
                    this.isLoadingSets = true;
                    this.sets = []; // Reset sets
                    this.selectedSetId = ''; // Reset selected set

                    // Use the route generated by Laravel, or a fixed path if needed
                    fetch(`/admin/quizzes/${quizId}/sets`)
                        .then(response => response.json())
                        .then(data => {
                            this.sets = data.sets;
                            // Optionally update quiz metadata from server response which is more reliable
                            if (data.quiz) {
                                this.quizMetadata.skill = data.quiz.skill;
                                this.quizMetadata.part = parseInt(data.quiz.part);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching sets:', error);
                            alert('Failed to load sets. Please try again.');
                        })
                        .finally(() => {
                            this.isLoadingSets = false;
                        });
                },

                get isReadingPart1() {
                    return this.quizMetadata.skill === 'reading' && this.quizMetadata.part === 1;
                },

                get isReadingPart2() {
                    return this.quizMetadata.skill === 'reading' && this.quizMetadata.part === 2;
                },

                get isReadingPart3() {
                    return this.quizMetadata.skill === 'reading' && this.quizMetadata.part === 3;
                },

                get isReadingPart4() {
                    return this.quizMetadata.skill === 'reading' && this.quizMetadata.part === 4;
                },

                get isListeningPart1() {
                    return this.quizMetadata.skill === 'listening' && this.quizMetadata.part === 1;
                },

                get isListeningPart2() {
                    return this.quizMetadata.skill === 'listening' && this.quizMetadata.part === 2;
                },

                get isListeningPart3() {
                    return this.quizMetadata.skill === 'listening' && this.quizMetadata.part === 3;
                },

                get isListeningPart4() {
                    return this.quizMetadata.skill === 'listening' && this.quizMetadata.part === 4;
                },

                get isWritingPart1() {
                    return this.quizMetadata.skill === 'writing' && this.quizMetadata.part === 1;
                },

                get isWritingPart2() {
                    return this.quizMetadata.skill === 'writing' && this.quizMetadata.part === 2;
                },

                get isWritingPart3() {
                    return this.quizMetadata.skill === 'writing' && this.quizMetadata.part === 3;
                },

                get isWritingPart4() {
                    return this.quizMetadata.skill === 'writing' && this.quizMetadata.part === 4;
                },

                get questionType() {
                    if (this.isReadingPart1) return 'fill_in_blanks_mc';
                    if (this.isReadingPart2) return 'sentence_ordering';
                    if (this.isReadingPart3) return 'text_question_match';
                    if (this.isReadingPart4) return 'matching_headings';
                    if (this.isListeningPart1) return 'listening_mcq';
                    if (this.isListeningPart2) return 'listening_matching';
                    if (this.isListeningPart3) return 'listening_conversation';
                    if (this.isListeningPart4) return 'listening_monologue';
                    if (this.isWritingPart1) return 'writing_form_filling';
                    if (this.isWritingPart2) return 'writing_informal_email';
                    if (this.isWritingPart3) return 'writing_social_response';
                    if (this.isWritingPart4) return 'writing_email_combined';
                    return 'unknown';
                },

                get questionDescription() {
                    if (this.isReadingPart1) return 'Fill in the blanks (Multiple Choice)';
                    if (this.isReadingPart2) return 'Reorder the sentenes to form a paragraph';
                    if (this.isReadingPart3) return 'Match questions to the correct opinion/text';
                    if (this.isReadingPart4) return 'Match each paragraph to the correct heading';
                    if (this.isListeningPart1) return 'Single question - audio with 3 choices';
                    if (this.isListeningPart2) return 'Match speakers to opinions (shared audio)';
                    if (this.isListeningPart3) return 'Conversation - multiple questions';
                    if (this.isListeningPart4) return 'Monologue - multiple questions';
                    if (this.isWritingPart1) return 'Fill in a form with personal information';
                    if (this.isWritingPart2) return 'Write an informal/semi-formal email (20-30 words)';
                    if (this.isWritingPart3) return 'Respond to 3 social media prompts (30-40 words each)';
                    if (this.isWritingPart4) return 'Read email → write to friend (~50w) + manager (~120-150w)';
                    return '';
                }
            }));
        });
    </script>
@endsection
