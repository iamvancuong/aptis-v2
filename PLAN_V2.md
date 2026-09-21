# PLAN V2 — APTIS (Bán khóa + Làm bài, dùng chung kho bài học v1)

> Tài liệu tham chiếu để dựng **dự án v2 mới hoàn toàn**. Repo hiện tại (v1 / milaedu.com)
> chỉ dùng để tham chiếu logic nghiệp vụ & schema. **v2 KHÔNG sửa trong repo này.**
>
> Trạng thái: PLAN — chưa code. Ngày tạo: 2026-09-19.

---

## 1. Mục tiêu

- Dựng **codebase v2 mới hoàn toàn**, UI/UX làm lại từ đầu.
- **Bán khóa học** với giá mới (giảm giá), tài khoản & dòng tiền tách biệt v1.
- **Dùng lại toàn bộ kho bài học của v1** bằng cách đọc realtime từ database v1.
- v2 tự lưu **toàn bộ user + thanh toán + log làm bài** trong database riêng.

### Phân vai rõ ràng
| | Vai trò |
|---|---|
| **v1 (milaedu.com)** | Nơi soạn & quản lý toàn bộ **nội dung bài học**. Giữ nguyên. |
| **v2 (domain mới)** | Bán khóa + quản lý **user/tiền** + cho học viên **làm bài** trên nội dung lấy từ v1. |

---

## 2. Hạ tầng (đã chốt)

- **Cùng 1 cPanel account**, chỉ khác **tên miền** (v2 = domain mới hoàn toàn, dạng `aptis...`, addon domain).
- **Cùng 1 MySQL server**: `db1` (nội dung v1) và `db2` (dữ liệu v2) chung một instance.
- v2 kết nối `db1` **nội bộ (localhost)** → nhanh, an toàn, không mở firewall ra internet.
- **Media dùng chung** (audio/ảnh) qua **symlink** tới `storage/app/public` của v1 (cùng account nên truy cập được).
- **PayOS: tài khoản MỚI** cho v2.

