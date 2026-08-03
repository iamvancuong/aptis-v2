@php
    // create.blade.php không truyền $classSession — dùng chung 1 form cho cả tạo/sửa.
    $session   = $classSession ?? null;
    $chonSan   = old('class_group_id', $session?->class_group_id);
    $khachCu   = old('extra_user_ids', $session?->extraMembers->pluck('id')->all() ?? []);
@endphp

<x-input
    name="title"
    label="Tên buổi học"
    :value="$session?->title ?? ''"
    required
    placeholder="VD: Buổi 5 — Chữa Writing Task 1"
    error="{{ $errors->first('title') }}"
/>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Lớp học</label>
    <x-select name="class_group_id">
        <option value="">— Không gắn lớp: MỌI học viên còn hạn đều vào được —</option>
        @foreach($groups as $g)
            <option value="{{ $g->id }}" {{ (string) $chonSan === (string) $g->id ? 'selected' : '' }}>
                {{ $g->name }}{{ $g->meet_link ? ' (đã có link phòng)' : ' (chưa có link phòng)' }}{{ $g->is_active ? '' : ' — ĐANG TẮT' }}
            </option>
        @endforeach
    </x-select>
    <p class="mt-1 text-xs text-gray-500">
        Gắn lớp thì <strong>chỉ thành viên lớp đó</strong> thấy và vào được buổi này.
        Lớp đã dán link phòng thì buổi tự dùng link đó — để trống ô link bên dưới là được.
    </p>
    @error('class_group_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Nội dung buổi học (tuỳ chọn)</label>
    <x-textarea name="description" rows="3" placeholder="VD: Chữa đề tuần trước, luyện viết email trang trọng.">{{ old('description', $session?->description) }}</x-textarea>
    @error('description')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- `type="text"` chứ không phải `type="url"`: trình duyệt sẽ tự chặn
     `meet.google.com/abc-defg-hij` (thiếu https) ngay tại chỗ, không cho gửi lên,
     nên phần chuẩn hoá ở server không bao giờ chạy tới. --}}
<x-input
    name="meet_link"
    label="Link phòng học riêng cho buổi này (không bắt buộc)"
    :value="$session?->meet_link ?? ''"
    placeholder="meet.google.com/abc-defg-hij  —  hoặc chỉ abc-defg-hij"
    error="{{ $errors->first('meet_link') }}"
/>
<p class="-mt-2 mb-3 text-xs text-gray-500">
    Gõ kiểu nào cũng được: <code>meet.google.com/abc-defg-hij</code>, có <code>https://</code> hay không,
    hoặc dán mỗi mã phòng <code>abc-defg-hij</code> — hệ thống tự bổ sung phần còn lại.
</p>
<p class="-mt-1 mb-3 text-xs text-gray-500">
    Đã chọn lớp có link phòng thì <strong>để trống ô này</strong>, buổi sẽ dùng link của lớp.
    Để trống cả hai cũng lưu được (lên lịch trước, mở phòng sau) — nhưng học viên chưa vào được
    cho tới khi có link. Link <strong>không hiển thị</strong> cho học viên: họ chỉ thấy nút “Vào lớp”,
    hệ thống kiểm tra tư cách thành viên, hạn tài khoản và giờ học rồi mới chuyển sang phòng.
</p>

{{-- Nhắc ngay chỗ dán link: web chỉ chặn được khâu LẤY link, không chặn được
     người đã vào phòng copy link gửi ra ngoài. Phòng chờ là hàng rào thứ hai. --}}
<div class="mb-4 flex gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div class="text-xs text-amber-800">
        <p class="font-semibold mb-1">Nhớ TẮT “Truy cập nhanh” trong phòng Meet</p>
        <p>
            Web chỉ kiểm soát <strong>ai được lấy link</strong>. Học viên đã vào phòng vẫn có thể copy link
            trên thanh địa chỉ gửi cho người ngoài — không code nào chặn được khâu này.
        </p>
        <p class="mt-1">
            Trong phòng Meet: bấm <strong>biểu tượng khiên (Điều khiển của chủ trì)</strong> →
            <strong>TẮT “Truy cập nhanh” (Quick access)</strong>. Khi đó người lạ phải xin vào và bạn duyệt từng người.
        </p>
        <p class="mt-1 text-amber-700">
            Lưu ý: “Phòng chờ” đúng nghĩa là tính năng <strong>trả phí</strong> (Business Standard trở lên).
            Với Gmail thường thì tắt “Truy cập nhanh” là cách tương đương.
        </p>
    </div>
</div>

