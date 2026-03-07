{{--
    Partial: _mcq.blade.php
    Variables: $i (int), $questions (Collection keyed by order), $config (array)
--}}
@php
    $q          = $questions->get($i);
    $meta       = $q?->metadata ?? [];
    $opts       = collect($meta['options'] ?? [])->keyBy('id');
    $correctOpt = old("questions.$i.metadata.correct_option", $meta['correct_option'] ?? '');
@endphp

<div class="border border-gray-100 rounded-xl overflow-hidden bg-white hover:shadow-sm transition">
    {{-- Header --}}
    <div class="flex items-center gap-2.5 px-4 py-2 bg-gray-50 border-b border-gray-100">
        <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">{{ $i }}</span>
        <span class="text-xs text-gray-400">Question {{ $i }} of 30</span>
        <span class="ml-auto text-xs text-gray-300">1 pt</span>
    </div>

    <div class="p-4 space-y-3">
        {{-- Câu hỏi --}}
        <input type="text"
               name="questions[{{ $i }}][stem]"
               value="{{ old("questions.$i.stem", $q?->stem ?? '') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 font-medium"
               placeholder="I think it is right ___ children to play outside with toys.">
        <input type="hidden" name="questions[{{ $i }}][metadata][vocab_type]" value="">

        {{-- Lựa chọn A / B / C --}}
        <div class="space-y-1.5" data-mcq-group="q{{ $i }}">
            @foreach(['A','B','C'] as $opt)
            @php $isCorrect = $correctOpt === $opt; @endphp
            <div class="mcq-option flex items-center gap-2.5 rounded-lg border px-3 py-2 transition
                {{ $isCorrect ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white' }}"
                 data-opt="{{ $opt }}">
                <span class="mcq-badge w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                    {{ $isCorrect ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $opt }}</span>
                <input type="text"
                       name="questions[{{ $i }}][metadata][options][{{ $opt }}]"
                       value="{{ old("questions.$i.metadata.options.$opt", $opts->get($opt)['text'] ?? '') }}"
                       class="flex-1 border-0 bg-transparent text-sm focus:ring-0 p-0 placeholder-gray-300"
                       placeholder="{{ $opt }}.">
                <label class="flex items-center gap-1 cursor-pointer shrink-0">
                    <input type="radio"
                           name="questions[{{ $i }}][metadata][correct_option]"
                           value="{{ $opt }}"
                           class="mcq-radio text-green-500 focus:ring-green-400"
                           data-group="q{{ $i }}"
                           {{ $isCorrect ? 'checked' : '' }}>
                    <span class="mcq-label text-xs {{ $isCorrect ? 'text-green-600 font-semibold' : 'text-gray-300' }}">Đúng</span>
                </label>
            </div>
            @endforeach
        </div>

        {{-- Giải thích --}}
        <div class="mt-3 pt-3 border-t border-indigo-100">
            <label class="block text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1.5">Giải thích (Explanation)</label>
            <textarea name="questions[{{ $i }}][explanation]"
                      rows="3"
                      class="editor-content w-full border border-gray-100 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 placeholder-gray-300"
                      placeholder="Giải thích đáp án câu {{ $i }} cho học sinh...">{{ old("questions.$i.explanation", $q?->explanation ?? '') }}</textarea>
        </div>
    </div>
</div>
