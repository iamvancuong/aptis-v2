<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Gắn mã sale giới thiệu (referral): link /dk/{sale}/{goi} → gắn mã vào đơn →
 * thống kê theo sale ở trang Doanh số + nội dung chuyển khoản có mã sale.
 */
class SaleReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Sale cứng trong config (không phụ thuộc .env).
        Config::set('sales.reps', [
            'M1' => ['name' => 'Sale 1', 'active' => true],
            'M2' => ['name' => 'Sale 2', 'active' => true],
            'M9' => ['name' => 'Sale nghỉ', 'active' => false],
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function paidReg(int $amount, ?string $sale): Order
    {
        return Order::create([
            'order_code' => random_int(100000, 9999999), 'email' => 'b' . random_int(1, 9999) . '@example.test',
            'type' => Order::TYPE_REGISTRATION, 'package' => 'month', 'quantity' => 1,
            'amount' => $amount, 'status' => Order::STATUS_PAID, 'paid_at' => now(), 'sale_code' => $sale,
        ]);
    }

    public function test_referral_link_sets_session_and_preselects_package(): void
    {
        $this->get('/dk/M1/thang')
            ->assertRedirect(route('register', ['goi' => 'month']))
            ->assertSessionHas('sale_code', 'M1');
    }

    public function test_referral_code_is_case_insensitive(): void
    {
        $this->get('/dk/m1/tuan')
            ->assertRedirect(route('register', ['goi' => 'week']))
            ->assertSessionHas('sale_code', 'M1');
    }

    public function test_unknown_or_inactive_sale_is_ignored_gracefully(): void
    {
        $this->get('/dk/BOGUS/thang')
            ->assertRedirect(route('register', ['goi' => 'month']))
            ->assertSessionMissing('sale_code');

        // Sale tồn tại nhưng active=false cũng không được gắn.
        $this->get('/dk/M9/thang')->assertSessionMissing('sale_code');
    }

    public function test_register_page_carries_hidden_sale_field(): void
    {
        $this->withSession(['sale_code' => 'M1'])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('name="sale" value="M1"', false);
    }

    public function test_store_attributes_order_to_sale(): void
    {
        $this->withSession(['sale_code' => 'M1'])->post(route('register.store'), [
            'email' => 'buyer@example.test', 'package' => 'month', 'quantity' => 1, 'sale' => 'M1',
        ]);

        $this->assertDatabaseHas('orders', ['email' => 'buyer@example.test', 'sale_code' => 'M1']);
    }

    public function test_store_ignores_invalid_sale_code(): void
    {
        $this->post(route('register.store'), [
            'email' => 'nosale@example.test', 'package' => 'month', 'quantity' => 1, 'sale' => 'HACKER',
        ]);

        $this->assertNull(Order::where('email', 'nosale@example.test')->first()->sale_code);
    }

    public function test_revenue_groups_orders_by_sale(): void
    {
        $this->paidReg(399000, 'M1');
        $this->paidReg(399000, 'M1');   // M1: 2 đơn / 798.000đ
        $this->paidReg(699000, 'M2');   // M2: 1 đơn / 699.000đ
        $this->paidReg(699000, null);   // không qua sale

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('Doanh số theo Sale')
            ->assertSee('Sale 1')
            ->assertSee('798.000')            // doanh thu M1
            ->assertSee('Không qua sale')
            ->assertSee('/dk/M1/thang');      // link giới thiệu hiện ra để copy
    }

    public function test_payment_description_includes_sale_code(): void
    {
        Config::set('payos.client_id', 'cid');
        Config::set('payos.api_key', 'key');
        Config::set('payos.checksum_key', 'sum');
        Config::set('payos.fake', false);
        Config::set('payos.base_url', 'https://api-merchant.payos.vn');
        Config::set('payos.checkout_base_url', 'https://pay.payos.vn/web/');

        Http::fake([
            '*/v2/payment-requests' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => ['checkoutUrl' => 'https://pay.payos.vn/web/LINK1', 'qrCode' => 'q', 'paymentLinkId' => 'LINK1'],
            ]),
        ]);

        $order = Order::create([
            'order_code' => 880001, 'email' => 's@example.test', 'type' => Order::TYPE_REGISTRATION,
            'package' => 'week', 'quantity' => 1, 'amount' => 399000, 'status' => Order::STATUS_PENDING,
            'sale_code' => 'M1',
        ]);

        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect('https://pay.payos.vn/web/LINK1');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/payment-requests')
            && ($request['description'] ?? null) === 'Milaedu M1');
    }
}