{{-- Giờ học kèm DIỄN GIẢI TRỰC TIẾP.
     Bản trước chỉ có hai ô datetime rỗng: nhập xong không có gì nói cho admin biết
     giờ đó nghĩa là gì, nên gõ nhầm 23:05 trong khi định để 21:05 thì phải tới lúc
     học viên kêu "không vào được" mới biết. Đã xảy ra thật (buổi #7). --}}
@php
    $batDauCu  = old('starts_at', $session?->starts_at?->format('Y-m-d\TH:i') ?? '');
    $ketThucCu = old('ends_at', $session?->ends_at?->format('Y-m-d\TH:i') ?? '');
@endphp

{{-- ⚠️ MÚI GIỜ — cái bẫy đã cắn thật, đọc trước khi sửa file này.
     `<input type="datetime-local">` KHÔNG mang múi giờ. Trình duyệt hiển thị và
     gợi ý theo giờ MÁY CỦA ADMIN, còn server lưu nguyên chuỗi rồi hiểu là giờ
     Việt Nam. Admin ngồi ở Nhật (UTC+9) gõ "bây giờ" = 23:30 thì hệ thống hiểu
     là 23:30 giờ VN, tức 2 tiếng nữa — và học viên không vào được.

     Nên MỌI phép tính giờ ở đây phải dựa vào ĐỒNG HỒ SERVER, tuyệt đối không
     dùng `new Date()` để lấy "bây giờ". Cách làm: quy cả hai về "mốc treo tường"
     (Date.UTC của các thành phần y/m/d h:m) rồi mới trừ nhau — như vậy múi giờ
     trình duyệt không còn tham gia vào phép tính nào cả. --}}
<div x-data="{
        batDau: @js($batDauCu),
        ketThuc: @js($ketThucCu),
        somPhut: {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }},
        serverGoc: @js(now()->format('Y-m-d\TH:i:s')),
        serverTen: @js(config('app.timezone')),
        serverOffset: @js(now()->format('P')),
        taiLuc: Date.now(),

        /* Đổi 'YYYY-MM-DDTHH:mm[:ss]' thành mili-giây theo ĐỒNG HỒ TREO TƯỜNG. */
        mocTuong(s) {
            const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?/);
            return m ? Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +(m[6] || 0)) : null;
        },
        dinhDang(ms) {
            const d = new Date(ms), n = (x) => String(x).padStart(2, '0');
            return n(d.getUTCHours()) + ':' + n(d.getUTCMinutes())
                 + ' ' + n(d.getUTCDate()) + '/' + n(d.getUTCMonth() + 1);
        },
        /* 'Bây giờ' theo server, có cộng thêm thời gian trang đã mở. */
        get serverBayGio() { return this.mocTuong(this.serverGoc) + (Date.now() - this.taiLuc); },
        get serverChu() { return this.dinhDang(this.serverBayGio); },

        /* Chênh lệch giữa đồng hồ MÁY ADMIN và đồng hồ server, tính bằng phút. */
        get lechPhut() {
            const may = Date.now() - new Date().getTimezoneOffset() * 60000;
            return Math.round((may - this.serverBayGio) / 60000);
        },
        get lechNhieu() { return Math.abs(this.lechPhut) >= 5; },
        get lechChu() {
            const p = Math.abs(this.lechPhut), h = Math.floor(p / 60), m = p % 60;
            return (h ? h + ' giờ ' : '') + m + ' phút ' + (this.lechPhut > 0 ? 'NHANH hơn' : 'CHẬM hơn');
        },

        moNgay() {
            /* Lấy giờ SERVER, không phải giờ máy — đây đúng là chỗ đã sai. */
            this.batDau = new Date(this.serverBayGio).toISOString().slice(0, 16);
        },
        xoaGio() { this.batDau = ''; this.ketThuc = ''; },
        get cuaMo() {
            const t = this.mocTuong(this.batDau);
            return t === null ? null : t - this.somPhut * 60000;
        },
        get dangMo() { return this.cuaMo !== null && this.cuaMo <= this.serverBayGio; },
        get moTa() {
            if (!this.batDau && !this.ketThuc) {
                return 'Mở tự do — học viên vào được ngay khi buổi đang bật, không giới hạn giờ.';
            }
            const c = this.cuaMo;
            if (c === null) return 'Chưa đặt giờ bắt đầu — buổi mở ngay khi bật.';
            const gio = this.dinhDang(c);
            let phut = Math.round((c - this.serverBayGio) / 60000);
            if (phut <= 0) return 'Cửa lớp ĐANG MỞ (từ ' + gio + ').';
            const ngay = Math.floor(phut / 1440); phut -= ngay * 1440;
            const h = Math.floor(phut / 60), m = phut % 60;
            const con = (ngay ? ngay + ' ngày ' : '') + (h ? h + ' giờ ' : '') + m + ' phút';
            return 'Cửa lớp mở lúc ' + gio + ' — CÒN ' + con + ' NỮA, học viên chưa vào được trước lúc đó.';
        }
     }">
    {{-- Đồng hồ SERVER, luôn hiện. Đây là đồng hồ duy nhất có ý nghĩa với hệ thống. --}}
    <p class="mb-2 text-xs text-gray-600">
        Giờ hệ thống bây giờ: <strong x-text="serverChu"></strong>
        (<span x-text="serverTen"></span>, <span x-text="serverOffset"></span>).
        Mọi giờ bạn nhập bên dưới được hiểu theo đồng hồ này.
    </p>

    {{-- Cảnh báo lệch múi giờ. Không có nó thì admin ở múi giờ khác gõ "bây giờ"
         theo đồng hồ máy mình và buổi học lệch đúng bằng khoảng chênh, im lặng. --}}
    <div x-cloak x-show="lechNhieu"
         class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
        <p class="font-semibold mb-1">⚠️ Máy bạn đang lệch múi giờ với hệ thống</p>
        <p>
            Đồng hồ máy bạn đang <strong x-text="lechChu"></strong> đồng hồ hệ thống.
            Ô chọn giờ bên dưới hiển thị theo <strong>giờ máy bạn</strong>, nhưng hệ thống
            hiểu con số bạn nhập là <strong x-text="serverTen"></strong>.
        </p>
        <p class="mt-1">
            Gõ giờ theo <strong>đồng hồ hệ thống</strong> ở trên, đừng gõ theo đồng hồ máy —
            hoặc bấm “Bắt đầu ngay bây giờ”, nút đó lấy giờ hệ thống nên luôn đúng.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 sm:gap-x-4">
        <x-input
            type="datetime-local"
            name="starts_at"
            label="Bắt đầu (không bắt buộc)"
            :value="$batDauCu"
            x-model="batDau"
            error="{{ $errors->first('starts_at') }}"
        />
        <x-input
            type="datetime-local"
            name="ends_at"
            label="Kết thúc (không bắt buộc)"
            :value="$ketThucCu"
            x-model="ketThuc"
            error="{{ $errors->first('ends_at') }}"
        />
    </div>

    <div class="-mt-2 mb-3 flex flex-wrap gap-2">
        <button type="button" x-on:click="moNgay()"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Bắt đầu ngay bây giờ
        </button>
        <button type="button" x-on:click="xoaGio()"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Xoá giờ (mở tự do)
        </button>
    </div>

    {{-- Câu này là thứ đáng lẽ phải có ngay từ đầu: nói thẳng hệ quả của giờ vừa nhập. --}}
    <div class="mb-4 p-3 rounded-lg border text-xs"
         x-bind:class="dangMo || (!batDau && !ketThuc)
            ? 'bg-green-50 border-green-200 text-green-800'
            : 'bg-amber-50 border-amber-200 text-amber-800'">
        <span x-text="moTa"></span>
    </div>
