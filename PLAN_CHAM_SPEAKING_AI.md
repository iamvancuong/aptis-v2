# 🎤 PLAN — CHẤM BÀI NÓI (SPEAKING) BẰNG AI

> **Trạng thái: CHƯA CODE GÌ.** Đây là plan *tìm hiểu*, không phải plan *xây dựng*.
> Mục tiêu của tài liệu: đọc file này là đủ để bắt đầu, không cần chat cũ.
> Lập ngày **02/08/2026**. Thay thế phần kế hoạch trong `TIEN_DO.md` §12 (§12 viết 26/07 đã lỗi thời — xem mục 2).
>
> ⚠️ **Giá API và tên model thay đổi rất nhanh.** Mọi con số dưới đây chốt ngày 02/08/2026.
> Nếu bạn đọc file này sau ~3 tháng, **tra lại giá trước khi tin số liệu**.

---

## 1. VÌ SAO NÊN LÀM — số liệu thật, không phải cảm tính

Truy vấn DB ngày 02/08/2026:

| Câu trả lời Speaking học viên đã nộp | Số lượng |
|---|---|
| Tổng | **2.540** |
| Đã chấm thật (score > 0) | **3** |
| Chưa ai chấm (score = 0, feedback rỗng) | **2.537** |
| Có `ai_metadata` | 0 |

**99,9% bài Nói chưa từng được chấm.** Học viên đang luyện Nói mà không nhận được phản hồi nào.

Đây mới là lý do làm — không phải "cho hiện đại". Cô Dung không thể chấm tay 2.500 bài.

> Lệnh tự kiểm tra lại con số:
> ```
> php artisan tinker --execute="\$q=\App\Models\AttemptAnswer::whereHas('attempt',fn(\$x)=>\$x->where('skill','speaking')); echo 'tong='.(clone \$q)->count().' | da cham='.(clone \$q)->where('score','>',0)->count();"
> ```

---

## 2. ⚠️ §12 ĐÃ LỖI THỜI Ở ĐÂU

§12 viết 26/07/2026. Hai điểm sai cần biết trước khi đọc lại §12:

**a) Model phiên âm §12 chọn đã bị thay thế.**
§12 khuyến nghị `whisper-1`. Nhưng **GPT-Transcribe ra ngày 28/07/2026** — đúng 2 ngày sau khi §12 được viết — và giờ là model OpenAI khuyến nghị, rẻ hơn whisper.

**b) Chi phí §12 ước tính cao gấp ~6 lần.**
§12 ghi *"Cách A ≈ $0.03/bài (~750đ)"*. Tính lại theo giá 02/08/2026: **~$0.005/bài (~130đ)**.

### Bảng giá phiên âm (02/08/2026)

| Model | Giá / phút audio | Ghi chú |
|---|---|---|
| `gpt-4o-mini-transcribe` | **$0.003** | Rẻ nhất |
| `gpt-transcribe` | $0.0045 | Mới (28/07/2026), OpenAI khuyến nghị |
| `whisper-1` | $0.006 | Cũ — §12 chọn cái này |

### Giá chấm bằng audio trực tiếp (cách B)
`gpt-4o-audio-preview`: **audio token $40/triệu input, $80/triệu output** → đắt hơn hướng phiên âm khoảng **10 lần**.

### Ước tính chi phí

| | Giả định | Chi phí |
|---|---|---|
| 1 bài (cách A) | ~1 phút audio | **~$0.005 (~130đ)** |
| Chấm sạch 2.537 bài tồn đọng | ~1 phút/bài | **~$8–13 (200–330k đ)** |
| 1 bài (cách B, audio trực tiếp) | ~1 phút | ~$0.05 (~1.300đ) |

> 🔴 **Giả định "1 phút/bài" CHƯA ĐƯỢC KIỂM CHỨNG.** Máy local không có file audio nào
> (tất cả nằm trên production). Đo độ dài thật ở **Giai đoạn 1** rồi mới chốt số.

---

## 3. HẠ TẦNG ĐÃ CÓ — kiểm chứng lại ngày 02/08/2026

Tin tốt: gần như không phải dựng gì mới. Đã xác minh trong code:

