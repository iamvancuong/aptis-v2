# 📌 MILAEDU — TÀI LIỆU BÀN GIAO & TIẾN ĐỘ

> File DUY NHẤT để nắm toàn bộ dự án. Đọc file này là đủ để tiếp tục, không cần chat cũ.
> Cập nhật: 26/07/2026 · Nhánh git: `feature/milaedu-commerce` · **85 test pass**
> Ký hiệu: ✅ xong · 🧪 xong-mới-test-giả-lập · 🔴 chờ bạn · 💡 nên làm · ⬜ tùy chọn
>
> Các batch đã làm: **(A) vá bảo mật** · **(B) thương mại hóa** (PayOS/Zoom/chấm bài/doanh số) ·
> **(C) hiệu năng + SEO + redesign UI trang public** (phiên 26/07 — xem **§10**).

---

## 1. DỰ ÁN LÀ GÌ
Aptis-v2 (Milaedu) — nền tảng luyện thi Aptis (Laravel 12 + PHP 8.2, dev SQLite, production MySQL cPanel).
Domain thật: **https://milaedu.com**.

**Đang ở giai đoạn DEV.** Bản production hiện tại vẫn chạy cho khách trên nhánh `main`. Toàn bộ việc mới nằm ở nhánh `feature/milaedu-commerce`. Deploy để sau.

---

## 2. LUỒNG NGHIỆP VỤ (đã build xong)

