<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * PayOS cấm tạo 2 link cho cùng một orderCode. Trước bản vá, mở lại trang thanh
 * toán của một đơn pending (khách back / dedupe đăng ký dùng lại đơn cũ) khiến
 * `createPaymentLink` bị gọi lần 2 và rơi vào màn "Chưa kết nối được cổng thanh
 * toán". Bản vá: đã có link thì TÁI DÙNG, không tạo mới.
 */
class PaymentLinkReuseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Giả có khóa PayOS để isConfigured() = true (không ở chế độ fake/unconfigured).
        Config::set('payos.client_id', 'cid');
        Config::set('payos.api_key', 'key');
        Config::set('payos.checksum_key', 'sum');
        Config::set('payos.fake', false);
        Config::set('payos.base_url', 'https://api-merchant.payos.vn');
        Config::set('payos.checkout_base_url', 'https://pay.payos.vn/web/');
    }

    private function pendingOrder(int $code, array $extra = []): Order
    {
        return Order::create(array_merge([
            'order_code' => $code, 'email' => 'buyer@example.test', 'type' => Order::TYPE_REGISTRATION,
            'package' => 'week', 'quantity' => 1, 'amount' => 399000, 'status' => Order::STATUS_PENDING,
        ], $extra));
    }

    public function test_reopening_pending_order_reuses_link_without_recreating(): void
    {
        Http::fake([
            '*/v2/payment-requests' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => [
                    'checkoutUrl'   => 'https://pay.payos.vn/web/LINK123',
                    'qrCode'        => 'qr',
                    'paymentLinkId' => 'LINK123',
                ],
            ]),
        ]);

        $order = $this->pendingOrder(700001);

        // Lần 1: tạo link, lưu payos_link_id + checkout_url, redirect sang PayOS.
        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect('https://pay.payos.vn/web/LINK123');

        $order->refresh();
        $this->assertSame('LINK123', $order->payos_link_id);
        $this->assertSame('https://pay.payos.vn/web/LINK123', $order->meta['checkout_url']);

        // Lần 2 (khách back rồi mở lại đơn cũ): KHÔNG tạo link mới, vẫn về đúng chỗ.
        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect('https://pay.payos.vn/web/LINK123');

        // Điểm mấu chốt: createPaymentLink chỉ được gọi ĐÚNG 1 lần.
        Http::assertSentCount(1);
    }

    public function test_legacy_order_with_link_but_no_stored_url_reuses_without_api_call(): void
    {
        // Đơn tạo trước bản vá: có payos_link_id nhưng chưa lưu checkout_url.
        $order = $this->pendingOrder(700002, ['payos_link_id' => 'OLD999']);

        Http::fake(); // bất kỳ lời gọi PayOS nào cũng là sai.

        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect('https://pay.payos.vn/web/OLD999');

        Http::assertNothingSent();
    }

    public function test_recovers_when_create_rejected_as_duplicate_ordercode(): void
    {
        $order = $this->pendingOrder(700003); // chưa có link (response lần trước bị mất).

        Http::fake([
            // Tạo mới bị PayOS từ chối vì orderCode đã tồn tại.
            '*/v2/payment-requests' => Http::response([
                'code' => '231', 'desc' => 'Đơn thanh toán đã tồn tại',
            ]),
            // Hỏi lại info → lấy được paymentLinkId đã tạo lần trước.
            '*/v2/payment-requests/700003' => Http::response([
                'code' => '00', 'desc' => 'success',
                'data' => ['id' => 'RECV1', 'orderCode' => 700003, 'status' => 'PENDING', 'amountPaid' => 0],
            ]),
        ]);

        $this->get(URL::signedRoute('payment.show', $order))
            ->assertRedirect('https://pay.payos.vn/web/RECV1');

        $order->refresh();
        $this->assertSame('RECV1', $order->payos_link_id);
        $this->assertSame('https://pay.payos.vn/web/RECV1', $order->meta['checkout_url']);
    }
}
