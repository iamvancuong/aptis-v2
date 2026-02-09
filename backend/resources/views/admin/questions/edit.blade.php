@extends('layouts.admin')

@section('title', 'Create Reading Question - Part 1')

@section('content')
<div class="space-y-6" x-data="questionForm()">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Create Reading Question (Part 1)</h1>
        <a href="{{ route('admin.questions.index') }}" class="text-gray-600 hover:text-gray-900">
            Back to List
        </a>
    </div>

    <!-- Main Card -->
    <x-card>
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button 
                    @click="tab = 'edit'"
                    :class="{'border-indigo-500 text-indigo-600': tab === 'edit', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'edit'}"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                    Edit Content
                </button>
                <button 
                    @click="updatePreview(); tab = 'preview'"
                    :class="{'border-indigo-500 text-indigo-600': tab === 'preview', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'preview'}"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                    Preview Mode
                </button>
            </nav>
        </div>

        <form action="{{ route('admin.questions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- Hidden Fields for Fixed Values -->
            <input type="hidden" name="skill" value="reading">
            <input type="hidden" name="part" value="1">
            <input type="hidden" name="type" value="fill_in_blanks_mc">
            <input type="hidden" name="point" value="5">

            <!-- EDIT TAB -->
            <div x-show="tab === 'edit'" class="space-y-6">
                
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Quiz</label>
                        <select name="quiz_id" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white" required>
                            @foreach($quizzes as $quiz)
                                <option value="{{ $quiz->id }}">{{ $quiz->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                        <input type="number" name="order" value="0" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200" required>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6"></div>

                <!-- Part 1 Specific Content -->
                <div class="space-y-8">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Enter 5 short paragraphs. Use <strong>[BLANK1]</strong> to <strong>[BLANK5]</strong> to indicate where the missing words should be.<br>
                                    Then provide 3 choices for each blank and select the correct answer.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 5 Questions Loop -->
                    <template x-for="(item, index) in items" :key="index">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 relative transition-all duration-200 hover:shadow-md">
                            <div class="absolute top-2 right-2 text-xs font-bold text-gray-400" x-text="'#' + (index + 1)"></div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="'Paragraph ' + (index + 1)"></label>
                                <textarea 
                                    :name="'metadata[paragraphs][' + index + ']'" 
                                    x-model="item.paragraph"
                                    rows="2" 
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-mono"
                                    :placeholder="'Example: When you are at the train [BLANK' + (index + 1) + '] go to the main gate.'"
                                    required
                                ></textarea>
                                <!-- Hidden blank key -->
                                <input type="hidden" :name="'metadata[blank_keys][' + index + ']'" :value="'BLANK' + (index + 1)">
                            </div>

                        <label class="block text-xs font-medium text-gray-500 mb-1">Choices (Select the correct answer)</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Choices -->
                                <template x-for="(choice, cIndex) in item.choices" :key="cIndex">
                                    <div class="relative">
                                        <div class="flex items-center">
                                            <input 
                                                type="text" 
                                                :name="'metadata[choices][' + index + '][' + cIndex + ']'"
                                                x-model="item.choices[cIndex]"
                                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                                                :class="{'bg-green-50 border-green-500 focus:border-green-500 focus:ring-green-500': item.correctIndex === cIndex && item.choices[cIndex] !== ''}"
                                                placeholder="Choice text"
                                                required
                                            >
                                            <div class="flex items-center h-full border-t border-r border-b border-gray-300 rounded-r-lg bg-gray-50 px-2">
                                                <input 
                                                    type="radio" 
                                                    :name="'correct_index_' + index"
                                                    :value="cIndex"
                                                    x-model.number="item.correctIndex"
                                                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 cursor-pointer"
                                                    title="Mark as correct answer"
                                                    required
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <!-- Hidden field to send correct answer VALUE to backend -->
                            <input type="hidden" :name="'metadata[correct_answers][' + index + ']'" :value="item.correctIndex !== null ? item.choices[item.correctIndex] : ''">
                            <div x-show="!item.paragraph.includes('[BLANK' + (index + 1) + ']')" class="mt-2 text-xs text-amber-600 flex items-center">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Warning: Paragraph is missing the <strong>[BLANK<span x-text="index + 1"></span>]</strong> placeholder.
                            </div>
                        </div>
                    </template>

                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-6">
                    <x-button>
                        Create Question
                    </x-button>
                </div>
            </div>

            <!-- PREVIEW TAB -->
            <div x-show="tab === 'preview'" class="space-y-8 max-w-3xl mx-auto">
                <div class="bg-white p-8 rounded-xl border border-gray-200 shadow-lg">
                    <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                        <h3 class="text-xl font-bold text-gray-800">Reading Part 1 Preview</h3>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">Student View</span>
                    </div>
                    
                    <div class="space-y-8">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-5 bg-white rounded-lg border border-gray-100 shadow-sm transition-all hover:shadow-md">
                                <!-- Paragraph with Highlighted Blank -->
                                <div class="mb-4 text-gray-800 text-lg leading-relaxed font-serif">
                                    <span class="font-bold text-blue-500 mr-2 select-none" x-text="(index + 1) + '.'"></span>
                                    <span x-html="renderParagraph(item.paragraph, index)"></span>
                                </div>

                                <!-- Choices -->
                                <div class="ml-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <template x-for="(choice, cIndex) in item.choices" :key="cIndex">
                                        <div 
                                            class="flex items-center space-x-2 p-2 rounded border transition-colors duration-200"
                                            :class="isCorrect(item, cIndex) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-transparent'"
                                        >
                                            <div 
                                                class="w-5 h-5 rounded-full border flex items-center justify-center flex-shrink-0"
                                                :class="isCorrect(item, cIndex) ? 'border-green-500 bg-white' : 'border-gray-300 bg-white'"
                                            >
                                                <div x-show="isCorrect(item, cIndex)" class="w-3 h-3 rounded-full bg-green-500"></div>
                                            </div>
                                            <span 
                                                class="text-sm font-medium truncate"
                                                :class="isCorrect(item, cIndex) ? 'text-green-800' : 'text-gray-600'"
                                                x-text="choice || '(Empty)'"
                                                :title="choice"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="item.correctIndex === null" class="mt-2 ml-8 text-xs text-red-500 italic">
                                    * No correct answer selected
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="tab = 'edit'" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back to Edit
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </x-card>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('questionForm', () => ({
        tab: 'edit',
        items: [
            { paragraph: '', choices: ['', '', ''], correctIndex: null },
            { paragraph: '', choices: ['', '', ''], correctIndex: null },
            { paragraph: '', choices: ['', '', ''], correctIndex: null },
            { paragraph: '', choices: ['', '', ''], correctIndex: null },
            { paragraph: '', choices: ['', '', ''], correctIndex: null }
        ],

        updatePreview() {
            // Logic handled by reactivity
        },

        renderParagraph(text, index) {
            if (!text) return '<span class="text-gray-400 italic">Start typing paragraph...</span>';
            const blankKey = '[BLANK' + (index + 1) + ']';
            
            if (text.includes(blankKey)) {
                return text.replace(
                    blankKey, 
                    `<span class="inline-flex items-center justify-center min-w-[60px] h-8 px-3 mx-1 text-sm font-bold text-blue-600 bg-blue-50 border-b-2 border-blue-400 rounded-t whitespace-nowrap select-none ring-2 ring-transparent transition-all">
                        ${blankKey}
                    </span>`
                );
            }
            return text;
        },

        isCorrect(item, choiceIndex) {
            return item.correctIndex === choiceIndex && item.choices[choiceIndex] !== '';
        }
    }));
});
</script>
@endsection
