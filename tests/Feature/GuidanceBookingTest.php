<?php

namespace Tests\Feature;

use App\Mail\GuidanceBookingMail;
use App\Models\GuidanceBooking;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuidanceBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tạo học viên. $withPaidOrder = có đơn PayOS đã thanh toán (tức đã mua gói).
     */
    private function learner(?Carbon $expiresAt, string $email = 'l@example.test', bool $withPaidOrder = true): User
    {
        $user = User::create([
            'name' => 'L', 'email' => $email, 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
            'expires_at' => $expiresAt,
        ]);

        if ($withPaidOrder) {
            Order::create([
                'order_code' => random_int(100000, 9999999),
                'email'      => $email,
                'type'       => Order::TYPE_REGISTRATION,
                'package'    => 'month',
                'quantity'   => 1,
                'amount'     => 500000,
                'status'     => Order::STATUS_PAID,
                'user_id'    => $user->id,
                'paid_at'    => now(),
            ]);
        }

        return $user;
    }

    /** Chỉ liệt kê thứ 7 nằm trong thời hạn tài khoản. */
    public function test_only_saturdays_within_validity_are_offered(): void
    {
        // Cố định "hôm nay" là thứ 2, 02/03/2026; hạn 20 ngày → chỉ tới ~22/03.
        Carbon::setTestNow(Carbon::parse('2026-03-02 09:00:00'));
        $user = $this->learner(Carbon::parse('2026-03-22'));

        $html = $this->actingAs($user)->get(route('guidance.index'))->assertOk()->getContent();

        // Thứ 7 trong hạn: 07/03, 14/03, 21/03.
        $this->assertStringContainsString('value="2026-03-07"', $html);
        $this->assertStringContainsString('value="2026-03-14"', $html);
        $this->assertStringContainsString('value="2026-03-21"', $html);
        // 28/03 đã quá hạn → không có.
        $this->assertStringNotContainsString('value="2026-03-28"', $html);

        Carbon::setTestNow();
    }

    public function test_booking_saves_and_emails_zoom(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-02 09:00:00'));
        $user = $this->learner(Carbon::parse('2026-04-30'));

        $this->actingAs($user)
            ->post(route('guidance.store'), ['session_date' => '2026-03-14'])
            ->assertRedirect(route('guidance.index'));

        $booking = GuidanceBooking::where('user_id', $user->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame('2026-03-14', $booking->session_date->toDateString());

        Mail::assertSent(GuidanceBookingMail::class, fn ($m) => $m->hasTo('l@example.test'));
        Carbon::setTestNow();
    }

    public function test_rebooking_replaces_the_single_slot(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-02 09:00:00'));
        $user = $this->learner(Carbon::parse('2026-04-30'));

        $this->actingAs($user)->post(route('guidance.store'), ['session_date' => '2026-03-14']);
        $this->actingAs($user)->post(route('guidance.store'), ['session_date' => '2026-03-21']);

        $this->assertSame(1, GuidanceBooking::where('user_id', $user->id)->count());
        $this->assertSame('2026-03-21', GuidanceBooking::where('user_id', $user->id)->first()->session_date->toDateString());
        Carbon::setTestNow();
    }

    public function test_permanent_account_cannot_access_guidance(): void
    {
        // Tài khoản cũ vĩnh viễn: không hạn, không đơn.
        $permanent = $this->learner(null, 'l@example.test', withPaidOrder: false);

        $this->actingAs($permanent)
            ->get(route('guidance.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($permanent)
            ->post(route('guidance.store'), ['session_date' => '2026-03-14'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('guidance_bookings', 0);
    }

    public function test_nav_link_hidden_for_permanent_account_shown_for_package(): void
    {
        // Vĩnh viễn (không đơn): không thấy link.
        $this->actingAs($this->learner(null, 'perm@example.test', withPaidOrder: false))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Buổi hướng dẫn');

        // Mua gói (có đơn đã trả): thấy link.
        $this->actingAs($this->learner(Carbon::now()->addMonth(), 'pkg@example.test'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buổi hướng dẫn');
    }

    public function test_account_with_admin_set_expiry_but_no_order_cannot_access(): void
    {
        // Tài khoản cũ được admin đặt hạn thủ công nhưng KHÔNG mua gói → vẫn ẩn.
        $oldWithExpiry = $this->learner(Carbon::now()->addMonth(), 'old@example.test', withPaidOrder: false);

        $this->actingAs($oldWithExpiry)
            ->get(route('guidance.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_rejects_a_non_saturday_or_out_of_range_date(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-02 09:00:00'));
        $user = $this->learner(Carbon::parse('2026-04-30'));

        // 2026-03-16 là thứ 2 (không phải thứ 7).
        $this->actingAs($user)
            ->post(route('guidance.store'), ['session_date' => '2026-03-16'])
            ->assertSessionHasErrors('session_date');

        $this->assertDatabaseCount('guidance_bookings', 0);
        Mail::assertNothingSent();
        Carbon::setTestNow();
    }
}
