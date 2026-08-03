# 🎓 PLAN — LỚP HỌC ONLINE QUA GOOGLE MEET (Pha 1)

> Soạn 03/08/2026. Nối tiếp §16 / §23 / §25 của `TIEN_DO.md`.
> Bối cảnh mới: **đã có Google Workspace Business Plus** → mở khoá phần trước đây phải hoãn.
> Yêu cầu chủ dự án: ① phân biệt tài khoản tạo tay vs mua qua CK · ② từ đó chia lớp khác nhau ·
> ③ tạo lớp rồi tự chọn thành viên được vào buổi · ④ chỉ tài khoản web vào thẳng, còn lại xin duyệt ·
> ⑤ chống học chui khi 1 Gmail dùng 2–3 thiết bị.

---

## 0. TL;DR — đọc 1 phút

- **Yêu cầu ① không làm được bằng bảng `orders`.** Chỉ **2/848** tài khoản có đơn CK đã thanh toán.
  PayOS mới chạy từ 28/07, gần như toàn bộ học viên hiện tại được tạo tay/import.
  → Phải thêm cột **`users.source`** và **backfill một lần**, không truy ra từ `orders`.
- **Yêu cầu ④ phải tách làm hai tầng**, vì Meet không đọc được DB Milaedu: web quyết định *ai lấy được link*,
  Google Calendar quyết định *ai vào thẳng phòng*. Hai danh sách này phải được **đồng bộ**, và đó chính là
  toàn bộ nội dung của Pha 1.
- **Yêu cầu ⑤ không có cách CHẶN, chỉ có cách PHÁT HIỆN.** Google Meet không giới hạn số thiết bị của cùng
  một tài khoản Google — không setting nào của Business Plus đổi được điều đó. Nhưng Business Plus cho
  **báo cáo điểm danh**, và đối chiếu nó với log web thì **bắt được đích danh**. Chi tiết §5.
- 🔴 **Tìm thấy 1 lỗ hổng thật đang chạy trên production**: cơ chế giới hạn thiết bị `SessionLimit` có thể
  bị vượt qua, và số liệu DB đã xác nhận có tài khoản vượt trần. Xem §2. **Nên vá trước khi mở lớp**,
  vì lớp online làm việc chia sẻ tài khoản trở nên đáng giá hơn nhiều.
- Lộ trình: **GĐ0 (thủ công, 0 dòng code, chạy được tuần này)** → **GĐ1 (lớp + thành viên)** →
  **GĐ2 (đối chiếu điểm danh)** → **GĐ3 (Calendar API tự động)**. Có cổng dừng giữa các giai đoạn.

---

## 1. HIỆN TRẠNG — CÁI GÌ ĐÃ CÓ

Pha 0 (§23) đã chạy và **không cần viết lại**:

| Đã có | File |
|---|---|
| Bảng buổi học + giờ mở/đóng + bật/tắt | `ClassSession`, migration `2026_08_01_000001` |
| Link Meet **không bao giờ render ra HTML** | `ClassSessionController@join` → `redirect()->away()` |
| Nhật ký vào lớp + cảnh báo nhiều IP | `class_session_joins`, `Admin/ClassSessionController@joins` |
| Danh sách mời Calendar copy-1-chạm + bắt gõ nhầm Gmail | `User::invitableToClass()`, `Support\InviteEmail` |
| Email nhắc trước giờ học (không chứa link Meet) | `classes:remind` |
| Ô để học viên khai Gmail riêng | `users.google_email` |
| Nhắc admin gỡ người hết hạn khỏi lời mời | `settings.class_invite_synced_at` |

**Chưa có:** khái niệm "lớp", khái niệm "thành viên của buổi", phân biệt nguồn tài khoản, và mọi thứ tự động
phía Google. Hiện **mọi tài khoản còn hạn đều vào được mọi buổi đang mở**.

---

## 2. 🔴 BA PHÁT HIỆN TỪ CODE + DB — ĐỌC TRƯỚC KHI THIẾT KẾ

### 2.1. `orders` KHÔNG phân biệt được nguồn tài khoản

Đếm trên DB test (`ujxmchhx_aptis_test_2026`):

```
Tổng học viên            = 848
Còn hạn (invitableToClass) = 316
Có đơn CK đã thanh toán    = 2       ← !!
Không có đơn              = 846
```

Nguyên nhân: luồng PayOS mới lên production **28/07/2026**, tức là ~1 tuần trước. Toàn bộ học viên cũ
được tạo bằng tay ở `/admin/users` hoặc import. Nếu code theo kiểu *"có `Order` paid ⇒ mua CK"* thì
**846 người bị xếp nhầm là tạo tay** — mà trong đó chắc chắn có người đã trả tiền qua kênh cũ.

> ⚠️ Số trên là **DB TEST**. Phải chạy lại trên production trước khi backfill:
> ```
> php artisan tinker --execute="echo User::where('role','!=','admin')->count().' / '.User::where('role','!=','admin')->whereHas('orders', fn(\$q)=>\$q->where('type','registration')->where('status','paid'))->count();"
> ```

