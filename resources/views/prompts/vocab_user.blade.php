SELECTED_TEXT:
{{ $term }}

@if(!empty($context))
CONTEXT_SENTENCE (câu chứa đoạn trên, chỉ dùng để hiểu ngữ cảnh):
{{ $context }}
@else
CONTEXT_SENTENCE: (không có)
@endif

Trả về JSON đúng schema đã mô tả.
