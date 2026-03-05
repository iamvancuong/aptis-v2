@extends('layouts.admin')

@section('title', 'Questions Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center sm:flex-row flex-col gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ ucfirst($currentSkill) }} Questions Management</h1>
        <div class="flex gap-2">
            <!-- @if(in_array($currentSkill, ['reading', 'listening']))
            <button onclick="document.getElementById('import-listening-modal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Import {{ ucfirst($currentSkill) }}
            </button>
            @endif -->
            <a href="{{ route('admin.questions.create', ['skill' => $currentSkill, 'part' => request('part')]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Question
            </a>
        </div>
    </div>

    <!-- Filters -->
    <x-card>
        <form method="GET" action="{{ route(request()->route()->getName()) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Title Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search by Title</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search titles..." class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white">
            </div>

            <!-- Quiz Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quiz</label>
                <x-select name="quiz_id">
                    <option value="">All Quizzes</option>
                    @foreach($quizzes as $quiz)
                        <option value="{{ $quiz->id }}" {{ request('quiz_id') == $quiz->id ? 'selected' : '' }}>
                            {{ $quiz->title }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <!-- Part Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Part</label>
                <x-select name="part">
                    <option value="">All Parts</option>
                    <option value="1" {{ request('part') == '1' ? 'selected' : '' }}>Part 1</option>
                    <option value="2" {{ request('part') == '2' ? 'selected' : '' }}>Part 2</option>
                    <option value="3" {{ request('part') == '3' ? 'selected' : '' }}>Part 3</option>
                    <option value="4" {{ request('part') == '4' ? 'selected' : '' }}>Part 4</option>
                </x-select>
            </div>

            <!-- Submit Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </x-card>

    <!-- Questions Table -->
    <div class="flex gap-2 w-full sm:w-auto mb-2">
        <button id="bulk-delete-btn" style="display: none;" onclick="bulkDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white shadow-sm rounded-md transition-all items-center text-sm font-medium">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            Xoá đã chọn (<span class="count">0</span>)
        </button>
    </div>
    <x-datatable 
        :data="$questions"
        :perPageOptions="[10, 20, 50]"
    >
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 w-10 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Skill</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Part</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại (Type)</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($questions as $question)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" value="{{ $question->id }}" class="bulk-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $question->order == 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $question->order }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $question->quiz->title }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                        @if($question->title)
                            <span class="font-medium">{{ $question->title }}</span>
                        @elseif($question->stem)
                            <span class="text-gray-400 italic text-xs">{{ Str::limit(strip_tags($question->stem), 60) }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($question->sets->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach($question->sets as $set)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $set->title }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 italic">No Set</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $question->skill === 'reading' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $question->skill === 'listening' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $question->skill === 'writing' ? 'bg-green-100 text-green-800' : '' }}
                        ">
                            {{ ucfirst($question->skill) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                        Part {{ $question->part }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ str_replace('_', ' ', ucfirst($question->type)) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                        {{ $question->point }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                        <!-- Edit Button -->
                        <a 
                            href="{{ route('admin.questions.edit', $question) }}" 
                            class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs"
                        >
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Sửa
                        </a>

                        <!-- Delete Button -->
                        <form id="delete-form-{{ $question->id }}" action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                onclick="return confirm('Bạn có chắc muốn xoá câu hỏi này?')"
                                class="inline-flex items-center px-3 py-1 bg-pink-100 text-pink-700 rounded-md hover:bg-pink-200 font-medium text-xs"
                            >
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Xoá
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                        No questions found. <a href="{{ route('admin.questions.create') }}" class="text-indigo-600 hover:underline">Create your first question</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-datatable>
</div>

@if(in_array($currentSkill, ['reading', 'listening']))
<!-- Modal Import Listening -->
<div id="import-listening-modal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[110] flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-md" x-data="importForm()">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">Nhập dữ liệu {{ ucfirst($currentSkill) }}</h3>
            <button onclick="document.getElementById('import-listening-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.questions.import', ['skill' => $currentSkill]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Chọn Quiz <span class="text-red-500">*</span></label>
                <select name="quiz_id" required x-model="selectedQuizId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Chọn Quiz --</option>
                    @foreach($quizzes as $quiz)
                        <option value="{{ $quiz->id }}">{{ $quiz->title }} (Part {{ $quiz->part }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Chọn Set <span class="text-red-500">*</span></label>
                <select name="set_id" required x-model="selectedSetId" :disabled="!selectedQuizId || isLoading" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">-- Chọn Set --</option>
                    <template x-for="set in sets" :key="set.id">
                        <option :value="set.id" x-text="set.title"></option>
                    </template>
                </select>
                <div x-show="isLoading" class="text-xs text-indigo-500 mt-1">Đang tải sets...</div>
                <div x-show="sets.length === 0 && selectedQuizId && !isLoading" class="text-xs text-amber-500 mt-1">Quiz này chưa có Set nào. Tham khảo Part Tương ứng.</div>
            </div>

            <div class="mb-4 mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">File JSON (.json) <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".json" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById('import-listening-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Hủy</button>
                <button type="submit" class="px-4 py-2 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50" :disabled="!selectedQuizId || !selectedSetId">Thực hiện Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('importForm', () => ({
            selectedQuizId: '',
            selectedSetId: '',
            isLoading: false,
            sets: [],

            init() {
                this.$watch('selectedQuizId', (value) => {
                    if (value) {
                        this.fetchSets(value);
                    } else {
                        this.sets = [];
                        this.selectedSetId = '';
                    }
                });
            },

            fetchSets(quizId) {
                this.isLoading = true;
                this.sets = [];
                this.selectedSetId = '';

                fetch(`/admin/quizzes/${quizId}/sets`)
                    .then(response => response.json())
                    .then(data => {
                        this.sets = data.sets || [];
                    })
                    .catch(error => {
                        console.error('Error fetching sets:', error);
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
            }
        }));
    });
</script>
@endif
@endsection
