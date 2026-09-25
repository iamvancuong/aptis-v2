# Hướng dẫn test: Tra từ bằng AI + Sổ tay từ vựng

Nhánh: `feat/vocab-ai-lookup` · Chi tiết kỹ thuật: `TIEN_DO.md` §33

```bash
git checkout feat/vocab-ai-lookup
```

> **Trạng thái hiện tại trên máy này (đã chuẩn bị sẵn — Đường B):**
> `.env` đang trỏ vào `database/local-test.sqlite`, DB đó đã migrate + nạp bài demo xong,
> assets đã build, `OPENAI_API_KEY` đã cắm (AI trả nghĩa thật, không còn giả lập).
> Chỉ cần `php artisan serve --host=127.0.0.1` rồi vào http://127.0.0.1:8000/practice/1.
> Đăng nhập `hocvien@example.test` / `12345678`, hoặc `admin@example.test` / `12345678` cho `/admin`.
> (Ngày 25/09 `.env` đã được trả về MySQL test từ xa — muốn chạy SQLite thì xem Đường B.)
>
> ⚠️ Đừng ghi đường dẫn Windows có dấu `\` vào file `.md` ở thư mục gốc: Tailwind v4 quét cả
> các file này, đọc `\987c…` thành mã ký tự CSS và `npm run build` hỏng với lỗi
> "Invalid code point". Dùng `/` thay cho `\`.
> **Nếu trang load chậm, đọc mục "Chạy local mà LAG" ở cuối file.**

Chọn **một** trong hai đường dưới. Đọc phần "Khác nhau chỗ nào" trước khi chọn.

| | Đường A — DB test từ xa | Đường B — SQLite thuần local |
|---|---|---|
| Bài đọc để thử | **Đề thật** (65 bộ, 557 câu) | 1 bài demo do seeder tạo |
| Có đụng vào DB dùng chung không | **Có** — thêm 3 bảng mới | Không |
| Ai khác bị ảnh hưởng | Người khác cũng dùng DB test đó | Không ai |
| Hợp khi nào | Muốn xem cảm giác thật trên đề thật | Chỉ muốn xem tính năng chạy đúng |

> **Đường A thêm 3 bảng MỚI (`ai_lookups`, `vocabulary_items`, `vocab_lookup_usages`),
> không sửa và không xoá bảng nào đang có.** Muốn gỡ: xem mục *Gỡ ra* ở cuối file.

---

## Đường A — test trên DB test từ xa (đề thật)

`.env` đang trỏ sẵn vào đó rồi, không phải sửa gì.

```bash
php artisan migrate
```

```bash
npm run build
```

```bash
php artisan serve --host=127.0.0.1
```

Mở http://127.0.0.1:8000 và **đăng nhập bằng tài khoản anh vẫn dùng trên DB test**.

> Nếu tài khoản đó là **admin** thì không bị trừ lượt tra — muốn thử phần hạn mức
> phải đăng nhập bằng tài khoản học viên thường.

---

## Đường B — test thuần local, không đụng DB nào khác

**B1.** Tạo file DB rỗng:

```bash
if (-not (Test-Path database\local-test.sqlite)) { New-Item -ItemType File database\local-test.sqlite }
```

**B2.** Mở `.env`, sửa khối `DB_` thành đúng 2 dòng này (nhớ **sao lưu `.env` trước**,
trong đó có mật khẩu DB và khoá PayOS):

```
DB_CONNECTION=sqlite
DB_DATABASE=C:/Cuong/01_coding/aptis-v2/database/local-test.sqlite
```

Bốn dòng `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` thì thêm dấu `#` ở đầu để tắt.

**B3.** Dựng bảng + nạp bài demo — chạy lần lượt 3 lệnh:

```bash
php artisan config:clear
```

```bash
php artisan migrate --force
```

```bash
php artisan db:seed --class=VocabDemoSeeder --force
```

**B4.** Build rồi chạy:

```bash
npm run build
```

```bash
php artisan serve --host=127.0.0.1
```

Hai tài khoản seeder tạo sẵn (đều mật khẩu `12345678`):