**Hệ quả thiết kế:** phải có cột `users.source` ghi rõ, gán tại **thời điểm tạo**, và backfill bằng tay một
lần cho dữ liệu cũ (§3.1). Không truy ngược từ `orders`.

### 2.2. 🔴 `SessionLimit` có lỗ hổng — và DB đã xác nhận bị vượt trần

`app/Http/Middleware/SessionLimit.php:38`:

```php
$existingSession = LoginSession::where('device_id', $deviceId)->first();

if ($existingSession) {
    $existingSession->update(['user_id' => $user->id, ...]);   // ← đổi chủ
    return $next($request);                                     // ← bỏ qua phép đếm
}
```

Truy vấn tìm theo `device_id` **mà không kèm `user_id`**. Hậu quả:

- Máy đã từng đăng nhập tài khoản A, nay đăng nhập tài khoản B → row cũ **bị gán sang B** rồi `return` sớm,
  **không chạy phép đếm thiết bị của B**. B được thêm 1 thiết bị miễn phí.
- Đồng thời A **mất một row** một cách âm thầm.

Bằng chứng trong DB: `max_devices = 3` cho 847/848 tài khoản, nhưng có tài khoản đang giữ **4 row**
`login_sessions` (user 3683, user 4029). Luồng bình thường không thể tạo ra con số 4 — chỉ nhánh "đổi chủ"
ở trên mới làm được.

**Vá:** thêm `->where('user_id', $user->id)` vào truy vấn. 1 dòng.

### 2.3. Cấu hình chống chia sẻ đang nói một đằng làm một nẻo

| Nơi | Nội dung |
|---|---|
| Thông báo cho học viên (`SessionLimit.php:63,73`) | "hệ thống **chỉ cho phép đăng nhập đồng thời 1 thiết bị**" |
| `max_devices` thực tế trong DB | **3** (847 tài khoản) |
| `OrderFulfillmentService` cấp cho tài khoản mới | **2** |
| `Setting: default_max_devices` | 2 |

Ba nguồn, ba con số, và không con số nào là 1. Học viên đọc "1 thiết bị" nhưng thực tế dùng 3 máy vẫn êm.
Ngoài ra `login_sessions` **không có TTL** — 150/1231 row đã quá 30 ngày không hoạt động nhưng vẫn chiếm
suất. Học viên đổi máy hoặc xoá cookie là tự ăn `violation_count` dù không chia sẻ cho ai.

Hiện có **105 tài khoản đã dính violation**, 4 tài khoản bị khoá. Con số này sẽ tăng vọt khi mở lớp online
nếu không dọn trước.

**Phải chốt một con số duy nhất trước khi mở lớp.** Xem §5.1.

---

## 3. THIẾT KẾ

### 3.1. Yêu cầu ① — Nguồn tài khoản (`users.source`)

**Migration** `2026_08_XX_add_source_to_users_table`:

```php
$table->string('source', 20)->default('manual')->after('role')->index();
// 'purchase' = tự mua qua PayOS · 'manual' = admin tạo tay · 'import' = nhập từ dữ liệu cũ
```

**Gán tại chỗ tạo** (2 nơi, không được sót):
- `OrderFulfillmentService::fulfillRegistration()` — nhánh `User::create` → `'source' => 'purchase'`.
- `Admin/UserController@store` → `'source' => 'manual'`.

**Backfill một lần** bằng command `users:backfill-source --dry-run`:
1. Có `Order` type=registration status=paid → `purchase`
2. Còn lại, tạo trước 28/07/2026 → `import`
3. Còn lại → `manual`

> ⚠️ **`source` là cách tài khoản được TẠO RA, và không đổi về sau.** Khi một tài khoản `manual` gia hạn
> bằng CK, `fulfillRegistration` đi vào nhánh gia hạn — **đừng ghi đè `source`**. Muốn biết "đã từng trả
> tiền" thì đó là câu hỏi khác, trả lời bằng `orders`, không phải bằng cột này. Trộn hai khái niệm vào một
> cột là kiểu lỗi sẽ phát hiện sau 3 tháng khi số liệu đã sai.

**Nhưng `source` KHÔNG trực tiếp quyết định lớp.** Xem ngay dưới.

### 3.2. Yêu cầu ② + ③ — Lớp và thành viên

Yêu cầu ② nói "từ đó sẽ có các lớp khác nhau", yêu cầu ③ nói "tôi tự chọn thành viên". Hai câu này sẽ đá
nhau nếu code *"lớp = tự động lấy hết người có source = X"*: lúc đó bạn không chọn được nữa, và trường hợp
"học viên tạo tay nhưng cần vào lớp trả phí" sẽ không xử lý được mà phải đi sửa `source` — làm hỏng dữ liệu.

