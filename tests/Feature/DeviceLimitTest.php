<?php

namespace Tests\Feature;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giới hạn thiết bị đồng thời (`config/devices.php`).
 *
 * Hai lỗi cũ mà bộ test này giữ cho không tái phát:
 *  1. Đếm CẢ LỊCH SỬ đăng nhập thay vì phiên đang hoạt động → một người ngồi một
 *     máy mở tab ẩn danh vài lần là bị khoá.
 *  2. Tra `device_id` không kèm `user_id` → đổi tài khoản trên cùng máy thì bỏ
 *     qua phép đếm, lách được giới hạn.
 */
class DeviceLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Chốt luật trong test để không phụ thuộc .env của máy chạy.
        config([
            'devices.max_devices'            => 2,
            'devices.activity_window_hours'  => 6,
            'devices.block_after_violations' => 2,
            'devices.violation_reset_days'   => 30,
        ]);
    }

    private function student(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'HV', 'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ], $overrides));
    }

    /** Trình duyệt chưa từng vào: không mang cookie thiết bị nào. */
    private function tuThietBiMoi(User $u)
    {
        return $this->actingAs($u)->get(route('dashboard'));
    }

    /**
     * Quay lại bằng một thiết bị đã biết.
     *
     * ⚠️ Phải dùng `withCookie` (không phải `withUnencryptedCookie`) và truyền
     * device_id THÔ. Cookie `aptis_device_id` đi qua middleware `EncryptCookies`;
     * `withCookie` tự mã hoá giá trị giúp nên app đọc ra đúng chuỗi thô, còn
     * `withUnencryptedCookie` gửi nguyên si thì middleware giải mã hỏng và hiểu
     * thành "chưa có cookie" — mọi ca test sẽ xanh vì lý do sai.
     */
    private function tuThietBiCu(User $u, string $deviceId)
    {
        return $this->actingAs($u)->withCookie('aptis_device_id', $deviceId)->get(route('dashboard'));
    }

    private function phienCu(User $u, string $deviceId, string $hoatDongLuc): void
    {
        LoginSession::create([
            'user_id' => $u->id, 'device_id' => $deviceId,
            'ip_address' => '1.1.1.1', 'user_agent' => 'test',
            'last_active_at' => $hoatDongLuc,
        ]);
    }

    // ─────────── Lỗi cũ #1: phiên chết không được chiếm suất ───────────

    public function test_phien_da_chet_khong_con_chiem_suat(): void
    {
        $u = $this->student();

        // 5 thiết bị cũ, đều không hoạt động quá cửa sổ 6 giờ (ẩn danh, xoá cookie…).
        foreach (range(1, 5) as $i) {
            $this->phienCu($u, "cu-{$i}", now()->subHours(20)->toDateTimeString());
        }

        $this->tuThietBiMoi($u)->assertOk();

        // Không vi phạm: 5 dòng kia đã chết, không tính là thiết bị đang dùng.
        $this->assertSame(0, $u->fresh()->violation_count);
        $this->assertSame('active', $u->fresh()->status);
    }

    public function test_mo_an_danh_nhieu_lan_khong_con_bi_khoa(): void
    {
        // Đây đúng là ca đã khoá oan chủ dự án: một người, một máy, mở ẩn danh
        // nhiều lần. Mỗi lần là một cookie mới nên thành "thiết bị mới" vĩnh viễn.
        $u = $this->student();

        foreach (range(1, 6) as $i) {
            $this->phienCu($u, "an-danh-{$i}", now()->subHours(10)->toDateTimeString());
        }

        $this->tuThietBiMoi($u)->assertOk();

        $this->assertSame('active', $u->fresh()->status);
    }

    // ─────────── Trần thiết bị đồng thời vẫn có hiệu lực ───────────

    public function test_thiet_bi_thu_3_dung_cung_luc_bi_canh_bao(): void
    {
        $u = $this->student();
        $this->phienCu($u, 'may-1', now()->subMinutes(5)->toDateTimeString());
        $this->phienCu($u, 'may-2', now()->subMinutes(2)->toDateTimeString());

        $this->tuThietBiMoi($u)->assertOk();

        $u->refresh();
        $this->assertSame(1, $u->violation_count);
        $this->assertSame('active', $u->status, 'Cảnh báo lần đầu thì vẫn cho vào.');
        $this->assertNotNull($u->last_violation_at);
    }

    public function test_vi_pham_lan_2_thi_khoa_tai_khoan(): void
    {
        $u = $this->student(['violation_count' => 1, 'last_violation_at' => now()->subDay()]);
        $this->phienCu($u, 'may-1', now()->subMinutes(5)->toDateTimeString());
        $this->phienCu($u, 'may-2', now()->subMinutes(2)->toDateTimeString());

        $this->tuThietBiMoi($u);

        $u->refresh();
        $this->assertSame(2, $u->violation_count);
        $this->assertSame('blocked', $u->status);
        // Khoá thì cắt sạch phiên, không để thiết bị nào còn đang đăng nhập.
        $this->assertSame(0, $u->loginSessions()->count());
    }

    // ─────────── Vi phạm hết hạn ───────────

    public function test_vi_pham_cu_hon_30_ngay_khong_duoc_cong_don(): void
    {
        // "Cố tình lần nữa" hàm ý hai lần GẦN NHAU. Một lần hồi tháng trước cộng
        // một lần hôm nay mà thành khoá thì đó là xui, không phải cố tình.
        $u = $this->student(['violation_count' => 1, 'last_violation_at' => now()->subDays(45)]);
        $this->phienCu($u, 'may-1', now()->subMinutes(5)->toDateTimeString());
        $this->phienCu($u, 'may-2', now()->subMinutes(2)->toDateTimeString());

        $this->tuThietBiMoi($u);

        $u->refresh();
        $this->assertSame(1, $u->violation_count, 'Đếm lại từ đầu, không cộng dồn.');
        $this->assertSame('active', $u->status);
    }

    // ─────────── Lỗi cũ #2: đổi tài khoản trên cùng máy ───────────

    public function test_doi_tai_khoan_tren_cung_may_van_bi_dem(): void
    {
        // Bản cũ tra `device_id` KHÔNG kèm `user_id`: dòng của A bị gán sang B rồi
        // thoát sớm, bỏ qua phép đếm của B — lách được giới hạn.
        $a = $this->student();
        $b = $this->student();

        $this->phienCu($a, 'may-chung', now()->subMinutes(1)->toDateTimeString());
        $this->phienCu($b, 'b-may-1', now()->subMinutes(5)->toDateTimeString());
        $this->phienCu($b, 'b-may-2', now()->subMinutes(2)->toDateTimeString());

        // B đăng nhập trên đúng cái máy đã có phiên của A.
        $this->tuThietBiCu($b, 'may-chung');

        $b->refresh();
        $this->assertSame(1, $b->violation_count, 'Phải bị đếm là thiết bị thứ 3 của B.');
        // Phiên của A không được cướp mất — đây là lỗ hổng cũ.
        $this->assertTrue(
            LoginSession::where('device_id', 'may-chung')->where('user_id', $a->id)->exists(),
            'Dòng của tài khoản A phải còn nguyên, không bị gán sang B.'
        );
    }

    public function test_cung_thiet_bi_cua_chinh_minh_thi_khong_bi_dem_lai(): void
    {
        $u = $this->student();
        $this->phienCu($u, 'may-cua-toi', now()->subHours(3)->toDateTimeString());
        $this->phienCu($u, 'may-2', now()->subMinutes(2)->toDateTimeString());

        $this->tuThietBiCu($u, 'may-cua-toi');

        $this->assertSame(0, $u->fresh()->violation_count);
        $this->assertSame(2, $u->loginSessions()->count(), 'Không sinh dòng mới.');
    }

    // ─────────── Admin ───────────

    public function test_admin_khong_bi_gioi_han_thiet_bi(): void
    {
        $admin = $this->student(['role' => 'admin']);
        foreach (range(1, 5) as $i) {
            $this->phienCu($admin, "ad-{$i}", now()->subMinute()->toDateTimeString());
        }

        $this->tuThietBiMoi($admin);

        $this->assertSame(0, $admin->fresh()->violation_count);
    }

    public function test_go_khoa_reset_luon_so_vi_pham(): void
    {
        // Bẫy cũ: Unblock chỉ đổi status, vi phạm giữ nguyên ở mức chạm ngưỡng →
        // tài khoản khoá lại ngay lần đăng nhập thiết bị mới kế tiếp.
        $admin = $this->student(['role' => 'admin']);
        $u = $this->student(['status' => 'blocked', 'violation_count' => 2, 'last_violation_at' => now()]);

        $this->actingAs($admin)->post(route('admin.users.unblock', $u));

        $u->refresh();
        $this->assertSame('active', $u->status);
        $this->assertSame(0, $u->violation_count);
        $this->assertNull($u->last_violation_at);
    }

    // ─────────── Lệnh triển khai ───────────

    public function test_lenh_ap_chinh_sach_dat_tran_va_reset_vi_pham(): void
    {
        $u = $this->student(['max_devices' => 3, 'violation_count' => 4, 'last_violation_at' => now()]);
        $this->phienCu($u, 'rat-cu', now()->subDays(90)->toDateTimeString());
        $this->phienCu($u, 'con-moi', now()->subMinute()->toDateTimeString());

        $this->artisan('devices:apply-policy')->assertSuccessful();

        $u->refresh();
        $this->assertSame(2, $u->max_devices);
        $this->assertSame(0, $u->violation_count);
        $this->assertNull($u->last_violation_at);
        // Dọn phiên chết, giữ phiên còn sống.
        $this->assertFalse(LoginSession::where('device_id', 'rat-cu')->exists());
        $this->assertTrue(LoginSession::where('device_id', 'con-moi')->exists());
    }

    public function test_lenh_ap_chinh_sach_dry_run_khong_ghi_gi(): void
    {
        $u = $this->student(['max_devices' => 3, 'violation_count' => 4]);

        $this->artisan('devices:apply-policy --dry-run')->assertSuccessful();

        $u->refresh();
        $this->assertSame(3, $u->max_devices);
        $this->assertSame(4, $u->violation_count);
    }

    public function test_lenh_ap_chinh_sach_khong_tu_mo_khoa_ai(): void
    {
        // Tài khoản có thể bị khoá vì lý do khác (admin chặn tay). Tự mở hết là vượt quyền.
        $u = $this->student(['status' => 'blocked', 'violation_count' => 3]);

        $this->artisan('devices:apply-policy')->assertSuccessful();

        $this->assertSame('blocked', $u->fresh()->status);
    }

    // ─────────── Múi giờ: sản phẩm cho người Việt ───────────

    public function test_ung_dung_luon_chay_gio_viet_nam(): void
    {
        // Dev ngồi ở Nhật (UTC+9) nhưng khách hàng ở Việt Nam. Nếu ai đó đổi
        // `config/app.php` sang UTC hoặc đọc từ env của máy dev thì mọi giờ buổi
        // học lệch đi mà không báo lỗi gì — ca này chặn đúng chuyện đó.
        $this->assertSame('Asia/Ho_Chi_Minh', config('app.timezone'));
        $this->assertSame('+07:00', now()->format('P'));
    }
}
