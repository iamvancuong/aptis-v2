<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lớp học + thành viên: ai được vào buổi nào.
 *
 * Điểm cần bảo vệ nhất là cổng `join` — đó là chỗ DUY NHẤT trả link Meet ra
 * ngoài. Danh sách ở `/lop-hoc` đã lọc, nhưng ai cũng gõ thẳng URL được, nên
 * mọi ca ở đây đều thử gõ thẳng chứ không đi qua giao diện.
 */
class ClassGroupAccessTest extends TestCase
{
    use RefreshDatabase;

    private const LINK_LOP  = 'https://meet.google.com/lop-aaaa-bbb';
    private const LINK_BUOI = 'https://meet.google.com/buoi-cccc-ddd';

    private function student(string $source = User::SOURCE_MANUAL, ?string $expiresAt = null): User
    {
        return User::create([
            'name' => 'Học viên ' . random_int(1, 99999),
            'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => $source,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => $expiresAt ?? now()->addMonth(),
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    private function group(array $overrides = []): ClassGroup
    {
        return ClassGroup::create(array_merge([
            'name' => 'Lớp B1 tối T7',
            'meet_link' => self::LINK_LOP,
            'is_active' => true,
        ], $overrides));
    }

    private function buoi(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'title' => 'Buổi 1',
            'meet_link' => null,
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ], $overrides));
    }

    // ─────────── Tương thích ngược: buổi không gắn lớp ───────────

    public function test_buoi_khong_gan_lop_van_mo_cho_moi_hoc_vien_con_han(): void
    {
        // Hành vi Pha 0 phải giữ nguyên: 3 buổi đang chạy trên production không
        // gắn lớp nào, deploy xong chúng không được đổi hành vi.
        $buoi = $this->buoi(['meet_link' => self::LINK_BUOI, 'class_group_id' => null]);

        $this->actingAs($this->student())
            ->get(route('classes.join', $buoi))
            ->assertRedirect(self::LINK_BUOI);
    }

    // ─────────── Cổng join: gõ thẳng URL ───────────

    public function test_hoc_vien_ngoai_lop_go_thang_url_thi_khong_lay_duoc_link(): void
    {
        $buoi = $this->buoi(['class_group_id' => $this->group()->id]);
        $nguoiLa = $this->student();

        $response = $this->actingAs($nguoiLa)->get(route('classes.join', $buoi));

        $response->assertRedirect(route('classes.index'));
        $this->assertStringNotContainsString(self::LINK_LOP, (string) $response->getContent());
        // Lượt bị chặn không được ghi vào nhật ký — nếu không nhật ký đầy rác.
        $this->assertDatabaseCount('class_session_joins', 0);
    }

    public function test_thanh_vien_lop_vao_duoc_va_ke_thua_link_cua_lop(): void
    {
        $lop  = $this->group();
        $buoi = $this->buoi(['class_group_id' => $lop->id]);   // buổi KHÔNG có link riêng
        $hv   = $this->student();
        $lop->members()->attach($hv->id);

        $this->actingAs($hv)
            ->get(route('classes.join', $buoi))
            ->assertRedirect(self::LINK_LOP);
    }

    public function test_link_rieng_cua_buoi_ghi_de_link_cua_lop(): void
    {
        $lop  = $this->group();
        $buoi = $this->buoi(['class_group_id' => $lop->id, 'meet_link' => self::LINK_BUOI]);
        $hv   = $this->student();
        $lop->members()->attach($hv->id);

        $this->actingAs($hv)
            ->get(route('classes.join', $buoi))
            ->assertRedirect(self::LINK_BUOI);
    }

