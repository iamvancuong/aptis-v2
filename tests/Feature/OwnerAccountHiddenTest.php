<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tài khoản CHỦ SỞ HỮU (role owner) phải:
 *  1) Bị ẩn khỏi mọi truy vấn của người khác — kể cả admin (danh sách user,
 *     scope mời lớp), nhờ global scope `hideOwner`.
 *  2) Chính owner đăng nhập vào thì thấy được mình + thấy khối "Doanh thu tổng
 *     thật" ở màn Doanh số, còn admin thì KHÔNG thấy khối đó.
 *  3) Vẫn đăng nhập được (global scope không được chặn Auth::attempt).
 */
class OwnerAccountHiddenTest extends TestCase
{
    use RefreshDatabase;

    private function make(array $overrides = []): User
    {
        return User::withoutGlobalScopes()->create(array_merge([
            'name' => 'X ' . random_int(1, 99999),
            'email' => 'x' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('secret123'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 3, 'violation_count' => 0,
        ], $overrides));
    }

    public function test_admin_khong_thay_owner_trong_danh_sach_user(): void
    {
        $admin = $this->make(['role' => 'admin', 'name' => 'Admin That']);
        $owner = $this->make(['role' => User::ROLE_OWNER, 'source' => User::SOURCE_SYSTEM, 'name' => 'Chu So Huu']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee($owner->email);

        // Truy vấn thường (đang acting-as admin) cũng không thấy owner.
        $this->assertNull(User::where('email', $owner->email)->first());
        $this->assertTrue(User::whereKey($owner->id)->doesntExist());
    }

    public function test_owner_bi_loai_khoi_scope_moi_lop(): void
    {
        $this->make(['role' => User::ROLE_OWNER, 'source' => User::SOURCE_SYSTEM]);
        $hocVien = $this->make(['name' => 'Hoc Vien']);

        // Chạy không đăng nhập (như console schedule) → global scope không áp,
        // nhưng scopeInvitableToClass phải tự loại owner bằng bộ lọc vai trò.
        $ids = User::invitableToClass()->pluck('role')->all();
        $this->assertNotContains(User::ROLE_OWNER, $ids);
        $this->assertContains('user', $ids);
    }

    public function test_owner_dang_nhap_duoc_va_thay_chinh_minh(): void
    {
        $owner = $this->make(['role' => User::ROLE_OWNER, 'source' => User::SOURCE_SYSTEM]);

        // Auth::attempt phải tìm được owner (global scope không chặn lúc login).
        $this->assertTrue(auth()->attempt(['email' => $owner->email, 'password' => 'secret123']));

        // Khi CHÍNH owner đăng nhập, truy vấn user thấy lại được owner.
        $this->actingAs($owner);
        $this->assertNotNull(User::where('email', $owner->email)->first());
    }

    public function test_chi_owner_thay_khoi_doanh_thu_tong_that(): void
    {
        $admin = $this->make(['role' => 'admin']);
        $owner = $this->make(['role' => User::ROLE_OWNER, 'source' => User::SOURCE_SYSTEM]);

        // 3 học viên thêm tay → offline = 3 × 2.000.000 = 6.000.000đ.
        $this->make(['source' => User::SOURCE_MANUAL]);
        $this->make(['source' => User::SOURCE_MANUAL]);
        $this->make(['source' => User::SOURCE_MANUAL]);

        // Admin KHÔNG thấy khối owner.
        $this->actingAs($admin)
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertDontSee('Doanh thu tổng thật');

        // Owner thấy khối + đúng số offline (6.000.000).
        $this->actingAs($owner)
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('Doanh thu tổng thật')
            ->assertSee('6.000.000đ');
    }
}
