# 📌 MILAEDU — TÀI LIỆU BÀN GIAO & TIẾN ĐỘ

> File DUY NHẤT để nắm toàn bộ dự án. Đọc file này là đủ để tiếp tục, không cần chat cũ.
> Cập nhật: 29/07/2026 · GitHub `main` = `origin/main` (**mọi việc A–K đã gộp + push**) · **90 test pass** · deploy cPanel: xem 🟠 dưới.
> Ký hiệu: ✅ xong · 🧪 xong-mới-test-giả-lập · 🔴 chờ bạn · 💡 nên làm · ⬜ tùy chọn
>
> Các batch đã làm: **(A) vá bảo mật** · **(B) thương mại hóa** (PayOS/chấm bài/doanh số) ·
> **(C) hiệu năng + SEO + redesign UI public** (§10) · **(D) gỡ Zoom + "buổi hướng dẫn"** (§13) ·
> **(E) deploy production + cộng đồng FB + responsive + vá Reading** (§14) ·
> **(F) vá bug thanh toán khi mở lại đơn** (§15) · **(G) mã sale referral + doanh số theo sale** (§17) ·
> **(H) bỏ watermark bài làm** (§18) · **(I) sửa copy bỏ Speaking + vá `&amp;` preview** (§19) ·
> **(J) nhãn Có phí/Miễn phí trang chấm Writing** (§20) · **(K) vá "Chờ chấm" sót bài đã trả phí** (§21).
>
> ▶️ **PRODUCTION** tại **https://milaedu.com** — release **CHỈ BÁN TÀI KHOẢN** (thanh toán → tạo tài khoản luyện thi, KHÔNG có lớp online).
> Deploy = **cPanel AZDIGI Terminal**: `git reset --hard origin/main` (+ `php artisan migrate --force` khi có migration mới; `npm run build` + upload `public/build` khi đổi Tailwind/JS). Quy trình đầy đủ **§14** + `DEPLOY.md`.
>
> 🟠 **GitHub đã có, cần KIỂM TRA & DEPLOY lên milaedu.com (G–K):**
> - **G (mã sale) có MIGRATION `sale_code`** → bắt buộc `php artisan migrate --force`. H–K chỉ Blade/PHP (không migrate/build).
> - 🔴 `config/sales.php` đã điền tên thật (M1 = Nguyệt Anh, M2 = Trinh). 4 link gửi sale: `/dk/M1|M2/thang|tuan`; admin copy ở `/admin/revenue`.
>
> ⏸️ **PENDING (chỉ làm khi bạn nhắc):**
> - **Lớp online Google Meet** (thay Zoom) — đã chốt hướng: gói **Business Plus**, **Pha 0 MVP** dán link thủ công (§16) + kế hoạch §11.
> - **Chấm Nói (Speaking) bằng AI** — đã khảo sát, kế hoạch + chi phí §12.
> - **Chấm Speaking (giáo viên) đang TẠM TẮT trong quảng cáo** (§19). Khi bật lại: thêm "Speaking" vào copy + làm nhãn Có phí/Miễn phí + vá "Chờ chấm" cho `/admin/speaking-reviews` (hiện mới làm cho Writing — §20/§21).

---

## 1. DỰ ÁN LÀ GÌ
Aptis-v2 (Milaedu) — nền tảng luyện thi Aptis (Laravel 12 + PHP 8.2, dev SQLite, production MySQL cPanel).
Domain thật: **https://milaedu.com**.

**PRODUCTION đang chạy cho khách** tại milaedu.com trên nhánh `main`. Việc mới làm trên nhánh ngắn hạn rồi **gộp vào `main`**; production cập nhật bằng cách pull `origin/main` trên cPanel (§14). (Nhánh `feature/milaedu-commerce` cũ đã hết vai trò — mọi thứ đã ở `main`.)

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

### Buổi học online — ⏸️ ĐÃ GỠ (Zoom bỏ hoàn toàn, Meet để sau)
Toàn bộ tính năng "buổi hướng dẫn" (đặt lịch + tạo phòng + gửi link) **đã bị xóa sạch** ở phiên này (§13).
Release hiện tại chỉ bán tài khoản: mua gói → tạo tài khoản luyện thi, **không** có lớp học online.
Khi cần, sẽ dựng lại bằng **Google Meet** (code mới — §11), không khôi phục code Zoom cũ.

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

**Config:** `config/pricing.php` · `config/payos.php` · **`config/seo.php`** (SEO tập trung, §10) · **`config/sales.php`** (mã sale referral M1/M2, §17)
> (Đã xóa `config/zoom.php` + `config/guidance.php` ở §13.)

