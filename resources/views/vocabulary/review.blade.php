@extends('layouts.app')

@section('title', 'Ôn tập từ vựng')

@section('content')
@php
    $scopeParams = array_filter(['folder' => $filters['folder'] ?? null, 'type' => $filters['type'] ?? null]);
    $speakerIcon = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M11 5L6 9H3v6h3l5 4V5z" /></svg>';
@endphp
<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('vocab.index', $scopeParams) }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Sổ tay từ vựng
        </a>
        <h1 class="text-lg font-bold text-gray-900">
            Ôn tập @if($scopeLabel)<span class="text-gray-400 font-medium">· {{ $scopeLabel }}</span>@endif
        </h1>
    </div>

    @if($cards->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-gray-300 py-16 px-6 text-center">
            <div class="text-4xl mb-3">{{ $totalItems === 0 ? '📒' : '🎉' }}</div>
            <h2 class="font-bold text-gray-900 mb-1">
                {{ $totalItems === 0 ? 'Chưa có từ nào để ôn' : 'Chưa có từ nào tới hạn ôn' }}
            </h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                @if($totalItems === 0)
                    Hãy bôi đen từ chưa hiểu trong bài đọc, bấm <strong>Tra từ</strong> rồi <strong>Lưu từ</strong>.
                    Từ đã lưu sẽ xuất hiện ở đây theo lịch ôn tập.
                @else
                    Từ đang học sẽ quay lại sau vài phút, từ đã thuộc sẽ quay lại sau vài ngày. Ôn đúng nhịp nhớ lâu hơn ôn dồn.
                @endif
            </p>
            @if($streak > 0)
                <p class="mt-3 text-sm font-semibold text-orange-600">🔥 Chuỗi {{ $streak }} ngày liên tiếp</p>
            @endif
            <a href="{{ route('vocab.index', $scopeParams) }}" class="inline-block mt-5 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                Về sổ tay
            </a>
        </div>
    @else
        <div x-data="vocabReview(@js($cards), '{{ url('/tu-vung') }}', '{{ csrf_token() }}', {{ $learnAheadMinutes }}, {{ $streak }})"
             x-init="start()">

            {{-- Kiểu ôn — nhớ theo trình duyệt --}}
            <div x-show="!finished" class="mb-4 grid grid-cols-4 gap-1 p-1 bg-gray-100 rounded-xl text-xs sm:text-sm font-medium">
                <template x-for="m in modes" :key="m.key">
                    <button type="button" @click="setMode(m.key)"
                            class="py-2 px-1 rounded-lg transition leading-tight"
                            :class="mode === m.key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'">
                        <span x-text="m.icon"></span> <span x-text="m.label"></span>
                    </button>
                </template>
            </div>

            {{-- Tiến độ --}}
            <div class="mb-4" x-show="!finished">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                    <span>
                        Đã ôn <span class="font-semibold text-gray-900" x-text="reviewed"></span> lượt ·
                        còn <span class="font-semibold text-gray-900" x-text="queue.length"></span> thẻ
                    </span>
                    <span x-show="learningCount > 0" x-text="`${learningCount} thẻ đang học sẽ quay lại`"></span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 transition-all duration-300"
                         :style="`width: ${progress}%`"></div>
                </div>
            </div>

            {{-- Chờ thẻ đang học tới hạn (bước 1 / 5 / 10 phút) --}}
            <div x-show="!finished && !current && waitingFor" x-cloak
                 class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-12 text-center">
                <div class="text-4xl mb-3">⏳</div>
                <h2 class="font-bold text-gray-900 text-lg mb-1">Nghỉ một chút</h2>
                <p class="text-sm text-gray-500 mb-1">
                    Thẻ tiếp theo quay lại sau <span class="font-semibold text-gray-900" x-text="countdown"></span>.
                </p>
                <p class="text-xs text-gray-400 mb-6">Đợi đúng giờ mới ôn thì mới luyện được trí nhớ — ôn ngay thì gần như chỉ là đọc lại.</p>
                <button type="button" @click="showNow()"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Ôn luôn không chờ
                </button>
            </div>

            {{-- ─────────────── Thẻ ─────────────── --}}
            <div x-show="!finished && current" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 pt-4 flex items-center justify-between gap-3">
                    <span class="text-[11px] font-semibold uppercase tracking-wide">
                        <span :class="badge.cls" x-text="badge.text"></span>
                        {{-- Kiểu Trộn: cho học viên biết thẻ này đang ở dạng nào --}}
                        <span x-show="mode === 'mixed'" class="text-gray-400" x-text="'· ' + kindLabel"></span>
                    </span>

                    {{-- Bật/tắt tự đọc khi lật thẻ — nhớ theo trình duyệt --}}
                    <label x-show="canSpeak" class="flex items-center gap-1.5 text-[11px] text-gray-400 select-none">
                        <input type="checkbox" x-model="autoSpeak" @change="saveAutoSpeak()"
                               class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Tự đọc khi lật
                    </label>
                </div>

                <div class="px-6 pb-8 pt-4 text-center min-h-[220px] flex flex-col items-center justify-center gap-3">

                    {{-- Mặt trước: Lật thẻ — hiện từ tiếng Anh --}}
                    <template x-if="kind === 'flip' || revealed">
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex items-center justify-center gap-2">
                                <p class="text-3xl font-black text-gray-900 break-words" x-text="current?.term"></p>
                                <button x-show="canSpeak" @click="speak()" type="button" title="Nghe phát âm (phím R)"
                                        class="shrink-0 p-2 rounded-full transition"
                                        :class="speaking ? 'text-indigo-600 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50'">
                                    {!! $speakerIcon !!}
                                </button>
                            </div>
                            <p class="text-sm text-gray-400" x-show="current?.phonetic" x-text="current?.phonetic"></p>
                            <p x-show="fallbackNote && !revealed" class="text-[11px] text-gray-400" x-text="fallbackNote"></p>
                        </div>
                    </template>

                    {{-- Mặt trước: Điền từ — câu gốc đục lỗ --}}
                    <template x-if="kind === 'cloze' && !revealed">
                        <div class="w-full">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3"
                               x-text="cloze.source === 'context' ? 'Câu gốc trong bài' : 'Câu ví dụ'"></p>
                            <p class="text-lg text-gray-800 leading-relaxed">
                                <span x-text="cloze.before"></span><span class="inline-block min-w-[5rem] mx-1 border-b-2 border-indigo-400 text-indigo-600 font-semibold"
                                      x-text="hint ? hintMask : ' '"></span><span x-text="cloze.after"></span>
                            </p>
                            <p class="mt-3 text-xs text-gray-400" x-show="current?.part_of_speech" x-text="current?.part_of_speech"></p>
                        </div>
                    </template>

                    {{-- Mặt trước: Điền từ khi không có câu nào chứa từ → nhìn nghĩa --}}
                    <template x-if="kind === 'meaning' && !revealed">
                        <div class="w-full">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Viết từ tiếng Anh có nghĩa</p>
                            <p class="text-2xl font-bold text-indigo-700 leading-snug" x-text="current?.meaning"></p>
                            <p class="mt-2 text-xs text-gray-400" x-show="current?.part_of_speech" x-text="current?.part_of_speech"></p>
                            <p class="mt-3 text-lg font-semibold text-indigo-600 tracking-widest" x-show="hint" x-text="hintMask"></p>
                        </div>
                    </template>

                    {{-- Mặt trước: Nghe & gõ --}}
                    <template x-if="kind === 'listen' && !revealed">
                        <div class="flex flex-col items-center gap-3">
                            <button @click="speak()" type="button" title="Nghe lại (phím R)"
                                    class="w-20 h-20 rounded-full flex items-center justify-center transition"
                                    :class="speaking ? 'bg-indigo-600 text-white scale-105' : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'">
                                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M11 5L6 9H3v6h3l5 4V5z" />
                                </svg>
                            </button>
                            <p class="text-sm text-gray-500"
                               x-text="isLongTerm ? 'Nghe rồi chép lại cả câu' : 'Nghe rồi gõ lại từ bạn nghe được'"></p>
                            <p class="text-sm text-indigo-600" x-show="hint" x-text="current?.meaning"></p>
                            <p class="text-lg font-semibold text-indigo-600 tracking-widest" x-show="hint && !isLongTerm" x-text="hintMask"></p>
                        </div>
                    </template>

                    {{-- Ô gõ đáp án (Điền từ / Nghe & gõ) --}}
                    <template x-if="typing && !revealed">
                        <div class="w-full max-w-md mt-2">
                            <div class="flex gap-2">
                                <input x-ref="answer" x-model="answer" type="text" autocomplete="off" autocapitalize="off"
                                       autocorrect="off" spellcheck="false"
                                       @keydown.enter.prevent="check()"
                                       :placeholder="isLongTerm ? 'Gõ lại cả câu…' : 'Gõ từ tiếng Anh…'"
                                       class="flex-1 min-w-0 px-4 py-3 text-base border border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="check()"
                                        class="px-4 py-3 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 transition">
                                    Kiểm tra
                                </button>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                                <span>Enter để kiểm tra</span>
                                <button type="button" @click="useHint()" x-show="!hint" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    💡 Gợi ý
                                </button>
                                <span x-show="hint" class="text-amber-600">Đã dùng gợi ý</span>
                            </div>
                        </div>
                    </template>

                    {{-- Kết quả so đáp án --}}
                    <template x-if="revealed && checked">
                        <div class="w-full max-w-md rounded-xl px-4 py-3 text-sm text-left"
                             :class="{
                                'bg-emerald-50 text-emerald-800 border border-emerald-100': checked.verdict === 'exact',
                                'bg-amber-50 text-amber-800 border border-amber-100': checked.verdict === 'close',
                                'bg-red-50 text-red-800 border border-red-100': checked.verdict === 'wrong',
                             }">
                            <p class="font-semibold"
                               x-text="{ exact: '✓ Chính xác', close: '≈ Gần đúng — sai chính tả nhẹ', wrong: '✗ Chưa đúng' }[checked.verdict]"></p>
                            <p class="mt-0.5" x-show="checked.verdict !== 'exact'">
                                Bạn gõ: <span class="font-mono line-through decoration-1" x-text="answer || '(bỏ trống)'"></span>
                            </p>
                        </div>
                    </template>

                    {{-- Mặt sau --}}
                    <template x-if="revealed">
                        <div class="w-full space-y-3 pt-4 mt-1 border-t border-gray-100">
                            <p class="text-xs text-gray-400 font-medium" x-show="current?.part_of_speech" x-text="current?.part_of_speech"></p>
                            <p class="text-xl font-bold text-indigo-700 leading-snug" x-text="current?.meaning"></p>
                            <p class="text-sm text-gray-600 italic leading-relaxed" x-show="current?.example" x-text="current?.example"></p>
                        </div>
                    </template>
                </div>

                {{-- Câu gốc: ở kiểu Lật thẻ là gợi ý (không lộ nghĩa tiếng Việt);
                     ở kiểu Điền từ thì chính nó là đề nên chỉ hiện sau khi lật. --}}
                <div class="px-6 pb-5" x-show="current?.context && (kind === 'flip' || revealed)">
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
                    <button x-show="!revealed && !typing" @click="reveal()" type="button"
                            class="w-full py-3.5 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">
                        Xem nghĩa
                        <span class="block text-[10px] font-normal text-gray-400 mt-0.5">phím cách</span>
                    </button>

                    {{-- Nhãn dưới mỗi nút = lần gặp lại, như Anki. Kiểu gõ thì nút
                         được gợi ý sáng viền — bấm Enter để chọn nút đó. --}}
                    <div x-show="revealed" class="grid grid-cols-3 gap-2">
                        <template x-for="b in gradeButtons" :key="b.key">
                            <button @click="grade(b.key)" :disabled="saving" type="button"
                                    class="py-3 rounded-xl font-semibold text-sm border disabled:opacity-50 transition"
                                    :class="[b.cls, suggestion === b.key ? 'ring-2 ring-offset-1 ' + b.ring : '']">
                                <span x-text="b.label"></span>
                                <span class="block text-[10px] font-normal mt-0.5" :class="b.sub"
                                      x-text="suggestion === b.key ? `${current?.intervals?.[b.key] ?? ''} · Enter` : current?.intervals?.[b.key]"></span>
                            </button>
                        </template>
                    </div>

                    <p x-show="error" x-cloak class="mt-3 text-xs text-red-600 text-center" x-text="error"></p>
                </div>
            </div>

            {{-- Kết thúc phiên --}}
            <div x-show="finished" x-cloak class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-12 text-center">
                <div class="text-4xl mb-3">🎯</div>
                <h2 class="font-bold text-gray-900 text-lg mb-1">Xong phiên ôn tập</h2>
                <p class="text-sm text-gray-500 mb-2">
                    Bạn vừa ôn <span class="font-semibold text-gray-900" x-text="reviewed"></span> lượt ·
                    nhớ <span class="font-semibold text-emerald-600" x-text="goodCount"></span> lượt.
                    <span x-show="laterCount > 0" x-text="`${laterCount} thẻ đang học sẽ quay lại sau 1 giờ.`"></span>
                </p>
                <p class="text-sm font-semibold text-orange-600 mb-6" x-show="streak > 0"
                   x-text="`🔥 Chuỗi ${streak} ngày liên tiếp`"></p>
                <div class="mb-6" x-show="streak === 0"></div>

                <div class="flex items-center justify-center gap-2">
                    @if($remainingAfter > 0)
                        <a href="{{ route('vocab.review', $scopeParams) }}"
                           class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                            Ôn tiếp {{ $remainingAfter }} từ
                        </a>
                    @endif
                    <a href="{{ route('vocab.index', $scopeParams) }}"
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
    /**
     * Phiên ôn kiểu Anki, 3 kiểu ôn:
     *   flip   — Lật thẻ: nhìn từ, tự nhớ nghĩa, tự chấm.
     *   cloze  — Điền từ: câu gốc trong bài bị đục đúng chỗ từ đó, gõ từ vào.
     *   listen — Nghe & gõ: nghe phát âm, gõ lại (câu thì chép cả câu).
     * Kiểu gõ tự so đáp án (resources/js/components/vocab-practice.js) và gợi ý
     * nút chấm; học viên vẫn có quyền bấm nút khác.
     *
     * Hàng đợi giữ thẻ kèm thời điểm tới hạn (`dueMs`). Thẻ vừa chấm mà tới hạn
     * trong `learnAhead` phút (bước 1 / 5 / 10 phút) thì quay lại hàng đợi của
     * chính phiên này; xa hơn (bước 1 giờ, hoặc đã sang ôn theo ngày) thì rời
     * phiên và sẽ hiện ở lần ôn sau.
     */
    function vocabReview(cards, baseUrl, csrf, learnAhead, streak) {
        const now = () => Date.now();
        const practice = () => window.vocabPractice;

        return {
            baseUrl, csrf,
            learnAheadMs: learnAhead * 60 * 1000,
            streak,

            modes: [
                { key: 'mixed', icon: '🔀', label: 'Trộn' },
                { key: 'flip', icon: '🃏', label: 'Lật thẻ' },
                { key: 'cloze', icon: '✍️', label: 'Điền từ' },
                { key: 'listen', icon: '🎧', label: 'Nghe & gõ' },
            ],
            // Mặc định Trộn: mỗi từ lần lượt gặp cả 3 dạng theo mức thuộc,
            // vẫn chỉ một lịch ôn nên khối lượng ôn mỗi ngày không tăng.
            mode: 'mixed',

            gradeButtons: [
                { key: 'again', label: 'Quên', cls: 'bg-red-50 text-red-700 border-red-100 hover:bg-red-100', sub: 'text-red-400', ring: 'ring-red-400' },
                { key: 'hard', label: 'Mơ hồ', cls: 'bg-amber-50 text-amber-700 border-amber-100 hover:bg-amber-100', sub: 'text-amber-500', ring: 'ring-amber-400' },
                { key: 'good', label: 'Nhớ', cls: 'bg-emerald-50 text-emerald-700 border-emerald-100 hover:bg-emerald-100', sub: 'text-emerald-500', ring: 'ring-emerald-400' },
            ],

            queue: cards.map((c) => ({ ...c, dueMs: 0 })),
            total: cards.length,
            current: null,
            saving: false,
            finished: false,
            reviewed: 0,
            goodCount: 0,
            laterCount: 0,
            error: null,

            // Trạng thái của thẻ đang hiện
            revealed: false,
            answer: '',
            checked: null,
            hint: false,

            waitingFor: null,
            countdown: '',
            timer: null,

            /* Đọc phát âm: dùng chung resources/js/components/speech.js với
               popup tra từ (gắn sẵn ở window.vocabSpeech). */
            canSpeak: !!window.vocabSpeech?.canSpeak,
            autoSpeak: true,
            speaking: false,

            start() {
                // localStorage có thể ném lỗi (chế độ ẩn danh, chặn dữ liệu trang).
                try {
                    this.autoSpeak = localStorage.getItem('vocab.autoSpeak') !== '0';
                    const saved = localStorage.getItem('vocab.reviewMode');
                    if (this.modes.some((m) => m.key === saved)) this.mode = saved;
                } catch (e) {}

                this.pick();
                document.addEventListener('keydown', (e) => {
                    if (!this.current || this.saving || e.target.closest?.('input, textarea, select')) return;

                    if (!this.revealed && !this.typing && (e.key === ' ' || e.key === 'Enter')) {
                        e.preventDefault();
                        this.reveal();
                    } else if (this.revealed && e.key === 'Enter' && this.suggestion) {
                        e.preventDefault();
                        this.grade(this.suggestion);
                    } else if (this.revealed && ['1', '2', '3'].includes(e.key)) {
                        this.grade({ 1: 'again', 2: 'hard', 3: 'good' }[e.key]);
                    } else if (e.key === 'r' || e.key === 'R') {
                        this.speak();
                    }
                });
            },

            /* ── Kiểu ôn của thẻ hiện tại ── */

            /** Kiểu thực tế cho thẻ này — có thể lùi về kiểu khác nếu không hợp. */
            get kind() {
                if (!this.current) return 'flip';
                const mode = this.mode === 'mixed'
                    ? practice().mixedMode(this.current, this.canSpeak)
                    : this.mode;
                if (mode === 'listen') return this.canSpeak ? 'listen' : 'flip';
                if (mode === 'cloze') return this.cloze.kind;
                return 'flip';
            },

            get kindLabel() {
                return { flip: 'Lật thẻ', cloze: 'Điền từ', meaning: 'Điền từ', listen: 'Nghe & gõ' }[this.kind];
            },

            get cloze() {
                return this.current ? practice().clozePrompt(this.current) : { kind: 'flip' };
            },

            get typing() {
                return ['cloze', 'meaning', 'listen'].includes(this.kind);
            },

            get isLongTerm() {
                return this.current ? practice().isSentence(this.current) : false;
            },

            /** Vì sao thẻ này không ở kiểu đã chọn. */
            get fallbackNote() {
                if (this.mode === 'cloze' && this.kind === 'flip') return 'Câu dài không đục lỗ được — ôn bằng lật thẻ';
                if (this.mode === 'listen' && !this.canSpeak) return 'Trình duyệt không hỗ trợ đọc — ôn bằng lật thẻ';
                return '';
            },

            /** Đáp án đúng: kiểu Điền từ lấy đúng dạng chữ trong câu bị đục. */
            get expected() {
                if (!this.current) return '';
                return this.kind === 'cloze' ? this.cloze.answer : this.current.term;
            },

            /** Gợi ý: chữ cái đầu + số ký tự còn lại, giữ khoảng trắng của cụm từ. */
            get hintMask() {
                const term = this.expected;
                return [...term].map((ch, i) => (i === 0 || ch === ' ' || ch === '-' ? ch : '_')).join(' ');
            },

            get suggestion() {
                return this.checked?.suggestion ?? null;
            },

            setMode(key) {
                this.mode = key;
                try { localStorage.setItem('vocab.reviewMode', key); } catch (e) {}
                this.resetCard();
                this.onCardShown();
            },

            resetCard() {
                this.revealed = false;
                this.answer = '';
                this.checked = null;
                this.hint = false;
            },

            /** Thẻ vừa hiện: kiểu gõ thì đặt con trỏ vào ô, kiểu nghe thì đọc luôn. */
            onCardShown() {
                if (!this.current) return;
                this.$nextTick(() => {
                    if (this.typing) this.$refs.answer?.focus();
                    // Chrome chặn đọc trước khi người dùng tương tác với trang:
                    // thẻ đầu tiên có thể phải bấm nút loa.
                    if (this.kind === 'listen') this.speak();
                });
            },

            useHint() {
                this.hint = true;
                this.$nextTick(() => this.$refs.answer?.focus());
            },

            check() {
                if (!this.current || this.revealed) return;

                this.checked = practice().applyHintPenalty(practice().checkAnswer(this.answer, this.expected), this.hint);
                this.revealed = true;
                // Rời ô gõ để phím Enter / 1-2-3 điều khiển nút chấm.
                this.$refs.answer?.blur();
                if (this.autoSpeak) this.speak();
            },

            reveal() {
                this.revealed = true;
                if (this.autoSpeak) this.speak();
            },

            saveAutoSpeak() {
                try {
                    localStorage.setItem('vocab.autoSpeak', this.autoSpeak ? '1' : '0');
                } catch (e) {}
            },

            speak() {
                if (!this.canSpeak || !this.current?.term) return;

                window.vocabSpeech.speak(this.current.term, {
                    onStart: () => { this.speaking = true; },
                    onEnd: () => { this.speaking = false; },
                });
            },

            /* ── Hàng đợi ── */

            get learningCount() {
                return this.queue.filter((c) => c.dueMs > now()).length;
            },

            get progress() {
                const done = this.total - this.queue.length;
                return this.total ? Math.round((done / this.total) * 100) : 0;
            },

            get badge() {
                const state = this.current?.srs_state;
                if (state === 'new') return { text: 'Từ mới', cls: 'text-sky-600' };
                if (state === 'learning') return { text: 'Đang học', cls: 'text-amber-600' };
                if (state === 'relearning') return { text: 'Học lại', cls: 'text-red-600' };
                return { text: 'Ôn tập', cls: 'text-emerald-600' };
            },

            /** Chọn thẻ tới hạn sớm nhất; không có thì chờ thẻ đang học. */
            pick() {
                clearInterval(this.timer);
                if (this.canSpeak) window.vocabSpeech.cancel();
                this.resetCard();
                this.waitingFor = null;

                if (this.queue.length === 0) {
                    this.current = null;
                    this.finished = true;
                    return;
                }

                this.queue.sort((a, b) => a.dueMs - b.dueMs);
                const next = this.queue[0];

                if (next.dueMs <= now()) {
                    this.current = next;
                    this.onCardShown();
                    return;
                }

                this.current = null;
                this.waitingFor = next;
                this.tick();
                this.timer = setInterval(() => this.tick(), 1000);
            },

            tick() {
                if (!this.waitingFor) return;
                const left = Math.max(0, this.waitingFor.dueMs - now());
                if (left === 0) {
                    this.pick();
                    return;
                }
                const s = Math.ceil(left / 1000);
                this.countdown = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
            },

            showNow() {
                if (!this.waitingFor) return;
                this.waitingFor.dueMs = 0;
                this.pick();
            },

            async grade(result) {
                if (this.saving || !this.current) return;

                this.saving = true;
                this.error = null;
                const card = this.current;

                try {
                    const response = await fetch(`${this.baseUrl}/${card.id}/ket-qua`, {
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

                    const body = await response.json();
                    this.reviewed++;
                    if (result === 'good') this.goodCount++;
                    if (typeof body.streak === 'number') this.streak = body.streak;

                    this.queue = this.queue.filter((c) => c !== card);

                    const dueMs = body.due_at ? Date.parse(body.due_at) : Infinity;
                    if (body.srs_state !== 'review' && dueMs - now() <= this.learnAheadMs) {
                        // Cập nhật bước học để kiểu Trộn đổi dạng bài ở lần gặp lại.
                        this.queue.push({
                            ...card,
                            srs_state: body.srs_state,
                            step: body.step,
                            reviews_count: (card.reviews_count || 0) + 1,
                            intervals: body.intervals,
                            dueMs,
                        });
                    } else if (body.srs_state !== 'review') {
                        this.laterCount++;
                    }

                    this.pick();
                } catch (e) {
                    this.error = 'Mất kết nối. Kiểm tra mạng rồi bấm lại nhé.';
                } finally {
                    this.saving = false;
                }
            },
        };
    }
</script>
@endsection
