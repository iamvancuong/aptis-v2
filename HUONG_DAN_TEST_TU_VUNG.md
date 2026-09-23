# Hướng dẫn test: Tra từ bằng AI + Sổ tay từ vựng

Nhánh: `feat/vocab-ai-lookup` · Chi tiết kỹ thuật: `TIEN_DO.md` §33

```bash
git checkout feat/vocab-ai-lookup
```

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
php artisan serve
```

Mở http://localhost:8000 và **đăng nhập bằng tài khoản anh vẫn dùng trên DB test**.

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
php artisan serve
```

Đăng nhập **hocvien@example.test / 12345678** rồi vào http://localhost:8000/practice/1

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

### 2. Tra cả câu
Bôi **nguyên một câu dài** (từ 5 chữ trở lên) → bấm "Tra từ".

Cần thấy: bản dịch cả câu + một ghi chú ngữ pháp. **Không** có phiên âm/loại từ nữa —
đúng như thiết kế, hai chế độ khác nhau.

### 3. Nghĩa có theo ngữ cảnh không (điểm bán hàng)
Tìm một từ nhiều nghĩa trong bài — ví dụ `charge`, `figure`, `run`, `address` — rồi
so nghĩa AI trả về với nghĩa trong câu. Đây chính là chỗ hơn Google Dịch; nếu chỗ
này sai thì báo lại để tôi chỉnh prompt.

### 4. Lưu và xem sổ tay
Bấm **"Lưu từ"** → nút đổi thành "Đã lưu" → vào menu **"Từ vựng"** trên thanh trên cùng.

Cần thấy: thẻ từ có **câu gốc trong bài** ở khung xám, nhãn nguồn (`Reading P4`),
thanh tiến độ 5 ô, dòng "Cần ôn hôm nay".

### 5. Ôn tập bằng thẻ
Bấm **"Ôn tập ngay (N từ)"** → "Xem nghĩa" → bấm **Nhớ**.

Cần thấy: hiện màn hình "Xong phiên ôn tập". Vào lại sổ tay, thẻ đó chuyển thành
**"Ôn lại"** kèm ngày sau đó 3 ngày (không còn "Cần ôn hôm nay").

Bấm **Quên** thì ngược lại — từ tụt về hộp 1, ôn lại ngày mai.

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