**Services:**
- `app/Services/PayosService.php` — createPaymentLink (ký HMAC), verifyWebhook, **getPaymentInfo**
- `app/Services/OrderFulfillmentService.php` — tạo/gia hạn tài khoản + email; **idempotent CÓ LOCK** (§10 #L1)
- `app/Services/AiService.php` — chấm Writing qua OpenAI (timeout 45s)
- `app/Services/QuestionSanitizer.php` — lọc đáp án khỏi payload client
- `app/Jobs/ProcessWritingGrading.php` — chấm AI Writing tự động (queue)
> (Đã xóa `ZoomService` + `GuidanceSessionService` ở §13.)

**Controllers:** `PaymentController` (show/return/cancel/**webhook**/devFulfill) · `RegistrationController` (thêm **`referral()`** — link /dk/{sale}, §17) · `PracticeController` · `Admin/ReportController` · `Admin/RevenueController` (thêm **doanh số theo sale**, §17) · `Admin/WritingReviewController` (nhãn Có phí + lọc "Chờ chấm", §20/§21) · `Admin/QuestionController` · `DashboardController` · `HistoryController` · …

**Support:** `app/Support/Sales.php` — resolve/validate mã sale từ `config/sales.php` (§17).
> (Watermark logo+email trên bài làm **đã gỡ** ở §18 — file `partials/watermark.blade.php` đã xóa.)

**Commands (lên lịch ở `routes/console.php`):** `payos:reconcile` (2 phút) · **`queue:work --stop-when-empty`** (mỗi phút — §10 #P4)

**Models:** `Order` · `SecurityFlag` · `User` (thêm `mockTests()` relation)

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

**Route công khai mới:** `/gioi-thieu` (name `about`) · `/luyen-thi-aptis` (name `aptis`) · `/sitemap.xml` · `/robots.txt` (động) · **`/dk/{sale}/{goi?}`** (link giới thiệu sale, §17)
**Route đáng nhớ:** `/register` · `/thanh-toan/{order}` (signed) · `/thanh-toan/{order}/thanh-cong` & `/huy` (signed) · `/webhooks/payos` (CSRF-excluded) · `/thanh-toan/{order}/gia-lap` (fake) · `/admin/revenue`

---

## 5. BẢNG `orders` (nguồn sự thật cho thanh toán + doanh số)
`order_code` (số, chuẩn PayOS) · `email` · `type` (registration|grading) · `package` (week|month) ·
`quantity` · `amount` · `status` (pending|paid|canceled|expired) · `user_id` · **`sale_code`** (mã sale referral, §17) · `payos_link_id` · `paid_at` · `meta` (json)
> Có index: `order_code`(unique), `email`, `type`, `status`, **`paid_at`** (26/07), **`sale_code`** (§17).
> `meta` (json): đơn grading lưu `{attempt_id, skill}`; đơn đăng ký có link PayOS lưu thêm `{checkout_url}` (§15).
> Đơn **grading** = tiền chấm bài 99k: `type=grading`, `meta->attempt_id` trỏ tới bài; trả tiền → bật `is_grading_requested` (§20/§21).

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

# (ĐÃ XÓA toàn bộ ZOOM_* — không còn code nào đọc, xem §13.)
```
**Test không mất tiền:** `PAYOS_FAKE=true`.
**Test CK thật ở local:** trả tiền → `php artisan payos:reconcile`.
**Chạy nền local (cron + queue):** terminal riêng `php artisan schedule:work`.
**Chạy test:** `php artisan test` (**90 pass** — tính tới §21). `phpunit.xml` cô lập khóa PayOS.
**Build UI:** `npm run build` (BẮT BUỘC sau khi kéo code — assets hash + font/Alpine bundled).

---

## 7. TRẠNG THÁI TỪNG PHẦN

### ✅ ĐÃ XONG (code + test)
- Bảo mật cốt lõi: lọc đáp án, vá chấm Reading Part 2, signed audio, chống DevTools.
- Thương mại hóa: đăng ký-PayOS · chấm bài 99k · doanh số · buộc đổi mật khẩu · không hoàn tiền. *(Buổi hướng dẫn đã gỡ ở §13.)*
- **Phiên 26/07:** vá logic/bảo mật + hiệu năng + SEO nền tảng + redesign UI public (**§10**).
- **Phiên 29/07 (F–K), đã push `main`:** vá bug thanh toán mở lại đơn (§15) · mã sale referral + doanh số theo sale (§17) · bỏ watermark bài làm (§18) · bỏ "Speaking" trong copy + vá `&amp;` preview (§19) · nhãn Có phí/Miễn phí (§20) + vá "Chờ chấm" sót bài đã trả phí (§21) trên trang chấm Writing.

### ✅ ĐÃ TEST THẬT
- CK thật 2.000đ qua PayOS → tài khoản tự tạo + email. PayOS link OK · Email Gmail OK (⚠️ spam).

### ⏸️ BUỔI HỌC ONLINE — ĐÃ GỠ (không còn việc dở)
Zoom + tính năng "buổi hướng dẫn" đã xóa sạch (§13). Không còn khóa Zoom cần cấp.
Nếu sau này cần lớp online → dựng lại bằng **Google Meet** (code mới, §11).

### ✅ DEPLOY PRODUCTION — ĐÃ LÊN (28/07, milaedu.com) · quy trình chi tiết ở §14
Backup DB → `.env` production (`APP_URL=https://milaedu.com`, `PAYOS_FAKE=false`, `PAYOS_VERIFY_SSL=true`, `PRICE_WEEK=399000`) →
`composer install` · `php artisan migrate` · **`npm run build`** · `config:cache route:cache view:cache` →
⭐ **Cron `* * * * * php artisan schedule:run`** — chạy `payos:reconcile` + `queue:work` (chấm AI tự động) →
(tùy chọn) đăng ký webhook PayOS → test 1 giao dịch thật.
> ✅ Đã merge + deploy 28/07. Các batch **F–K** (sau đó) đã ở `origin/main` — lần deploy tiếp nhớ `migrate --force` (G có migration). Xem 🟠 đầu file.

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
- ~~Buổi hướng dẫn: 1 thứ 7… phòng Zoom riêng.~~ **← ĐÃ HỦY/GỠ ở §13** (release chỉ bán tài khoản; lớp online sau này dùng Google Meet §16).
- **Mã sale referral** (§17): cứng trong `config/sales.php`, chưa tính hoa hồng, "số người" = số đơn đã thanh toán, 4 link chọn sẵn gói.
- **Chấm Speaking (giáo viên) TẠM TẮT** trong quảng cáo (§19) — copy chỉ nói "chấm chữa Writing" cho tới khi bật lại.
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
- ⏸️ **PENDING — Buổi học online (Google Meet):** Zoom đã gỡ. Khi cần lớp online, code MỚI bằng Google Meet
  (Calendar API tạo link Meet, hoặc link phòng cố định) — không khôi phục code Zoom cũ. Chỉ làm khi bạn nhắc.
- **Nội dung thật (SEO):** bio THẬT của cô (sửa `config/seo.php` hoặc env `SEO_INSTRUCTOR_BIO`) · testimonial + số liệu thật (hiện KHÔNG bịa) · ảnh giảng viên thật (đang dùng ô chữ "D").
- **Off-page SEO:** Google Search Console (submit sitemap) · Google Business Profile · backlink.
- ⬜ **Tùy chọn — Turbo (SPA thật):** chuyển tab không reload cho khu marketing. Chưa bật vì app dùng Alpine dày (form/thanh toán/admin) → cần cấu hình lại vòng đời Alpine + **test trình duyệt thật**. Đã đặt sẵn `data-turbo="false"` ở các link ra ngoài khu marketing. (Hiện dùng View Transitions — mượt, an toàn.)
- ⬜ Đồng bộ tông cho `layouts/app` (khu học viên) + `layouts/admin` cho khớp public.
- ⬜ Rà mobile tổng thể.

---

## 12. 🎤 CHẤM BÀI NÓI BẰNG AI — KHẢO SÁT & KẾ HOẠCH (⏸️ PENDING — chưa code, chỉ làm khi bạn nhắc)

**Kết luận khảo sát (26/07):** Khả thi cao, độ khó trung bình. Cái khó KHÔNG phải kỹ thuật (hạ tầng gần như có sẵn) mà là **độ chính xác chấm phát âm / độ trôi chảy** → định vị điểm AI là **NHÁP tham khảo, giáo viên xác nhận** (đúng triết lý dự án). Về cơ bản = **nhân bản luồng Writing AI + thêm bước xử lý âm thanh**.

### Hạ tầng ĐÃ CÓ (tái dùng — quan trọng)
- OpenAI đã tích hợp: `config/services.php` → `openai.key` = env `OPENAI_API_KEY`. `AiService::gradeWriting` (gpt-4o-mini, JSON output, có mock mode khi thiếu key).
- Queue worker đã chạy (cron `queue:work` — §10 #P4).
- **Audio bài nói đã ghi + lưu sẵn:** client ghi (thường `webm`) → upload field `speaking_audio[qId]` → `store('speaking_attempts','public')` → path (mảng) lưu vào `AttemptAnswer.answer`. Xem `PracticeController` (~173–190) và `MockTestController@submit` (~234–300).
- **Chấm tay đã có:** `Admin/SpeakingReviewController` — mỗi `AttemptAnswer` có `score`(0–10) + `feedback` + `grading_status`; overall % ở `Attempt.score`. View giáo viên: `admin/speaking-reviews/show`; học viên xem: `history/speaking-show`.
- **Credit Speaking AI đã có scaffold sẵn:** cột `users.speaking_ai_reset_version`, `Admin/UserController::resetSpeakingAi`/`resetAllAi`, setting `speaking_grading_limit`. (Chỉ thiếu hàm `User::recordSpeakingAiUsage` — thêm giống `recordWritingAiUsage`.)
- **Mẫu để copy:** `app/Jobs/ProcessWritingGrading.php` (job queue → lưu `ai_metadata` trên `AttemptAnswer`).

### Cách làm (2 bước)
1. **Audio → text:** Whisper (`whisper-1` hoặc `gpt-4o-transcribe`). Rẻ, ≤25MB/file (bài nói ngắn → ok).
2. **Chấm điểm** — chọn theo tham vọng:
   - **(A) GPT-4o-mini chấm transcript** → nội dung/từ vựng/ngữ pháp/mạch lạc. **KHÔNG chấm được phát âm/fluency.** ← MVP.
   - **(B) `gpt-4o-audio` nghe trực tiếp** → thêm phát âm/fluency. Phase 2.
   - **(C) Azure Pronunciation / Speechace / ELSA** → chính xác phát âm nhất, thêm vendor.

### Chi phí (ước tính — VERIFY lại giá OpenAI trước khi làm)
- **Cách A ≈ $0.03/bài (~750đ)** (chủ yếu Whisper). 100 bài ≈ $3 · 500 ≈ $15 · 1.000 ≈ $30 (~750k)/tháng.
- Cách B ≈ $0.1–0.5/bài. Cách C ≈ $0.08/bài.
- Là chi phí **trả theo lượng dùng trên tài khoản OpenAI**, tách khỏi tiền hosting.

### Hosting hiện tại: AZDIGI Premium Business (shared cPanel) — 2 core / 4GB RAM / 30GB NVMe / 21 web / băng thông ∞
**Đủ cho Phase 1** vì phần nặng (AI) chạy trên OpenAI; hosting chỉ nhận file + gọi HTTPS + lưu kết quả. Lưu ý:
- ⚠️ **Phải test outbound HTTPS tới `api.openai.com` 1 lần** (shared host đôi khi chặn/thiếu CA). PayOS gọi ra ngoài OK ở production → khả năng cao OpenAI cũng OK.
- ⚠️ **Cần chính sách dọn file audio cũ** (30GB chia 21 web sẽ đầy dần) — thêm command xóa audio đã chấm sau X ngày.
- ✅ Giữ chấm **async qua queue** (`queue:work --max-time=50` đã hợp giới hạn shared).
- Không self-host Whisper (cần GPU/VPS) — dùng OpenAI API nên không cần. Quy mô lớn hẳn mới cần lên VPS.
- Quyền riêng tư: audio giọng học viên gửi lên OpenAI — cân nhắc/thông báo trong điều khoản.

### Phase 1 — MVP (việc code cụ thể, ~2–4 ngày)
1. `AiService::transcribe($path): string` (Whisper) + `AiService::gradeSpeaking($transcript, $question, $targetLevel): array` (GPT JSON, khung giống `gradeWriting`).
2. Job `ProcessSpeakingGrading` (copy `ProcessWritingGrading`) — dispatch khi nộp mock Speaking (nhánh speaking trong `MockTestController@submit`). Trong job: đọc path audio từ `AttemptAnswer.answer` → transcribe → grade → lưu `ai_metadata`.
3. Hiển thị `ai_metadata` ở `history/speaking-show` (học viên) + **pre-fill form `admin/speaking-reviews/show`** cho giáo viên xác nhận. Mục phát âm/fluency ghi "cần giáo viên xác nhận".
4. `User::recordSpeakingAiUsage()` dùng `speaking_ai_reset_version` (đã có cột).
5. (Nên) command `speaking:cleanup-audio` + lên lịch — dọn 30GB.

### 🔴 QUYẾT ĐỊNH CẦN CHỐT TRƯỚC KHI CODE
1. Bản đầu dùng **(A) transcript** hay **(B) audio-native**? → khuyến nghị **(A)** (rẻ, dễ, đủ hữu ích).
2. **Tính phí** (như chấm tay 99k) hay **miễn phí theo credit** (như Writing AI)?
3. Xác nhận: điểm AI là **nháp tham khảo, giáo viên xác nhận** (không phải điểm cuối chính thức).

> 💡 Gợi ý mở màn phiên mới: viết 1 script test nhỏ gọi Whisper + GPT trên 1 file audio mẫu trong `storage/app/public/speaking_attempts/` để **kiểm chứng outbound HTTPS + chất lượng** trước khi làm đầy đủ.

---

## 13. 🧹 PHIÊN 26/07 (D) — GỠ ZOOM + TÍNH NĂNG "BUỔI HƯỚNG DẪN"

**Mục tiêu:** Release "chỉ bán tài khoản". Bỏ Zoom hoàn toàn; lớp học online để sau (Google Meet, code mới). Sau thanh toán chỉ tạo tài khoản luyện thi, KHÔNG gửi link học.

**Đã xóa (21 file):**
- Zoom: `app/Services/ZoomService.php`, `config/zoom.php`.
- Guidance service/command/controller: `GuidanceSessionService`, `SendGuidanceLinks` (command `guidance:dispatch`), `Admin/GuidanceSessionController`, `GuidanceController`, `config/guidance.php`.
- Mail (3 class + 3 view): `GuidanceBookingMail` / `GuidanceLinkMail` / `GuidanceHostMail` + `resources/views/mail/guidance-{booking,link,host}.blade.php`.
- Models + migrations: `GuidanceBooking`, `GuidanceSession` + `2026_03_06_000400_*` (bookings) + `2026_03_06_000500_*` (sessions).
- Views: `resources/views/guidance/index.blade.php`, `resources/views/admin/guidance-sessions/index.blade.php`.
- Tests: `GuidanceBookingTest`, `GuidanceSessionTest`.

**Đã sửa (9 file):**
- `routes/web.php` — bỏ 2 route `/buoi-huong-dan` (học viên) + 2 route `admin/guidance-sessions` + dòng `Disallow: /buoi-huong-dan` trong robots.txt.
- `routes/console.php` — bỏ `Schedule::command('guidance:dispatch')`.
- `app/Models/User.php` — bỏ `canBookGuidance()`.
- `resources/views/layouts/app.blade.php` + `layouts/admin.blade.php` — bỏ nav "Buổi hướng dẫn".
- `phpunit.xml` — bỏ env `ZOOM_FAKE` + sửa comment; `.env.example` + `.env` — bỏ block `ZOOM_*`.

**Kiểm chứng:** `optimize:clear` OK · `route:list` không còn route guidance · **`php artisan test` = 74 pass** (trước 85; chênh 11 = 2 file test guidance đã xóa) · `npm run build` OK · migrate sạch (test RefreshDatabase pass → không FK mồ côi).

**Bàn giao:** commit trên `feature/milaedu-commerce`, **chưa merge/push** — bạn tự merge vào `main` rồi deploy (checklist §7).

---

## 14. 🚀 PHIÊN 28/07/2026 (E) — DEPLOY PRODUCTION + CỘNG ĐỒNG + RESPONSIVE + VÁ BUG READING

### A. ✅ ĐÃ DEPLOY LÊN PRODUCTION (milaedu.com)
Merge `feature/milaedu-commerce` → `main` → chạy thật trên **AZDIGI cPanel**. Quy trình deploy chuẩn của dự án này:
- **Hosting:** AZDIGI Premium Business (cPanel). Code ở **`/home/ujxmchhx/repositories/aptis-v2`**. Remote GitHub: `github.com/iamvancuong/aptis-v2`.
- **Cập nhật code:** cPanel → **Terminal** (KHÔNG dùng nút cPanel Git Version Control — hay lỗi "could not contact remote"):
  ```
  cd /home/ujxmchhx/repositories/aptis-v2
  git fetch origin && git reset --hard origin/main
  composer install --no-dev --optimize-autoloader   # khi đổi package
  php artisan migrate --force                        # khi có migration mới
  php artisan storage:link                           # audio bài Nói
  php artisan optimize:clear
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  ```
- ⚠️ **UI (CSS/JS Vite):** `public/build` **bị gitignore → `git pull` KHÔNG mang UI**. Phải `npm run build` ở LOCAL → nén `public/build` → upload + Extract vào `public/` trên cPanel. **CHỈ cần làm bước này khi đổi Tailwind class / JS trong `resources/`** — sửa Blade/PHP thuần thì KHÔNG cần build.
  - ⚠️ Lỗi `npm` trên Windows (`npm.ps1 ... execution policy`): dùng `npm.cmd run build`, hoặc Git Bash.
- **`.env` production** (khác `.env` local): `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://milaedu.com`, `LOG_LEVEL=error`, DB `ujxmchhx_aptis_v2`@`127.0.0.1`, PayOS keys thật, `MAIL_MAILER=smtp` Gmail, `OPENAI_API_KEY` thật. (Local trỏ DB test `ujxmchhx_aptis_test_2026`@`103.221.223.60`.)
- **Runbook đầy đủ:** file **`DEPLOY.md`** ở gốc repo.

### B. ⭐ CRON — QUAN TRỌNG (đã sửa)
Cron cPanel trước đây chạy thẳng `queue:work` → **`payos:reconcile` không bao giờ chạy** → đơn đã trả tiền không tự tạo tài khoản (phải chạy tay). Đã đổi thành **đúng 1 dòng** chạy `schedule:run` (tự lo cả reconcile 2 phút + queue:work mỗi phút):
```
* * * * * /usr/local/bin/ea-php82 /home/ujxmchhx/repositories/aptis-v2/artisan schedule:run >> /dev/null 2>&1
```
> 500 "sau thanh toán" từng gặp = **migration chưa chạy** trên prod → `php artisan migrate --force` là hết. Đơn kẹt cứu bằng `php artisan payos:reconcile` (tạo tài khoản + gửi mail; tài khoản mặc định mật khẩu `12345678`).
> PayOS: web báo `paid` nhưng ngân hàng chưa có tiền = **bình thường** — tiền ở ví PayOS, giải ngân về bank theo chu kỳ (xem my.payos.vn).

### C. 🤝 Section cộng đồng Facebook (trang chủ)
- Thêm section **"Cộng đồng Milaedu"** ở `welcome.blade.php` (giữa "Về giảng viên" và bảng giá) mời vào nhóm FB — **tông sáng** (nền gradient xanh nhạt, thẻ trắng), không phải khối xanh đậm chói.
- Link nhóm ở `config/seo.php` → `contact.facebook` (đổi qua env `SEO_FACEBOOK_GROUP`).
- Dọn 2 chỗ **quảng cáo nhầm tính năng đã gỡ**: feature card + quyền lợi bảng giá "Buổi hướng dẫn 19h30 thứ 7" → "Chấm Writing bằng AI".

### D. 📱 Responsive (iPad/iPhone)
- **Hero** trước ép `min-h-[calc(100vh-4rem)]` → trên iPad **dọc** thừa khoảng trắng rất dài. Đổi `lg:landscape:min-h-[...]`: chỉ full-màn ở **desktop ngang**, còn iPad/iPhone dọc **co theo nội dung**. (Verified 375 / 768 / 1024×1366 / 1280×800, không tràn ngang.)
- Section cộng đồng: `md:grid-cols-2` — 2 cột từ iPad dọc, 1 cột iPhone.

### E. 🐞 VÁ BUG READING (client hiển thị — điểm lưu DB vẫn đúng)
- **Giải thích không hiện (mọi part):** `explanation` là **CỘT** của bảng `questions` (không phải metadata), mà `QuestionSanitizer` chưa bao giờ gửi cột này xuống client. **Fix:** `answerKeyFor()` release cột `explanation` sau khi trả lời → `revealAnswerKey()` merge vào `metadata.explanation` → `_feedback.blade.php` (`currentQuestion.metadata.explanation`) hiển thị. Vẫn KHÔNG lộ lúc load (bảo mật).
- **Part 3/4 "làm đúng báo sai" (verdict tổng):** `submitPart3/4` dùng `correctCount === correct_answers.length`; `.length` = undefined khi `correct_answers` là object → luôn sai. **Fix:** mẫu số = số câu đã trả lời, so `String()===String()`.
- **Part 2 câu đặt đúng vẫn đỏ:** per-slot chấm bằng `slot.originalIndex` (gán từ mảng đã **shuffle** server) → vô nghĩa. **Fix:** so **text** với `metadata.sentences[slotIdx+1]` (thứ tự thật sau reveal), khớp `submitPart2`.
- **Part 2 câu đầu mờ oan:** `x-if="sentences[0]"` — giá trị trống-nhưng-non-null (dấu cách / `<p></p>`) vẫn mờ. **Fix:** helper `p2HasFixedStart()` (strip tag + `&nbsp;` + trim) → chỉ mờ khi Fixed Start có chữ thật.
  - ⚠️ Nếu 1 câu vẫn mờ dù không muốn: ô "Sentence 1 (Fixed Start)" trong admin **thực sự có chữ** → xóa trống ô đó (dữ liệu, không phải code). qid 43/46 (DB test) bị gõ dư tiền tố "0. ".
- **Test:** thêm `PracticeAnswerLeakTest::test_check_endpoint_releases_the_explanation_column` (không lộ lúc load + release qua check). **Tổng 75 pass.**

**File chạm:** `QuestionSanitizer.php`, `resources/views/practice/show.blade.php`, `practice/parts/_feedback.blade.php`, `practice/parts/reading-part2.blade.php`, `welcome.blade.php`, `partials/pricing.blade.php`, `config/seo.php`, `layouts/marketing.blade.php`, `tests/Feature/PracticeAnswerLeakTest.php`. Thêm mới: `DEPLOY.md`.

---

## 15. 🐞 PHIÊN 29/07/2026 (F) — VÁ BUG "Chưa kết nối được cổng thanh toán" KHI MỞ LẠI ĐƠN

### Triệu chứng (khách báo)
Màn "Chưa kết nối được cổng thanh toán… Thử lại" xuất hiện khi:
- Thanh toán → hủy/back → **thanh toán lại**; hoặc
- Vào trang thanh toán → **back** → chọn gói → thanh toán.

### Nguyên nhân gốc
`PaymentController@show` gọi `createPaymentLink()` **MỖI lần** mở trang. **PayOS cấm tạo 2 link cho cùng một `orderCode`** (mã duy nhất vĩnh viễn) → lần gọi thứ 2 bị từ chối → rơi vào nhánh `catch` = `state='error'`. Thông báo "mạng chập chờn" đổ oan cho mạng.
Bị kích hoạt vì: khách **back/đóng tab** khiến đơn vẫn `pending` (đã có link), rồi **dedupe đăng ký** (§10 #L1b) dùng lại đúng đơn đó → `show()` tạo link lần 2 cùng orderCode. Nút **"Thử lại"** trỏ về cùng đơn → lỗi lặp vô hạn.
(Nếu hủy qua **đúng nút Hủy** thì đơn thành `canceled`, đăng ký lại tạo đơn mới → không dính. Thủ phạm là đường back/đóng tab.)

### Cách sửa — nguyên tắc **1 orderCode = 1 link, đã tạo thì TÁI DÙNG**
- `PaymentController@show`: đơn `pending` **đã có `payos_link_id`** → **redirect thẳng link cũ**, KHÔNG gọi tạo mới. Khi tạo link lần đầu → **lưu `checkout_url` vào `meta`** (không cần migration).
- Helper `checkoutUrl(Order)` (ưu tiên URL đã lưu, fallback dựng lại từ `payos_link_id` cho đơn cũ trước bản vá).
- 🛟 **Lưới an toàn `recoverExistingLink()`:** nếu link đã tạo nhưng response mất giữa chừng (đơn chưa kịp lưu id) → PayOS từ chối "orderCode đã tồn tại" ở mọi lần thử → **hỏi lại `getPaymentInfo` lấy `id` (paymentLinkId) rồi tái dựng checkout URL**, tránh kẹt lỗi vĩnh viễn.
- `PayosService::checkoutUrlFor($paymentLinkId)` — dựng `{checkout_base_url}/{id}`.
- `config/payos.php`: thêm `checkout_base_url` (mặc định `https://pay.payos.vn/web/`, override qua env `PAYOS_CHECKOUT_BASE_URL`).

### Kiểm chứng
- Test mới `tests/Feature/PaymentLinkReuseTest.php` (3 ca): (1) mở lại đơn pending → **createPaymentLink chỉ gọi 1 lần**; (2) đơn cũ có link nhưng chưa lưu URL → tái dùng **không gọi API**; (3) tạo mới bị từ chối trùng mã → **tự phục hồi** qua getPaymentInfo. **Tổng 78 pass** (trước 75).
- ⚠️ Bug chỉ tái hiện với **khóa PayOS thật** (`PAYOS_FAKE=false`); `.env` local đang fake sẽ không thấy.
- Edge hiếm (không phải bug này): khách bấm Hủy **ngay trên UI PayOS** (không qua nút hủy của web) → link tái dùng hiện trang "đã hủy" của PayOS. Xử lý sau bằng cách check trạng thái nếu cần.

**File chạm:** `app/Http/Controllers/PaymentController.php`, `app/Services/PayosService.php`, `config/payos.php`. Thêm mới: `tests/Feature/PaymentLinkReuseTest.php`.

**Bàn giao:** commit trên nhánh `fix/payos-duplicate-payment-link` — **chưa merge/push**. Sửa Blade/PHP thuần (không đổi Tailwind/JS) → **deploy KHÔNG cần `npm run build`**: merge vào `main` → trên cPanel Terminal `git fetch origin && git reset --hard origin/main` → `php artisan optimize:clear && config:cache && route:cache && view:cache` (quy trình §14).

---

## 16. 🎥 GHI CHÚ TƯ VẤN — LỚP ONLINE GOOGLE MEET (⏸️ PENDING — chưa code, đã chốt hướng 29/07)
Bổ sung cho §11 (Zoom đã gỡ, làm mới bằng Meet khi cần). Phiên 29/07 đã tư vấn + chốt sơ bộ:
- **Chi phí:** chỉ trả license cho **tài khoản HOST** (Cô Dung ±1 co-host); **học sinh join miễn phí** bằng link, không cần license. Giới hạn người/phòng do **gói của host** quyết định.
- **Gói (đã chốt):** ~300–500 học sinh vào **CÙNG 1 buổi** → **Business Plus (~$22/host·tháng, cap 500 người)**. Standard (150) không đủ. Nên 2 host (~$44/tháng). ⚠️ Trần cứng 500 → nếu vượt phải lên **Enterprise** (1.000).
- **Email học sinh đa số Gmail** → Pha 1 dùng được `accessType=RESTRICTED` (mời đích danh, chống học chui chặt nhất).
- **Chống "học chui":** enforce ở **tầng web Milaedu**, KHÔNG phải Meet — không gửi link trần, chỉ hiện nút "Vào lớp" cho tài khoản **đăng nhập + còn hạn**, trong **khung giờ**; host bật phòng chờ. Chống share tài khoản = bật **1 phiên/tài khoản** qua `LoginSession` sẵn có (Meet không chặn 1 account nhiều thiết bị).
- **Hướng code (đã chọn Pha 0 MVP trước):** bảng `class_sessions` + admin dán link Meet thủ công + route `/lop-hoc/{session}/join` (check hạn+giờ rồi redirect, link không nằm trong HTML). **Pha 1** sau: Google Calendar/Meet REST API (service account + domain-wide delegation) tự sinh phòng + mời email còn hạn + gửi mail. → **Chưa bắt đầu code, chờ bạn nhắc.**

---

## 17. 🏷️ PHIÊN 29/07/2026 (G) — MÃ SALE GIỚI THIỆU (referral attribution)

**Mục tiêu:** 2 bạn sale (M1, M2) mỗi bạn có link để gửi học sinh; đơn mua qua link được **gắn công đúng sale**; nội dung chuyển khoản có mã sale; trang Doanh số có **thống kê theo sale**. Sale **KHÔNG cần đăng nhập** — admin lấy link ở trang Doanh số gửi cho sale.

**Quyết định đã chốt:** chưa tính hoa hồng (chỉ thống kê) · "số người" = **số đơn đã thanh toán** · **4 link chọn sẵn gói** · sale **cứng trong `config/sales.php`** (không có bảng/CRUD).

**Cách hoạt động:**
- **Định nghĩa sale:** `config/sales.php` → `reps` = `['M1' => ['name','active'], ...]`. 🔴 **Cần đổi `name` thành tên thật.** Thêm sale mới = thêm 1 dòng + deploy.
- **4 link gửi sale:** `/dk/{sale}/{goi?}` — `goi` slug tiếng Việt (`thang`→month, `tuan`→week):
  `/dk/M1/thang` · `/dk/M1/tuan` · `/dk/M2/thang` · `/dk/M2/tuan`. Mã sai/`active=false` → bỏ qua, đăng ký bình thường.
- **Gắn mã:** `/dk/...` lưu mã vào **session** → trang đăng ký chọn sẵn gói + input ẩn `sale` → `store()` validate (chỉ nhận mã active) rồi lưu `orders.sale_code`.
- **Nội dung CK:** `description` PayOS = `"Milaedu M1"` khi có sale (giữ ≤25 ký tự). Nguồn quy công là cột `sale_code`; description chỉ để đối soát bằng mắt.
- **Doanh số theo sale** ở `/admin/revenue`: bảng số đơn + doanh thu mỗi sale (đơn đăng ký đã thanh toán) + nhóm "Không qua sale"; kèm **hộp link copy-1-chạm** để admin gửi sale; lịch sử giao dịch thêm nhãn mã sale.

**Kỹ thuật:** helper `App\Support\Sales` (chuẩn hoá mã CHỮ HOA, `resolve/name/active`). Mã sale lưu **chuỗi denormalized** (xoá sale khỏi config vẫn giữ lịch sử; bảng doanh số hiện "(đã ẩn)").

**Kiểm chứng:** `tests/Feature/SaleReferralTest.php` (8 ca: link set session + chọn gói, không phân biệt hoa/thường, mã lạ/tắt bị bỏ, input ẩn, gắn đơn, chặn mã bịa, gộp doanh thu, description có mã). **Tổng 86 pass** (trước 78). Migration `2026_07_29_000001_add_sale_code_to_orders_table` (ĐÃ migrate local).

**File chạm:** thêm `config/sales.php`, `app/Support/Sales.php`, migration, `tests/Feature/SaleReferralTest.php`. Sửa `routes/web.php`, `RegistrationController` (referral+create+store), `resources/views/auth/register.blade.php`, `PaymentController` (description), `app/Models/Order.php` (fillable), `Admin/RevenueController` + `admin/revenue/index.blade.php`.

**Bàn giao:** nhánh `feature/sale-referral` (nối tiếp `fix/payos-duplicate-payment-link`) — **chưa merge/push**. Deploy: cần `php artisan migrate --force` (có migration mới); **không cần `npm run build`** (chỉ Blade/PHP; nút Copy dùng Alpine đã bundle sẵn). 🔴 Nhớ điền tên thật cho M1/M2 trong `config/sales.php`.

---

## 18. 🧽 PHIÊN 29/07/2026 (H) — BỎ WATERMARK (logo + gmail) TRÊN BÀI LÀM
Watermark lát chữ `milaedu.com` + email học viên (mờ, phủ toàn trang) gây rối khi làm bài → **gỡ bỏ**.
- Bỏ `@include('partials.watermark')` ở `resources/views/practice/show.blade.php` và `mock-test/show.blade.php`.
- **Xoá** file `resources/views/partials/watermark.blade.php` (không còn nơi dùng).
- Không đụng logic chấm/điểm. Test vẫn **86 pass**. Chỉ Blade → deploy không cần `npm run build`.
- Nhánh `fix/remove-watermark` (chồng lên `feature/sale-referral`).

---

## 19. ✍️ PHIÊN 29/07/2026 (I) — SỬA COPY: BỎ "SPEAKING" + VÁ LỖI `&amp;` TRÊN PREVIEW CHIA SẺ
**Bối cảnh:** khi chia sẻ link milaedu.com, tiêu đề/preview hiện `Writing &amp; Speaking` (crawler Zalo/Messenger không giải mã entity `&amp;`). Đồng thời **tạm chưa chấm Speaking** nên mọi câu quảng cáo "chấm chữa Speaking" là sai.
- **Cách xử lý:** bỏ hẳn dấu `&` trong copy (trùng luôn với việc bỏ Speaking) → vừa hết `&amp;` vừa đúng thực tế. Đổi mọi claim **"chấm chữa Writing & Speaking / Writing, Speaking"** → **"chấm chữa Writing"**.
- **Giữ nguyên** các chỗ Speaking hợp lệ: mô tả format 4 kỹ năng (Reading/Listening/Writing/Speaking), keyword SEO "Aptis Speaking", mục luyện Speaking (đã bỏ câu "nhận nhận xét"), tính năng admin reset lượt chấm AI.
- **File chạm:** `welcome.blade.php` (title/meta/hero/feature), `pages/gioi-thieu.blade.php` (×4), `pages/luyen-thi-aptis.blade.php` (FAQ×2 + meta + hero + mục Speaking), `partials/pricing.blade.php`, `layouts/marketing.blade.php` (footer), `config/seo.php` (default_description + bio giảng viên).
- Chỉ Blade/PHP → deploy **không cần `npm run build`**. Test **86 pass**.
- 💡 Khi có chấm Speaking trở lại → thêm "Speaking" vào các câu này; tránh viết dấu `&` trong `@section('title')`/`meta_description` (dùng chữ "và" hoặc liệt kê dấu phẩy) để không tái phát `&amp;` trên preview.

---

## 20. 💰 PHIÊN 29/07/2026 (J) — NHÃN "CÓ PHÍ / MIỄN PHÍ" TRÊN TRANG CHẤM WRITING
**Bối cảnh:** `/admin/writing-reviews` trộn bài chấm **có thu phí** (học viên trả 99k) với **dữ liệu cũ / admin chấm miễn phí** → không phân biệt được. Thêm nhãn.
- **Cách xác định:** bài **CÓ PHÍ** = tồn tại `Order` type=`grading`, `status=paid`, `meta->attempt_id` = attempt đó (đơn tạo ở `HistoryController@requestGrading`, meta `{attempt_id, skill}`). Không có = **MIỄN PHÍ** (admin bật cờ trực tiếp hoặc dữ liệu cũ trước khi có thu phí).
- **Controller** `Admin/WritingReviewController@index`: sau phân trang, 1 truy vấn gộp lấy set `attempt_id` đã thanh toán trong trang (`whereIn('meta->attempt_id', $pageIds)` — chạy cả MySQL/SQLite), tránh N+1. Truyền `$paidAttemptIds` (flip để tra O(1)) ra view.
- **View** `admin/writing-reviews/index.blade.php`: thêm cột **"Chấm phí"** với `<x-badge success>💰 Có phí</x-badge>` / `<x-badge default>Miễn phí</x-badge>`. Đơn **pending** (chưa trả) KHÔNG tính là có phí.
- **Test** `WritingReviewPaidBadgeTest` (2 ca). **Tổng 88 pass** (trước 86). Chỉ đọc DB + Blade → deploy **không cần migrate / npm build**.
- 💡 Trang **Speaking** (`/admin/speaking-reviews`) cùng cơ chế (đơn grading skill=speaking) — CHƯA thêm nhãn, làm tương tự khi cần.

---

## 21. 🐞 PHIÊN 29/07/2026 (K) — VÁ BUG: bài ĐÃ TRẢ PHÍ không hiện trong "Chờ chấm" Writing
**Triệu chứng:** học viên thanh toán nhờ chấm → admin mở tab "Chờ chấm" (`/admin/writing-reviews`) KHÔNG thấy bài.
**Nguyên nhân:** vòng đời `grading_status` của answer Writing gồm `pending → ai_graded → graded`, **và `limit_reached`** khi học viên **hết lượt chấm AI** lúc nộp. Bộ lọc "Chờ chấm" cũ chỉ lấy `whereIn(['pending','ai_graded'])` → **bỏ sót `limit_reached`** (thậm chí lọt nhầm sang tab "Đã chấm"). Mà nhóm hết lượt AI chính là nhóm hay **trả phí** nhờ giáo viên chấm → gặp bug nhiều.
**Sửa** (`Admin/WritingReviewController@index`): định nghĩa lại theo hướng chuẩn — "Chờ chấm" = còn ≥1 phần **chưa `graded`** (`grading_status != 'graded' OR NULL`); "Đã chấm" = tất cả đã `graded`. Nay `limit_reached`/`pending`/`ai_graded` đều nằm đúng "Chờ chấm".
**Test** `WritingReviewQueueTest` (2 ca: limit_reached vào Chờ chấm & không ở Đã chấm; graded thì ngược lại). **Tổng 90 pass**. Chỉ PHP → deploy không cần migrate/build.
> ⚠️ Nếu bài vẫn không hiện sau khi trả tiền → kiểm tra đơn đã `paid` chưa (webhook/`payos:reconcile` đã chạy để bật `is_grading_requested`). Đơn kẹt `pending` = vấn đề fulfillment (xem §14B), không phải bộ lọc này.
> 💡 Trang Speaking cùng bộ lọc — nên vá y hệt khi đụng tới.
