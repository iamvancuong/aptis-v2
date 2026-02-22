@extends('layouts.admin')

@section('content')
    <div class="min-h-screen" x-data="questionForm({{ json_encode($question) }})">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Question</h1>
                <p class="text-sm text-gray-500 mt-1">Update question details and content</p>
            </div>
            <a href="{{ route('admin.questions.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Back to List
            </a>
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

        <form action="{{ route('admin.questions.update', $question) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            @method('PUT')

            <!-- Left Column: Settings -->
            <div class="lg:col-span-1 border-r border-gray-200 pr-8">
                <div class="sticky top-8 space-y-6">
                    <!-- Basic Info Card -->
                    <x-card class="bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="space-y-5">
                            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Configuration</h3>

                            <!-- Quiz Selection (Read-only in edit mode) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Quiz
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="quiz_id" required disabled
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg bg-gray-100 text-gray-700 pointer-events-none">
                                    <option value="{{ $question->quiz_id }}" selected>
                                        {{ $question->quiz->name }} ({{ ucfirst($question->quiz->skill) }} - Part {{ $question->quiz->part }})
                                    </option>
                                </select>
                                <input type="hidden" name="quiz_id" value="{{ $question->quiz_id }}">
                                <p class="text-xs text-gray-500 mt-1">Quiz không thể thay đổi khi chỉnh sửa</p>
                            </div>

                            <!-- Title (Optional) -->
                            <x-input 
                                name="title"
                                label="Title (Optional)"
                                :value="$question->title"
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
                                        <option :value="set.id" x-text="set.title" :selected="set.id == selectedSetId"></option>
                                    </template>
                                </select>
                                <div x-show="isLoadingSets" class="text-xs text-indigo-500 mt-1 flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading sets...
                                </div>
                            </div>

                             <!-- Hidden Fields -->
                            <input type="hidden" name="skill" :value="quizMetadata.skill">
                            <input type="hidden" name="part" :value="quizMetadata.part">
                            <input type="hidden" name="type" :value="questionType"> 

                            <!-- Metadata Info -->
                            <div x-show="selectedQuizId" class="p-3 bg-indigo-50 rounded-lg text-xs text-indigo-700 border border-indigo-100">
                                <p><strong>Skill:</strong> <span x-text="quizMetadata.skill"></span></p>
                                <p><strong>Part:</strong> <span x-text="quizMetadata.part"></span></p>
                                <p class="mt-1 italic" x-text="questionDescription"></p>
                            </div>
                            
                            <!-- Audio Upload (For Listening) -->
                            <div x-show="quizMetadata.skill === 'listening'" x-data="{ audioFileName: '', audioPreviewUrl: '' }">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Audio File (Optional)</label>
                                
                                @if($question->audio_path)
                                    <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-xs font-medium text-gray-600 mb-2">Current Audio:</p>
                                        <p class="text-sm text-gray-700 mb-2 truncate">{{ basename($question->audio_path) }}</p>
                                        <audio src="{{ Storage::url($question->audio_path) }}" controls class="w-full"></audio>
                                    </div>
                                @endif
                                
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition-colors duration-200">
                                    <div class="space-y-1 text-center w-full">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="audio-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                                <span>{{ $question->audio_path ? 'Change audio' : 'Upload audio' }}</span>
                                                <input 
                                                    id="audio-upload" 
                                                    name="audio" 
                                                    type="file" 
                                                    accept="audio/*" 
                                                    class="sr-only"
                                                    @change="audioFileName = $el.files[0]?.name || ''; audioPreviewUrl = $el.files[0] ? URL.createObjectURL($el.files[0]) : ''"
                                                >
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">MP3, WAV, OGG, M4A up to 10MB</p>
                                        
                                        <div x-show="audioFileName" class="mt-3 p-2 bg-indigo-50 rounded border border-indigo-200">
                                            <p class="text-xs font-medium text-indigo-700" x-text="'New: ' + audioFileName"></p>
                                            <audio x-show="audioPreviewUrl" :src="audioPreviewUrl" controls class="w-full mt-2"></audio>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Points -->
                            <!-- Points -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Points</label>
                                <input type="number" name="point" value="{{ $question->point }}" min="0" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200" required>
                            </div>
                        </div>
                    </x-card>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <x-button type="submit" class="flex-1 justify-center bg-indigo-600 hover:bg-indigo-700 text-white shadow-md hover:shadow-lg transition-all duration-200">
                            Update Question
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

               <!-- PART 1: READING (Fill in Blanks) -->
               <template x-if="isReadingPart1">
                    <div x-data="readingPart1(questionMetadata)">
                        <x-card title="Part 1: Sentence Completion (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 2: READING (Sentence Ordering) -->
               <template x-if="isReadingPart2">
                    <div x-data="readingPart2(questionMetadata)">
                        <x-card title="Part 2: Sentence Ordering (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 3: READING (Opinion Matching) -->
               <template x-if="isReadingPart3">
                    <div x-data="readingPart3(questionMetadata)">
                        <x-card title="Part 3: Opinion Matching (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- PART 4: READING (Heading Matching) -->
               <template x-if="isReadingPart4">
                    <div x-data="readingPart4(questionMetadata)">
                        <x-card title="Part 4: Heading Matching (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.reading.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.reading.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 1: Single MCQ -->
               <template x-if="isListeningPart1">
                    <div x-data="listeningPart1(questionMetadata)">
                        <x-card title="Listening Part 1: Information Recognition (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 2: Speaker Matching -->
               <template x-if="isListeningPart2">
                    <div x-data="listeningPart2(questionMetadata)">
                        <x-card title="Listening Part 2: Speaker Matching (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 3: Conversation -->
               <template x-if="isListeningPart3">
                    <div x-data="listeningPart3(questionMetadata)">
                        <x-card title="Listening Part 3: Conversation Questions (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- LISTENING PART 4: Monologue -->
               <template x-if="isListeningPart4">
                    <div x-data="listeningPart4(questionMetadata)">
                        <x-card title="Listening Part 4: Monologue Questions (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.listening.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.listening.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 1: Form Filling -->
               <template x-if="isWritingPart1">
                    <div x-data="writingPart1(questionMetadata)">
                        <x-card title="Writing Part 1: Form Filling (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part1-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part1-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 2: Email -->
               <template x-if="isWritingPart2">
                    <div x-data="writingPart2(questionMetadata)">
                        <x-card title="Writing Part 2: Email Writing (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part2-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part2-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 3: Social Responses -->
               <template x-if="isWritingPart3">
                    <div x-data="writingPart3(questionMetadata)">
                        <x-card title="Writing Part 3: Social Responses (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part3-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part3-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- WRITING PART 4: Email Writing (Task 1 + Task 2) -->
               <template x-if="isWritingPart4">
                    <div x-data="writingPart4(questionMetadata)">
                        <x-card title="Writing Part 4 & 5: Email Writing (Edit)" class="bg-white shadow-sm ring-1 ring-black/5">
                            @include('admin.questions.partials.writing.part4-form')
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                @include('admin.questions.partials.writing.part4-preview')
                            </div>
                        </x-card>
                    </div>
               </template>

               <!-- Fallback -->
               <div x-show="selectedQuizId && !isReadingPart1 && !isReadingPart2 && !isReadingPart3 && !isReadingPart4 && !isListeningPart1 && !isListeningPart2 && !isListeningPart3 && !isListeningPart4 && !isWritingPart1 && !isWritingPart2 && !isWritingPart3 && !isWritingPart4" class="p-8 text-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Form Not Available</h3>
                    <p class="mt-1 text-sm text-gray-500">The edit form for <span class="font-bold" x-text="quizMetadata.skill + ' Part ' + quizMetadata.part"></span> is not ready yet.</p>
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
            Alpine.data('questionForm', (question) => ({
                selectedQuizId: question.quiz_id,
                selectedSetId: question.sets && question.sets.length > 0 ? question.sets[0].id : '',
                isLoadingSets: false,
                sets: @json($sets), 
                quizMetadata: { skill: question.skill, part: question.part },
                questionMetadata: question.metadata || null,

                init() {
                    this.$nextTick(() => {
                        this.updateQuizMetadata();
                        if (question.sets && question.sets.length > 0) {
                            let setId = question.sets[0].id;
                            this.selectedSetId = setId;
                        } else {
                            console.warn('No sets found in question data');
                        }
                    });

                    this.$watch('selectedQuizId', (value) => {
                        if (value && value != question.quiz_id) {
                            this.fetchSets(value);
                            this.updateQuizMetadata();
                            this.questionMetadata = null; 
                        } else if (value == question.quiz_id) {
                             this.questionMetadata = question.metadata;
                             if (question.sets && question.sets.length > 0) {
                                this.selectedSetId = question.sets[0].id;
                                this.sets = @json($sets);
                             }
                        }
                    });
                },

                updateQuizMetadata() {
                    const select = document.getElementById('quiz_id');
                    if (select && select.selectedIndex >= 0) {
                        const option = select.options[select.selectedIndex];
                        if (option.dataset.skill) {
                            this.quizMetadata.skill = option.dataset.skill;
                            this.quizMetadata.part = parseInt(option.dataset.part);
                        }
                    }
                },

                fetchSets(quizId) {
                    this.isLoadingSets = true;
                    this.sets = []; 
                    this.selectedSetId = ''; 

                    fetch(`/admin/quizzes/${quizId}/sets`)
                        .then(response => response.json())
                        .then(data => {
                            this.sets = data.sets;
                            if (data.quiz) {
                                this.quizMetadata.skill = data.quiz.skill;
                                this.quizMetadata.part = parseInt(data.quiz.part);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching sets:', error);
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
                    if (this.isListeningPart2) return 'listening_speaker_match';
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
                    if (this.isListeningPart1) return 'Listen and answer 1 multiple choice question';
                    if (this.isListeningPart2) return 'Match speakers to opinions/statements';
                    if (this.isListeningPart3) return 'Listen to conversation and answer statements';
                    if (this.isListeningPart4) return 'Listen to monologue and answer 2 questions';
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
