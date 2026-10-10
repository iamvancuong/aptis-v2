{{-- Phản hồi chung sau khi kiểm tra một câu (trắc nghiệm). Writing/Speaking dùng nhận xét AI riêng. --}}
<template x-if="currentQuestion.skill !== 'writing' && currentQuestion.skill !== 'speaking'">
    <div x-show="hasAnswered(currentQuestion.id)" class="px-5 sm:px-6 py-4 bg-gray-50 border-t border-gray-100">
        <div class="flex items-start gap-3">
            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                  :class="feedback[currentQuestion.id]?.correct ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'">
                <x-ui.icon name="check" class="w-4 h-4" x-show="feedback[currentQuestion.id]?.correct" />
                <x-ui.icon name="x" class="w-4 h-4" x-show="!feedback[currentQuestion.id]?.correct" />
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold pt-1.5" :class="feedback[currentQuestion.id]?.correct ? 'text-emerald-800' : 'text-red-800'"
                   x-text="feedback[currentQuestion.id]?.correct ? 'Chính xác, làm tốt lắm!' : 'Chưa đúng hết — xem lại các ý đánh dấu đỏ ở trên.'"></p>
                <div class="mt-3 bg-white rounded-xl p-4 border border-gray-200" x-show="currentQuestion.metadata.explanation">
                    <p class="text-sm font-semibold text-gray-900 mb-1.5">Giải thích / Transcript</p>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap" x-html="currentQuestion.metadata.explanation"></div>
                </div>
            </div>
        </div>
    </div>
</template>
