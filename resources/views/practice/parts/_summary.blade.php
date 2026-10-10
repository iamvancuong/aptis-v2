{{-- Màn tổng kết sau khi làm hết bộ đề (dùng chung mọi kỹ năng). --}}
@php
    $summaryBackRoute = $set->quiz->skill === 'grammar'
        ? route('grammar.index')
        : route('sets.index', ['skill' => $set->quiz->skill, 'part' => $set->quiz->part]);
@endphp
<div x-show="step === 'summary'" x-cloak class="max-w-xl mx-auto">
    <x-ui.panel class="text-center">
        <div class="py-4">
            <x-ui.icon-badge icon="check" tone="bg-blue-50 text-blue-600" size="lg" class="mx-auto" />
            <h2 class="mt-4 text-2xl font-bold text-gray-900">Hoàn thành bộ đề</h2>
            <p class="mt-1 text-gray-500">Kết quả lần luyện này của bạn</p>

            <p class="mt-6 text-5xl font-bold text-blue-600"><span x-text="calculateScore()"></span>%</p>

            <div class="grid grid-cols-2 gap-3 max-w-sm mx-auto mt-6 text-left">
                <x-ui.stat label="Câu đúng" tone="green" bind="Object.values(feedback).filter(f => f.correct).length" />
                <x-ui.stat label="Câu sai" tone="red" bind="Object.values(feedback).filter(f => !f.correct).length" />
            </div>

            <div class="flex flex-col sm:flex-row justify-center gap-3 mt-8">
                <x-button variant="secondary" :href="$summaryBackRoute">Về danh sách đề</x-button>
                <x-button x-on:click="resetPractice()">Làm lại</x-button>
                <template x-if="redirectUrl">
                    <a :href="redirectUrl" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700">Xem chi tiết bài làm</a>
                </template>
            </div>
        </div>
    </x-ui.panel>
</div>
