{{--
    Popup tra từ.

    Cách dùng:
      1. Include partial này một lần trong trang.
      2. Đánh dấu vùng cho phép bôi chọn bằng `data-vocab-scope`.

    Biến nhận vào (đều không bắt buộc):
      $vocabSkill, $vocabPart, $vocabSetId — để biết từ được lưu từ bài nào.

    KHÔNG include partial này vào `mock-test/*`. Tra được từ trong lúc thi thử
    thì điểm Reading mất hết ý nghĩa đánh giá.
--}}
@if(config('aptis.vocab.enabled'))
<div x-data="vocabLookup({
        lookupUrl: '{{ route('vocab.lookup') }}',
        saveUrl: '{{ route('vocab.store') }}',
        notebookUrl: '{{ route('vocab.index') }}',
        csrf: '{{ csrf_token() }}',
        source: {
            skill: @js($vocabSkill ?? null),
            part: @js($vocabPart ?? null),
            setId: @js($vocabSetId ?? null),
        },
     })"
     x-ref="root">

    {{-- Nút "Tra từ" bám theo vùng bôi --}}
    <button x-show="trigger.show"
            x-cloak
            x-transition.opacity.duration.120ms
            @click="lookup()"
            type="button"
            class="fixed z-[60] flex items-center gap-1.5 px-3 py-2 rounded-full bg-indigo-600 text-white text-sm font-semibold shadow-lg hover:bg-indigo-700 active:scale-95 transition"
            :style="`left:${trigger.x}px; top:${trigger.y}px;`">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        Tra từ
    </button>

    {{-- Thẻ kết quả --}}
    <div x-show="panel.show"
         x-cloak
         x-ref="panel"
         x-transition.opacity.duration.150ms
         @click.outside="closeAll()"
         class="fixed z-[61] w-[340px] max-w-[calc(100vw-16px)] bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden"
         :style="`left:${panel.x}px; top:${panel.y}px;`">

        {{-- Đầu thẻ: từ đang tra --}}
        <div class="flex items-start justify-between gap-2 px-4 py-3 bg-indigo-50 border-b border-indigo-100">
            <div class="min-w-0">
                <p class="font-bold text-indigo-900 break-words leading-snug" x-text="term"></p>
                <p class="text-xs text-indigo-600 mt-0.5"
                   x-show="result && result.phonetic"
                   x-text="result?.phonetic"></p>
            </div>
            <button @click="closeAll()" type="button" class="shrink-0 text-indigo-400 hover:text-indigo-700 p-1 -m-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 py-3 max-h-[320px] overflow-y-auto">
            {{-- Đang tải --}}
            <template x-if="loading">
                <div class="space-y-2 animate-pulse py-1">
                    <div class="h-3.5 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-100 rounded w-full"></div>
                    <div class="h-3 bg-gray-100 rounded w-5/6"></div>
                </div>
            </template>

            {{-- Lỗi --}}
            <template x-if="!loading && error">
                <p class="text-sm text-red-600 leading-relaxed" x-text="error"></p>
            </template>

            {{-- Kết quả --}}
            <template x-if="!loading && result">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template x-if="result.part_of_speech">
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[11px] font-medium"
                                  x-text="result.part_of_speech"></span>
                        </template>
                        <template x-if="result.cefr">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold"
                                  x-text="result.cefr"></span>
                        </template>
                    </div>

                    <p class="text-[15px] text-gray-900 font-medium leading-relaxed" x-text="result.meaning"></p>

                    <template x-if="result.example">
                        <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2">
                            <p class="text-sm text-gray-700 italic leading-relaxed" x-text="result.example"></p>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed"
                               x-show="result.example_vi"
                               x-text="result.example_vi"></p>
                        </div>
                    </template>

                    <template x-if="result.note">
                        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 leading-relaxed">
                            <span class="font-semibold">Ghi chú:</span> <span x-text="result.note"></span>
                        </p>
                    </template>
                </div>
            </template>
        </div>

        {{-- Chân thẻ: lưu vào sổ tay --}}
        <div class="flex items-center justify-between gap-2 px-4 py-2.5 bg-gray-50 border-t border-gray-100"
             x-show="!loading && result">
            <span class="text-[11px] text-gray-400" x-text="remainingLabel"></span>

            <div class="flex items-center gap-2">
                <a :href="cfg.notebookUrl"
                   x-show="saved"
                   class="text-xs font-medium text-gray-500 hover:text-indigo-600">Xem sổ tay</a>

                <button @click="save()"
                        type="button"
                        :disabled="saved || saving"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="saved
                            ? 'bg-emerald-100 text-emerald-700 cursor-default'
                            : 'bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60'">
                    <template x-if="saved">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="!saved">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </template>
                    <span x-text="saved ? 'Đã lưu' : (saving ? 'Đang lưu…' : 'Lưu từ')"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    /*
      Mở lại quyền bôi chọn CHỈ trong vùng bài đọc.

      Trang luyện tập cố tình đặt `user-select:none` lên thẻ gốc để chống sao
      chép đề. Không ghi đè ở đây thì không bôi được chữ và cả tính năng vô
      dụng. Chặn Ctrl+C / chuột phải vẫn giữ nguyên (xem cuối practice/show),
      nên đề vẫn không copy ra ngoài được — chỉ là chọn được để tra nghĩa.
    */
    [data-vocab-scope],
    [data-vocab-scope] * {
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        user-select: text !important;
    }

    /* iOS hay bật kính lúp và menu Copy của hệ thống khi giữ lâu; tắt cho gọn. */
    [data-vocab-scope] {
        -webkit-touch-callout: none;
    }
</style>
@endif
