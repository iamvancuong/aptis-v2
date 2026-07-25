# 📌 MILAEDU — TÀI LIỆU BÀN GIAO & TIẾN ĐỘ

> File DUY NHẤT để nắm toàn bộ dự án. Đọc file này là đủ để tiếp tục, không cần chat cũ.
> Cập nhật: 22/07/2026 · Nhánh git: `feature/milaedu-commerce` · **82 test pass**
> Ký hiệu: ✅ xong · 🧪 xong-mới-test-giả-lập · 🔴 chờ bạn · 💡 nên làm · ⬜ tùy chọn

---

## 1. DỰ ÁN LÀ GÌ
Aptis-v2 (Milaedu) — nền tảng luyện thi Aptis (Laravel 12 + PHP 8.2, dev SQLite, production MySQL cPanel).
Batch công việc này: **(A) vá bảo mật** + **(B) thương mại hóa** (bán gói học qua PayOS, buổi hướng dẫn Zoom, chấm bài tính phí, màn doanh số).

**Đang ở giai đoạn DEV.** Bản production hiện tại vẫn chạy cho khách trên nhánh `main`. Toàn bộ việc mới nằm ở nhánh `feature/milaedu-commerce`. Deploy để sau.

---

## 2. LUỒNG NGHIỆP VỤ (đã build xong)

### Đăng ký & thanh toán (PayOS)
`/register` → chọn gói (2 tuần / 1 tháng) + số lượng (cộng dồn hạn) + email → tạo `Order` (pending) →
trang thanh toán ký (signed URL) → QR PayOS thật → khách chuyển khoản → **kích hoạt** bằng 1 trong:
- **Webhook** `/webhooks/payos` (tức thì — nếu đã đăng ký URL trong dashboard PayOS)
- **Reconcile** `php artisan payos:reconcile` (lưới an toàn, hỏi thẳng PayOS, cron mỗi 2 phút)
→ Fulfillment: tạo tài khoản mới (mật khẩu mặc định `12345678`, buộc đổi lần đầu) **hoặc gia hạn cộng dồn** nếu email đã tồn tại → gửi email thông tin tài khoản.

### Buổi hướng dẫn thứ 7 (Zoom)
Chỉ tài khoản **đã mua gói qua PayOS** (có đơn `registration` đã `paid`) mới thấy `/buoi-huong-dan` →
chọn 1 thứ 7 trong hạn → email xác nhận (KHÔNG kèm link). **Trước buổi**, admin bấm "Tạo phòng & gửi"
(hoặc cron `guidance:dispatch`) → tạo 1 phòng Zoom riêng (waiting room + passcode) → gửi `join_url`
cho học viên + `start_url` cho admin (người dạy).

### Chấm bài tính phí
Bài mock Writing/Speaking → nút "Thanh toán 99.000đ & gửi chấm" → tạo đơn `grading` → trả tiền →
bật `is_grading_requested` → bài vào hàng đợi giáo viên. Admin chấm miễn phí (bỏ qua thanh toán).

### Doanh số (admin)
`/admin/revenue` — tính từ `orders` đã `paid`: doanh thu ĐĂNG KÝ chia **Cô Dung 40% / Cường 30% / Còn lại 30%**;
doanh thu CHẤM BÀI để **riêng 100% Cô Dung**. Không thuế. Lọc theo ngày + lịch sử.

---

## 3. BẢNG GIÁ (trong `.env`, đọc qua `config/pricing.php`)
| Gói | Biến env | Giá | Hạn |
|---|---|---|---|
| Gói 2 Tuần | `PRICE_WEEK` | 399.000đ | 14 ngày |
| Gói 1 Tháng | `PRICE_MONTH` | 699.000đ | 30 ngày |
| Chấm 1 bài | `PRICE_GRADING` | 99.000đ | — |
> ⚠️ Hiện `.env` local đang để `PRICE_WEEK=2000` (để test CK thật) — **nhớ đổi lại 399000**.

---

## 4. KIẾN TRÚC — FILE QUAN TRỌNG

**Config:** `config/pricing.php` · `config/payos.php` · `config/zoom.php` · `config/guidance.php`