```
┌───────────────────── cPanel (1 account, 1 MySQL) ─────────────────────┐
│                                                                        │
│   v1 (milaedu.com)                  v2 (aptis-domain-moi)              │
│   Laravel + Blade                   Laravel + Inertia/Vue (mới)        │
│        │ ghi/đọc                        │ đọc(legacy)   │ ghi(mysql)    │
│        ▼                                ▼               ▼               │
│   ┌──────────┐   read-only (localhost) ┌──────────────────────────┐    │
│   │  db1     │◄────────────────────────│  db2                      │    │
│   │ nội dung │                         │ users, orders, attempts…  │    │
│   │ + user v1│                         │ (không đụng user v1)      │    │
│   └──────────┘                         └──────────────────────────┘    │
│        │                                                                │
│        └── storage/app/public (media) ──symlink──► v2                   │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Kiến trúc dữ liệu

### 3.1 Hai kết nối DB trong v2
- `legacy` → **db1** (READ-ONLY). Dùng cho các model nội dung.
- `mysql` (mặc định) → **db2**. Dùng cho user/tiền/log. **Mọi migration của v2 chỉ chạy ở đây.**

### 3.2 Ranh giới bảng
**Đọc từ db1 (read-only) — model đặt `protected $connection = 'legacy'`:**
- `quizzes`, `sets`, `questions`, `set_question`, `instructions`, `feedback`
- (tùy chọn dùng chung: `settings`, `high_scores` — mặc định để v2 tự quản, xem 3.4)
- ⚠️ **`mock_tests` KHÔNG đọc từ db1**: bảng này có `user_id`/`started_at`/`score`/`status`
  → là **lượt mock test của học viên**, không phải nội dung. v2 tự tạo trong db2.
  Nội dung mà mock test dùng chính là `sets` + `questions` (đã có trong legacy).

Số liệu db1 production (kiểm tra 2026-09-19): quizzes 17 · sets 65 · questions 557 ·
set_question 557 · instructions 11 · feedback 3. (users 849, mock_tests 8774 → của v1, bỏ qua.)

**v2 tự sở hữu trong db2:**
- `users`, `login_sessions`
- `orders` + bảng giá/gói + mã sale (pricing cấu hình được)
- `attempts`, `attempt_answers`
- `writing_reviews` (chỉ chứa kết quả **AI**), `writing_ai_usages`, `speaking_ai_usages`
- `high_scores` / leaderboard, `security_flags`

### 3.3 Cross-database join
- Vì **cùng một MySQL server**, v2 join chéo được bằng tên bảng đầy đủ `db1.<table>`.
- Model nội dung dùng connection `legacy`; quan hệ Eloquent thường (hasMany/belongsTo) chạy bình thường.
- Query cần join thật (vd `attempts` × `questions` cho history/report) → viết trên connection `mysql` với tên `db1.questions`.
- **KHÔNG** đặt foreign key ràng buộc chéo DB: cột như `attempt_answers.question_id` để dạng số thường (bỏ `constrained()`), không tạo FK sang db1.

### 3.4 Nguyên tắc
- v2 **không bao giờ** migrate/ghi vào db1.
- `db1` chỉ được đọc đúng các bảng nội dung ở mục 3.2; **không đọc user/đơn/lịch sử của v1**.
- Cần "khóa hợp đồng schema": nếu v1 đổi cấu trúc bảng nội dung, v2 phải cập nhật theo.

---

## 4. Media (audio/ảnh)

- File nằm ở `storage/app/public` của v1, phục vụ qua route có **signed URL + auth** (tham chiếu `app/Http/Controllers/MediaController.php` của v1).
- v2 **symlink** disk `public` sang thư mục media của v1 → không copy, không S3.
- v2 **tự ký signed URL bằng `APP_KEY` riêng** (key v2 khác v1) và bảo vệ bằng session/auth của v2.
- Port `MediaController` sang v2, đọc `audio_path` / `metadata.audio_files` / `image_path` từ model nội dung (legacy).

---

## 5. Chức năng

### 5.1 Giữ (học viên)
- Practice + **chấm tức thời** (endpoint check từng câu, có throttle).
- Mock Test (tạo, làm, nộp, kết quả).
- **Chấm AI** Writing & Speaking (dùng OpenAI như v1).
- History: reading / writing / speaking.
- Leaderboard, Grammar, Instructions.
- Marketing: landing, giới thiệu, luyện thi APTIS, chính sách; đăng ký → thanh toán → **cấp quyền/thời hạn tự động**.

### 5.2 Bỏ hẳn ở v2
- ❌ Lớp học online / Google Meet (`class_groups`, `class_sessions`, `class_session_joins`, `google_email`, route `/lop-hoc`, `/cong-cu-lop`, cron nhắc lớp).
- ❌ **Chấm tay** Writing/Speaking (chỉ còn AI). Bỏ hàng đợi chấm tay & route `admin/writing-reviews`, `admin/speaking-reviews`.

### 5.3 Admin v2 — CHỈ 2 nhóm
- **Quản lý User**: danh sách/tìm, xem thông tin, cấp quyền, gia hạn, chặn/mở, reset lượt AI, xem lịch sử làm bài.
- **Quản lý Thanh toán/Bán**: đơn hàng, trạng thái PayOS, bảng giá/gói, mã sale/khuyến mãi, doanh thu/báo cáo.
- ❌ **Không** CRUD nội dung (sets, questions, quizzes, mock tests, grammar, instructions, feedback, high scores…). Sửa bài học làm ở admin v1.
- ❌ **Không** chấm tay.

---

## 6. Công nghệ & UI/UX

- Nền **Laravel** (bản mới nhất tương thích), PHP 8.2+.
- Frontend **hybrid**:
  - **Blade server-render** cho trang **marketing/SEO** (home, giới thiệu, luyện thi APTIS, chính sách, đăng ký) — giữ SEO bán hàng, không cần Node SSR (hợp cPanel).
  - **Inertia + Vue 3** cho **khu đăng nhập/làm bài** (dashboard, skills, sets, practice, mock test, history, leaderboard) — cảm giác app mượt. Build bằng Vite, deploy chỉ cần asset tĩnh.
- **Design system mới hoàn toàn** (tokens màu/typography/spacing, bộ component mới).
- Thanh toán: **PayOS tài khoản mới**.
- Giá/gói: **fake tạm**, tách thành cấu hình để chốt số sau.

---

## 7. Roadmap theo pha

### Pha 0 — Khởi tạo
- [ ] Tạo project Laravel v2 mới (repo mới).
- [ ] Kéo bản sao `db1` từ prod về local để dev.
- [ ] Cấu hình 2 connection (`legacy` → db1, `mysql` → db2).
- [ ] Cài Inertia + Vue 3 + Vite; dựng khung layout Blade (marketing) + Inertia (app).

### Pha 1 — Nền tảng data
- [ ] Model nội dung (Quiz/Set/Question/MockTest/Instruction/Feedback) trỏ connection `legacy`, read-only.
- [ ] Migration db2: users, login_sessions, orders, attempts, attempt_answers, writing_reviews(AI), *_ai_usages, high_scores, security_flags.
- [ ] Bỏ FK chéo DB; chuẩn hóa cách join `db1.<table>`.
- [ ] Media: symlink + MediaController v2 + signed URL bằng key v2.
- [ ] Auth v2 (đăng nhập/đăng ký).
- **Mốc:** chạy được 1 bài practice đọc nội dung từ db1, lưu attempt vào db2.

### Pha 2 — Marketing + bán
- [ ] Landing + trang SEO (Blade), design mới.
- [ ] Đăng ký → chọn gói (giá fake, cấu hình được) → **PayOS mới** → cấp quyền/thời hạn tự động.
- [ ] Mã sale/khuyến mãi, link giới thiệu.
- [ ] Admin: quản lý đơn hàng + doanh thu.

### Pha 3 — Khu làm bài (Inertia/Vue) + Admin User
- [ ] Dashboard học viên, skills, sets, practice (chấm tức thời), mock test, history, leaderboard, grammar, instructions.
- [ ] Admin: quản lý user (cấp quyền/gia hạn/chặn/reset AI/xem lịch sử).

### Pha 4 — Chấm AI
- [ ] Chấm AI Writing & Speaking (OpenAI), quản lý lượt dùng AI.

### Pha 5 — QA & Go-live
- [ ] Kiểm thử end-to-end, kiểm tra realtime đồng bộ nội dung từ v1.
- [ ] Cấu hình domain mới trên cPanel, symlink media, deploy.

---

## 8. Rủi ro & lưu ý

- **Coupling schema:** v2 phụ thuộc cấu trúc bảng nội dung của v1 → cần theo dõi khi v1 đổi schema.
- **Read-only kỷ luật:** đảm bảo connection `legacy` không bao giờ bị ghi (chỉ dùng cho đọc).
- **Signed media:** key v2 khác v1 → phải tự ký, không tái dùng URL ký của v1.
- **PayOS mới:** cấu hình webhook/redirect theo domain mới.
- **Khối lượng full parity:** làm đúng thứ tự pha để bán được sớm (sau Pha 2) trước khi hoàn thiện hết.

---

## 9. Checklist quyết định

- [x] Hạ tầng: cùng cPanel, cùng MySQL, khác domain.
- [x] Đồng bộ nội dung: đọc trực tiếp db1 realtime (read-only).
- [x] UI: Inertia+Vue (app) + Blade (marketing), design mới.
- [x] Scope: full parity như v1, **bỏ lớp online/Meet** và **bỏ chấm tay**.
- [x] Admin v2: chỉ User + Thanh toán, không CRUD nội dung.
- [x] PayOS: tài khoản mới.
- [x] Chấm: chỉ AI.
- [ ] Tên domain cụ thể (`aptis...`): **chốt sau**.
- [ ] Giá/gói cụ thể: **chốt sau** (đang fake tạm).
