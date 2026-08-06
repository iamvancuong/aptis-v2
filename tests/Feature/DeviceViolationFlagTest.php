<?php

namespace Tests\Feature;

use App\Models\SecurityFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vi phạm thiết bị hiện trên trang Cảnh báo bảo mật.
 *
 * Trước đây `SessionLimit` chỉ tăng `violation_count` — một con số nói "3 lần"
 * mà không nói lần nào, từ đâu, bằng máy gì. Nó lại bị reset sau 30 ngày và khi
 * admin bấm Unblock, nên nhìn vào đó không dựng lại được chuyện đã xảy ra.
 */
class DeviceViolationFlagTest extends TestCase
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

    private function hocVien(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Hoc Vien', 'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_IMPORT,
            'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ], $overrides));
    }

    public function test_tai_khoan_dang_mang_vi_pham_hien_ra_du_chua_co_nhat_ky(): void
    {
        // Đây là lý do bảng này đọc `violation_count` chứ không đọc `security_flags`:
        // tài khoản vi phạm TRƯỚC khi có tính năng ghi log vẫn phải nhìn thấy được.
        $this->hocVien([
            'name' => 'Nguoi Vi Pham',
            'violation_count' => 2,
            'last_violation_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.security-flags.index'))
            ->assertOk()
            ->assertSee('Nguoi Vi Pham')
            ->assertSee('Tài khoản đang mang vi phạm thiết bị (1)');
    }

    public function test_tai_khoan_sach_khong_hien_trong_bang_vi_pham(): void
    {
        $this->hocVien(['name' => 'Nguoi Ngoan', 'violation_count' => 0]);

        $this->actingAs($this->admin())
            ->get(route('admin.security-flags.index'))
            ->assertOk()
            ->assertDontSee('Nguoi Ngoan');
    }

    public function test_nhat_ky_vi_pham_thiet_bi_hien_ip_va_ghi_chu(): void
    {
        $hv = $this->hocVien(['name' => 'Co Nhat Ky', 'violation_count' => 1]);

        SecurityFlag::create([
            'user_id' => $hv->id,
            'type' => SecurityFlag::TYPE_DEVICE,
            'ip_address' => '203.0.113.45',
            'user_agent' => 'Mozilla/5.0',
            'url' => 'Vi phạm 1/2 · trần 2 thiết bị',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.security-flags.index'))
            ->assertOk()
            ->assertSee('203.0.113.45')
            ->assertSee('Vi phạm 1/2 · trần 2 thiết bị', false);
    }

    public function test_hai_loai_canh_bao_khong_tron_vao_nhau(): void
    {
        // Gộp chung thì DevTools (hiếm, đáng chú ý) sẽ chìm trong vi phạm thiết
        // bị (nhiều hơn hẳn) và trôi khỏi trang đầu.
        $hv = $this->hocVien(['violation_count' => 1]);

        SecurityFlag::create([
            'user_id' => $hv->id, 'type' => SecurityFlag::TYPE_DEVICE,
            'ip_address' => '203.0.113.45', 'user_agent' => 'x', 'url' => 'thiet-bi',
        ]);
        SecurityFlag::create([
            'user_id' => $hv->id, 'type' => SecurityFlag::TYPE_DEVTOOLS,
            'ip_address' => '198.51.100.7', 'user_agent' => 'x', 'url' => '/dashboard',
        ]);

        $res = $this->actingAs($this->admin())->get(route('admin.security-flags.index'))->assertOk();

        // Bảng DevTools chỉ đếm 1 lần — không cộng cả dòng vi phạm thiết bị vào.
        $res->assertSee('198.51.100.7');
        $this->assertSame(1, SecurityFlag::where('type', SecurityFlag::TYPE_DEVTOOLS)->count());
        $this->assertSame(1, SecurityFlag::where('type', SecurityFlag::TYPE_DEVICE)->count());
    }

    public function test_hoc_vien_khong_vao_duoc_trang_canh_bao(): void
    {
        $this->actingAs($this->hocVien())
            ->get(route('admin.security-flags.index'))
            ->assertForbidden();
    }
}
