<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bộ lọc "quá hạn lâu" + số đếm hiện trong ô lọc trên `/admin/users`.
 *
 * "Đã quá hạn" gộp cả người vừa hết hạn hôm qua — mà người đó thường gia hạn
 * ngay sau khi thi. Tách riêng mốc 30/90 ngày mới nhìn ra nhóm thật sự nguội.
 */
class AdminUserExpiryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    private function hocVien(string $ten, ?string $han): User
    {
        return User::create([
            'name' => $ten, 'email' => strtolower(str_replace(' ', '', $ten)) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_IMPORT,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => $han,
        ]);
    }

    private function duLieuMau(): void
    {
        $this->hocVien('Moi Het Han', now()->subDays(2));
        $this->hocVien('Het Han 60 Ngay', now()->subDays(60));
        $this->hocVien('Het Han 200 Ngay', now()->subDays(200));
        $this->hocVien('Sap Thi', now()->addDays(3));
        $this->hocVien('Con Dai', now()->addYear());
        $this->hocVien('Khong Gioi Han', null);
    }

    public function test_loc_qua_han_tren_30_ngay(): void
    {
        $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['expiration' => 'expired_30']))
            ->assertOk()
            ->assertSee('Het Han 60 Ngay')
            ->assertSee('Het Han 200 Ngay')
            ->assertDontSee('Moi Het Han');
    }

    public function test_loc_qua_han_tren_90_ngay(): void
    {
        $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['expiration' => 'expired_90']))
            ->assertOk()
            ->assertSee('Het Han 200 Ngay')
            ->assertDontSee('Het Han 60 Ngay');
    }

    public function test_tai_khoan_khong_gioi_han_khong_bi_tinh_la_qua_han(): void
    {
        // `NULL < x` cho ra NULL chứ không phải true. Nếu ngày nào đó đổi sang
        // so sánh kiểu khác thì ca này đỏ trước khi admin xoá nhầm người.
        $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['expiration' => 'expired']))
            ->assertOk()
            ->assertDontSee('Khong Gioi Han');
    }

    public function test_so_dem_hien_trong_o_loc_va_khop_voi_bo_loc(): void
    {
        $this->duLieuMau();

        // 3 quá hạn · 2 trong đó quá 30 ngày · 1 quá 90 ngày · 1 sắp thi
        // · 1 còn dài · 1 không giới hạn.
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Đã quá hạn (3)')
            ->assertSee('Quá hạn trên 30 ngày (2)')
            ->assertSee('Quá hạn trên 90 ngày (1)')
            ->assertSee('Sắp thi (7 ngày) (1)')
            ->assertSee('Không giới hạn (1)');
    }

    public function test_so_dem_khong_tinh_tai_khoan_admin(): void
    {
        $this->hocVien('Het Han 200 Ngay', now()->subDays(200));

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            // Admin không có `expires_at` nhưng không được cộng vào ô "Không giới hạn".
            ->assertSee('Không giới hạn (0)');
    }
}