| Tài khoản | Vai trò | Dùng để |
|---|---|---|
| `hocvien@example.test` | học viên | Test tra từ, sổ tay, ôn tập, **và hạn mức lượt/ngày** |
| `admin@example.test` | admin | Vào `/admin`. ⚠️ Admin **không bị trừ lượt tra** nên không thử được hạn mức |

Vào http://127.0.0.1:8000/practice/1

> Test xong nhớ **khôi phục lại `.env`** từ bản sao lưu, nếu không lần sau chạy vẫn
> đang trỏ vào SQLite cục bộ.

---

## Muốn xem chất lượng AI thật

Chưa có `OPENAI_API_KEY` thì hệ thống chạy **chế độ giả lập**: popup vẫn hiện đầy đủ
nhưng nghĩa trả về là chữ `[Nghĩa giả lập] …`. Đủ để xem giao diện, **không** đủ để
đánh giá chất lượng dịch.

Muốn xem thật, thêm vào `.env`:

```
OPENAI_API_KEY=sk-...
```

rồi `php artisan config:clear` và chạy lại.

Chi phí: khoảng **3–4 VNĐ mỗi lượt tra** không trúng kho đệm. Tra thử 50 từ hết
chưa tới 200đ. **Nên tra thử ~20 từ trong đề thật** rồi tự chấm xem nghĩa có đúng
ngữ cảnh không — đây là thứ quyết định có bán được tính năng này hay không.

---

## Kịch bản test — bấm theo thứ tự này

### 1. Tra một từ đơn
Vào trang luyện tập Reading. **Bôi đen** một từ khó trong bài (ví dụ `indispensable`)
→ nút tím **"Tra từ"** hiện ngay trên chữ vừa bôi → bấm.

Cần thấy: nghĩa tiếng Việt, loại từ, phiên âm, nhãn CEFR, câu ví dụ kèm bản dịch.

### 2. Tra cụm từ và cả câu
Bôi **từ 2 chữ trở lên** → bấm "Tra từ". Thử cả hai loại:

- Câu tự do, ví dụ `I cycle to work` → phải ra **"Tôi đạp xe đi làm"** (cả câu), loại từ
  "câu", không phiên âm, có ghi chú ngữ pháp. *Lỗi cũ: chỉ ra "đạp xe".*
- Cụm cố định, ví dụ `give up`, `in order to`, `take part in` → nghĩa của **cả cụm**,
  loại "cụm động từ"/"thành ngữ", có phiên âm cả cụm và câu ví dụ.

### 3. Nghĩa có theo ngữ cảnh không (điểm bán hàng)
Tìm một từ nhiều nghĩa trong bài — ví dụ `charge`, `figure`, `run`, `address` — rồi
so nghĩa AI trả về với nghĩa trong câu. Đây chính là chỗ hơn Google Dịch; nếu chỗ
này sai thì báo lại để tôi chỉnh prompt.

### 4. Lưu vào thư mục và xem sổ tay
Trong popup, ô **"Lưu vào"**: để *Chỉ xếp theo loại từ*, hoặc chọn **+ Thư mục mới…**,
gõ tên (vd `Môi trường`) → **Tạo** → **Lưu từ**. Lưu thêm vài từ khác loại (danh từ,
động từ, một câu).

Vào menu **"Từ vựng"**. Cần thấy:
- Cột trái: **Theo loại từ** (Danh từ / Động từ / Câu… kèm số từ, tự xếp) và
  **Thư mục của tôi**. Bấm từng mục thì danh sách lọc đúng.
- Mỗi thẻ có ô **📁** ở chân để chuyển sang thư mục khác.
- Trong một thư mục: nút đổi tên / xoá thư mục (xoá thư mục **không** xoá từ bên trong).
- Tra lại một từ đã lưu: ô "Lưu vào" phải hiện đúng thư mục đang chứa từ đó.

### 5. Ôn tập kiểu Anki
Bấm **"Ôn tập ngay"** (hoặc **"Ôn thư mục này"** khi đang ở trong một thư mục) →
"Xem nghĩa" (hoặc phím cách). Dưới mỗi nút ghi **lần gặp lại**; phím 1/2/3 = Quên/Mơ hồ/Nhớ.

