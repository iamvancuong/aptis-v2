<?php

namespace Tests\Feature;

use App\Mail\AccountCredentialsMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Admin tạo tài khoản bằng tay → gửi luôn email thông tin đăng nhập cho học
 * viên (mặc định bật), thay vì admin phải copy mật khẩu gửi riêng.
 */
class AdminCreateUserMailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'status' => 'active', 'source' => User::SOURCE_MANUAL,
            'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    private function tao(array $them = [])
    {
        return $this->actingAs($this->admin())->post(route('admin.users.store'), array_merge([
            'name' => 'Nguyen Van An', 'email' => 'an@example.test', 'role' => 'user',
        ], $them));
    }

    public function test_tick_gui_email_thi_hoc_vien_nhan_thu_co_mat_khau_dang_nhap_duoc(): void
    {
        Mail::fake();

        $this->tao(['send_credentials' => '1'])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'gửi email'));

        $user = User::where('email', 'an@example.test')->firstOrFail();
        Mail::assertSent(AccountCredentialsMail::class, function ($mail) use ($user) {
            return $mail->hasTo('an@example.test') && $mail->isNew
                && \Illuminate\Support\Facades\Hash::check($mail->password, $user->password);
        });
        $this->assertTrue((bool) $user->must_change_password);
    }

    public function test_bo_tick_thi_khong_gui(): void
    {
        Mail::fake();
        $this->tao(['send_credentials' => '0'])->assertSessionHas('success');
        Mail::assertNothingSent();
    }

    public function test_gui_loi_van_tao_tai_khoan_va_canh_bao_kem_mat_khau(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->tao(['send_credentials' => '1'])
            ->assertSessionHas('warning', fn ($msg) => str_contains($msg, 'GỬI EMAIL THẤT BẠI') && str_contains($msg, 'Mật khẩu tạm'));
        $this->assertDatabaseHas('users', ['email' => 'an@example.test']);
    }
}
