<?php

namespace Tests\Feature;

use App\Mail\GuidanceHostMail;
use App\Mail\GuidanceLinkMail;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSession;
use App\Models\Order;
use App\Models\User;
use App\Services\GuidanceSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuidanceSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('zoom.fake', true);              // không gọi Zoom thật
        Config::set('zoom.admin_email', 'admin@milaedu.test');
    }

    private function packageLearner(string $email): User
    {
        $user = User::create([
            'name' => 'L', 'email' => $email, 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ]);
        Order::create([
            'order_code' => random_int(100000, 9999999), 'email' => $email,
            'type' => Order::TYPE_REGISTRATION, 'package' => 'month', 'quantity' => 1,
            'amount' => 699000, 'status' => Order::STATUS_PAID, 'user_id' => $user->id, 'paid_at' => now(),
        ]);
        return $user;
    }

    private function book(User $user, string $date): void
    {
        GuidanceBooking::create(['user_id' => $user->id, 'session_date' => $date]);
    }

    public function test_activate_creates_room_and_emails_students_and_admin(): void
    {
        Mail::fake();
        $u1 = $this->packageLearner('a@milaedu.test');
        $u2 = $this->packageLearner('b@milaedu.test');
        $this->book($u1, '2026-03-14');
        $this->book($u2, '2026-03-14');

        $result = app(GuidanceSessionService::class)->activateAndSend(Carbon::parse('2026-03-14'));

        $this->assertSame(2, $result['sent']);
        $this->assertDatabaseHas('guidance_sessions', ['session_date' => '2026-03-14 00:00:00']);

        Mail::assertSent(GuidanceLinkMail::class, 2);
        Mail::assertSent(GuidanceLinkMail::class, fn ($m) => $m->hasTo('a@milaedu.test'));
        Mail::assertSent(GuidanceHostMail::class, fn ($m) => $m->hasTo('admin@milaedu.test'));
    }

    public function test_activate_is_idempotent_room_not_recreated(): void
    {
        Mail::fake();
        $u = $this->packageLearner('a@milaedu.test');
        $this->book($u, '2026-03-14');

        $svc = app(GuidanceSessionService::class);
        $first  = $svc->activateAndSend(Carbon::parse('2026-03-14'));
        $second = $svc->activateAndSend(Carbon::parse('2026-03-14'));

        // Cùng 1 phòng (join_url không đổi), chỉ có 1 dòng session.
        $this->assertSame(1, GuidanceSession::count());
        $this->assertSame(
            $first['session']->join_url,
            $second['session']->fresh()->join_url
        );
    }

    public function test_admin_can_trigger_activation(): void
    {
        Mail::fake();
        $admin = User::create([
            'name' => 'A', 'email' => 'root@milaedu.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
        $u = $this->packageLearner('a@milaedu.test');
        $this->book($u, '2026-03-14');

        $this->actingAs($admin)
            ->post(route('admin.guidance-sessions.activate'), ['session_date' => '2026-03-14'])
            ->assertRedirect();

        Mail::assertSent(GuidanceLinkMail::class, 1);
    }

    public function test_booking_email_no_longer_contains_a_link(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-02 09:00:00'));
        $u = $this->packageLearner('a@milaedu.test');

        $this->actingAs($u)->post(route('guidance.store'), ['session_date' => '2026-03-14']);

        Mail::assertSent(\App\Mail\GuidanceBookingMail::class, fn ($m) =>
            $m->hasTo('a@milaedu.test') && ! property_exists($m, 'zoomLink'));
        Carbon::setTestNow();
    }
}