| Thành phần | Trạng thái | Vị trí |
|---|---|---|
| OpenAI đã tích hợp | ✅ `gpt-4o-mini`, timeout 45s, retry 2 lần, có mock mode khi thiếu key | `app/Services/AiService.php` |
| Queue worker chạy nền | ✅ cron `queue:work` mỗi phút | `routes/console.php` (§10 #P4) |
| Audio bài Nói đã lưu sẵn | ✅ đường dẫn `.webm` trong `AttemptAnswer.answer` (mảng JSON) | VD: `["speaking_attempts/VPBDsG...webm"]` |
| Màn chấm tay của giáo viên | ✅ mỗi answer có `score` (0–10) + `feedback` + `grading_status` | `Admin/SpeakingReviewController` |
| Màn học viên xem kết quả | ✅ | `history/speaking-show` |
| Cột credit Speaking AI | ✅ `users.speaking_ai_reset_version` + `Admin/UserController::resetSpeakingAi` | |
| Hàm `User::recordSpeakingAiUsage()` | ❌ **CHƯA CÓ** — copy y hệt `recordWritingAiUsage()` (`app/Models/User.php:145`) | |
| Mẫu job để nhân bản | ✅ `app/Jobs/ProcessWritingGrading.php` | |

**Kết luận: đây về cơ bản là nhân bản luồng Writing AI + thêm một bước xử lý âm thanh.**

---

## 4. RỦI RO LỚN NHẤT (§12 chưa nhắc)

### 🔴 Rủi ro 1 — Giọng Việt nói tiếng Anh
Máy phiên âm sai → AI chấm một bài mà học viên **không hề nói**. Sai từ gốc, không prompt nào cứu được.
Đây là lý do **Giai đoạn 2 là cổng quan trọng nhất**, phải làm trước khi bàn bất cứ thứ gì khác.

### 🔴 Rủi ro 2 — Cách (A) KHÔNG chấm được phát âm và độ trôi chảy
Aptis Speaking chấm **phát âm + độ trôi chảy**. Đọc transcript chỉ biết học viên *nói gì*,
không biết *nói nghe thế nào*. Transcript đẹp không có nghĩa là nói hay.
→ Nếu định **bán** tính năng này, gần như chắc chắn phải đi cách (B) hoặc (C).

### 🟠 Rủi ro 3 — Dung lượng ổ đĩa
Hosting AZDIGI Premium Business: **30GB chia cho 21 web**. Audio tích luỹ mãi.
Cần command dọn audio cũ sau X ngày — nếu không sẽ đầy ổ.

### 🟠 Rủi ro 4 — Shared host có thể chặn gọi ra ngoài
§12 đã cảnh báo. PayOS gọi ra ngoài được nên khả năng cao OpenAI cũng được, **nhưng chưa test**.

### 🟠 Rủi ro 5 — Quyền riêng tư
Giọng học viên gửi lên OpenAI. Cần nêu trong điều khoản sử dụng.

---

## 5. PLAN TÌM HIỂU — 4 giai đoạn, mỗi giai đoạn một cổng dừng

**Nguyên tắc: rẻ nhất trước, bỏ sớm nếu hỏng. KHÔNG viết dòng code production nào cho tới hết Giai đoạn 3.**

### Giai đoạn 0 — Chốt mục tiêu (5 phút, người quyết: chủ dự án)

Dùng AI chấm Nói để làm gì? Ba mục tiêu đòi chất lượng khác hẳn nhau:

| Mục tiêu | Ngưỡng chất lượng | Hướng phù hợp |
|---|---|---|
| **(a)** Dọn 2.537 bài tồn — học viên có phản hồi ngay | Thấp, "có còn hơn không" | Cách A đủ |
| **(b)** Bản nháp để cô Dung chấm nhanh hơn | Trung bình | Cách A đủ |
| **(c)** Bán như tính năng trả phí | Cao — sai là mất uy tín | Cần B hoặc C |

> ✍️ **Mục tiêu đã chọn: _____________** (điền vào đây khi chốt)

### Giai đoạn 1 — Kỹ thuật có chạy được không (~30 phút)

Chạy trên **cPanel Terminal**:

```
cd /home/ujxmchhx/repositories/aptis-v2 && curl -s -o /dev/null -w "openai: HTTP %{http_code}\n" https://api.openai.com/v1/models -H "Authorization: Bearer $(grep '^OPENAI_API_KEY=' .env | cut -d= -f2-)" && du -sh storage/app/public/speaking_attempts/ && ls -lS storage/app/public/speaking_attempts/ | head -5
```

Cho biết 3 thứ: host có gọi ra ngoài được không · audio chiếm bao nhiêu GB · kích thước file thật.

Sau đó: tải 5–10 file `.webm` thật về máy, **đo độ dài thật** → chốt lại chi phí ở mục 2.

🚦 **DỪNG nếu**: HTTP khác 200 → host chặn outbound → phải đổi kiến trúc (chạy qua VPS/proxy) trước khi bàn tiếp.

### Giai đoạn 2 — Phiên âm có chính xác không ← **CỔNG QUAN TRỌNG NHẤT** (~30 phút, cần cô Dung)

1. Chạy 10 bài thật qua model phiên âm (script test độc lập, **không đụng code production**)
2. Cô Dung **vừa nghe audio vừa đọc transcript**, chấm: đúng bao nhiêu %?

🚦 **DỪNG HẲN nếu**: transcript sai nhiều → toàn bộ hướng này vô nghĩa, không cứu được bằng prompt.
Lúc đó chỉ còn đường (C) — dùng dịch vụ chuyên chấm phát âm.

### Giai đoạn 3 — Chấm có sát cô Dung không (~1 giờ, cần cô Dung)

1. Cô Dung **chấm tay 10 bài đó TRƯỚC**, chấm mù — không được xem điểm AI
2. AI chấm cùng 10 bài đó
3. So độ lệch từng bài

🚦 **Ngưỡng**: lệch trung bình **>1.5/10** → chỉ dùng làm nháp nội bộ cho giáo viên,
**KHÔNG hiển thị điểm cho học viên**.

> Vì sao phải chấm mù trước: nhìn điểm AI rồi mới chấm thì con người bị neo theo nó,
> so sánh mất ý nghĩa.

### Giai đoạn 4 — Chốt hướng và quyết định

Lúc này mới đủ dữ liệu để trả lời:

| # | Câu hỏi | Ghi chú |
|---|---|---|
| 1 | Cách **(A) transcript** hay **(B) audio trực tiếp**? | Phụ thuộc mục tiêu ở GĐ0. Bán → B/C |
| 2 | **Tính phí** (như chấm tay 99k) hay **miễn phí theo credit** (như Writing AI)? | Credit đã có cột sẵn |
| 3 | Xác nhận điểm AI là **nháp tham khảo, giáo viên xác nhận** — không phải điểm chính thức? | Đúng triết lý dự án |
| 4 | Chấp nhận cách A **không chấm được phát âm/trôi chảy**? | Câu quan trọng nhất |

---

## 6. NẾU QUYẾT ĐỊNH LÀM — việc code cụ thể (~2–4 ngày)

Chỉ bắt đầu **sau khi qua hết 4 giai đoạn trên**.

1. `AiService::transcribe($path): string` — gọi model phiên âm (chọn ở GĐ4)
2. `AiService::gradeSpeaking($transcript, $question, $targetLevel): array` — khung giống `gradeWriting`
3. Job `ProcessSpeakingGrading` — copy `ProcessWritingGrading`. Dispatch khi nộp mock Speaking
   (nhánh speaking trong `MockTestController@submit`). Trong job: đọc path audio từ
   `AttemptAnswer.answer` → phiên âm → chấm → lưu `ai_metadata`
4. Hiển thị `ai_metadata` ở `history/speaking-show` (học viên) + **điền sẵn form**
   `admin/speaking-reviews/show` cho giáo viên xác nhận.
   Mục phát âm/trôi chảy ghi rõ **"cần giáo viên xác nhận"** nếu đi cách A
5. `User::recordSpeakingAiUsage()` — copy `recordWritingAiUsage()`
6. Command `speaking:cleanup-audio` + lên lịch — dọn ổ đĩa (rủi ro 3)
7. Bổ sung điều khoản: audio gửi lên OpenAI (rủi ro 5)

### ⚠️ Bẫy đã biết trong codebase (đừng lặp lại)
- **Key mismatch**: `gradeWriting` từng truyền `question`/`answer` trong khi `AiService` đọc
  `question_stem`/`student_answer` → **gửi bài RỖNG cho AI mà không ai biết**. Đã vá ở §10 #P4.
  Làm `gradeSpeaking` nhớ kiểm tra tên khoá khớp nhau.
- **Job vào queue nhưng không có worker** → job nằm chết. Đã có cron `queue:work` (§10 #P4), giữ nguyên.
- Giới hạn file phiên âm thường là **25MB/file** — bài nói ngắn nên ổn, nhưng vẫn nên kiểm tra kích thước trước khi gửi.

---

## 7. GHI CHÚ

- Không self-host Whisper: cần GPU/VPS, dùng API rẻ hơn nhiều ở quy mô này.
- Giữ chấm **bất đồng bộ qua queue** (`queue:work --max-time=50` đã hợp giới hạn shared host).
- Chấm Speaking của **giáo viên** hiện đang **TẠM TẮT trong quảng cáo** (`TIEN_DO.md` §19).
  Khi bật lại còn 2 việc kèm theo: thêm nhãn Có phí/Miễn phí và vá bộ lọc "Chờ chấm"
  cho `/admin/speaking-reviews` (Writing đã làm ở §20/§21, Speaking chưa).

---

## 8. NHẬT KÝ TIẾN ĐỘ

Điền khi làm, để phiên sau biết đang ở đâu.

| Giai đoạn | Ngày | Kết quả | Người làm |
|---|---|---|---|
| 0 — Chốt mục tiêu | | | |
| 1 — Kỹ thuật | | | |
| 2 — Chất lượng phiên âm | | | |
| 3 — Chất lượng chấm | | | |
| 4 — Quyết định | | | |
