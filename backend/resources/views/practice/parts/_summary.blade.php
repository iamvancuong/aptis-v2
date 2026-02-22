{{-- Summary View (Reusable across skills) --}}
<div x-show="step === 'summary'" x-cloak class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-xl overflow-hidden text-center p-10">
        <div class="mb-6">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-blue-100">
                <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900">Practice Completed!</h2>
            <p class="mt-2 text-lg text-gray-500">Here's how you did:</p>
        </div>

        <div class="text-5xl font-bold text-blue-600 mb-8">
            <span x-text="calculateScore()"></span>%
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left max-w-lg mx-auto mb-8">
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <div class="text-sm text-green-600 font-medium">Correct Answers</div>
                <div class="text-2xl font-bold text-green-700" x-text="Object.values(feedback).filter(f => f.correct).length"></div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                <div class="text-sm text-red-600 font-medium">Incorrect Answers</div>
                <div class="text-2xl font-bold text-red-700" x-text="Object.values(feedback).filter(f => !f.correct).length"></div>
            </div>
        </div>

        <div class="space-x-4">
            <a href="{{ route('sets.index', ['skill' => $set->quiz->skill, 'part' => $set->quiz->part]) }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                Back to Sets
            </a>
            <button @click="resetPractice()" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Try Again
            </button>
        </div>
    </div>
</div>