**Cách làm đúng: `source` là BỘ LỌC, thành viên là DANH SÁCH TƯỜNG MINH.**
Màn thêm thành viên có sẵn nút lọc "Chỉ hiện tài khoản mua CK" / "Chỉ hiện tài khoản tạo tay" + "Chọn tất
cả kết quả lọc" — bạn bấm 2 nút là xong cả trăm người, nhưng vẫn sửa được từng người.

**Ba bảng mới:**

```php
// class_groups — cái "lớp"
id · name · description · source_filter(nullable: 'purchase'|'manual'|null) · is_active · timestamps

// class_group_user — thành viên của lớp
class_group_id · user_id · added_at        // unique(class_group_id, user_id)

// class_session_user — khách mời THÊM cho riêng một buổi (ngoại lệ)
class_session_id · user_id                 // unique(class_session_id, user_id)
```

**Sửa `class_sessions`:** thêm `class_group_id` **nullable**.

**Luật vào buổi:**

| `class_group_id` | Ai vào được |
|---|---|
| `NULL` | Mọi học viên còn hạn — **giữ nguyên hành vi cũ**, 3 buổi đang có không bị ảnh hưởng |
| có giá trị | Thành viên của lớp đó **∪** khách mời thêm của riêng buổi đó |

`source_filter` trên `class_groups` chỉ để nhớ ý định của lớp và làm mặc định cho ô lọc — **không tham gia
kiểm tra quyền**. Kiểm tra quyền chỉ đọc pivot.

> 💡 Vì sao `class_group_id` nullable chứ không bắt buộc: bắt buộc thì phải migrate 3 buổi đang có vào một
> lớp giả, và mất luôn kiểu buổi "mở cho tất cả" (workshop, buổi demo) — thứ chắc chắn sẽ cần.

**Sửa quyền ở 3 chỗ, không được sót chỗ nào:**
1. `ClassSessionController@index` — chỉ liệt kê buổi user được vào (thêm scope `visibleTo($user)`).
2. `ClassSessionController@join` — **kiểm tra lại lần nữa**. Đây là chỗ duy nhất trả link ra ngoài;
   không được tin vào việc "index đã lọc rồi". Ai gõ thẳng URL `/lop-hoc/9/join` phải bị chặn.
3. `DashboardController` (`$nextClass`) — không thì học viên thấy thẻ "lớp sắp tới" của lớp không phải của mình.

**Sửa `classes:remind`** — chỉ nhắc thành viên của buổi, không nhắc toàn trường.

### 3.3. Yêu cầu ④ — Chỉ tài khoản web vào thẳng, còn lại xin duyệt

Đây là chỗ **dễ hiểu nhầm nhất**, nên nói thẳng:

> **Milaedu và Google là hai hệ thống danh tính TÁCH RỜI.** Meet không đọc được DB của bạn. Nó chỉ biết
> hai thứ: *tài khoản Google nào đang gõ cửa* và *danh sách khách mời trên sự kiện Calendar*.
> Web chặn được việc **lấy link**; nó không chặn được việc **dùng link**.

Nên yêu cầu ④ phải hiện thực bằng **hai tầng chạy song song**:

**Tầng web (đã có + mở rộng ở §3.2):** chỉ thành viên buổi mới thấy nút và mới redirect được.

**Tầng Google:** sự kiện Calendar của lớp có **attendees = đúng danh sách email thành viên lớp đó**, và
phòng **tắt "Truy cập nhanh" (Quick Access)**. Kết quả:
- Người trong danh sách mời → **vào thẳng**.
- Người ngoài → **gõ cửa, chờ giảng viên duyệt** ✅ đúng yêu cầu ④.

**Ba mức truy cập của Meet — chọn đúng mức:**

| Mức | Hành vi | Hợp với yêu cầu ④? |
|---|---|---|
| Open (Truy cập nhanh BẬT) | Ai có link cũng vào thẳng | ❌ |
| **Trusted + tắt Truy cập nhanh** | Người được mời vào thẳng, người khác **gõ cửa** | ✅ **chọn cái này** |
| Restricted | Chỉ người được mời; người khác **không vào được, không gõ cửa được** | Chặt hơn nhưng mất "hàng chờ" |

> 🔴 **PHẢI TỰ KIỂM CHỨNG bằng 1 buổi thử.** Google đổi ngữ nghĩa các mức này vài lần rồi, và hành vi còn
> khác nhau giữa các gói. Cách thử ở §6-GĐ0 bước 6. Đừng deploy dựa trên bảng này.

🔴 **Hệ quả bắt buộc phải nhớ:** tắt "Truy cập nhanh" mà **không mời ai** thì **CẢ LỚP phải gõ cửa**, giảng
viên duyệt từng người mỗi buổi. Lớp 100 người = không dạy được. Danh sách mời Calendar **không phải tuỳ chọn** —
nó chính là thứ tạo ra trạng thái "học viên thật vào thẳng, người lạ mới bị xét".

