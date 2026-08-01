# 🚀 DEPLOY MILAEDU LÊN PRODUCTION (cPanel — milaedu.com)

> Bản release: bán tài khoản luyện thi + lớp online Google Meet Pha 0 (xem `TIEN_DO.md` §23).
> Nhánh code: `feature/milaedu-commerce`. **74 test pass · npm build OK.**
> ⚠️ Production hiện chạy `main` (bản cũ). Deploy = merge `feature` → `main` rồi đẩy lên.

---

## ✅ CHECKLIST NHANH (đọc là làm được)

- [ ] **B0.** Merge `feature/milaedu-commerce` → `main`
- [ ] **B1.** Backup DB production
- [ ] **B2.** Sửa `.env` production đúng theo §1 dưới (🔴 PayOS keys + MAIL + https)
- [ ] **B3.** Kéo code + `composer install`
- [ ] **B4.** `php artisan migrate`
- [ ] **B5.** `php artisan storage:link`
- [ ] **B6.** `npm run build` (hoặc upload thư mục `public/build`)
- [ ] **B7.** `php artisan config:cache route:cache view:cache`
- [ ] **B8.** Cron `* * * * * php artisan schedule:run`
- [ ] **B9.** Smoke test theo §3
- [ ] **B10.** (Sau) Rotate OpenAI key vì đã lộ trong chat

---

## 1. 📋 FILE `.env` PRODUCTION ĐÚNG

> Chép khối này đè lên `.env` trên cPanel. Chỗ `<...>` là **secret — tự điền**, KHÔNG commit file có secret.
> Các giá `PRICE_*` bỏ trống cũng được (code default đã đúng 399000/699000/99000), nhưng ghi rõ cho chắc.

```dotenv
APP_NAME=Milaedu
APP_ENV=production
APP_KEY=<GIỮ NGUYÊN APP_KEY production hiện có>
APP_DEBUG=false
APP_URL=https://milaedu.com          # 🔴 HTTPS, không phải http

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error                      # 🟡 error (không để debug trên prod)

# ─── Database (MySQL cPanel) ───────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujxmchhx_aptis_v2
DB_USERNAME=ujxmchhx_ujxmchhx
DB_PASSWORD=<MẬT KHẨU DB>

# ─── Session / Queue / Cache (đều dùng database) ───────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log

# ─── PayOS (🔴 BẮT BUỘC — thiếu là KHÔNG thanh toán được) ──
PAYOS_CLIENT_ID=<PAYOS_CLIENT_ID>
PAYOS_API_KEY=<PAYOS_API_KEY>
PAYOS_CHECKSUM_KEY=<PAYOS_CHECKSUM_KEY>
# PAYOS_FAKE bỏ trống = false (gọi PayOS thật). PAYOS_VERIFY_SSL bỏ trống = true.

# ─── Giá (bỏ trống cũng đúng nhờ default; ghi cho rõ) ──────
PRICE_WEEK=399000
PRICE_MONTH=699000
PRICE_GRADING=99000

# ─── Email (🔴 BẮT BUỘC — thiếu là khách trả tiền KHÔNG nhận được tài khoản) ──
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=milaedu.hn@gmail.com
MAIL_PASSWORD=<GMAIL APP PASSWORD>   # copy từ .env local (đang [SET])
MAIL_FROM_ADDRESS=milaedu.hn@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# ─── OpenAI (chấm AI Writing) ─────────────────────────────
OPENAI_API_KEY=<OPENAI_API_KEY MỚI SAU KHI ROTATE>

# ─── SEO (tùy chọn, nên điền bio thật của cô) ─────────────
# SEO_INSTRUCTOR_BIO="..."
```

### ⚠️ 3 dòng KHÔNG được sai (đây là lý do bản cũ chưa deploy được)
| Dòng | Nếu sai | Hậu quả |
|---|---|---|
| `PAYOS_CLIENT_ID/API_KEY/CHECKSUM_KEY` **trống** | 🔴 | Không tạo được link → **không ai thanh toán được** |
| `MAIL_MAILER=log` | 🔴 | Email tài khoản chỉ ghi vào log → **khách trả tiền không nhận được đăng nhập** |
| `APP_URL=http://...` | 🔴 | Canonical/OG/sitemap SEO trỏ http · rủi ro chữ ký URL thanh toán |

---

## 2. ⚙️ CÁC LỆNH TRÊN SERVER (theo thứ tự)

```bash
# B0 — trên máy bạn: gộp code rồi push
git checkout main
git merge feature/milaedu-commerce
git push origin main

# B1 — BACKUP DB TRƯỚC (bắt buộc — prod đang có khách thật)
#   Dùng phpMyAdmin > Export, hoặc:
mysqldump -u ujxmchhx_ujxmchhx -p ujxmchhx_aptis_v2 > backup_$(date +%F).sql

# B3 — kéo code + cài package (không cài dev)
git pull origin main
composer install --no-dev --optimize-autoloader

# B4 — chạy migration (thêm bảng orders, index hiệu năng, cột speaking…)
php artisan migrate --force

# B5 — symlink storage (audio bài Nói cần cái này mới phát được)
php artisan storage:link

# B6 — build asset (nếu server có Node; nếu không, build ở local rồi upload public/build)
npm ci && npm run build

# B7 — cache config/route/view cho nhanh
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (nếu vừa đổi .env sau khi cache → chạy lại config:cache, hoặc optimize:clear rồi cache lại)
```

### Cron (cPanel → Cron Jobs) — thêm đúng 1 dòng
```
* * * * * cd /home/ujxmchhx/<đường-dẫn-app> && php artisan schedule:run >> /dev/null 2>&1
```
Cron này tự chạy: `payos:reconcile` (đối soát đơn mỗi 2 phút) + `queue:work` (chấm AI Writing tự động mỗi phút).

---

## 3. 🧪 SMOKE TEST SAU DEPLOY (5 phút)

- [ ] Mở `https://milaedu.com` — trang chủ lên bình thường.
- [ ] `https://milaedu.com/sitemap.xml` và `/robots.txt` — trả về, Sitemap là URL **https**.
- [ ] Vào `/register` → chọn gói → ra trang QR PayOS **thật** (không lỗi "chưa cấu hình").
- [ ] Thanh toán thử 1 đơn nhỏ (hoặc nhờ 1 người) → nhận được **email tài khoản + mật khẩu** (kiểm cả hộp **Spam**).
- [ ] Đăng nhập bằng tài khoản vừa tạo → bị buộc **đổi mật khẩu lần đầu** → vào học được.
- [ ] Admin `/admin/revenue` — thấy đơn vừa trả tiền.
- [ ] (Nếu dùng AI) Nộp 1 bài Writing → sau ~1 phút có kết quả chấm AI (queue chạy).

---

## 4. ↩️ ROLLBACK (nếu hỏng)
```bash
git checkout main
git reset --hard <commit-cũ-trước-merge>
php artisan migrate:rollback   # nếu migration mới gây lỗi
# khôi phục DB từ backup B1 nếu cần
php artisan optimize:clear
```

---

## 5. ℹ️ Đã biết — KHÔNG chặn deploy
- **Email Gmail hay vào Spam** → nhắc khách kiểm Spam. Về lâu dài chuyển SendGrid/SES + SPF/DKIM (TIEN_DO §7 💡).
- **Buổi học online** đã gỡ — bản này chỉ bán tài khoản. Muốn lớp online sau → làm Google Meet (TIEN_DO §11, pending).
- **Chấm bài Nói AI** — pending (TIEN_DO §12).
