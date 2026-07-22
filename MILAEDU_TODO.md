# Milaedu — Trạng thái thương mại hóa

> Cập nhật: sau P6. Toàn bộ **73 test pass**. Đánh dấu `[x]` = xong, `[ ]` = chưa.

---

## ✅ ĐÃ LÀM (P0 → P6)

### P0 — Nền tảng dữ liệu
- [x] `config/pricing.php` — giá tuyến tính: Tuần 150k, Tháng 500k, chấm bài 100k, tỉ lệ chia 40/30/30.
- [x] `config/payos.php` + `.env` (khối PAYOS, để trống chờ khóa).
- [x] Bảng `orders` (nguồn sự thật cho đăng ký + chấm bài + doanh số) + model `Order`.
- [x] Cột `users.must_change_password`.

### P1 — Trang chủ
- [x] Gỡ gói "Miễn phí" + mọi chữ "miễn phí".
- [x] Gỡ Zalo khỏi trang chủ.
- [x] Bảng giá mới đọc từ config (Tuần/Tháng, chọn số lượng).
- [x] Trang chọn gói `/register` + CTA dẫn tới đăng ký-trả phí.

### P2 — Đăng ký trả phí (auto PayOS)
- [x] `PayosService`: tạo payment link (ký HMAC) + verify webhook.
- [x] Tạo `Order` pending → trang thanh toán → return/cancel.
- [x] Webhook: chỉ fulfill khi chữ ký đúng + đúng số tiền.
- [x] Fulfillment idempotent: tạo tài khoản (mật khẩu `12345678` + buộc đổi) hoặc **gia hạn cộng dồn** nếu email đã có.
- [x] Email thông tin tài khoản (`AccountCredentialsMail`).
- [x] **Gmail SMTP đã cấu hình + gửi thử thành công.**

### P3 — Buổi hướng dẫn thứ 7
- [x] Bảng `guidance_bookings` + config (giờ 19h30, link Zoom **giả**).
- [x] Chọn 1 thứ 7 trong hạn → lưu + gửi email link Zoom.
- [x] **Chỉ tài khoản đã mua gói qua PayOS** (có đơn `registration` đã `paid`) mới thấy;
      tài khoản cũ — kể cả admin đặt hạn tay — bị ẩn.

### P4 — Thanh toán 100k chấm bài
- [x] `request-grading`: học viên tạo đơn `grading` 100k → PayOS; admin chấm miễn phí.
- [x] Webhook trả xong → bật `is_grading_requested` → bài vào hàng đợi giáo viên.
- [x] UI nút "Thanh toán 100.000đ & gửi chấm".

### P5 — Doanh số (admin)
- [x] Tính từ `orders` đã thanh toán: Tổng · đăng ký chia 40/30/30 · **chấm bài riêng 100% Cô Dung**.
- [x] Không thuế. Bộ lọc theo ngày + lịch sử giao dịch. Link sidebar "Doanh số".

### P6 — Hoàn thiện bảo mật & pháp lý
- [x] **Buộc đổi mật khẩu lần đầu** (middleware `MustChangePassword` + màn `/doi-mat-khau`).
- [x] **Signed URL** trang thanh toán (chống dò đơn người khác).
- [x] **Chính sách không hoàn tiền** (`/chinh-sach-hoan-tien`), link ở footer + đăng ký + thanh toán.

---

## 🔴 ĐANG CHỜ BẠN (chặn chạy thật đầu-cuối)
- [x] **3 khóa PayOS đã cắm vào `.env`** — đã test tạo link thật thành công (code 00). ✔
- [x] Giá đưa vào env: `PRICE_WEEK=199999`, `PRICE_MONTH=499000`, `PRICE_GRADING=99000`. ✔
- [ ] **Khai báo webhook** trong dashboard PayOS: `https://<domain>/webhooks/payos`
- [ ] Đặt `APP_URL=https://<domain>` trong `.env` production (để returnUrl/cancelUrl + signed URL đúng).
- [ ] Xác minh **kỳ đối soát tiền về (T+?)** với PayOS (1900 8144) — không ảnh hưởng code.
- [ ] Production: đảm bảo **queue worker** đang chạy (email + fulfillment gửi qua queue).
- [ ] **Test đầu-cuối thật** với PayOS: đăng ký → trả tiền → nhận email → đổi mật khẩu.
- ⚠️ Lưu ý: **local Windows** bị lỗi SSL khi gọi PayOS (thiếu CA bundle) — test thật làm trên
      **production/staging** (webhook cũng cần domain công khai). Production Linux/cPanel không bị.

---

## ⬜ CẦN LÀM (chưa làm)

### P7 — Zoom tự động (để cuối, theo yêu cầu)
- [ ] Sinh mã phòng Zoom riêng theo từng ngày (chống học chui).
- [ ] Gán & gửi user đúng nhóm đặt buổi đó; admin quản lý.
- [ ] Thay link Zoom giả trong `config/guidance.php` bằng phòng thật.

### P6 — tùy chọn (chưa gấp)
- [ ] Biên lai email sau thanh toán.
- [ ] Nút "đánh dấu đã thu" thủ công trong admin (dự phòng khi webhook lỗi).

### Vận hành
- [ ] Sau này **xóa bớt tài khoản cũ vĩnh viễn** (theo yêu cầu) — làm thủ công khi cần.

---

## ⚪ QUYẾT ĐỊNH ĐÃ CHỐT (tham chiếu)
- Thanh toán: **PayOS** auto (webhook).
- Giá tuyến tính: Tuần 150k × n · Tháng 500k × n (KHÔNG giảm giá).
- Chấm bài 100k: chỉ cho giáo viên chấm tay; AI credit giữ nguyên.
- Doanh số: chia 40/30/30 trên doanh thu ĐĂNG KÝ; chấm bài riêng 100% Cô Dung; **bỏ thuế & hóa đơn điện tử**; thu tiền cá nhân.
- Email trùng = cộng dồn hạn (không tạo trùng). Tài khoản cũ giữ nguyên.
- Buổi hướng dẫn: chọn 1 thứ 7 trong hạn; chỉ tài khoản mua gói qua PayOS mới có.
- Không hoàn tiền.

---

## 🧪 TEST KHÔNG MẤT TIỀN (local)
- Đặt `PAYOS_FAKE=true` trong `.env` (đã bật sẵn ở local) → `php artisan config:clear`.
- Vào `/register` → chọn gói → email → "Tiếp tục thanh toán" → trang thanh toán hiện nút
  **"Giả lập đã thanh toán ✓"** → bấm → tạo tài khoản + gửi email THẬT + về trang cảm ơn.
- ⚠️ **PRODUCTION phải để `PAYOS_FAKE=false` (hoặc xóa dòng đó)** — nếu bật, route giả lập
  cho phép kích hoạt không cần trả tiền. Route tự chặn (404) khi cờ tắt.
- Đã chạy demo: đơn 699k → tài khoản `milaedu.hn@gmail.com` (mật khẩu `12345678`, buộc đổi).

## 🔧 GHI CHÚ KỸ THUẬT
- Email đang dùng **Gmail SMTP** (`.env`, đã gitignore).
- Webhook PayOS đã loại trừ CSRF (`bootstrap/app.php`).
- Tài khoản mới có `must_change_password=true` → bị ép đổi mật khẩu ngay lần đầu.
- Production: **MySQL cPanel** (dev đang SQLite). Migrations tương thích cả hai.