⚠️ **Giới hạn của GĐ0/GĐ1 — nói trước để không vỡ kỳ vọng:** khi dùng **sự kiện lặp lại** (1 lớp = 1 event =
1 link cố định), danh sách mời là **của cả chuỗi**, không phải của từng buổi. Nên:
- Web: chọn thành viên **theo từng buổi** ✅ (yêu cầu ③ được đáp ứng ở tầng web)
- Meet: vào thẳng **theo lớp**, không theo buổi ⚠️

Nghĩa là thành viên lớp bị bỏ khỏi buổi X vẫn vào thẳng phòng được **nếu họ còn giữ link cũ**. Muốn khớp
tuyệt đối thì mỗi buổi phải là một sự kiện riêng với danh sách riêng → **đó là GĐ3 (API)**. Nếu bạn cần
khớp tuyệt đối ngay, nói tôi biết để đảo thứ tự ưu tiên.

### 3.4. Tương thích ngược — kiểm tra trước khi lên

- 3 buổi đang có: `class_group_id = NULL` → hành vi không đổi. ✅
- Học viên chưa thuộc lớp nào: vẫn thấy buổi `NULL`, không thấy buổi có lớp. ✅
- `classes:remind` phải sửa **cùng lúc** với việc thêm cột, nếu không buổi của lớp A sẽ gửi mail cho cả 316 người.

---

## 4. GOOGLE WORKSPACE BUSINESS PLUS — CÁI GÌ THẬT SỰ ĐƯỢC MỞ KHOÁ

| | Gmail free (đang dùng) | Business Plus |
|---|---|---|
| Số người/phòng | 100 | **500** |
| Thời lượng buổi (≥3 người) | **60 phút** rồi rớt | ~24 giờ |
| Báo cáo điểm danh | ❌ | ✅ ← **quan trọng nhất cho yêu cầu ⑤** |
| Ghi hình buổi học vào Drive | ❌ | ✅ |
| Admin console → chạy được API tự động | ❌ | ✅ ← mở khoá GĐ3 |

**Cái Business Plus KHÔNG cho:**
- ❌ Không tự nhận ra học viên Milaedu. Học viên dùng `@gmail.com` = **người ngoài tổ chức** của bạn.
  Vẫn phải mời qua Calendar. Nâng gói không bỏ được bước này.
- ❌ **Không giới hạn số thiết bị của cùng một tài khoản Google.** Đây là câu trả lời thẳng cho yêu cầu ⑤ —
  xem §5.
- ❌ Không có link riêng cho từng người.

🔴 **Cảnh báo về trần 500:** đang có ~316 học viên còn hạn (test DB; production cần đếm lại). Gom hết vào
một buổi thì còn dư ~184 chỗ. Bán thêm chừng đó tài khoản nữa là **chạm trần cứng**, phải lên Enterprise
(đắt hơn nhiều). Việc chia lớp ở §3.2 vì thế không chỉ là nghiệp vụ — nó còn là **cách tránh trần 500**.

**Số license cần:** 1 license = 1 host. Các lớp dạy **lần lượt** dùng chung 1 license được. Chỉ cần license
thứ 2 khi có **hai buổi chạy cùng lúc**, hoặc muốn có co-host mở phòng khi cô Dung rớt mạng.

---

## 5. YÊU CẦU ⑤ — CHỐNG HỌC CHUI KHI 1 GMAIL DÙNG 2–3 THIẾT BỊ

### Sự thật phải chấp nhận trước

**Không có cách CHẶN.** Cùng một tài khoản Google vào Meet từ 3 máy sẽ thành **3 người tham gia riêng biệt,
trùng tên**. Google không coi đó là vi phạm và không cung cấp nút nào để cấm. Ai hứa với bạn là "chặn được"
thì hoặc nhầm, hoặc đang nói về việc chặn ở tầng web (chỉ chặn được khâu lấy link, không chặn được khâu
dùng link).

Vì vậy chiến lược đúng là **3 lớp: làm khó → phát hiện → chế tài**. Bỏ lớp 3 thì lớp 1 và 2 thành trang trí.

### 5.1. Lớp 1 — LÀM KHÓ (phòng ngừa)

**a) Vá lỗ hổng `SessionLimit`** (§2.2) — 1 dòng. Làm trước tiên.

**b) Chốt một con số `max_devices` duy nhất.** Đề xuất **2** (điện thoại + laptop là nhu cầu thật của học
viên, ép về 1 sẽ tạo ra hàng loạt khiếu nại giả). Rồi:
- Sửa `Setting: default_max_devices` = 2
- Chạy `User::where('max_devices','>',2)->update(['max_devices'=>2])` — **847 tài khoản đang là 3**
- **Sửa câu thông báo** cho khớp: bỏ chữ "1 thiết bị", ghi đúng "2 thiết bị"

> ⚠️ Hạ từ 3 xuống 2 sẽ khiến một số học viên đang dùng 3 máy bị đá thiết bị + ăn violation ngay hôm sau.
> Nên **dọn `login_sessions` cũ trước** (mục c) và **reset `violation_count` về 0** cho toàn bộ học viên
> cùng lúc, coi như làm lại từ đầu với luật mới. 105 người đang có violation, phần lớn nhiều khả năng là
> nạn nhân của mục c chứ không phải chia sẻ tài khoản.

