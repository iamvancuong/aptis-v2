@extends('layouts.app')

@section('title', 'Ôn tập từ vựng')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('vocab.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Sổ tay từ vựng
        </a>
        <h1 class="text-lg font-bold text-gray-900">Ôn tập</h1>
    </div>

    @if($cards->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-gray-300 py-16 px-6 text-center">
            <div class="text-4xl mb-3">{{ $totalItems === 0 ? '📒' : '🎉' }}</div>
            <h2 class="font-bold text-gray-900 mb-1">
                {{ $totalItems === 0 ? 'Chưa có từ nào để ôn' : 'Hôm nay bạn ôn xong rồi!' }}
            </h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                @if($totalItems === 0)
                    Hãy bôi đen từ chưa hiểu trong bài đọc, bấm <strong>Tra từ</strong> rồi <strong>Lưu từ</strong>.
                    Từ đã lưu sẽ xuất hiện ở đây theo lịch ôn tập.
                @else
                    Quay lại vào ngày mai để ôn những từ tới hạn tiếp theo. Ôn đúng nhịp nhớ lâu hơn ôn dồn.
                @endif
            </p>
            <a href="{{ route('vocab.index') }}" class="inline-block mt-5 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                Về sổ tay
            </a>
        </div>
    @else
        <div x-data="vocabReview(@js($cards->map(fn ($c) => [
                'id' => $c->id,
                'term' => $c->term,
                'phonetic' => $c->phonetic,
                'part_of_speech' => $c->part_of_speech,
                'meaning' => $c->meaning,
                'example' => $c->example,
                'context' => $c->context_sentence,
            ])->values()), '{{ url('/tu-vung') }}', '{{ csrf_token() }}')">

            {{-- Tiến độ --}}
            <div class="mb-4" x-show="!finished">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                    <span>Thẻ <span class="font-semibold text-gray-900" x-text="index + 1"></span> / <span x-text="cards.length"></span></span>
                    <span x-show="{{ $remainingAfter }} > 0">Còn {{ $remainingAfter }} từ nữa sau phiên này</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 transition-all duration-300"
                         :style="`width: ${(index / cards.length) * 100}%`"></div>
                </div>
            </div>

            {{-- Thẻ --}}
            <div x-show="!finished" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-10 text-center min-h-[220px] flex flex-col items-center justify-center gap-3">
                    <p class="text-3xl font-black text-gray-900 break-words" x-text="current?.term"></p>

                    <p class="text-sm text-gray-400" x-show="current?.phonetic" x-text="current?.phonetic"></p>

                    {{-- Mặt sau --}}
                    <template x-if="revealed">
                        <div class="w-full space-y-3 pt-4 mt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-400 font-medium" x-show="current?.part_of_speech" x-text="current?.part_of_speech"></p>
                            <p class="text-xl font-bold text-indigo-700 leading-snug" x-text="current?.meaning"></p>
                            <p class="text-sm text-gray-600 italic leading-relaxed" x-show="current?.example" x-text="current?.example"></p>
                        </div>
                    </template>
                </div>

                {{-- Câu gốc: gợi ý ngữ cảnh, xem trước khi lật vẫn không lộ nghĩa
                     tiếng Việt nên không phá giá trị của lần nhớ lại. --}}
                <div class="px-6 pb-5" x-show="current?.context">
                    <details class="group">
                        <summary class="text-xs text-gray-400 cursor-pointer hover:text-indigo-600 list-none select-none">
                            Xem câu gốc trong bài
                        </summary>
                        <p class="mt-2 text-xs text-gray-600 bg-gray-50 border-l-2 border-gray-200 pl-3 py-2 leading-relaxed"
                           x-text="current?.context"></p>
                    </details>
                </div>

                {{-- Hành động --}}
                <div class="border-t border-gray-100 p-4">
                    <button x-show="!revealed" @click="revealed = true" type="button"
                            class="w-full py-3.5 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">
                        Xem nghĩa
                    </button>

                    <div x-show="revealed" class="grid grid-cols-3 gap-2">
                        <button @click="grade('again')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-red-50 text-red-700 font-semibold text-sm border border-red-100 hover:bg-red-100 disabled:opacity-50 transition">
                            Quên
                            <span class="block text-[10px] font-normal text-red-400 mt-0.5">ôn lại mai</span>
                        </button>
                        <button @click="grade('hard')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-amber-50 text-amber-700 font-semibold text-sm border border-amber-100 hover:bg-amber-100 disabled:opacity-50 transition">
                            Mơ hồ
                            <span class="block text-[10px] font-normal text-amber-500 mt-0.5">giữ nhịp cũ</span>
                        </button>
                        <button @click="grade('good')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-emerald-50 text-emerald-700 font-semibold text-sm border border-emerald-100 hover:bg-emerald-100 disabled:opacity-50 transition">
                            Nhớ
                            <span class="block text-[10px] font-normal text-emerald-500 mt-0.5">giãn cách ra</span>
                        </button>
                    </div>

                    <p x-show="error" x-cloak class="mt-3 text-xs text-red-600 text-center" x-text="error"></p>
                </div>
            </div>

            {{-- Kết thúc phiên --}}
            <div x-show="finished" x-cloak class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-12 text-center">
                <div class="text-4xl mb-3">🎯</div>
                <h2 class="font-bold text-gray-900 text-lg mb-1">Xong phiên ôn tập</h2>
                <p class="text-sm text-gray-500 mb-6">
                    Bạn vừa ôn <span class="font-semibold text-gray-900" x-text="cards.length"></span> từ ·
                    nhớ được <span class="font-semibold text-emerald-600" x-text="goodCount"></span> từ.
                </p>

                <div class="flex items-center justify-center gap-2">
                    @if($remainingAfter > 0)
                        <a href="{{ route('vocab.review') }}"
                           class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                            Ôn tiếp {{ $remainingAfter }} từ
                        </a>
                    @endif
                    <a href="{{ route('vocab.index') }}"
                       class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                        Về sổ tay
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<style>[x-cloak] { display: none !important; }</style>

<script>
    function vocabReview(cards, baseUrl, csrf) {
        return {
            cards: cards,
            baseUrl: baseUrl,
            csrf: csrf,

            index: 0,
            revealed: false,
            saving: false,
            finished: false,
            goodCount: 0,
            error: null,

            get current() {
                return this.cards[this.index] ?? null;
            },

            async grade(result) {
                if (this.saving || !this.current) return;

                this.saving = true;
                this.error = null;

                try {
                    const response = await fetch(`${this.baseUrl}/${this.current.id}/ket-qua`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ result }),
                    });

                    if (!response.ok) {
                        // Không sang thẻ kế tiếp khi lưu hỏng: đi tiếp thì lượt ôn
                        // vừa rồi mất trắng mà học viên không hề biết.
                        this.error = 'Chưa lưu được kết quả. Kiểm tra mạng rồi bấm lại nhé.';
                        return;
                    }

                    if (result === 'good') this.goodCount++;
                    this.next();
                } catch (e) {
                    this.error = 'Mất kết nối. Kiểm tra mạng rồi bấm lại nhé.';
                } finally {
                    this.saving = false;
                }
            },

            next() {
                this.revealed = false;

                if (this.index + 1 >= this.cards.length) {
                    this.finished = true;
                    return;
                }

                this.index++;
            },
        };
    }
</script>
@endsection
