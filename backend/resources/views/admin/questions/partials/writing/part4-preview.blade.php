<div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <h4 class="text-sm font-semibold text-gray-700 mb-4">Preview — Giống giao diện thi thật</h4>

    {{-- Email Context --}}
    <div class="space-y-3 mb-6" x-show="context || emailBody">
        <p class="text-sm font-bold text-gray-800" x-text="context"></p>

        <div class="p-4 bg-white rounded border border-gray-200 space-y-2">
            <p class="text-sm text-gray-700" x-text="emailGreeting"></p>
            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="emailBody"></p>
            <p class="text-sm text-gray-700 mt-2" x-text="emailSignOff"></p>
        </div>
    </div>

    {{-- Task 1 Preview --}}
    <div class="mb-5" x-show="task1Instruction">
        <div class="p-3 bg-green-50 rounded border border-green-200 mb-2">
            <p class="text-sm font-bold text-gray-800" x-text="task1Instruction"></p>
        </div>
        <div class="p-3 bg-white border border-gray-200 rounded min-h-[60px]">
            <span class="text-gray-300 text-sm italic">Student writes informal email here...</span>
        </div>
        <div class="flex gap-4 text-xs text-gray-500 mt-1">
            <span>Words: <strong x-text="task1WordLimitMin + '-' + task1WordLimitMax"></strong></span>
        </div>
    </div>

    {{-- Task 2 Preview --}}
    <div x-show="task2Instruction">
        <div class="p-3 bg-orange-50 rounded border border-orange-200 mb-2">
            <p class="text-sm font-bold text-gray-800" x-text="task2Instruction"></p>
        </div>
        <div class="p-3 bg-white border border-gray-200 rounded min-h-[100px]">
            <span class="text-gray-300 text-sm italic">Student writes formal email here...</span>
        </div>
        <div class="flex gap-4 text-xs text-gray-500 mt-1">
            <span>Words: <strong x-text="task2WordLimitMin + '-' + task2WordLimitMax"></strong></span>
        </div>
    </div>

    <div x-show="!context && !emailBody" class="text-center text-gray-400 italic text-sm py-4">
        Start typing to see preview...
    </div>
</div>