**c) Thêm TTL cho `login_sessions`.** Hiện row không bao giờ hết hạn → "số thiết bị đang hoạt động" thực ra
là "số thiết bị từng dùng". Thêm command `sessions:prune` xoá row `last_active_at` quá **14 ngày**, chạy
hằng ngày (cron `schedule:run` đã có sẵn, không phải thêm cron).

> Đánh đổi phải biết: TTL càng ngắn thì càng dễ chia sẻ luân phiên (3 bạn thay nhau học, mỗi tuần một
> người). 14 ngày là mức cân bằng — đủ để học viên đổi máy/xoá cookie không bị phạt oan, đủ ngắn để
> không tích rác. Đừng để 90 ngày.

**d) Bắt buộc khai `google_email` với thành viên lớp online.** Hiện `classInviteEmail()` fallback về email
tài khoản — đúng cho 96% và nên **giữ nguyên mặc định đó**. Nhưng riêng người vào lớp, cần khoá chặt:
**một Gmail chỉ được gắn với một tài khoản Milaedu** (unique index trên `users.google_email`). Không có
ràng buộc này thì 3 anh em dùng chung 1 Gmail để vào Meet và §5.2 không phát hiện được gì.

**e) Đừng dùng link cố định vĩnh viễn.** Sự kiện lặp lại rất tiện nhưng link không đổi — rò một lần là rò
mãi. Ít nhất **đổi phòng mỗi khoá học** (tạo sự kiện lặp lại mới). GĐ3 tự sinh phòng mỗi buổi thì hết vấn đề.

### 5.2. Lớp 2 — PHÁT HIỆN (đây mới là phần Business Plus vừa mở khoá)

Sau mỗi buổi ≥2 người, Business Plus gửi cho host một **CSV điểm danh**: tên, **email Google**, giờ vào,
giờ ra, tổng phút. Đây là mảnh ghép còn thiếu — lần đầu tiên bạn biết **ai thật sự ngồi trong phòng**,
chứ không chỉ ai bấm nút trên web.

**Đề xuất: màn `/admin/class-sessions/{id}/doi-chieu` — upload CSV, hệ thống tự khớp 3 nguồn:**

| Nguồn | Câu hỏi trả lời |
|---|---|
| (A) `class_group_user` + `class_session_user` | Ai **được phép** vào buổi này |
| (B) `class_session_joins` | Ai **bấm nút** trên web |
| (C) CSV điểm danh Meet | Ai **thật sự ở trong phòng** |

**Bốn dấu hiệu tự tô màu:**

1. 🔴 **Có trong (C), không có trong (A)** → người lạ đã lọt vào. Biết **đích danh Gmail**.
   → Kiểm tra ngay: hoặc danh sách mời sai, hoặc "Truy cập nhanh" chưa tắt, hoặc giảng viên lỡ duyệt nhầm.
2. 🟠 **Có trong (C), không có trong (B)** → vào Meet mà **không đi qua cổng web**. Dấu hiệu dùng link cũ
   còn trong Google Calendar (đúng cái lỗ hổng §25② đang vá tay), hoặc được ai đó chuyển link.
3. 🔴 **Cùng một email, hai dòng có khoảng thời gian CHỒNG NHAU** → **1 Gmail, 2 thiết bị cùng lúc**.
   ✅ **Đây chính xác là câu bạn hỏi, và đây là cách duy nhất trả lời được nó.**
   > ⚠️ Chỉ tính khi **chồng lấn thời gian**. Học viên rớt mạng rồi vào lại cũng tạo 2 dòng — nhưng
   > **nối tiếp**, không chồng. Không phân biệt hai ca này thì bạn sẽ phạt oan hàng loạt người mạng yếu,
   > và chỉ cần vài lần như vậy là toàn bộ cơ chế mất uy tín.
4. 🟡 **Có trong (A), tổng phút rất thấp** → điểm danh rồi bỏ đi. Không phải học chui, nhưng là dữ liệu
   hữu ích cho giảng viên.

**Nâng cấp về sau (GĐ3+):** Meet REST API v2 có `conferenceRecords.participantSessions` trả về thẳng từng
phiên tham gia kèm mốc bắt đầu/kết thúc — khớp tự động, không phải upload CSV.
🔴 **Cần kiểm chứng** endpoint này có sẵn cho Business Plus không (một số phần của Meet API bị giới hạn
theo gói và theo trạng thái Developer Preview). Kiểm bằng 1 lệnh `curl` trước khi tính vào kế hoạch.

### 5.3. Lớp 3 — CHẾ TÀI (bỏ lớp này thì 2 lớp trên vô nghĩa)

