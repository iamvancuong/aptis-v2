{{-- Prompt là CHỮ THUẦN gửi cho AI, không phải HTML: dùng {!! !!} để KHÔNG mã hoá ' " & thành &#039; &quot; &amp; (bản cũ dùng {{ }} nên AI thấy "I&#039;ve" và chấm sai). --}}
SELECTED_TEXT:
{!! $term !!}

@if(!empty($context))
CONTEXT_SENTENCE (câu chứa đoạn trên, chỉ dùng để hiểu ngữ cảnh):
{!! $context !!}
@else
CONTEXT_SENTENCE: (không có)
@endif

Trả về JSON đúng schema đã mô tả.