**Services:**
- `app/Services/PayosService.php` — createPaymentLink (ký HMAC), verifyWebhook, **getPaymentInfo** (hỏi trạng thái đơn)
- `app/Services/OrderFulfillmentService.php` — tạo/gia hạn tài khoản + email (registration); bật cờ chấm (grading); **idempotent**
- `app/Services/ZoomService.php` — token S2S OAuth + createMeeting + fake mode
- `app/Services/GuidanceSessionService.php` — activateAndSend: tạo phòng + gửi email học viên/admin
- `app/Services/QuestionSanitizer.php` — lọc đáp án khỏi payload client

**Controllers:** `PaymentController` (show/return/cancel/**webhook**/devFulfill) · `RegistrationController` · `Admin/RevenueController` · `Admin/GuidanceSessionController` · `GuidanceController` · `PasswordChangeController` · `Admin/SecurityFlagController` · `SecurityController` · `MediaController`

**Commands:** `payos:reconcile` (ReconcilePayos) · `guidance:dispatch` (SendGuidanceLinks) — cả 2 đã lên lịch trong `routes/console.php`

**Models:** `Order` · `GuidanceBooking` · `GuidanceSession` · `SecurityFlag` · (User thêm cột `must_change_password`, `devtools_guard_disabled`)

**Emails (`app/Mail/`):** `AccountCredentialsMail` · `GuidanceBookingMail` · `GuidanceLinkMail` · `GuidanceHostMail`

**Middleware mới:** `MustChangePassword` (ép đổi mật khẩu lần đầu — đăng ký ở `bootstrap/app.php`)

**Migration mới (6):** `orders`, `users.must_change_password`, `users.devtools_guard_disabled`, `security_flags`, `guidance_bookings`, `guidance_sessions`

**Route đáng nhớ:** `/register` · `/thanh-toan/{order}` (signed) · `/webhooks/payos` (CSRF-excluded) · `/thanh-toan/{order}/gia-lap` (fake) · `/buoi-huong-dan` · `/doi-mat-khau` · `/chinh-sach-hoan-tien` · `/admin/revenue` · `/admin/guidance-sessions` · `/admin/security-flags`

---

## 5. BẢNG `orders` (nguồn sự thật cho thanh toán + doanh số)
`order_code` (số, chuẩn PayOS orderCode) · `email` · `type` (registration|grading) · `package` (week|month) ·
`quantity` · `amount` · `status` (pending|paid|canceled|expired) · `user_id` · `payos_link_id` · `paid_at` · `meta` (json: attempt_id, skill…)

---

## 6. ENV & CHẾ ĐỘ TEST
```
# PayOS (đã có khóa thật)
PAYOS_CLIENT_ID / PAYOS_API_KEY / PAYOS_CHECKSUM_KEY
PAYOS_FAKE=false          # true = hiện nút "giả lập đã thanh toán" (không mất tiền)
PAYOS_VERIFY_SSL=false    # ⚠️ CHỈ LOCAL (Windows thiếu CA). PRODUCTION phải =true/xóa

# Giá
PRICE_WEEK / PRICE_MONTH / PRICE_GRADING

# Email: Gmail SMTP (đã cấu hình, gửi thật OK nhưng hay vào SPAM)
MAIL_* (milaedu.hn@gmail.com)

# Zoom (CHƯA có khóa — đang chờ)
ZOOM_ACCOUNT_ID / ZOOM_CLIENT_ID / ZOOM_CLIENT_SECRET / ZOOM_HOST_USER / ZOOM_ADMIN_EMAIL
ZOOM_FAKE=true            # true = tạo link Zoom giả để test
```
**Test không mất tiền:** `PAYOS_FAKE=true` → nút "Giả lập đã thanh toán".
**Test CK thật ở local KHÔNG cần ngrok:** trả tiền → `php artisan payos:reconcile` là tạo tài khoản.
**Tự động ở local:** mở terminal riêng chạy `php artisan schedule:work`.
**Chạy test:** `php artisan test` (82 pass). `phpunit.xml` đã cô lập khóa PayOS/Zoom.

---