- **Nội quy tại `/lop-hoc` phải nói đúng những gì hệ thống LÀM THẬT** — nguyên tắc đã ghi ở §23 và vẫn đúng:
  *"Mỗi lượt vào lớp đều được ghi lại. Sau mỗi buổi, danh sách người trong phòng được đối chiếu với danh
  sách học viên. Một tài khoản xuất hiện ở hai nơi cùng lúc: cảnh cáo lần đầu, khoá tài khoản lần hai."*
  Đừng thêm lời doạ suông — học viên thử một lần là biết bịa, mất sạch tác dụng răn đe.
- **Trong buổi:** giảng viên thấy hai dòng trùng tên → **Remove from call** ngay. Rẻ và hiệu quả nhất.
- **Sau buổi:** dùng `violation_count` + `status='blocked'` đã có sẵn. Không cần cơ chế mới.
- **Trước khoá học:** thông báo trước 1 lần rằng từ nay có đối chiếu điểm danh. Mục tiêu là **răn đe**,
  không phải bắt được nhiều người — bắt được nhiều nghĩa là răn đe đã thất bại.

### 5.4. Cái tôi KHÔNG đề xuất, và vì sao

- ❌ **Token vào lớp một lần / link ký số:** đích đến vẫn là một link Meet tĩnh. Học viên vào phòng rồi
  copy link trên thanh địa chỉ là xong. Tốn công, không thêm an toàn thật.
- ❌ **Bắt bật camera cả buổi:** Meet không cho ép, và học viên sẽ phản ứng mạnh.
- ❌ **Nhận diện khuôn mặt / chống gian lận sinh trắc:** lệch hoàn toàn quy mô dự án, và là dữ liệu nhạy cảm.
- ❌ **Khoá theo IP:** học viên dùng 4G, IP đổi liên tục. Sẽ chặn nhầm người thật.

---

## 6. LỘ TRÌNH — 4 GIAI ĐOẠN CÓ CỔNG DỪNG

> 📌 Rút kinh nghiệm: plan chấm Speaking AI đã bị **bỏ qua cả 3 cổng dừng** và giờ vẫn chưa ai biết host
> có gọi được `api.openai.com` không. Lần này các cổng dừng đều **rẻ và nhanh** — đừng bỏ qua.

### GĐ0 — Chạy thật, thủ công, KHÔNG viết dòng code nào (~1 giờ)

Mục tiêu: **chứng minh mô hình chạy được trước khi đầu tư code.** Rất có thể GĐ0 đã đủ dùng cho 2–3 lớp.

1. Đăng nhập Google Workspace bằng **tài khoản host** (cô Dung).
2. Admin console → xác nhận đang là **Business Plus**, và bật **Attendance reports** (Apps → Google Workspace
   → Google Meet → Meet video settings). Không thấy mục này = gói không có, dừng lại và báo tôi.
3. Google Calendar → tạo **sự kiện LẶP LẠI** cho lớp đầu tiên (vd "Lớp A2 — 19h30 T7 hàng tuần").
   Bấm **Add Google Meet video conferencing**.
4. Ô **Khách mời**: dán danh sách email — copy 1 chạm ở `/admin/class-sessions` (đã có sẵn).
5. Trong tuỳ chọn sự kiện: **tắt "Guests can invite others"** (`guestsCanInviteOthers = false`).
6. **Kiểm chứng — bước quan trọng nhất, đừng bỏ:**
   - Vào phòng bằng tài khoản host → tắt **"Truy cập nhanh" (Quick Access)**.
   - Lấy **một Gmail KHÔNG có trong danh sách mời** (máy khác / cửa sổ ẩn danh) → mở link →
     **phải thấy màn "Đang chờ được duyệt"**. Nếu vào thẳng ⇒ Quick Access chưa tắt.
   - Lấy **một Gmail CÓ trong danh sách mời** → mở link → **phải vào thẳng**. Nếu cũng bị bắt chờ ⇒
     lời mời chưa nhận hoặc mức truy cập đang là Restricted → xem lại §3.3.
   - ✅ Chỉ khi **cả hai** đúng thì yêu cầu ④ mới thật sự hoạt động.
7. Copy link Meet → dán vào buổi học ở `/admin/class-sessions`.
8. Dạy thử 1 buổi thật. Sau buổi, **mở email điểm danh** xem CSV có đúng cột (email, giờ vào, giờ ra) không.

> **🚦 CỔNG DỪNG 0:** chưa qua được bước 6 và bước 8 thì **dừng, không code gì cả**. Cả GĐ2 và GĐ3 đều
> dựng trên hai thứ đó.

### GĐ1 — Lớp + thành viên ✅ ĐÃ CODE XONG (03/08/2026, nhánh `feature/class-groups`)

Phần vá `SessionLimit` / `max_devices` **đã bỏ theo quyết định §8.1** — không đụng nữa.
(Lỗi đếm thiết bị ở §2.2 vẫn còn thật; đã tách thành việc riêng, không nằm trong nhánh này.)

- [x] Migration: `users.source` · `class_groups` · `class_group_user` · `class_session_user` ·
      `class_sessions.class_group_id` · `class_sessions.meet_link` thành nullable · unique `users.google_email`