Với từ mới, bấm **Nhớ** liên tục thì nhãn phải đi đúng thang
**5 phút → 10 phút → 1 giờ → 1 ngày**. Quên luôn là **1 phút**; Mơ hồ lặp lại bước hiện tại.

Cần thấy:
- Thẻ hẹn trong ≤ 20 phút thì **quay lại trong chính phiên đó**. Hết thẻ khác thì hiện màn
  **"Nghỉ một chút"** đếm ngược, có nút *Ôn luôn không chờ*.
- Bước 1 giờ thì rời phiên — vào lại sổ tay thấy "Đang học · bước 4/4".
- Từ đã ôn theo ngày: Nhớ thì khoảng cách nhân ~2,5 (1 → 3 → 7 → 18 ngày…);
  **Quên thì reset về 1 phút** và học lại từ đầu. Khoảng cách ≥ 21 ngày = "Đã thuộc".

### 6. Thi thử KHÔNG tra được từ (quan trọng)
Vào một bài **thi thử** (`/mock-test/...`), thử bôi chữ trong bài.

Cần thấy: **không bôi được / không có nút "Tra từ" nào hiện ra**. Đây là ràng buộc
cố ý — tra được từ lúc thi thì điểm Reading mất hết ý nghĩa.

### 7. Kho đệm có ăn không (tiết kiệm tiền)
Tra một từ, rồi **tra lại đúng từ đó** (hoặc đăng nhập tài khoản khác rồi tra).
Lần hai phải ra kết quả **gần như tức thì**.

Kiểm chứng bằng số:

```bash
php artisan tinker --execute="echo 'so tu da dem=' . App\Models\AiLookup::count() . ' | tong luot trung dem=' . App\Models\AiLookup::sum('hit_count');"
```

```bash
php artisan tinker --execute="dump(App\Models\VocabLookupUsage::select('user_id','usage_date','count','api_calls')->get()->toArray());"
```

`count` là số lượt tra, `api_calls` là số lần **thật sự tốn tiền**. Chênh lệch càng
lớn thì kho đệm càng có giá trị.

### 8. Hết lượt trong ngày
Hạ hạn mức xuống cho dễ thử — thêm vào `.env`:

```
VOCAB_DAILY_LIMIT=3
```

rồi `php artisan config:clear`, tra 4 từ khác nhau. Từ thứ 4 phải báo
*"Bạn đã dùng hết lượt tra từ hôm nay."*

Thử xong nhớ đổi lại `VOCAB_DAILY_LIMIT=60`.

### 9. Công tắc tắt nóng
Thêm `VOCAB_LOOKUP_ENABLED=false` vào `.env`, chạy `php artisan config:clear`.

Cần thấy: menu "Từ vựng" biến mất, bôi chữ không ra nút nào, vào thẳng
`/tu-vung` thì **404**.

### 10. Trên điện thoại
Mở bằng điện thoại cùng mạng Wi-Fi:

```bash
php artisan serve --host=0.0.0.0
```

rồi vào `http://<IP-máy-anh>:8000`. **Giữ lâu** vào một chữ để bôi chọn.

Chỗ này đáng thử kỹ nhất — thao tác bôi chọn trên iOS khó hơn trên máy tính nhiều.
Nút "Tra từ" phải hiện đúng vị trí và không bị thanh công cụ của iOS che.

---

## Chạy local mà LAG / không vào được

Đo trên chính máy này, có **ba** nguyên nhân độc lập cộng dồn. Không liên quan gì
tới tính năng tra từ — là môi trường dev.

### 1. `.env` trỏ vào MySQL từ xa (nặng nhất)

```
DB_HOST=103.221.223.60
```

Số đo thật:

| | Kết nối lần đầu | Mỗi truy vấn |
|---|---|---|
| MySQL từ xa | **4.596 ms** | **242–1.414 ms** |
| SQLite cục bộ | 6,6 ms | 0,02–0,69 ms |

Trang `/dashboard` chạy **12 truy vấn**. Qua DB từ xa ≈ 4,6s kết nối + 3s truy vấn
≈ **7,6 giây cho MỘT trang**. Thêm nữa `php artisan serve` trên Windows chỉ xử lý
**một request tại một thời điểm**, nên CSS/JS/font xếp hàng chờ phía sau → trình
duyệt đứng im hàng chục giây, đúng cảm giác "không vào được".

