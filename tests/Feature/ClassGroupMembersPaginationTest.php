<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Màn thành viên lớp với lớp ĐÔNG (lớp thật đang có 710 người).
 *
 * Ca quan trọng nhất là ca thứ hai: **danh sách mời Calendar phải đủ cả lớp**,
 * không phải chỉ trang đang xem. Phân trang mà kéo theo cả nút copy thì hỏng
 * IM LẶNG — admin dán vào Calendar, thấy có email nên tin là xong, và chỉ phát
 * hiện khi phần lớn học viên phải xin duyệt giữa buổi dạy.
 */
class ClassGroupMembersPaginationTest extends TestCase
{
    use RefreshDatabase;

    private const SO_THANH_VIEN = 30;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    private function lopDong(): ClassGroup
    {
        $lop = ClassGroup::create(['name' => 'Nhóm chính', 'is_active' => true]);

        for ($i = 1; $i <= self::SO_THANH_VIEN; $i++) {
            $stt = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            $hv = User::create([
                'name' => "HV {$stt}", 'email' => "hv{$stt}@example.test",
                'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_IMPORT,
                'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
                'expires_at' => now()->addMonth(),
            ]);

            $lop->members()->attach($hv->id, ['added_at' => now()]);
        }

        return $lop;
    }

    public function test_bang_thanh_vien_duoc_phan_trang(): void
    {
        $lop = $this->lopDong();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', $lop))
            ->assertOk()
            ->assertSee('HV 01')
            ->assertSee('HV 25')
            // Người thứ 26 trở đi nằm ở trang sau, không đổ hết ra một trang.
            ->assertDontSee('HV 26')
            ->assertSee('Thành viên hiện tại (30)');
    }

    public function test_danh_sach_moi_calendar_van_du_ca_lop_du_bang_da_phan_trang(): void
    {
        $lop = $this->lopDong();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', $lop))
            ->assertOk()
            // Nút copy phải nói đúng 30, không phải 25 của trang đang xem.
            ->assertSee('Copy 30 địa chỉ')
            // Và email của người ở trang 2 vẫn phải nằm trong danh sách copy.
            ->assertSee('hv30@example.test');
    }

    public function test_tim_thanh_vien_trong_lop(): void
    {
        $lop = $this->lopDong();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', [$lop, 'qtv' => 'HV 30']))
            ->assertOk()
            ->assertSee('HV 30')
            ->assertDontSee('HV 01');
    }

    public function test_trang_hai_hien_nhung_nguoi_con_lai(): void
    {
        $lop = $this->lopDong();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', [$lop, 'tv' => 2]))
            ->assertOk()
            ->assertSee('HV 26')
            ->assertDontSee('HV 01');
    }
}