    public function test_khong_co_link_o_ca_buoi_lan_lop_thi_bao_loi_chu_khong_redirect_rong(): void
    {
        $lop  = $this->group(['meet_link' => null]);
        $buoi = $this->buoi(['class_group_id' => $lop->id]);
        $hv   = $this->student();
        $lop->members()->attach($hv->id);

        $this->actingAs($hv)
            ->get(route('classes.join', $buoi))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    // ─────────── Khách mời riêng cho một buổi ───────────

    public function test_khach_moi_rieng_chi_vao_duoc_dung_buoi_do(): void
    {
        $lop    = $this->group();
        $buoiA  = $this->buoi(['class_group_id' => $lop->id, 'title' => 'Buổi A']);
        $buoiB  = $this->buoi(['class_group_id' => $lop->id, 'title' => 'Buổi B']);
        $khach  = $this->student();

        $buoiA->extraMembers()->attach($khach->id);

        $this->actingAs($khach)->get(route('classes.join', $buoiA))->assertRedirect(self::LINK_LOP);
        $this->actingAs($khach)->get(route('classes.join', $buoiB))->assertRedirect(route('classes.index'));
    }

    public function test_tat_lop_thi_dong_ca_thanh_vien_lan_khach_moi(): void
    {
        $lop   = $this->group(['is_active' => false]);
        $buoi  = $this->buoi(['class_group_id' => $lop->id]);
        $tv    = $this->student();
        $khach = $this->student();
        $lop->members()->attach($tv->id);
        $buoi->extraMembers()->attach($khach->id);

        $this->actingAs($tv)->get(route('classes.join', $buoi))->assertRedirect(route('classes.index'));
        $this->actingAs($khach)->get(route('classes.join', $buoi))->assertRedirect(route('classes.index'));
    }

    // ─────────── Danh sách và dashboard không lộ buổi của lớp khác ───────────

    public function test_danh_sach_va_dashboard_chi_hien_buoi_cua_lop_minh(): void
    {
        $lopA = $this->group(['name' => 'Lớp A']);
        $lopB = $this->group(['name' => 'Lớp B']);
        $this->buoi(['class_group_id' => $lopA->id, 'title' => 'Buổi của lớp A']);
        $this->buoi(['class_group_id' => $lopB->id, 'title' => 'Buổi của lớp B']);

        $hv = $this->student();
        $lopA->members()->attach($hv->id);

        $this->actingAs($hv)->get(route('classes.index'))
            ->assertSee('Buổi của lớp A')
            ->assertDontSee('Buổi của lớp B')
            ->assertDontSee(self::LINK_LOP);      // link không bao giờ nằm trong HTML

        $this->actingAs($hv)->get(route('dashboard'))
            ->assertSee('Buổi của lớp A')
            ->assertDontSee('Buổi của lớp B')
            ->assertDontSee(self::LINK_LOP);
    }

    // ─────────── Email nhắc giờ ───────────

    public function test_mail_nhac_gio_chi_gui_cho_thanh_vien_cua_buoi(): void
    {
        Mail::fake();

        $lop  = $this->group();
        $buoi = $this->buoi([
            'class_group_id' => $lop->id,
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addHours(2),
        ]);

        $trongLop = $this->student();
        $khach    = $this->student();
        $ngoaiLop = $this->student();
        $lop->members()->attach($trongLop->id);
        $buoi->extraMembers()->attach($khach->id);

        $this->artisan('classes:remind')->assertSuccessful();

        Mail::assertSent(\App\Mail\ClassSessionReminderMail::class, 2);
        Mail::assertSent(\App\Mail\ClassSessionReminderMail::class,
            fn ($m) => $m->hasTo($trongLop->email));
        Mail::assertSent(\App\Mail\ClassSessionReminderMail::class,
            fn ($m) => $m->hasTo($khach->email));
        // Người ngoài lớp KHÔNG được nhận. Gửi nhầm ở đây là gửi cho hàng trăm
        // người về một buổi họ không vào được — lỗi chỉ lộ ra sau khi đã gửi.
        Mail::assertNotSent(\App\Mail\ClassSessionReminderMail::class,
            fn ($m) => $m->hasTo($ngoaiLop->email));
    }

    // ─────────── Hai chiều của cùng một luật phải khớp nhau ───────────

    public function test_hai_chieu_cua_luat_quyen_luon_cho_cung_ket_qua(): void
    {
        // `User::canJoinClassSession` (chiều người → buổi) và
        // `User::scopeForClassSession` / `ClassSession::scopeAllowedFor`
        // (chiều buổi → người) mô tả CÙNG một luật. Chúng nằm ở ba chỗ khác
        // nhau nên sẽ lệch nhau lúc nào đó; ca này là cái chuông báo.
        $lopBat = $this->group(['name' => 'Bật']);
        $lopTat = $this->group(['name' => 'Tắt', 'is_active' => false]);

        $buoi = [
            'khong_lop' => $this->buoi(['meet_link' => self::LINK_BUOI]),
            'lop_bat'   => $this->buoi(['class_group_id' => $lopBat->id]),
            'lop_tat'   => $this->buoi(['class_group_id' => $lopTat->id]),
        ];

        $tv = $this->student();
        $lopBat->members()->attach($tv->id);
        $lopTat->members()->attach($tv->id);

        $khach = $this->student();
        $buoi['lop_bat']->extraMembers()->attach($khach->id);

        $nguoiLa  = $this->student();
        $hetHan   = $this->student(User::SOURCE_MANUAL, now()->subDay());

        foreach ($buoi as $ten => $s) {
            foreach ([$tv, $khach, $nguoiLa, $hetHan] as $u) {
                $theoNguoi = $u->fresh()->canJoinClassSession($s);

                $theoBuoi = User::forClassSession($s)->whereKey($u->id)->exists();
                $theoScope = ClassSession::whereKey($s->id)->allowedFor($u)->exists()
                    && ! $u->isExpired() && ! $u->isBlocked();

                $this->assertSame($theoNguoi, $theoBuoi,
                    "Lệch chiều người↔buổi: user {$u->id} với buổi {$ten}");
                $this->assertSame($theoNguoi, $theoScope,
                    "Lệch chiều người↔scope: user {$u->id} với buổi {$ten}");
            }
        }
    }

    // ─────────── Khu admin ───────────

    public function test_admin_quan_ly_lop_va_chon_thanh_vien(): void
    {
        $admin = $this->admin();
        $muaCK = $this->student(User::SOURCE_PURCHASE);
        $taoTay = $this->student(User::SOURCE_MANUAL);

        $this->actingAs($admin)->get(route('admin.class-groups.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.class-groups.store'), [
            'name' => 'Lớp mua CK', 'source_filter' => User::SOURCE_PURCHASE,
            'meet_link' => self::LINK_LOP, 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $lop = ClassGroup::firstWhere('name', 'Lớp mua CK');
        $this->actingAs($admin)->get(route('admin.class-groups.members', $lop))->assertOk();

        // "Thêm tất cả khớp bộ lọc" phải chỉ lấy đúng nguồn đang lọc.
        $this->actingAs($admin)->post(route('admin.class-groups.members.add-all', $lop), [
            'source' => User::SOURCE_PURCHASE, 'q' => '',
        ]);

        $this->assertTrue($lop->members()->whereKey($muaCK->id)->exists());
        $this->assertFalse($lop->members()->whereKey($taoTay->id)->exists());

        // Gỡ khỏi lớp.
        $this->actingAs($admin)->delete(route('admin.class-groups.members.remove', [$lop, $muaCK]));
        $this->assertFalse($lop->fresh()->members()->whereKey($muaCK->id)->exists());
    }

    public function test_khong_xoa_duoc_lop_con_buoi_hoc(): void
    {
        // Khoá ngoại là `restrictOnDelete`, nhưng admin phải đọc được câu tiếng
        // Việt chứ không phải trang lỗi SQL.
        $lop = $this->group();
        $this->buoi(['class_group_id' => $lop->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.class-groups.destroy', $lop))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('class_groups', ['id' => $lop->id]);
    }

    public function test_hoc_vien_bi_chan_khoi_khu_quan_ly_lop(): void
    {
        $this->actingAs($this->student())
            ->get(route('admin.class-groups.index'))
            ->assertForbidden();
    }

    public function test_buoi_khong_gan_lop_thi_khach_moi_bi_xoa_sach(): void
    {
        // Buổi mở cho tất cả thì danh sách khách mời chỉ tạo ảo giác là nó đang
        // hạn chế ai đó. Controller phải dọn, không để lại dữ liệu vô nghĩa.
        $khach = $this->student();

        $this->actingAs($this->admin())->post(route('admin.class-sessions.store'), [
            'title' => 'Workshop mở', 'meet_link' => self::LINK_BUOI,
            'class_group_id' => '', 'is_active' => 1,
            'extra_user_ids' => [$khach->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('class_session_user', 0);
    }

    public function test_buoi_gan_lop_co_link_thi_khong_bat_dan_lai_link(): void
    {
        $lop = $this->group();

        $this->actingAs($this->admin())->post(route('admin.class-sessions.store'), [
            'title' => 'Buổi kế thừa link', 'class_group_id' => $lop->id,
            'meet_link' => '', 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_sessions', [
            'title' => 'Buổi kế thừa link', 'meet_link' => null,
        ]);
    }

    public function test_buoi_khong_link_van_luu_duoc_nhung_phai_canh_bao(): void
    {
        // Lên lịch buổi trước, mở phòng Meet sau là quy trình thật. Chặn lúc nhập
        // chỉ ép admin dán link giả cho qua cửa. Nhưng phải NÓI RA, không thì tới
        // giờ học viên không thấy nút và không ai hiểu vì sao.
        $this->actingAs($this->admin())->post(route('admin.class-sessions.store'), [
            'title' => 'Buổi thiếu link', 'class_group_id' => '', 'meet_link' => '', 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('warning');

        $buoi = ClassSession::firstWhere('title', 'Buổi thiếu link');

        // Không có link ⇒ không mở cửa ⇒ học viên không thấy nút.
        $this->assertFalse($buoi->isJoinable());
        $this->actingAs($this->student())
            ->get(route('classes.join', $buoi))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cachGoLink')]
    public function test_nhap_link_thieu_https_hoac_chi_ma_phong_van_luu_duoc(string $nhap, string $mongDoi): void
    {
        $this->actingAs($this->admin())->post(route('admin.class-sessions.store'), [
            'title' => 'Buổi test link', 'class_group_id' => '', 'meet_link' => $nhap, 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($mongDoi, ClassSession::firstWhere('title', 'Buổi test link')->meet_link);
    }

    public static function cachGoLink(): array
    {
        return [
            'thiếu https'      => ['meet.google.com/bbb-tigq-saf', 'https://meet.google.com/bbb-tigq-saf'],
            'đủ https'         => ['https://meet.google.com/bbb-tigq-saf', 'https://meet.google.com/bbb-tigq-saf'],
            'chỉ mã phòng'     => ['bbb-tigq-saf', 'https://meet.google.com/bbb-tigq-saf'],
            'mã phòng CHỮ HOA' => ['BBB-TIGQ-SAF', 'https://meet.google.com/bbb-tigq-saf'],
            'thừa khoảng trắng'=> ['  meet.google.com/bbb-tigq-saf  ', 'https://meet.google.com/bbb-tigq-saf'],
            'giữ nguyên http'  => ['http://meet.google.com/bbb-tigq-saf', 'http://meet.google.com/bbb-tigq-saf'],
        ];
    }

    public function test_buoi_thieu_link_noi_ro_ly_do_chu_khong_in_mo_luc_trong(): void
    {
        // Bản trước in "Mở lúc {joinOpensAt}" cho MỌI trường hợp không vào được.
        // Buổi thiếu link mà đang trong giờ thì `joinOpensAt` là giờ quá khứ hoặc
        // null → học viên đọc được "Mở lúc" cụt hoặc một giờ đã trôi qua, và không
        // ai hiểu vì sao không có nút.
        $lop  = $this->group(['meet_link' => null]);
        $buoi = $this->buoi(['class_group_id' => $lop->id, 'title' => 'Buổi chưa có phòng']);
        $hv   = $this->student();
        $lop->members()->attach($hv->id);

        $this->actingAs($hv)->get(route('classes.index'))
            ->assertSee('Buổi chưa có phòng')
            ->assertSee('Giảng viên chưa mở phòng')
            ->assertDontSee('Mở lúc');
    }

    public function test_form_buoi_hoc_co_nut_bat_dau_ngay_va_o_dien_giai_gio(): void
    {
        // Khối Alpine diễn giải giờ là phần chính của bản vá "tưởng đặt giờ hiện
        // tại mà thật ra 2 tiếng nữa". Nếu Blade escape hỏng thì nó biến mất âm
        // thầm — trang vẫn 200, chỉ là không còn tác dụng gì.
        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.create'))
            ->assertOk()
            ->assertSee('Bắt đầu ngay bây giờ')
            ->assertSee('Xoá giờ (mở tự do)')
            ->assertSee('x-text="moTa"', false);
    }

    public function test_lenh_chan_doan_chi_dung_cua_dang_chan(): void
    {
        $lop  = $this->group(['meet_link' => null, 'name' => 'Lớp chưa có link']);
        $buoi = $this->buoi(['class_group_id' => $lop->id]);
        $ngoaiLop = $this->student();

        $this->artisan("classes:diagnose {$buoi->id} --user={$ngoaiLop->email}")
            ->expectsOutputToContain('Có link phòng')
            ->expectsOutputToContain('CHƯA CÓ LINK')
            ->expectsOutputToContain('CHƯA CÓ THÀNH VIÊN NÀO')
            ->expectsOutputToContain('KHÔNG VÀO ĐƯỢC')
            ->assertSuccessful();
    }

    public function test_lenh_chan_doan_bao_vao_duoc_khi_moi_thu_dung(): void
    {
        $lop  = $this->group();
        $buoi = $this->buoi(['class_group_id' => $lop->id]);
        $hv   = $this->student();
        $lop->members()->attach($hv->id);

        $this->artisan("classes:diagnose {$buoi->id} --user={$hv->email}")
            ->expectsOutputToContain('VÀO ĐƯỢC')
            ->assertSuccessful();
    }

    public function test_link_cua_lop_cung_duoc_chuan_hoa(): void
    {
        // ⚠️ Phải khẳng định cả REDIRECT, không chỉ `assertSessionHasNoErrors`:
        // trang lỗi 500 cũng "không có lỗi trong session" nên assert kia một mình
        // sẽ xanh trong khi thực tế controller đang nổ. Chính ca này bắt được lỗi
        // "Undefined array key source_filter" — form không gửi ô đó thì 500.
        $this->actingAs($this->admin())->post(route('admin.class-groups.store'), [
            'name' => 'Lớp nhập link tắt', 'meet_link' => 'meet.google.com/xyz-abcd-efg', 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(
            'https://meet.google.com/xyz-abcd-efg',
            ClassGroup::firstWhere('name', 'Lớp nhập link tắt')->meet_link
        );
    }
}
