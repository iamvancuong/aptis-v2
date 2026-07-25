<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * P1: trang chủ đã bỏ nội dung miễn phí + Zalo và dựng bảng giá mới;
 * flow chọn gói tính đúng tổng tiền.
 */
class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_paid_pricing_and_no_free_or_zalo(): void
    {
        $week  = number_format(config('pricing.packages.week.price'), 0, ',', '.');
        $month = number_format(config('pricing.packages.month.price'), 0, ',', '.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($week)              // gói tuần
            ->assertSee($month)             // gói tháng
            ->assertDontSee('Miễn phí')
            ->assertDontSee('miễn phí')
            ->assertDontSee('zalo.me');
    }

    public function test_register_page_lists_packages(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(config('pricing.packages.week.label'))
            ->assertSee(config('pricing.packages.month.label'));
    }

    public function test_register_preselects_package_from_query(): void
    {
        $this->get(route('register', ['goi' => 'week']))
            ->assertOk()
            ->assertSee("pkg: 'week'", false);
    }

    public function test_store_creates_pending_order_with_linear_total(): void
    {
        $monthPrice = (int) config('pricing.packages.month.price');
        $total      = $monthPrice * 2;   // tuyến tính, cộng dồn

        $response = $this->post(route('register.store'), [
            'email'    => 'buyer@example.test',
            'package'  => 'month',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('orders', [
            'email'    => 'buyer@example.test',
            'type'     => 'registration',
            'package'  => 'month',
            'quantity' => 2,
            'amount'   => $total,
            'status'   => 'pending',
        ]);

        $order = \App\Models\Order::first();
        $response->assertRedirect(URL::signedRoute('payment.show', $order));

        // Trang thanh toán (chưa cấu hình PayOS) hiển thị đúng tổng tiền.
        $this->get(URL::signedRoute('payment.show', $order))
            ->assertOk()
            ->assertSee(number_format($total, 0, ',', '.'))
            ->assertSee('buyer@example.test');
    }

    public function test_fake_mode_fulfills_without_payos(): void
    {
        config(['payos.fake' => true]);

        $order = \App\Models\Order::create([
            'order_code' => 999001, 'email' => 'fake@example.test', 'type' => 'registration',
            'package' => 'month', 'quantity' => 1, 'amount' => 699000, 'status' => 'pending',
        ]);

        $this->get(route('payment.dev-fulfill', $order))
            ->assertRedirect(URL::signedRoute('payment.return', $order));

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull(\App\Models\User::where('email', 'fake@example.test')->first());
    }

    public function test_return_page_requires_signature_but_allows_payos_params(): void
    {
        $order = \App\Models\Order::create([
            'order_code' => 555001, 'email' => 'r@example.test', 'type' => 'registration',
            'package' => 'month', 'quantity' => 1, 'amount' => 699000, 'status' => 'pending',
        ]);

        // Không chữ ký → chặn (chống dò đơn người khác).
        $this->get(route('payment.return', $order))->assertForbidden();

        // Có chữ ký + các query PayOS tự gắn khi redirect về → vẫn hợp lệ.
        $signed = URL::signedRoute('payment.return', $order);
        $withPayosParams = $signed . '&code=00&id=abc123&cancel=false&status=PAID&orderCode=' . $order->order_code;
        $this->get($withPayosParams)->assertOk();
    }

    public function test_canceled_order_cannot_reopen_payment(): void
    {
        $order = \App\Models\Order::create([
            'order_code' => 555002, 'email' => 'c@example.test', 'type' => 'registration',
            'package' => 'month', 'quantity' => 1, 'amount' => 699000, 'status' => 'canceled',
        ]);

        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect(route('register'));
    }

    public function test_fake_route_is_blocked_when_flag_off(): void
    {
        config(['payos.fake' => false]);

        $order = \App\Models\Order::create([
            'order_code' => 999002, 'email' => 'x@example.test', 'type' => 'registration',
            'package' => 'month', 'quantity' => 1, 'amount' => 699000, 'status' => 'pending',
        ]);

        $this->get(route('payment.dev-fulfill', $order))->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_payment_page_rejects_unsigned_access(): void
    {
        $order = \App\Models\Order::create([
            'order_code' => 12345, 'email' => 'x@example.test', 'type' => 'registration',
            'package' => 'week', 'quantity' => 1, 'amount' => 150000, 'status' => 'pending',
        ]);

        // Không có chữ ký → chặn (chống dò đơn người khác).
        $this->get(route('payment.show', $order))->assertForbidden();
    }

    public function test_duplicate_submit_reuses_pending_order(): void
    {
        $payload = ['email' => 'dup@example.test', 'package' => 'month', 'quantity' => 1];

        $this->post(route('register.store'), $payload);
        $this->post(route('register.store'), $payload); // bấm lại / double-submit

        $this->assertSame(1, \App\Models\Order::where('email', 'dup@example.test')->count());
    }

    public function test_store_validates_input(): void
    {
        $this->post(route('register.store'), [
            'email'    => 'not-an-email',
            'package'  => 'unknown',
            'quantity' => 0,
        ])->assertSessionHasErrors(['email', 'package', 'quantity']);
    }
}