### Đăng ký & thanh toán (PayOS)
`/register` → chọn gói (2 tuần / 1 tháng) + số lượng (cộng dồn hạn) + email → tạo `Order` (pending) →
trang thanh toán ký (signed URL) → QR PayOS thật → khách chuyển khoản → **kích hoạt** bằng 1 trong:
- **Webhook** `/webhooks/payos` (tức thì — nếu đã đăng ký URL trong dashboard PayOS)
- **Reconcile** `php artisan payos:reconcile` (lưới an toàn, hỏi thẳng PayOS, cron mỗi 2 phút)
→ Fulfillment: tạo tài khoản mới (mật khẩu mặc định `12345678`, buộc đổi lần đầu) **hoặc gia hạn cộng dồn** nếu email đã tồn tại → gửi email thông tin tài khoản.
- Đăng ký **dedupe** đơn pending trùng (email+gói+SL+amount trong 2h) — chống double-submit (§10 #L1b).
- `payment.show` chặn đơn `canceled`/`expired` mở lại (§10 #F4). Route `return`/`cancel` **có chữ ký** (§10 #L2/#L3).

### Buổi hướng dẫn thứ 7 (Zoom)
Chỉ tài khoản **đã mua gói qua PayOS** (có đơn `registration` đã `paid`) mới thấy `/buoi-huong-dan` →
chọn 1 thứ 7 trong hạn → email xác nhận (KHÔNG kèm link). **Trước buổi**, admin bấm "Tạo phòng & gửi"
(hoặc cron `guidance:dispatch`) → tạo 1 phòng Zoom riêng (waiting room + passcode) → gửi `join_url`
cho học viên + `start_url` cho admin (người dạy). **🔴 CHƯA có khóa Zoom — xem §7.**

### Chấm bài
- **Giáo viên chấm tay (tính phí):** bài mock Writing/Speaking → "Thanh toán 99.000đ & gửi chấm" → đơn `grading` → trả tiền → bật `is_grading_requested`. Admin chấm miễn phí.
- **Chấm AI Writing (tự động):** nộp mock/practice Writing → dispatch job `ProcessWritingGrading` (queue `database`). ⚠️ Cần worker chạy — đã thêm vào cron (§10 #P4).

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
> ⚠️ Nếu `.env` local đang để `PRICE_WEEK=2000` (test CK thật) — **nhớ đổi lại 399000** trước khi deploy.

---

## 4. KIẾN TRÚC — FILE QUAN TRỌNG

**Config:** `config/pricing.php` · `config/payos.php` · `config/zoom.php` · `config/guidance.php` · **`config/seo.php`** (mới — SEO tập trung, xem §10)

**Services:**
- `app/Services/PayosService.php` — createPaymentLink (ký HMAC), verifyWebhook, **getPaymentInfo**
- `app/Services/OrderFulfillmentService.php` — tạo/gia hạn tài khoản + email; **idempotent CÓ LOCK** (§10 #L1)
- `app/Services/ZoomService.php` — token S2S OAuth + createMeeting + fake mode
- `app/Services/GuidanceSessionService.php` — activateAndSend
- `app/Services/AiService.php` — chấm Writing qua OpenAI (timeout 45s)
- `app/Services/QuestionSanitizer.php` — lọc đáp án khỏi payload client
- `app/Jobs/ProcessWritingGrading.php` — chấm AI Writing tự động (queue)

**Controllers:** `PaymentController` (show/return/cancel/**webhook**/devFulfill) · `RegistrationController` · `PracticeController` · `Admin/ReportController` · `Admin/RevenueController` · `Admin/QuestionController` · `Admin/GuidanceSessionController` · `DashboardController` · `HistoryController` · `GuidanceController` · …

**Commands (lên lịch ở `routes/console.php`):** `payos:reconcile` (2 phút) · `guidance:dispatch` (hourly) · **`queue:work --stop-when-empty`** (mỗi phút — §10 #P4)

**Models:** `Order` · `GuidanceBooking` · `GuidanceSession` · `SecurityFlag` · `User` (thêm `mockTests()` relation)

### 🎨 KIẾN TRÚC UI TRANG PUBLIC (phiên 26/07 — chỉ còn 2 layout)
- **`layouts/marketing.blade.php`** — header (logo + nav Trang chủ/Giới thiệu/Luyện thi Aptis/Bảng giá, auth-aware) + footer (có tên giảng viên) + SEO head.
  Dùng cho: **home (`welcome`)**, **`/gioi-thieu`**, **`/luyen-thi-aptis`**, **`/chinh-sach-hoan-tien`**.
- **`layouts/auth.blade.php`** — MỘT CỘT căn giữa (header logo + "Về trang chủ", thẻ trắng nền slate-50, footer nhỏ).
  Dùng cho: **login**, **register**, **payment/pending·return·cancel**.
- **`layouts/guest.blade.php` ĐÃ XÓA** (không còn trang nào dùng).
- **`layouts/app.blade.php`** (khu học viên đã đăng nhập) và **`layouts/admin.blade.php`** — giữ nguyên, chưa đồng bộ tông (việc mở, §11).

**Components:** `x-seo` · `x-favicon` · `x-input` (focus blue) · `x-button` · `x-card` · `x-alert` · `x-table`
**Partials:** `partials/pricing.blade.php` (bảng giá dùng chung) · `partials/structured-data.blade.php` (JSON-LD home)
**Pages:** `pages/gioi-thieu.blade.php` · `pages/luyen-thi-aptis.blade.php`

**Route công khai mới:** `/gioi-thieu` (name `about`) · `/luyen-thi-aptis` (name `aptis`) · `/sitemap.xml` · `/robots.txt` (động)
**Route đáng nhớ:** `/register` · `/thanh-toan/{order}` (signed) · `/thanh-toan/{order}/thanh-cong` & `/huy` (signed) · `/webhooks/payos` (CSRF-excluded) · `/thanh-toan/{order}/gia-lap` (fake) · `/admin/revenue` · `/admin/guidance-sessions`

---

## 5. BẢNG `orders` (nguồn sự thật cho thanh toán + doanh số)
`order_code` (số, chuẩn PayOS) · `email` · `type` (registration|grading) · `package` (week|month) ·
`quantity` · `amount` · `status` (pending|paid|canceled|expired) · `user_id` · `payos_link_id` · `paid_at` · `meta` (json)
> Có index: `order_code`(unique), `email`, `type`, `status`, **`paid_at`** (thêm 26/07).

---

## 6. ENV & CHẾ ĐỘ TEST
```
APP_NAME=Milaedu               # đã set (trước là Laravel)
APP_URL=https://milaedu.com    # đã set — canonical/OG/sitemap phụ thuộc giá trị này
QUEUE_CONNECTION=database       # ⚠️ cần worker chạy → đã có queue:work trong cron

# PayOS (đã có khóa thật)
PAYOS_CLIENT_ID / PAYOS_API_KEY / PAYOS_CHECKSUM_KEY
PAYOS_FAKE=false          # true = nút "giả lập đã thanh toán"
PAYOS_VERIFY_SSL=false    # ⚠️ CHỈ LOCAL. PRODUCTION phải =true/xóa

PRICE_WEEK / PRICE_MONTH / PRICE_GRADING

# SEO (tùy chọn, ghi đè config/seo.php)
SEO_INSTRUCTOR_NAME / SEO_INSTRUCTOR_TITLE / SEO_INSTRUCTOR_BIO  # 🔴 nên điền bio THẬT của cô

# Email: Gmail SMTP (gửi thật OK nhưng hay vào SPAM)
MAIL_* (milaedu.hn@gmail.com)

# Zoom (🔴 CHƯA có khóa)
ZOOM_ACCOUNT_ID / ZOOM_CLIENT_ID / ZOOM_CLIENT_SECRET / ZOOM_HOST_USER / ZOOM_ADMIN_EMAIL
ZOOM_FAKE=true
```
**Test không mất tiền:** `PAYOS_FAKE=true`.
**Test CK thật ở local:** trả tiền → `php artisan payos:reconcile`.
**Chạy nền local (cron + queue):** terminal riêng `php artisan schedule:work`.
**Chạy test:** `php artisan test` (**85 pass**). `phpunit.xml` cô lập khóa PayOS/Zoom.
**Build UI:** `npm run build` (BẮT BUỘC sau khi kéo code — assets hash + font/Alpine bundled).

---

## 7. TRẠNG THÁI TỪNG PHẦN

### ✅ ĐÃ XONG (code + test)
- Bảo mật cốt lõi: lọc đáp án, vá chấm Reading Part 2, signed audio, chống DevTools.
- Thương mại hóa: đăng ký-PayOS · buổi hướng dẫn · chấm bài 99k · doanh số · buộc đổi mật khẩu · không hoàn tiền.
- **Phiên 26/07:** vá logic/bảo mật + hiệu năng + SEO nền tảng + redesign UI public (chi tiết **§10**).

### ✅ ĐÃ TEST THẬT
- CK thật 2.000đ qua PayOS → tài khoản tự tạo + email. PayOS link OK · Email Gmail OK (⚠️ spam).

### 🧪 ZOOM — ĐANG DỞ (việc trước mắt, cần bạn)
Code xong, chạy giả lập. **Cần bạn:**
1. marketplace.zoom.us → Develop → Build App → **Server-to-Server OAuth** → Create.
2. Copy **Account ID / Client ID / Client Secret**.
3. Scopes: `meeting:write:admin` + `user:read:admin`. Activate.
4. Gửi 3 khóa + `ZOOM_HOST_USER` → `.env`, `ZOOM_FAKE=false` → test tạo phòng thật.
   ⚠️ Chưa vá race tạo trùng phòng (guidance:dispatch + nút admin) — nên thêm unique `session_date` + `withoutOverlapping` khi làm tiếp Zoom.

### 🔴 DEPLOY PRODUCTION (để sau)
Backup DB → `.env` production (`APP_URL=https://milaedu.com`, `PAYOS_FAKE=false`, `PAYOS_VERIFY_SSL=true`, `PRICE_WEEK=399000`) →
`composer install` · `php artisan migrate` · **`npm run build`** · `config:cache route:cache view:cache` →
⭐ **Cron `* * * * * php artisan schedule:run`** — giờ CHẠY thêm `queue:work` nên **chấm AI tự động mới hoạt động** (trước đây job nằm chết) →
(tùy chọn) đăng ký webhook PayOS → test 1 giao dịch thật.

### 💡 NÊN LÀM
- Đổi email sang dịch vụ chuyên (SendGrid/SES/Mailgun) + SPF/DKIM.
- Off-page SEO: khai báo `https://milaedu.com/sitemap.xml` trong Google Search Console; Google Business Profile; backlink → mới đẩy được từ khóa "Cô Dung Aptis".

---

## 8. HOUSEKEEPING
- ⚠️ Nhiều file phiên 26/07 **chưa commit** (config/seo, layouts marketing/auth, pages, partials, welcome rewrite, controllers hiệu năng, migration index, console queue, css/js). Nên commit + push nhánh.
- ⚠️ Nếu `.env` còn CHẾ ĐỘ TEST (`PRICE_WEEK=2000`, `PAYOS_VERIFY_SSL=false`, fake flags) — trả lại khi xong test.

---

## 9. QUYẾT ĐỊNH ĐÃ CHỐT (không bàn lại)
- Thanh toán **PayOS** auto (webhook + reconcile). Giá tuyến tính, cộng dồn, KHÔNG giảm giá.
- Chấm bài 99k chỉ cho giáo viên chấm tay; AI credit giữ nguyên.
- Doanh số: 40/30/30 trên đăng ký; chấm bài riêng 100% Cô Dung; bỏ thuế + hóa đơn; thu tiền cá nhân.
- Email trùng = cộng dồn hạn. Không hoàn tiền.
- Buổi hướng dẫn: 1 thứ 7 trong hạn; chỉ tài khoản mua gói; mỗi buổi 1 phòng Zoom riêng.
- **Tên "Cô Dung" KHÔNG phô trương trên web nhưng PHẢI tìm ra web khi search** → đặt tên ở nội dung thật (structured data, trang giới thiệu, footer, meta) — KHÔNG giấu chữ cho bot (cloaking = bị Google phạt).
- **KHÔNG bịa số liệu/review giả** — số liệu & testimonial phải thật (chờ bạn cấp), placeholder ghi rõ.

---

## 10. 📋 PHIÊN 26/07/2026 — LOG CHI TIẾT (bảo mật · hiệu năng · SEO · UI)

### A. Bảo mật / logic (đã sửa + test)
- **#L1 Double-fulfillment (nghiêm trọng):** `OrderFulfillmentService` giờ mở transaction, `lockForUpdate` row đơn (+ user ở nhánh gia hạn), **re-check `isPaid()` trong lock**, email gửi SAU commit. Chống cộng hạn/email 2 lần khi webhook + reconcile chạy song song.
- **#L1b Dedupe đăng ký:** `RegistrationController` tái dùng đơn pending trùng thay vì đẻ đơn mới mỗi POST.
- **#L2/#L3 Ký route return/cancel:** `payment.return` & `payment.cancel` giờ middleware `signed:code,id,cancel,status,orderCode` (bỏ qua query PayOS tự gắn). URL sinh qua `URL::signedRoute`. Chống IDOR/dò đơn.
- **#F1 Lỗi PayOS:** `PaymentController@show` tách 3 trạng thái (`fake`/`unconfigured`/`error`). Lỗi tạm thời báo đúng + nút "Thử lại", bỏ thông báo sai "đang hoàn thiện cổng".
- **#F2** `layouts/app` render `session('info')`. **#F4** `payment.show` chặn đơn canceled/expired.
- **#U1** nút submit đăng ký có loading/disabled (Alpine `submitting`).
- Test mới: signed-return chấp param PayOS · guard canceled · dedupe đăng ký.

### B. Hiệu năng
- **#P1 Index DB** (migration `2026_07_26_000001_add_performance_indexes`, ĐÃ migrate): `attempts`(user+finished_at, user+skill, is_grading_requested) · `mock_tests`(skill+status+score) · `orders`(paid_at).
- **#P2 ReportController:** bỏ N+1 (đếm mock bằng `withCount`, AI usage 1 query gộp), `export()` dùng `chunk(200)` streaming (chống OOM), bỏ lời gọi `index()` thừa.
- **#P3** `whereDate` → `where` datetime (History/Report/Revenue) để index có tác dụng.
- **#P4 ⭐ QUAN TRỌNG:** Job `ProcessWritingGrading` (chấm AI Writing tự động) vào queue `database` nhưng **KHÔNG có worker** → job nằm chết, production không chấm được. Đã thêm `Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping()`. Cũng: giảm timeout `AiService` 90→45s; **sửa bug key mismatch** (`gradeWriting` truyền `question`/`answer` nhưng AiService đọc `question_stem`/`student_answer` → trước đó gửi bài RỖNG cho AI).
- **#P5** `DashboardController` total/avg bằng aggregate query + biểu đồ giới hạn 6 tháng/cột cần; `Admin/QuestionController` `load('sets')` batch.

### C. SEO nền tảng
- **`config/seo.php`** — nguồn tập trung: site_name, title/description mặc định, `keywords` (chứa "cô Dung Aptis"), `instructor`{name,job_title,bio}, contact, og_image. Ghi đè qua env `SEO_*`.
- **`<x-seo>`** — title (không nhân đôi hậu tố), description, keywords, canonical (dựng từ `APP_URL`+path, bỏ query), Open Graph + Twitter, noindex.
- **`<x-favicon>`** + file thật: `favicon.svg`, `favicon.ico` (PNG-in-ICO hợp lệ), png 16/32/48, `apple-touch-icon.png`, `site.webmanifest`, theme-color. **`/images/og-default.png`** 1200×630. (Sinh bằng PHP GD — logo chữ "M" navy.)
- **Structured data JSON-LD:** home = `EducationalOrganization` + `Person`(Cô Dung) + `WebSite`; `/gioi-thieu` = `AboutPage`+`Person`+`Breadcrumb`; `/luyen-thi-aptis` = `FAQPage`.
- **`/sitemap.xml`** (động, 5 URL) + **`/robots.txt`** (động, Sitemap tuyệt đối; ĐÃ xóa `public/robots.txt` tĩnh).
- **Self-host font Be Vietnam Pro** (`@fontsource`) + **bundle Alpine qua Vite** (bỏ CDN Alpine + Google Fonts). `resources/js/app.js` import+start Alpine; `resources/css/app.css` @import font + `--font-sans` + `[x-cloak]` + keyframes `animate-blob` + **`@view-transition{navigation:auto}`** (chuyển trang cross-fade mượt).
- `APP_NAME=Milaedu`, `APP_URL=https://milaedu.com` (set trong `.env` + `.env.example`).

### D. Redesign UI trang public
- **Home (`welcome`) rewrite RÚT GỌN**, extends `layouts.marketing`. Hero **full màn** (`min-h-[calc(100vh-4rem)]`) với mockup "bảng chấm Writing bằng AI" (thẻ cửa sổ — KHÔNG còn badge nổi, KHÔNG khung laptop cũ). Thứ tự: hero → 3 điểm mạnh → **Về giảng viên** (link `/gioi-thieu`, tên Cô Dung) → **bảng giá** (`partials/pricing`) → cảm nhận (grid từ `$feedbacks` take 3) → CTA. **Đã bỏ:** about dài, how-it-works, "Vinh danh chiến thần" 3D, ảnh Unsplash "team giả", số liệu bịa "5k+/100+".
- **Login/Register:** extends `layouts.auth` (một cột, thẻ trắng). Register **giữ nguyên Alpine** (chọn gói/số lượng/tổng tiền realtime, nút loading, preselect `?goi=`), nén cho đỡ scroll (`items-start`).
- **Payment (pending/return/cancel)** → `layouts.auth`. **Policy** → `layouts.marketing`.
- Đồng bộ: `x-input` focus indigo→blue; logo "A"→"M"; title "APTIS Practice"→"Milaedu".
- Home route chỉ còn `$feedbacks` (take 3).

---

## 11. 🔴 CẦN BẠN / VIỆC CÒN MỞ
- **Zoom:** cấp khóa S2S OAuth (xem §7) — việc dở lớn nhất.
- **Nội dung thật (SEO):** bio THẬT của cô (sửa `config/seo.php` hoặc env `SEO_INSTRUCTOR_BIO`) · testimonial + số liệu thật (hiện KHÔNG bịa) · ảnh giảng viên thật (đang dùng ô chữ "D").
- **Off-page SEO:** Google Search Console (submit sitemap) · Google Business Profile · backlink.
- ⬜ **Tùy chọn — Turbo (SPA thật):** chuyển tab không reload cho khu marketing. Chưa bật vì app dùng Alpine dày (form/thanh toán/admin) → cần cấu hình lại vòng đời Alpine + **test trình duyệt thật**. Đã đặt sẵn `data-turbo="false"` ở các link ra ngoài khu marketing. (Hiện dùng View Transitions — mượt, an toàn.)
- ⬜ Đồng bộ tông cho `layouts/app` (khu học viên) + `layouts/admin` cho khớp public.
- ⬜ Rà mobile tổng thể.
