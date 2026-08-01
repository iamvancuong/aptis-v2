<?php

namespace Tests\Feature;

use App\Mail\ClassSessionReminderMail;
use App\Models\ClassSession;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Nhắc giờ lớp online + danh sách cần gỡ khỏi lời mời Calendar.
 */
class ClassReminderTest extends TestCase
{
    use RefreshDatabase;

    private function student(?string $expiresAt = null, string $email = null): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => $email ?: ('hv' . random_int(1, 999999) . '@gmail.com'),
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0, 'expires_at' => $expiresAt,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function makeSession(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'title' => 'Buổi chữa Writing',
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'is_active' => true,
        ], $overrides));
    }

    public function test_reminder_goes_to_in_date_students_only(): void
    {
        Mail::fake();
        $conHan = $this->student(now()->addMonth());
        $hetHan = $this->student(now()->subDay());
        $session = $this->makeSession();

        $this->artisan('classes:remind')->assertSuccessful();

        Mail::assertSent(ClassSessionReminderMail::class, 1);
        Mail::assertSent(ClassSessionReminderMail::class, fn ($m) => $m->hasTo($conHan->email));
        Mail::assertNotSent(ClassSessionReminderMail::class, fn ($m) => $m->hasTo($hetHan->email));

        $this->assertNotNull($session->fresh()->reminder_sent_at);
    }

    public function test_reminder_is_sent_only_once_per_session(): void
    {
        Mail::fake();
        $this->student(now()->addMonth());
        $this->makeSession();

        $this->artisan('classes:remind');
        $this->artisan('classes:remind');   // cron chạy mỗi 5 phút — không được gửi lại
        $this->artisan('classes:remind');

        Mail::assertSent(ClassSessionReminderMail::class, 1);
    }

    public function test_sessions_outside_the_window_are_not_reminded(): void
    {
        Mail::fake();
        $this->student(now()->addMonth());

        $this->makeSession(['title' => 'Còn xa', 'starts_at' => now()->addHours(5), 'ends_at' => now()->addHours(6)]);
        $this->makeSession(['title' => 'Đã bắt đầu', 'starts_at' => now()->subMinutes(10), 'ends_at' => now()->addHour()]);
        $this->makeSession(['title' => 'Đang tắt', 'is_active' => false]);
        $this->makeSession(['title' => 'Mở tự do', 'starts_at' => null, 'ends_at' => null]);

        $this->artisan('classes:remind');

        Mail::assertNothingSent();
    }

    public function test_reminder_email_never_contains_the_meet_link(): void
    {
        Mail::fake();
        $this->student(now()->addMonth());
        $session = $this->makeSession();

        $this->artisan('classes:remind');

        Mail::assertSent(ClassSessionReminderMail::class, function ($mail) use ($session) {
            $html = $mail->render();
            return ! str_contains($html, $session->meet_link)
                && str_contains($html, route('classes.index'));
        });
    }

    public function test_admin_sees_expired_students_to_remove_from_calendar(): void
    {
        $vuaHetHan = $this->student(now()->subDays(2), 'vua-het-han@gmail.com');
        $this->student(now()->addMonth(), 'con-han@gmail.com');

        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertSee('cần GỠ khỏi lời mời Calendar', false)
            ->assertSee($vuaHetHan->email);
    }

    public function test_marking_invites_synced_clears_the_removal_list(): void
    {
        $this->student(now()->subDays(2), 'da-go-roi@gmail.com');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.class-sessions.invite-synced'))
            ->assertRedirect(route('admin.class-sessions.index'));

        $this->assertNotNull(Setting::where('key', 'class_invite_synced_at')->value('value'));

        // Người hết hạn TRƯỚC mốc đồng bộ không hiện lại nữa.
        $this->actingAs($admin)
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertDontSee('da-go-roi@gmail.com');
    }
}
