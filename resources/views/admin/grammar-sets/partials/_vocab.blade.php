{{--
    Partial: _vocab.blade.php
    Variables: $vocabIdx (1-5), $orderI (26-30), $vdef (array), $questions (Collection), $config (array)
--}}
@php
    $vq      = $questions->get($orderI);
    $vmeta   = $vq?->metadata ?? [];
    $pairs   = $vmeta['pairs'] ?? array_fill(0, 5, []);
    $pool    = $vmeta['dropdown_pool'] ?? [];
    $correct = $vmeta['correct_answers'] ?? [];
    $example = $vmeta['example'] ?? ['left' => 'big', 'right' => 'large'];
@endphp

<div class="border border-gray-200 rounded-xl overflow-hidden bg-white hover:shadow-sm transition">
    {{-- Header --}}
    <div class="px-4 py-3 bg-purple-50 border-b border-purple-100">
        <div class="flex items-center gap-2.5">
            <span class="w-7 h-7 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">{{ $orderI }}</span>
            <span class="text-xs text-purple-400 uppercase tracking-wide font-medium">{{ $vdef['type'] }}</span>
            <span class="ml-auto text-xs font-semibold text-purple-500 shrink-0">5 pts</span>
        </div>
        {{-- Instruction title --}}
        <p class="mt-2 text-sm text-gray-600 italic leading-snug">{{ $vdef['desc'] }}</p>
    </div>

    <div class="p-4 space-y-4">
        <input type="hidden" name="questions[{{ $orderI }}][metadata][vocab_type]" value="{{ $vdef['type'] }}">
        <input type="hidden" name="questions[{{ $orderI }}][stem]" value="{{ $vdef['desc'] }}">

        {{-- Example (synonym / collocation only) --}}
        @if($vdef['hasExample'])
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-400 text-xs font-medium w-16 shrink-0">Example</span>
            <input type="text" name="questions[{{ $orderI }}][metadata][example][left]"
                   value="{{ old("questions.$orderI.metadata.example.left", $example['left'] ?? 'big') }}"
                   class="w-20 border border-gray-200 rounded px-2 py-1 text-xs text-center focus:ring-1 focus:ring-purple-300">
            <span class="text-gray-400 font-mono text-sm">{{ $vdef['connector'] }}</span>
            <input type="text" name="questions[{{ $orderI }}][metadata][example][right]"
                   value="{{ old("questions.$orderI.metadata.example.right", $example['right'] ?? 'large') }}"
                   class="w-20 border border-gray-200 rounded px-2 py-1 text-xs text-center focus:ring-1 focus:ring-purple-300">
            <input type="hidden" name="questions[{{ $orderI }}][metadata][connector]" value="{{ $vdef['connector'] }}">
        </div>
        <hr class="border-gray-100">
        @endif

        {{-- 5 Pairs --}}
        <div class="space-y-2">
            @for($pi = 1; $pi <= 5; $pi++)
                @php
                    $pair       = $pairs[$pi - 1] ?? [];
                    $correctVal = old("questions.$orderI.metadata.correct_answers.$pi", $correct[$pi] ?? '');
                @endphp
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 w-4 text-center">{{ $pi }}</span>

                    @if($vdef['type'] === 'sentence_completion')
                        <input type="text" name="questions[{{ $orderI }}][metadata][pairs][{{ $pi-1 }}][prefix]"
                               value="{{ old("questions.$orderI.metadata.pairs.".($pi-1).".prefix", $pair['prefix'] ?? '') }}"
                               class="flex-1 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:ring-1 focus:ring-purple-300 min-w-0"
                               placeholder="Prefix...">
                        <input type="text" name="questions[{{ $orderI }}][metadata][pairs][{{ $pi-1 }}][suffix]"
                               value="{{ old("questions.$orderI.metadata.pairs.".($pi-1).".suffix", $pair['suffix'] ?? '') }}"
                               class="flex-1 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:ring-1 focus:ring-purple-300 min-w-0"
                               placeholder="Suffix...">
                        <span class="text-gray-300 text-xs font-mono shrink-0">___</span>
                    @else
                        <input type="text" name="questions[{{ $orderI }}][metadata][pairs][{{ $pi-1 }}][prompt]"
                               value="{{ old("questions.$orderI.metadata.pairs.".($pi-1).".prompt", $pair['prompt'] ?? '') }}"
                               class="w-28 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:ring-1 focus:ring-purple-300"
                               placeholder="word...">
                        <span class="text-gray-400 font-mono text-sm shrink-0">{{ $vdef['connector'] }}</span>
                    @endif

                    <input type="hidden" name="questions[{{ $orderI }}][metadata][pairs][{{ $pi-1 }}][id]" value="{{ $pi }}">

                    {{-- Correct answer select (populated via JS from pool) --}}
                    <select name="questions[{{ $orderI }}][metadata][correct_answers][{{ $pi }}]"
                            class="pool-select w-32 border border-green-300 bg-green-50 rounded-lg px-2 py-1.5 text-sm focus:ring-1 focus:ring-green-400"
                            data-order="{{ $orderI }}"
                            data-current="{{ $correctVal }}">
                        <option value="">Đáp án...</option>
                        @foreach($pool as $word)
                            <option value="{{ $word }}" {{ $correctVal === $word ? 'selected' : '' }}>{{ $word }}</option>
                        @endforeach
                    </select>

                    @if($vdef['type'] === 'sentence_completion')
                        <input type="text" name="questions[{{ $orderI }}][metadata][pairs][{{ $pi-1 }}][after]"
                               value="{{ old("questions.$orderI.metadata.pairs.".($pi-1).".after", $pair['after'] ?? '') }}"
                               class="flex-1 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:ring-1 focus:ring-purple-300 min-w-0"
                               placeholder="After...">
                    @endif
                </div>
            @endfor
        </div>

        {{-- Dropdown Pool --}}
        <div>
            <label class="block text-xs text-gray-400 mb-1">Pool <span class="text-gray-300">(các từ cách nhau dấu phẩy)</span></label>
            <input type="text"
                   name="questions[{{ $orderI }}][metadata][dropdown_pool_raw]"
                   value="{{ old("questions.$orderI.metadata.dropdown_pool_raw", implode(', ', $pool)) }}"
                   class="pool-input w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-purple-300 text-gray-600"
                   data-order="{{ $orderI }}"
                   placeholder="learn, get, begin, speak, choose, donate, go, run">
        </div>

        {{-- Giải thích --}}
        <div class="mt-3 pt-3 border-t border-purple-100">
            <label class="block text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1.5">Giải thích (Explanation)</label>
            <textarea name="questions[{{ $orderI }}][explanation]"
                      rows="3"
                      class="editor-content w-full border border-gray-100 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-purple-300 focus:border-purple-400 placeholder-gray-300"
                      placeholder="Giải thích các đáp án trong phần này cho học sinh...">{{ old("questions.$orderI.explanation", $vq?->explanation ?? '') }}</textarea>
        </div>
    </div>
</div>
