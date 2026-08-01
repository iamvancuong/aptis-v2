Grade this APTIS Speaking Part {{ $part }} response.

---
## Đề bài
{{ $question }}

@if(!empty($metadata['questions']))
## Các câu hỏi học viên phải trả lời:
@foreach($metadata['questions'] as $idx => $q)
- Q{{ $idx + 1 }}: {{ is_string($q) ? $q : ($q['prompt'] ?? '') }}
@endforeach
@endif

@if($part == 2 || $part == 3)
> Lưu ý: phần này có ảnh đề bài mà bạn KHÔNG nhìn thấy được.
> Đừng chấm việc mô tả ảnh có "đúng" hay không — chỉ chấm xem học viên có nói
> được một đoạn mô tả mạch lạc, đủ ý và đúng hướng câu hỏi hay không.
@endif

---
## Transcript bài nói của học viên
(Đây là văn bản do máy phiên âm tự động. Chỗ nào nghe như sai từ thì coi là lỗi
của máy phiên âm, KHÔNG trừ điểm học viên.)

"""
{{ $transcript }}
"""

---
Chấm theo đúng rubric và khung JSON trong system prompt. Chỉ trả về JSON.