</div>

<div class="-mt-2 mb-4 text-xs text-gray-500 space-y-1">
    <p><strong>Để trống cả hai ô là xong</strong> — lớp mở ngay khi bật, đóng khi bạn bỏ tick “Bật buổi học”. Cách này ít thao tác nhất.</p>
    <p>Nếu có điền: nút “Vào lớp” tự bật trước giờ bắt đầu {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút và tự tắt khi qua giờ kết thúc — không cần nhớ tắt tay.</p>
</div>

<div class="flex items-center">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $session?->is_active ?? true) ? 'checked' : '' }}
           class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors cursor-pointer">
    <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
        Bật buổi học (học viên nhìn thấy và vào được)
    </label>
</div>

{{-- Khách mời riêng cho buổi này. Chỉ có tác dụng khi buổi đã gắn lớp — buổi
     không gắn lớp thì ai còn hạn cũng vào được rồi, giữ danh sách này lại chỉ
     tạo ảo giác là nó đang hạn chế ai đó (controller cũng xoá sạch khi lưu). --}}
<div class="mt-6 pt-6 border-t border-gray-100">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Mời thêm khách cho riêng buổi này (tuỳ chọn)
    </label>
    <select name="extra_user_ids[]" multiple size="8"
            class="w-full px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        @foreach($ungVien as $u)
            <option value="{{ $u->id }}" {{ in_array($u->id, (array) $khachCu) ? 'selected' : '' }}>
                {{ $u->name }} — {{ $u->email }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500">
        Dành cho học viên <strong>ngoài lớp</strong> cần vào đúng buổi này (học thử, học bù).
        Giữ Ctrl (hoặc Cmd) để chọn nhiều người. Chỉ có tác dụng khi buổi đã gắn lớp.
    </p>
    <p class="mt-1 text-xs text-amber-700">
        ⚠️ Web sẽ cho họ lấy link, nhưng Google <strong>vẫn bắt họ xin duyệt</strong> nếu chưa có tên
        trong lời mời Calendar của lớp. Nhớ duyệt cho họ vào khi bắt đầu buổi.
    </p>
</div>
