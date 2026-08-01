<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cổng vào lớp online (Pha 0 — §16).
 *
 * Điểm cần bảo vệ: link Google Meet CHỈ được trả về khi tài khoản còn hạn VÀ
 * buổi học đang mở cửa. Ngoài ra link không bao giờ nằm trong HTML để copy.
 */
class ClassSessionJoinTest extends TestCase
{
    use RefreshDatabase;

    private const MEET_LINK = 'https://meet.google.com/abc-defg-hij';

    private function student(?string $expiresAt = null): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0, 'expires_at' => $expiresAt,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function makeSession(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'title' => 'Buổi 1 — Chữa Writing',
            'meet_link' => self::MEET_LINK,
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ], $overrides));
    }

    public function test_student_in_time_window_is_redirected_to_the_meet_link(): void
    {
        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $this->makeSession()))
            ->assertRedirect(self::MEET_LINK);
    }

    public function test_join_opens_early_before_the_start_time(): void
    {
        // Cửa lớp mở trước giờ bắt đầu JOIN_EARLY_MINUTES phút.
        $session = $this->makeSession(['starts_at' => now()->addMinutes(10), 'ends_at' => now()->addHours(2)]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(self::MEET_LINK);
    }

    public function test_expired_account_never_reaches_the_link(): void
    {
        // Middleware CheckAccountExpiration (toàn cục) logout + đẩy về login,
        // nên tài khoản hết hạn không thể chạm tới link Meet.
        $this->actingAs($this->student(now()->subDay()))
            ->get(route('classes.join', $this->makeSession()))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_session_not_started_yet_is_blocked(): void
    {
        $session = $this->makeSession(['starts_at' => now()->addHours(3), 'ends_at' => now()->addHours(4)]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    public function test_ended_session_is_blocked(): void
    {
        $session = $this->makeSession(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHour()]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    public function test_deactivated_session_is_blocked(): void
    {
        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $this->makeSession(['is_active' => false])))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('classes.join', $this->makeSession()))
            ->assertRedirect(route('login'));
    }

    public function test_meet_link_never_appears_in_the_class_list_html(): void
    {
        $this->makeSession();

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Vào lớp')
            ->assertDontSee(self::MEET_LINK);
    }

    public function test_ended_sessions_are_hidden_from_students(): void
    {
        $this->makeSession(['title' => 'Buổi đã xong', 'starts_at' => now()->subHours(3), 'ends_at' => now()->subHour()]);
        $this->makeSession(['title' => 'Buổi đã tắt', 'is_active' => false]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Buổi đã xong')
            ->assertDontSee('Buổi đã tắt');
    }

    public function test_session_without_any_time_is_open_while_active(): void
    {
        // Cách dùng ít thao tác nhất: chỉ tên + link, is_active là công tắc.
        $session = $this->makeSession(['starts_at' => null, 'ends_at' => null]);

        $this->assertTrue($session->isAlwaysOpen());

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(self::MEET_LINK);
    }

    public function test_always_open_session_closes_when_deactivated(): void
    {
        $session = $this->makeSession(['starts_at' => null, 'ends_at' => null, 'is_active' => false]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    public function test_only_start_time_given_still_gates_the_opening(): void
    {
        $notYet = $this->makeSession(['starts_at' => now()->addHours(3), 'ends_at' => null]);
        $opened = $this->makeSession(['starts_at' => now()->subHour(), 'ends_at' => null]);
        $student = $this->student(now()->addMonth());

        // Không đặt giờ kết thúc thì không bao giờ tự đóng.
        $this->assertFalse($opened->hasEnded());

        $this->actingAs($student)->get(route('classes.join', $notYet))
            ->assertRedirect(route('classes.index'));
        $this->actingAs($student)->get(route('classes.join', $opened))
            ->assertRedirect(self::MEET_LINK);
    }

    public function test_only_end_time_given_closes_the_session(): void
    {
        $session = $this->makeSession(['starts_at' => null, 'ends_at' => now()->subMinute()]);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('classes.join', $session))
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_create_a_session_without_times(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-sessions.store'), [
                'title' => 'Buổi không giờ',
                'meet_link' => self::MEET_LINK,
                'starts_at' => '',
                'ends_at' => '',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.class-sessions.index'))
            ->assertSessionHasNoErrors();

        // Ô trống phải vào DB là null, không phải chuỗi rỗng.
        $this->assertDatabaseHas('class_sessions', [
            'title' => 'Buổi không giờ', 'starts_at' => null, 'ends_at' => null,
        ]);
    }

    public function test_dashboard_shows_the_live_class_without_leaking_the_link(): void
    {
        $this->makeSession(['title' => 'Buổi đang chạy']);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buổi đang chạy')
            ->assertDontSee(self::MEET_LINK);
    }

    public function test_student_can_save_and_clear_google_email(): void
    {
        $student = $this->student(now()->addMonth());

        $this->actingAs($student)
            ->post(route('classes.google-email'), ['google_email' => 'hocvien@gmail.com'])
            ->assertRedirect(route('classes.index'))
            ->assertSessionHas('success');
        $this->assertSame('hocvien@gmail.com', $student->fresh()->google_email);

        // Bỏ trống = xoá, phải về null chứ không phải chuỗi rỗng.
        $this->actingAs($student)->post(route('classes.google-email'), ['google_email' => '']);
        $this->assertNull($student->fresh()->google_email);
    }

    public function test_invalid_google_email_is_rejected(): void
    {
        $student = $this->student(now()->addMonth());

        $this->actingAs($student)
            ->post(route('classes.google-email'), ['google_email' => 'khong-phai-email'])
            ->assertSessionHasErrors('google_email');

        $this->assertNull($student->fresh()->google_email);
    }

    public function test_invite_list_defaults_to_account_email(): void
    {
        // Không khai Gmail riêng → vẫn được mời bằng email tài khoản.
        $mac_dinh = $this->student(now()->addMonth());
        // Có khai Gmail riêng → lấy Gmail đó, KHÔNG lấy email tài khoản.
        $ghi_de = $this->student(now()->addMonth());
        $ghi_de->update(['google_email' => 'gmail-khac@gmail.com']);

        $list = User::invitableToClass()->get(['email', 'google_email'])->map->classInviteEmail()->all();

        $this->assertContains($mac_dinh->email, $list);
        $this->assertContains('gmail-khac@gmail.com', $list);
        $this->assertNotContains($ghi_de->email, $list);
    }

    public function test_invite_list_excludes_expired_blocked_and_admins(): void
    {
        $con_han = $this->student(now()->addMonth());
        $vo_han  = $this->student(null);                 // expires_at null = không giới hạn
        $het_han = $this->student(now()->subDay());
        $bi_khoa = $this->student(now()->addMonth());
        $bi_khoa->update(['status' => 'blocked']);
        $admin = $this->admin();

        $list = User::invitableToClass()->get(['email', 'google_email'])->map->classInviteEmail()->all();

        $this->assertContains($con_han->email, $list);
        $this->assertContains($vo_han->email, $list);
        $this->assertNotContains($het_han->email, $list);
        $this->assertNotContains($bi_khoa->email, $list);
        $this->assertNotContains($admin->email, $list);
    }

    public function test_admin_index_shows_invite_list_and_flags_non_gmail(): void
    {
        $gmail = $this->student(now()->addMonth());
        $gmail->update(['google_email' => 'binh-thuong@gmail.com']);
        $khac = $this->student(now()->addMonth());
        $khac->update(['google_email' => 'sinhvien@hus.edu.vn']);

        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertSee('binh-thuong@gmail.com')
            ->assertSee('sinhvien@hus.edu.vn')
            ->assertSee('1 địa chỉ không phải', false);
    }

    public function test_admin_screens_render(): void
    {
        $session = $this->makeSession();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.class-sessions.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.class-sessions.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.class-sessions.edit', $session))->assertOk();
    }

    public function test_admin_can_create_a_session(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-sessions.store'), [
                'title' => 'Buổi mới',
                'meet_link' => self::MEET_LINK,
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->addHour()->format('Y-m-d\TH:i'),
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.class-sessions.index'));

        $this->assertDatabaseHas('class_sessions', ['title' => 'Buổi mới']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-sessions.store'), [
                'title' => 'Sai giờ',
                'meet_link' => self::MEET_LINK,
                'starts_at' => now()->addDay()->addHour()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseMissing('class_sessions', ['title' => 'Sai giờ']);
    }

    public function test_student_cannot_manage_sessions(): void
    {
        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('admin.class-sessions.index'))
            ->assertForbidden();
    }
}