## 7. TRẠNG THÁI TỪNG PHẦN

### ✅ ĐÃ XONG (code + test)
- Bảo mật: lọc đáp án (QuestionSanitizer), vá chấm điểm Reading Part 2, watermark, signed audio URL, chống DevTools (flag + admin duyệt + công tắc + miễn trừ)
- P0 nền dữ liệu · P1 home mới · P2 đăng ký-trả phí PayOS · P3 buổi hướng dẫn · P4 chấm bài 99k · P5 doanh số · P6 buộc đổi mật khẩu + signed URL + không hoàn tiền
- Lưới an toàn `payos:reconcile`

### ✅ ĐÃ TEST THẬT
- Chuyển khoản thật 2.000đ qua PayOS → tài khoản tự tạo (`vancuongit2021@gmail.com`) + email gửi
- PayOS tạo link OK · Email Gmail gửi OK (⚠️ vào spam)

### 🧪 ZOOM — ĐANG DỞ (việc trước mắt)
Code xong, chạy giả lập. **Cần bạn:**
1. Vào marketplace.zoom.us (đã có Zoom Pro) → Develop → Build App → **Server-to-Server OAuth** → Create
2. Tab App Credentials: copy **Account ID / Client ID / Client Secret**
3. Tab Information: điền đủ. Tab Scopes: thêm `meeting:write:admin` + `user:read:admin`. Tab Activation: **Activate**
4. Gửi 3 khóa + `ZOOM_HOST_USER` (email zoom giáo viên) → dán vào `.env`, `ZOOM_FAKE=false` → test tạo phòng thật
   (Zoom API cũng dính lỗi SSL trên Windows local như PayOS → thêm toggle verify SSL giống PayOS khi test.)

### 🔴 DEPLOY PRODUCTION (để sau)
Backup DB → `.env` production (`APP_URL`, `PAYOS_FAKE=false`, `PAYOS_VERIFY_SSL=true`) →
`composer install` · `migrate` · `npm run build` · `config:cache` →
⭐ **Cron `* * * * * php artisan schedule:run`** (tự kích hoạt đơn + gửi Zoom) →
(tùy chọn) đăng ký webhook PayOS `https://<domain>/webhooks/payos` → test 1 giao dịch thật.

### 💡 NÊN LÀM
- Đổi email sang **dịch vụ chuyên** (SendGrid/SES/Mailgun) + SPF/DKIM — Gmail SMTP hay vào spam.

### ⬜ TÙY CHỌN
- Nút "kích hoạt tay" trong admin (ca khách trả thiếu) · biên lai email · xóa tài khoản cũ vĩnh viễn

### 🟡 QUYẾT ĐỊNH TRƯỚC LAUNCH
- Công tắc chặn DevTools bật/tắt lúc đầu (nên tắt) · thông báo khách việc chấm bài giờ tính 99k

---

## 8. HOUSEKEEPING
- ⚠️ **~30 file chưa commit** (P7 Zoom + reconcile + UI đăng ký + fake mode). Nên commit + push nhánh.
- ⚠️ `.env` đang ở CHẾ ĐỘ TEST: `PRICE_WEEK=2000`, `PAYOS_VERIFY_SSL=false`, `PAYOS_FAKE`/`ZOOM_FAKE` — nhớ trả lại khi xong test.

---

## 9. QUYẾT ĐỊNH ĐÃ CHỐT (không bàn lại)
- Thanh toán **PayOS** auto (webhook + reconcile). Giá tuyến tính, cộng dồn, KHÔNG giảm giá.
- Chấm bài 99k chỉ cho giáo viên chấm tay; AI credit giữ nguyên.
- Doanh số: 40/30/30 trên đăng ký; chấm bài riêng 100% Cô Dung; **bỏ thuế + hóa đơn điện tử**; thu tiền cá nhân.
- Email trùng = cộng dồn hạn. Tài khoản cũ giữ nguyên (sau xóa bớt).
- Buổi hướng dẫn: 1 thứ 7 trong hạn; chỉ tài khoản mua gói; gửi link TRƯỚC buổi; mỗi buổi 1 phòng Zoom riêng.
- Không hoàn tiền.
