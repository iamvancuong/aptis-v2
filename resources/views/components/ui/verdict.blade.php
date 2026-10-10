{{-- Kết quả một ý trong màn luyện tập (Alpine). Hai prop là BIỂU THỨC JS, không phải giá trị:
     ok     — biểu thức đúng/sai, vd. "part4Answers[pIdx] == currentQuestion.metadata.correct_answers[pIdx]"
     answer — biểu thức ra chữ đáp án đúng, hiện khi sai (html=true nếu đáp án chứa HTML).
     Thay cho khối "Correct / Incorrect. Answer:" từng chép lặp ở mọi phần đề. --}}
@props(['ok', 'answer' => null, 'html' => false])
<div {{ $attributes->merge(['class' => 'text-sm']) }}>
    <span x-show="{{ $ok }}" class="inline-flex items-center gap-1.5 font-semibold text-emerald-600">
        <x-ui.icon name="check" class="w-4 h-4" /> Đúng
    </span>
    <span x-show="!({{ $ok }})" class="inline-flex flex-wrap items-center gap-x-1.5 text-red-600">
        <x-ui.icon name="x" class="w-4 h-4" />
        <span class="font-semibold">Sai.</span>
        @if($answer)
            <span class="text-gray-500">Đáp án:</span>
            <span class="font-semibold text-emerald-700" {{ $html ? 'x-html' : 'x-text' }}="{{ $answer }}"></span>
        @endif
    </span>
</div>
