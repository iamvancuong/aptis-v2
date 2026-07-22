<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Order;
use App\Models\User;
use App\Services\PayosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GradingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role . '@example.test', 'password' => bcrypt('x'),
            'role' => $role, 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function attempt(User $user): Attempt
    {
        return Attempt::create([
            'user_id'    => $user->id,
            'skill'      => 'writing',
            'mode'       => 'mock',
            'started_at' => now()->subMinutes(30),
            'finished_at'=> now(),
        ]);
    }

    // ─── Học viên: phải trả 100k ─────────────────────────────────────────────

    public function test_student_request_creates_grading_order_and_redirects_to_payment(): void
    {
        $user    = $this->user();
        $attempt = $this->attempt($user);

        $this->actingAs($user)
            ->post(route('attempts.request-grading', $attempt))
            ->assertRedirect();

        $order = Order::where('type', 'grading')->first();
        $this->assertNotNull($order);
        $this->assertSame((int) config('pricing.grading_price'), (int) $order->amount);
        $this->assertSame($attempt->id, $order->meta['attempt_id']);
        $this->assertSame('pending', $order->status);

        // Chưa trả tiền → bài CHƯA vào hàng đợi chấm.
        $this->assertFalse($attempt->fresh()->is_grading_requested);
    }

    public function test_repeat_request_reuses_pending_order(): void
    {
        $user    = $this->user();
        $attempt = $this->attempt($user);

        $this->actingAs($user)->post(route('attempts.request-grading', $attempt));
        $this->actingAs($user)->post(route('attempts.request-grading', $attempt));

        $this->assertSame(1, Order::where('type', 'grading')->count());
    }

    public function test_paid_webhook_flags_attempt_for_grading(): void
    {
        $user    = $this->user();
        $attempt = $this->attempt($user);

        $this->actingAs($user)->post(route('attempts.request-grading', $attempt));
        $order = Order::where('type', 'grading')->first();

        Config::set('payos.checksum_key', 'test-checksum-key');
        $data = [
            'orderCode' => $order->order_code,
            'amount'    => (int) $order->amount,
            'description' => 'Thanh toan Milaedu',
        ];
        $payload = [
            'code' => '00', 'success' => true, 'data' => $data,
            'signature' => app(PayosService::class)->signData($data),
        ];

        $this->postJson(route('payment.webhook'), $payload)->assertOk();

        $this->assertTrue($attempt->fresh()->is_grading_requested);
        $this->assertSame('paid', $order->fresh()->status);
    }

    // ─── Admin: miễn phí ─────────────────────────────────────────────────────

    public function test_admin_request_is_free_and_flags_immediately(): void
    {
        $admin   = $this->user('admin');
        $attempt = $this->attempt($admin);

        $this->actingAs($admin)
            ->post(route('attempts.request-grading', $attempt))
            ->assertRedirect();

        $this->assertTrue($attempt->fresh()->is_grading_requested);
        $this->assertSame(0, Order::where('type', 'grading')->count());
    }

    public function test_already_requested_attempt_is_blocked(): void
    {
        $user    = $this->user();
        $attempt = $this->attempt($user);
        $attempt->update(['is_grading_requested' => true]);

        $this->actingAs($user)->post(route('attempts.request-grading', $attempt));

        $this->assertSame(0, Order::where('type', 'grading')->count());
    }
}