→ **Cách sửa: đi Đường B (SQLite cục bộ).** DB từ xa chỉ hợp khi cần đề thật và
chấp nhận chờ.

### 2. Gõ `localhost` thay vì `127.0.0.1`

Trên Windows, `localhost` phân giải ra `::1` (IPv6) trước. `php artisan serve` chỉ
nghe IPv4, nên mỗi request phải chờ `::1` thất bại rồi mới lùi về IPv4:

| Địa chỉ | Thời gian **kết nối** |
|---|---|
| `http://localhost:8000` | **210 ms** |
| `http://127.0.0.1:8000` | **2,7 ms** |

Mất thêm 210ms × ~11 request mỗi trang ≈ **+2,3 giây**.

→ **Cách sửa: luôn gõ `http://127.0.0.1:8000`.** Chạy server bằng:

```bash
php artisan serve --host=127.0.0.1
```

### 3. PHP chưa bật opcache

Máy này đang **không nạp opcache**, nên mỗi request biên dịch lại ~1.500 file PHP
của Laravel. Đo bằng CLI (cùng đoạn code Laravel bootstrap + xử lý request):

| | Thời gian |
|---|---|
| Không opcache | **373–560 ms** |
| Có opcache | **51–92 ms** |

File `php_opcache.dll` đã có sẵn trong `C:\php-8.2.14-nts-Win32-vs16-x64\ext\`,
chỉ là chưa được khai báo. Mở `C:\php-8.2.14-nts-Win32-vs16-x64\php.ini`, thêm
vào cuối file:

```
zend_extension=php_opcache.dll
opcache.enable=1
opcache.enable_cli=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0
```

`validate_timestamps=1` + `revalidate_freq=0` = sửa file PHP là ăn ngay, không
phải khởi động lại server. Đây là cấu hình cho máy dev; **production thì ngược lại**
(`validate_timestamps=0` để nhanh nhất).

⚠️ Sửa `php.ini` là đổi cấu hình **toàn máy**, ảnh hưởng mọi project PHP khác.
Sao lưu file trước khi sửa.

### Sau khi sửa — số đo thực tế trên máy này

Trang `/practice/1` (11 tài nguyên), SQLite + `127.0.0.1`, **chưa** bật opcache:

```
HTML: 194 ms · tải xong toàn trang: 451 ms
```

Từ ~8 giây xuống dưới **nửa giây**. Bật thêm opcache thì còn khoảng một nửa nữa.

---

## Hai cái bẫy hay mất thì giờ

**1. Sửa JS mà quên build.** Mọi thay đổi trong `resources/js` chỉ có tác dụng sau
`npm run build`. Trang trắng hoặc không có gì xảy ra khi bôi chữ thì kiểm tra cái này trước.

**2. Bộ canh DevTools đá ra ngoài.** Mở DevTools (F12) lúc đang ở trang luyện tập
thì sau 10 giây bị đăng xuất. Nó đo chênh lệch kích thước cửa sổ nên **thu nhỏ cửa
sổ trình duyệt cũng có thể bị tưởng nhầm**. Miễn trừ cho tài khoản đang test:

```bash
php artisan tinker --execute="App\Models\User::where('email','EMAIL-CUA-ANH')->update(['devtools_guard_disabled' => true]); echo 'da tat';"
```

(Tài khoản demo ở đường B đã được miễn sẵn.)

---

## Gỡ ra

Bỏ 3 bảng mới, **không** ảnh hưởng dữ liệu cũ:

```bash
php artisan migrate:rollback --step=3
```

Hoặc không gỡ bảng, chỉ tắt tính năng: `VOCAB_LOOKUP_ENABLED=false` trong `.env`
rồi `php artisan config:clear`.

---

## Gặp lỗi thì gửi tôi cái này

```bash
php artisan tinker --execute="echo 'vocab enabled=' . var_export(config('aptis.vocab.enabled'), true) . ' | co api key=' . var_export(!empty(config('services.openai.key')), true) . ' | DB=' . config('database.default');"
```

Kèm 20 dòng log cuối:

```bash
php artisan pail --timeout=0
```

hoặc đọc thẳng `storage/logs/laravel.log`.