- [x] Command `users:backfill-source --dry-run` (§3.1)
- [x] Gán `source` ở `OrderFulfillmentService` + `Admin/UserController@store`
- [x] Model `ClassGroup` + quan hệ; `ClassSession::belongsTo(ClassGroup)`
- [x] `User::canJoinClassSession()` — **một hàm duy nhất**, ba nơi gọi
- [x] Admin CRUD `/admin/class-groups` + màn chọn thành viên (lọc theo `source`, "thêm tất cả kết quả lọc",
      tìm theo tên/email)
- [x] Form buổi học thêm ô chọn lớp + ô "khách mời thêm cho buổi này"
- [x] Sửa `ClassSessionController@index/@join`, `DashboardController`, `classes:remind`
- [x] Danh sách mời Calendar ở `/admin/class-groups/{id}/thanh-vien` — **copy theo từng lớp**
- [x] Test: **22 ca mới, tổng 200 pass**

**Thêm ngoài kế hoạch ban đầu** (phát sinh khi code, đều có lý do ghi trong mã nguồn):
- **Link Meet ở mức LỚP**, buổi kế thừa → admin dán một lần cho cả khoá. Không chỉ tiện: bắt dán lại mỗi
  buổi thì sớm muộn cũng có lần dán nhầm link lớp khác, và đó là rò phòng học chứ không phải lỗi chính tả.
- **Tắt lớp = đóng mọi buổi của lớp**, kể cả với khách mời riêng.
- **Khoá ngoại `restrictOnDelete`**: không xoá được lớp còn buổi. Nếu để `nullOnDelete` thì xoá nhầm một lớp
  sẽ biến mọi buổi của nó thành "mở cho toàn trường" — một thao tác xoá lại thành lộ quyền, im lặng.
- **Test đối chiếu hai chiều của luật quyền** (`canJoinClassSession` ↔ `forClassSession` ↔ `allowedFor`):
  ba chỗ mô tả cùng một luật thì sẽ lệch nhau lúc nào đó, ca test này là cái chuông báo.

> **🚦 CỔNG DỪNG 1 — CHƯA QUA:** phải dạy 2 buổi thật với lớp đã chia, đúng người vào được, sai người bị chặn.
> Test tự động chỉ chứng minh luật đúng trong SQLite, không chứng minh quy trình đúng với người thật.

### GĐ2 — Đối chiếu điểm danh (code, ~1–2 ngày)

- [ ] Màn upload CSV `/admin/class-sessions/{id}/doi-chieu`
- [ ] Parser CSV Google (⚠️ tên cột đổi theo ngôn ngữ Workspace — **map theo vị trí + kiểm tra header**,
      đừng hard-code tên cột tiếng Anh)
- [ ] Thuật toán khớp 4 dấu hiệu §5.2, **đặc biệt là luật chồng lấn thời gian**
- [ ] Nút "Đánh dấu vi phạm" → tăng `violation_count`, có ghi chú lý do
- [ ] Cập nhật nội quy ở `/lop-hoc` cho khớp việc thật sự làm

> **🚦 CỔNG DỪNG 2:** chạy đối chiếu 3 buổi thật. Nếu 3 buổi đều 0 vi phạm → **học chui không phải vấn đề
> thật**, dừng ở đây, đừng làm GĐ3 vì lý do bảo mật (có thể vẫn làm vì lý do tiết kiệm thao tác).

### GĐ3 — Tự động hoá bằng Google API (code, ~3–5 ngày)

**🔴 Cổng bắt buộc trước khi viết dòng nào — test outbound HTTPS từ cPanel:**
```bash
curl -sS -o /dev/null -w '%{http_code}\n' https://www.googleapis.com/discovery/v1/apis
```
Không ra `200` ⇒ shared host chặn ⇒ **toàn bộ GĐ3 bất khả thi**, dừng lại. Đây đúng loại rủi ro đã bị bỏ
qua với `api.openai.com` lần trước — 30 giây để khỏi mất 5 ngày.

**Xác thực: service account + domain-wide delegation.** (Không dùng OAuth refresh token: GĐ3 chạy trong
cron không có người ngồi trước màn hình, mà refresh token thì hết hạn/bị thu hồi được — DWD thì không.)
1. Google Cloud Console → tạo project → bật **Google Calendar API** → tạo **service account** → tải JSON key
2. Admin console → Security → API controls → **Domain-wide delegation** → thêm Client ID của service account
   với scope `https://www.googleapis.com/auth/calendar`
3. Key JSON để **ngoài webroot**, đường dẫn khai qua `.env`, **thêm vào `.gitignore`**
4. `composer require google/apiclient`

**Code:**
- [ ] `GoogleCalendarService::syncGroupEvent(ClassGroup $g)` — tạo/cập nhật sự kiện + `conferenceData`
      (tự sinh link Meet) + `attendees` = email thành viên lớp
