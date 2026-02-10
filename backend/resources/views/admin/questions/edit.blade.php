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

                            <!-- Quiz Selection (Read-only for Edit to prevent logic break or complexity) -->
                            <!-- Or allow change if careful. Let's allow change but warn it might reset metadata -->
                            <div>
                                <label for="quiz_id" class="block text-sm font-medium text-gray-700 mb-1">Quiz</label>
                                <select 
                                    name="quiz_id" 
                                    id="quiz_id" 
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white" 
                                    required
                                    x-model="selectedQuizId"
                                >
                                    <option value="">Select Quiz</option>
                                    @foreach($quizzes as $quiz)
                                        <option value="{{ $quiz->id }}" data-skill="{{ $quiz->skill }}" data-part="{{ $quiz->part }}">
                                            {{ $quiz->title }} ({{ ucfirst($quiz->skill) }} - Part {{ $quiz->part }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

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
                            
                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image (Optional)</label>
                                @if($question->image_path)
                                    <div class="mb-2">
                                        <img src="{{ Storage::url($question->image_path) }}" alt="Current Image" class="h-20 rounded border border-gray-200">
                                    </div>
                                @endif
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition-colors duration-200">
                                    <div class="space-y-1 text-center">
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                                <span>Upload a new file</span>
                                                <input id="image" name="image" type="file" class="sr-only">
                                            </label>
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

               <!-- Fallback -->
               <div x-show="selectedQuizId && !isReadingPart1 && !isReadingPart2 && !isReadingPart3 && !isReadingPart4" class="p-8 text-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
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

                get questionType() {
                    if (this.isReadingPart1) return 'fill_in_blanks_mc';
                    if (this.isReadingPart2) return 'sentence_ordering';
                    if (this.isReadingPart3) return 'text_question_match';
                    if (this.isReadingPart4) return 'matching_headings';
                    return 'unknown';
                },

                get questionDescription() {
                    if (this.isReadingPart1) return 'Fill in the blanks (Multiple Choice)';
                    if (this.isReadingPart2) return 'Reorder the sentenes to form a paragraph';
                    if (this.isReadingPart3) return 'Match questions to the correct opinion/text';
                    if (this.isReadingPart4) return 'Match each paragraph to the correct heading';
                    return '';
                }
            }));
        });
    </script>
@endsection
