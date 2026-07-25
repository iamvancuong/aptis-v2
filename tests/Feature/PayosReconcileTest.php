<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lệnh payos:reconcile — lưới an toàn khi webhook không tới: hỏi PayOS, đơn nào
 * đã PAID thì kích hoạt.
 */
class PayosReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Giả có khóa PayOS để isConfigured() = true.
        Config::set('payos.client_id', 'cid');
        Config::set('payos.api_key', 'key');
        Config::set('payos.checksum_key', 'sum');
        Config::set('payos.base_url', 'https://api-merchant.payos.vn');
    }

    private function pendingOrder(int $code, int $amount = 2000): Order
    {
        return Order::create([
            'order_code' => $code, 'email' => 'buyer@example.test', 'type' => Order::TYPE_REGISTRATION,
            'package' => 'week', 'quantity' => 1, 'amount' => $amount, 'status' => Order::STATUS_PENDING,
        ]);
    }

    public function test_paid_order_is_activated(): void
    {
        Mail::fake();
        $order = $this->pendingOrder(111);

        Http::fake([
            '*/v2/payment-requests/111' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => ['orderCode' => 111, 'amount' => 2000, 'amountPaid' => 2000, 'status' => 'PAID'],
            ]),
        ]);

        $this->artisan('payos:reconcile', ['--order' => 111])->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull(User::where('email', 'buyer@example.test')->first());
    }

    public function test_pending_order_is_left_alone(): void
    {
        Mail::fake();
        $order = $this->pendingOrder(222);

        Http::fake([
            '*/v2/payment-requests/222' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => ['orderCode' => 222, 'amount' => 2000, 'amountPaid' => 0, 'status' => 'PENDING'],
            ]),
        ]);

        $this->artisan('payos:reconcile', ['--order' => 222])->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull(User::where('email', 'buyer@example.test')->first());
    }

    public function test_underpaid_order_is_not_activated(): void
    {
        Mail::fake();
        $order = $this->pendingOrder(333, 2000);

        // PayOS báo PAID nhưng số tiền trả thiếu (1000 < 2000) → không kích hoạt.
        Http::fake([
            '*/v2/payment-requests/333' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => ['orderCode' => 333, 'amount' => 2000, 'amountPaid' => 1000, 'status' => 'PAID'],
            ]),
        ]);

        $this->artisan('payos:reconcile', ['--order' => 333])->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
    }
}
