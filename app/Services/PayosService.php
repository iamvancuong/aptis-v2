<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tích hợp PayOS: tạo link thanh toán và xác thực webhook.
 *
 * Chữ ký theo đúng thuật toán PayOS: sắp xếp khóa dữ liệu theo alphabet, ghép
 * thành chuỗi `key=value&...`, ký HMAC-SHA256 bằng Checksum Key.
 *
 * ⚠️ Khi 3 khóa PayOS còn trống (chưa cấu hình), mọi lời gọi API sẽ ném lỗi rõ
 * ràng — nhờ đó phần còn lại của flow vẫn build/test được, chỉ khâu gọi PayOS
 * thật là cần khóa.
 */
class PayosService
{
    public function isConfigured(): bool
    {
        return filled(config('payos.client_id'))
            && filled(config('payos.api_key'))
            && filled(config('payos.checksum_key'));
    }

    /**
     * Tạo payment link cho một đơn hàng.
     *
     * @return array{checkoutUrl:string, qrCode:string, paymentLinkId:string}
     */
    public function createPaymentLink(Order $order, string $description, string $returnUrl, string $cancelUrl): array
    {
        $this->assertConfigured();

        $body = [
            'orderCode'   => $order->order_code,
            'amount'      => (int) $order->amount,
            'description' => $description,
            'cancelUrl'   => $cancelUrl,
            'returnUrl'   => $returnUrl,
        ];

        // Chữ ký create: giá trị THÔ (không URL-encode), đúng thứ tự alphabet
        // amount, cancelUrl, description, orderCode, returnUrl — theo chuẩn PayOS.
        $signatureData = "amount={$body['amount']}"
            . "&cancelUrl={$body['cancelUrl']}"
            . "&description={$body['description']}"
            . "&orderCode={$body['orderCode']}"
            . "&returnUrl={$body['returnUrl']}";

        $body['signature'] = hash_hmac('sha256', $signatureData, config('payos.checksum_key'));

        $response = Http::withHeaders([
            'x-client-id' => config('payos.client_id'),
            'x-api-key'   => config('payos.api_key'),
        ])
            ->withOptions(['verify' => config('payos.verify_ssl', true)])
            ->post(rtrim(config('payos.base_url'), '/') . '/v2/payment-requests', $body);

        if (! $response->successful() || ($response->json('code') !== '00')) {
            throw new RuntimeException('PayOS tạo link thất bại: ' . $response->body());
        }

        $data = $response->json('data');

        return [
            'checkoutUrl'   => $data['checkoutUrl'] ?? '',
            'qrCode'        => $data['qrCode'] ?? '',
            'paymentLinkId' => $data['paymentLinkId'] ?? '',
        ];
    }

    /**
     * Dựng lại URL trang thanh toán PayOS từ paymentLinkId. Dùng để TÁI DÙNG link
     * đã tạo thay vì gọi tạo mới (PayOS cấm 2 link cùng một orderCode).
     */
    public function checkoutUrlFor(string $paymentLinkId): string
    {
        return rtrim(config('payos.checkout_base_url'), '/') . '/' . $paymentLinkId;
    }

    /**
     * Hỏi PayOS trạng thái một đơn (backstop khi webhook không tới).
     *
     * @return array{status:string, amountPaid:int, raw:array}
     */
    public function getPaymentInfo(int $orderCode): array
    {
        $this->assertConfigured();

        $response = Http::withHeaders([
            'x-client-id' => config('payos.client_id'),
            'x-api-key'   => config('payos.api_key'),
        ])
            ->withOptions(['verify' => config('payos.verify_ssl', true)])
            ->get(rtrim(config('payos.base_url'), '/') . '/v2/payment-requests/' . $orderCode);

        if (! $response->successful() || $response->json('code') !== '00') {
            throw new RuntimeException('PayOS truy vấn đơn thất bại: ' . $response->body());
        }

        $data = $response->json('data', []);

        return [
            'status'     => $data['status'] ?? 'UNKNOWN',   // PAID / PENDING / CANCELLED / EXPIRED
            'amountPaid' => (int) ($data['amountPaid'] ?? 0),
            'raw'        => $data,
        ];
    }

    /**
     * Xác thực chữ ký của payload webhook. So khớp HMAC của phần `data` với
     * `signature` PayOS gửi kèm.
     */
    public function verifyWebhook(array $payload): bool
    {
        $data      = $payload['data'] ?? null;
        $signature = $payload['signature'] ?? null;

        if (! is_array($data) || ! is_string($signature) || blank(config('payos.checksum_key'))) {
            return false;
        }

        return hash_equals($this->signData($data), $signature);
    }

    /**
     * Chuỗi ký của một object data: khóa sắp theo alphabet, `key=value`, nối bằng &.
     * null → chuỗi rỗng; mảng/đối tượng → JSON.
     */
    public function signData(array $data): string
    {
        ksort($data);

        $pairs = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $value = '';
            } elseif (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $pairs[] = $key . '=' . $value;
        }

        return hash_hmac('sha256', implode('&', $pairs), config('payos.checksum_key'));
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình khóa PayOS (PAYOS_CLIENT_ID/API_KEY/CHECKSUM_KEY trong .env).');
        }
    }
}
