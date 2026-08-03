<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bộ lọc ở `/admin/users`.
 *
 * `User::scopeFilter` được dùng chung bởi màn danh sách VÀ Export Excel. Trước
 * đây hai chỗ chép logic của nhau và đã lệch: export chỉ hiểu search/role/status
 * nên lọc "đã quá hạn" rồi bấm Export ra file chứa toàn bộ người dùng. Bộ test
 * này giữ cho chúng không lệch lại.
 */
class AdminUserFilterTest extends TestCase
{
    use RefreshDatabase;

    /** `created_at` không nằm trong $fillable nên phải ghi riêng sau khi tạo. */
    private function user(array $overrides = [], ?string $createdAt = null): User
    {
        $u = User::create(array_merge([
            'name' => 'HV ' . random_int(1, 99999),
            'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 3, 'violation_count' => 0,
        ], $overrides));

        if ($createdAt) {
            $u->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $u->fresh();
    }

    private function admin(): User
    {
        return $this->user(['role' => 'admin']);
    }

    public function test_loc_theo_nguon_tai_khoan(): void
    {
        $muaWeb = $this->user(['source' => User::SOURCE_PURCHASE, 'name' => 'Nguoi mua web']);
        $taoTay = $this->user(['source' => User::SOURCE_MANUAL, 'name' => 'Nguoi admin them']);

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['source' => User::SOURCE_PURCHASE]))
            ->assertOk()
            ->assertSee($muaWeb->email)
            ->assertDontSee($taoTay->email);
    }

    public function test_loc_moi_them_trong_7_va_14_ngay(): void
    {
        $homQua  = $this->user([], now()->subDay()->toDateTimeString());
        $muoiNgay = $this->user([], now()->subDays(10)->toDateTimeString());
        $cuLam   = $this->user([], now()->subDays(60)->toDateTimeString());

        $admin = $this->admin();

        // 7 ngày: chỉ người hôm qua.
        $this->actingAs($admin)->get(route('admin.users.index', ['joined' => '7']))
            ->assertSee($homQua->email)
            ->assertDontSee($muoiNgay->email)
            ->assertDontSee($cuLam->email);

        // 14 ngày: thêm người 10 ngày trước.
        $this->actingAs($admin)->get(route('admin.users.index', ['joined' => '14']))
            ->assertSee($homQua->email)
            ->assertSee($muoiNgay->email)
            ->assertDontSee($cuLam->email);
    }

    public function test_loc_moi_them_tuy_chinh_so_ngay(): void
    {
        $trong20 = $this->user([], now()->subDays(20)->toDateTimeString());
        $ngoai20 = $this->user([], now()->subDays(40)->toDateTimeString());

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['joined' => 'custom', 'joined_days' => 25]))
            ->assertSee($trong20->email)
            ->assertDontSee($ngoai20->email);
    }

    public function test_ket_hop_nguon_va_moi_them(): void
    {
        // Ca dùng thật: "học viên tự thanh toán qua web trong 14 ngày qua".
        $can    = $this->user(['source' => User::SOURCE_PURCHASE], now()->subDays(3)->toDateTimeString());
        $saiNguon = $this->user(['source' => User::SOURCE_MANUAL], now()->subDays(3)->toDateTimeString());
        $quaCu  = $this->user(['source' => User::SOURCE_PURCHASE], now()->subDays(40)->toDateTimeString());

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['source' => User::SOURCE_PURCHASE, 'joined' => '14']))
            ->assertSee($can->email)
            ->assertDontSee($saiNguon->email)
            ->assertDontSee($quaCu->email);
    }

    public function test_export_ton_trong_moi_bo_loc_chu_khong_chi_search_role_status(): void
    {
        // Đây là bản vá cho lỗi cũ: export bỏ qua `expiration` nên xuất cả người
        // còn hạn. Lỗi im lặng — file vẫn tải về bình thường, chỉ là sai nội dung.
        $quaHan = $this->user(['expires_at' => now()->subDay()]);
        $conHan = $this->user(['expires_at' => now()->addMonth()]);

        $rows = (new UsersExport(['expiration' => 'expired']))->collection();

        $this->assertTrue($rows->contains('id', $quaHan->id));
        $this->assertFalse($rows->contains('id', $conHan->id));
    }

    public function test_export_ton_trong_loc_nguon_va_moi_them(): void
    {
        $can  = $this->user(['source' => User::SOURCE_PURCHASE], now()->subDays(2)->toDateTimeString());
        $khong = $this->user(['source' => User::SOURCE_PURCHASE], now()->subDays(30)->toDateTimeString());

        $rows = (new UsersExport(['source' => User::SOURCE_PURCHASE, 'joined' => '7']))->collection();

        $this->assertTrue($rows->contains('id', $can->id));
        $this->assertFalse($rows->contains('id', $khong->id));
    }

    public function test_khong_loc_gi_thi_tra_ve_tat_ca(): void
    {
        $this->user();
        $this->user();

        $this->assertSame(2, User::filter([])->where('role', 'user')->count());
        // Chuỗi rỗng phải được coi như "không lọc", không phải `where source = ''`.
        $this->assertSame(2, User::filter(['source' => '', 'joined' => ''])->where('role', 'user')->count());
    }
}