- [ ] Lưu `class_groups.google_event_id` + `meet_link` tự điền
- [ ] Đặt `guestsCanInviteOthers = false`, mức truy cập phòng theo kết luận GĐ0 bước 6
- [ ] Command `classes:sync-invites` chạy hằng ngày: so `class_group_user` với `attendees` hiện tại →
      **thêm người mới, GỠ người hết hạn / đã rời lớp**
      → 🎯 vá hẳn lỗ hổng §25② mà không cần thao tác tay
- [ ] Bỏ hộp "cần GỠ khỏi lời mời" ở màn admin (hết tác dụng)
- [ ] (nếu §5.2 xác nhận có) kéo điểm danh tự động qua Meet API v2 thay cho upload CSV

> ⚠️ **Quota Calendar API** và **giới hạn số khách mời trên một sự kiện** — tra số thật trước khi đồng bộ
> lớp vài trăm người. Đừng để phát hiện lúc cron chạy trên production.

---

## 7. RỦI RO

| Rủi ro | Mức | Xử lý |
|---|---|---|
| cPanel chặn outbound tới `googleapis.com` | **Cao** — đã từng bỏ qua với OpenAI | Cổng đầu GĐ3, 30 giây |
| Hạ `max_devices` 3→2 gây khiếu nại hàng loạt | Trung bình | Reset violation cùng lúc + thông báo trước |
| Backfill `source` sai trên production | Trung bình | `--dry-run` bắt buộc; đếm lại số production trước |
| Sự kiện lặp lại ⇒ link cố định, rò là rò mãi | Trung bình | Đổi phòng mỗi khoá; GĐ3 xử lý hẳn |
| Chạm trần 500 người/phòng | Thấp giờ, cao sau | Chia lớp (§3.2) là cách tránh |
| Class Tailwind mới không có trong `public/build` | **Cao** — đã dính ở §25 | Chạy đúng lệnh kiểm tra ở §25 trước khi tuyên bố "không cần build" |
| `classes:remind` gửi nhầm toàn trường | Cao nếu quên | Sửa **cùng commit** với `class_group_id` |

---

## 8. ✅ QUYẾT ĐỊNH ĐÃ CHỐT (03/08/2026)

1. **`max_devices` = 3, GIỮ NGUYÊN như trong DB.** Lý do chủ dự án đưa ra: 1 tài khoản = 1 người, và người
   đó dùng điện thoại + iPad + máy tính. **Không đụng vào `SessionLimit`** — chủ dự án xác nhận cơ chế
   đang chạy đúng trên thực tế.
   > 📌 Hệ quả cho §5: vì 1 tài khoản = 1 người, mà 1 người chỉ ngồi được 1 lớp tại 1 thời điểm, nên dấu
   > hiệu "cùng Gmail, 2 phiên Meet **chồng giờ**" (§5.2 dấu hiệu 3) càng đáng tin. Web cho 3 thiết bị
   > nhưng Meet thì không nên có 2 phiên cùng lúc.
   > 🟡 Còn mở: câu thông báo trong `SessionLimit.php:63,73` vẫn ghi "chỉ cho phép 1 thiết bị" trong khi
   > thực tế cho 3 — sai lệch về CHỮ, chưa sửa vì chưa được yêu cầu.
2. **Domain Workspace = `milaedu.com`, tài khoản host = `support@milaedu.com`** → GĐ3 (service account +
   domain-wide delegation) khả thi.
3. **Yêu cầu ③ đã hiện thực ở dạng SIÊU TẬP** — không cần chốt nữa: lớp có danh sách thành viên dùng chung
   cho mọi buổi, **và** mỗi buổi có thêm ô "khách mời riêng". Chọn một lần hay chọn lại từng buổi đều làm được.
   ⚠️ Giới hạn ở §3.3 vẫn nguyên: **tầng Meet chỉ khớp theo LỚP**, không theo buổi, cho tới khi có GĐ3.

### Còn cần bạn trả lời (không chặn việc đang làm)

4. **Bao nhiêu lớp, mỗi lớp bao nhiêu người, có hai lớp chạy cùng giờ không?** → quyết định số license host.
   Một license dạy được nhiều lớp **lần lượt**; chỉ cần license thứ 2 khi hai buổi trùng giờ hoặc muốn co-host.
5. **Có bắt buộc học viên khai `google_email` khi vào lớp online không?** Bắt buộc thì §5.2 phát hiện chính
   xác hơn nhiều, đổi lại thêm một bước cho học viên. (Đã thêm sẵn **unique index** trên cột này, nên dù
   không bắt buộc thì cũng không có chuyện 3 người khai chung một Gmail.)

---

## 9. VIỆC LÀM NGAY (không cần chờ quyết định nào)

1. Chạy lệnh đếm production ở §2.1 — biết con số thật.
2. Chạy `curl` kiểm outbound ở GĐ3 — 30 giây, biết GĐ3 có khả thi không.
3. Làm **GĐ0** — 1 giờ, không code, và rất có thể đã đủ dùng cho lớp đầu tiên.
4. Vá `SessionLimit` (§2.2) — 1 dòng, độc lập hoàn toàn với mọi thứ khác trong plan này.
