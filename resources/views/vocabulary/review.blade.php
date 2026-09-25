@extends('layouts.app')

@section('title', 'Ôn tập từ vựng')

@section('content')
@php
    $scopeParams = array_filter(['folder' => $filters['folder'] ?? null, 'type' => $filters['type'] ?? null]);
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
            <a href="{{ route('vocab.index', $scopeParams) }}" class="inline-block mt-5 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                Về sổ tay
            </a>
        </div>
    @else
        <div x-data="vocabReview(@js($cards), '{{ url('/tu-vung') }}', '{{ csrf_token() }}', {{ $learnAheadMinutes }})"
             x-init="start()">

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

            {{-- Thẻ --}}
            <div x-show="!finished && current" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 pt-4 flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wide" :class="badge.cls" x-text="badge.text"></span>

                    {{-- Bật/tắt tự đọc khi lật thẻ — nhớ theo trình duyệt --}}
                    <label x-show="canSpeak" class="flex items-center gap-1.5 text-[11px] text-gray-400 select-none">
                        <input type="checkbox" x-model="autoSpeak" @change="saveAutoSpeak()"
                               class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Tự đọc khi lật
                    </label>
                </div>
                <div class="px-6 pb-10 pt-4 text-center min-h-[220px] flex flex-col items-center justify-center gap-3">
                    <div class="flex items-center justify-center gap-2">
                        <p class="text-3xl font-black text-gray-900 break-words" x-text="current?.term"></p>
                        <button x-show="canSpeak" @click="speak()" type="button" title="Nghe phát âm (phím R)"
                                class="shrink-0 p-2 rounded-full transition"
                                :class="speaking ? 'text-indigo-600 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M11 5L6 9H3v6h3l5 4V5z" />
                            </svg>
                        </button>
                    </div>

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
                    <button x-show="!revealed" @click="reveal()" type="button"
                            class="w-full py-3.5 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">
                        Xem nghĩa
                        <span class="block text-[10px] font-normal text-gray-400 mt-0.5">phím cách</span>
                    </button>

                    {{-- Nhãn dưới mỗi nút = lần gặp lại, như Anki --}}
                    <div x-show="revealed" class="grid grid-cols-3 gap-2">
                        <button @click="grade('again')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-red-50 text-red-700 font-semibold text-sm border border-red-100 hover:bg-red-100 disabled:opacity-50 transition">
                            Quên
                            <span class="block text-[10px] font-normal text-red-400 mt-0.5" x-text="current?.intervals?.again"></span>
                        </button>
                        <button @click="grade('hard')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-amber-50 text-amber-700 font-semibold text-sm border border-amber-100 hover:bg-amber-100 disabled:opacity-50 transition">
                            Mơ hồ
                            <span class="block text-[10px] font-normal text-amber-500 mt-0.5" x-text="current?.intervals?.hard"></span>
                        </button>
                        <button @click="grade('good')" :disabled="saving" type="button"
                                class="py-3 rounded-xl bg-emerald-50 text-emerald-700 font-semibold text-sm border border-emerald-100 hover:bg-emerald-100 disabled:opacity-50 transition">
                            Nhớ
                            <span class="block text-[10px] font-normal text-emerald-500 mt-0.5" x-text="current?.intervals?.good"></span>
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
                    Bạn vừa ôn <span class="font-semibold text-gray-900" x-text="reviewed"></span> lượt ·
                    nhớ <span class="font-semibold text-emerald-600" x-text="goodCount"></span> lượt.
                    <span x-show="laterCount > 0" x-text="`${laterCount} thẻ đang học sẽ quay lại sau 1 giờ.`"></span>
                </p>

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
     * Phiên ôn kiểu Anki.
     *
     * Hàng đợi giữ thẻ kèm thời điểm tới hạn (`dueMs`). Thẻ vừa chấm mà tới hạn
     * trong `learnAhead` phút (bước 1 / 5 / 10 phút) thì quay lại hàng đợi của
     * chính phiên này; xa hơn (bước 1 giờ, hoặc đã sang ôn theo ngày) thì rời
     * phiên và sẽ hiện ở lần ôn sau.
     */
    function vocabReview(cards, baseUrl, csrf, learnAhead) {
        const now = () => Date.now();

        return {
            baseUrl, csrf,
            learnAheadMs: learnAhead * 60 * 1000,

            queue: cards.map((c) => ({ ...c, dueMs: 0 })),
            total: cards.length,
            current: null,
            revealed: false,
            saving: false,
            finished: false,
            reviewed: 0,
            goodCount: 0,
            laterCount: 0,
            error: null,

            waitingFor: null,
            countdown: '',
            timer: null,

            /* Đọc phát âm bằng giọng có sẵn của trình duyệt (Web Speech API):
               không tốn tiền API, không cần file âm thanh. */
            canSpeak: typeof window.speechSynthesis !== 'undefined',
            autoSpeak: true,
            speaking: false,

            start() {
                // localStorage có thể ném lỗi (chế độ ẩn danh, chặn dữ liệu trang).
                try {
                    this.autoSpeak = localStorage.getItem('vocab.autoSpeak') !== '0';
                } catch (e) {}

                this.pick();
                document.addEventListener('keydown', (e) => {
                    if (!this.current || this.saving || e.target.closest('input, textarea, select')) return;
                    if (!this.revealed && (e.key === ' ' || e.key === 'Enter')) {
                        e.preventDefault();
                        this.reveal();
                    } else if (this.revealed && ['1', '2', '3'].includes(e.key)) {
                        this.grade({ 1: 'again', 2: 'hard', 3: 'good' }[e.key]);
                    } else if (e.key === 'r' || e.key === 'R') {
                        this.speak();
                    }
                });
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

            /** Chọn giọng Anh-Anh cho khớp phiên âm IPA; không có thì giọng Anh bất kỳ. */
            voice() {
                const voices = window.speechSynthesis.getVoices();
                return voices.find((v) => v.lang === 'en-GB')
                    || voices.find((v) => v.lang && v.lang.startsWith('en'))
                    || null;
            },

            speak() {
                if (!this.canSpeak || !this.current?.term) return;

                const synth = window.speechSynthesis;
                // Bấm liên tục thì đọc lại từ đầu, không xếp hàng chồng lên nhau.
                synth.cancel();

                const utterance = new SpeechSynthesisUtterance(this.current.term);
                const voice = this.voice();
                if (voice) utterance.voice = voice;
                utterance.lang = voice?.lang || 'en-GB';
                utterance.rate = 0.9;
                utterance.onstart = () => { this.speaking = true; };
                utterance.onend = utterance.onerror = () => { this.speaking = false; };

                synth.speak(utterance);
            },

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
                if (this.canSpeak) window.speechSynthesis.cancel();
                this.revealed = false;
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

                    this.queue = this.queue.filter((c) => c !== card);

                    const dueMs = body.due_at ? Date.parse(body.due_at) : Infinity;
                    if (body.srs_state !== 'review' && dueMs - now() <= this.learnAheadMs) {
                        this.queue.push({ ...card, srs_state: body.srs_state, intervals: body.intervals, dueMs });
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
