<?php

namespace Tests\Feature;

use App\Mail\AccountCredentialsMail;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\PayosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attrs = []): Order
    {
        return Order::create(array_merge([
            'order_code' => random_int(100000, 999999),
            'email'      => 'newbuyer@example.test',
            'type'       => Order::TYPE_REGISTRATION,
            'package'    => 'month',
            'quantity'   => 1,
            'amount'     => 500000,
            'status'     => Order::STATUS_PENDING,
        ], $attrs));
    }

    // ─── Fulfillment ────────────────────────────────────────────────────────

    public function test_fulfillment_creates_account_and_emails_credentials(): void
    {
        Mail::fake();
        $order = $this->order();

        app(OrderFulfillmentService::class)->fulfill($order);

        $user = User::where('email', 'newbuyer@example.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check(OrderFulfillmentService::DEFAULT_PASSWORD, $user->password));
        $this->assertTrue($user->expires_at->isFuture());

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame($user->id, $order->fresh()->user_id);

        Mail::assertSent(AccountCredentialsMail::class, fn ($m) => $m->hasTo('newbuyer@example.test') && $m->isNew);
    }

    public function test_fulfillment_is_idempotent(): void
    {
        Mail::fake();
        $order = $this->order();
        $service = app(OrderFulfillmentService::class);

        $service->fulfill($order);
        $service->fulfill($order->fresh()); // webhook bắn trùng
        $service->fulfill($order->fresh());

        $this->assertSame(1, User::where('email', 'newbuyer@example.test')->count());
        Mail::assertSent(AccountCredentialsMail::class, 1);
    }

    public function test_existing_email_extends_validity_without_new_account(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Old', 'email' => 'renew@example.test',
            'password' => Hash::make('secret-existing'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
            'expires_at' => now()->addDays(10),
        ]);

        app(OrderFulfillmentService::class)->fulfill(
            $this->order(['email' => 'renew@example.test']) // +30 ngày
        );

        $user->refresh();
        // Cộng dồn từ mốc còn hạn: 10 + 30 ≈ 40 ngày.
        $this->assertEqualsWithDelta(40, now()->diffInDays($user->expires_at), 1);
        // Mật khẩu cũ không bị đổi.
        $this->assertTrue(Hash::check('secret-existing', $user->password));
        $this->assertSame(1, User::where('email', 'renew@example.test')->count());

        Mail::assertSent(AccountCredentialsMail::class, fn ($m) => ! $m->isNew);
    }

    // ─── Webhook ────────────────────────────────────────────────────────────

    private function signedPayload(Order $order, array $overrides = []): array
    {
        Config::set('payos.checksum_key', 'test-checksum-key');

        $data = array_merge([
            'orderCode'   => $order->order_code,
            'amount'      => (int) $order->amount,
            'description' => 'Thanh toan Milaedu',
            'reference'   => 'FT123',
        ], $overrides);

        $signature = app(PayosService::class)->signData($data);

        return ['code' => '00', 'success' => true, 'data' => $data, 'signature' => $signature];
    }

    public function test_webhook_with_valid_signature_fulfills_order(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->postJson(route('payment.webhook'), $this->signedPayload($order))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull(User::where('email', $order->email)->first());
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        Mail::fake();
        $order = $this->order();

        $payload = $this->signedPayload($order);
        $payload['signature'] = 'tampered';

        $this->postJson(route('payment.webhook'), $payload)->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull(User::where('email', $order->email)->first());
    }

    public function test_webhook_rejects_amount_mismatch(): void
    {
        Mail::fake();
        $order = $this->order();

        // Chữ ký hợp lệ nhưng số tiền không khớp đơn → không fulfill.
        $this->postJson(route('payment.webhook'), $this->signedPayload($order, ['amount' => 1]))
            ->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_webhook_acks_unknown_order(): void
    {
        $order = $this->order();
        $payload = $this->signedPayload($order, ['orderCode' => 999999999]);

        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
