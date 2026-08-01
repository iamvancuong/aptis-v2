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

    public function test_dashboard_shows_the_live_class_without_leaking_the_link(): void
    {
        $this->makeSession(['title' => 'Buổi đang chạy']);

        $this->actingAs($this->student(now()->addMonth()))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buổi đang chạy')
            ->assertDontSee(self::MEET_LINK);
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
