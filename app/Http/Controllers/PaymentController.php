<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use App\Services\PayosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PaymentController extends Controller
{
    public function __construct(
        private PayosService $payos,
        private OrderFulfillmentService $fulfillment,
    ) {}

    /**
     * Trang thanh toán cho một đơn. Có khóa PayOS thì chuyển sang trang QR của
     * PayOS; chưa có khóa thì hiển thị tóm tắt đơn + thông báo chờ cấu hình.
     */
    public function show(Order $order)
    {
        if ($order->isPaid()) {
            return redirect()->route('login')->with('success', 'Đơn đã được thanh toán. Vui lòng kiểm tra email.');
        }

        // Đơn đã hủy/hết hạn thì không cho mở lại để tạo link mới — tránh thanh
        // toán cho một đơn "chết". Điều hướng khách tạo đơn mới.
        if (in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_EXPIRED], true)) {
            $reason = $order->status === Order::STATUS_CANCELED ? 'đã bị hủy' : 'đã hết hạn';

            return redirect()->route('register')
                ->with('error', "Đơn {$order->order_code} {$reason}. Vui lòng chọn gói và tạo đơn mới.");
        }

        $package = config("pricing.packages.{$order->package}");

        // 🧪 Chế độ giả lập: hiện nút "giả lập đã thanh toán".
        if (config('payos.fake')) {
            return view('payment.pending', ['order' => $order, 'package' => $package, 'state' => 'fake']);
        }

        // Chưa cấu hình khóa PayOS: báo đang hoàn thiện cổng (khác với lỗi tạm thời).
        if (! $this->payos->isConfigured()) {
            return view('payment.pending', ['order' => $order, 'package' => $package, 'state' => 'unconfigured']);
        }

        // ♻️ Đơn PENDING đã có link PayOS → TÁI DÙNG, tuyệt đối không tạo link thứ 2.
        // PayOS cấm 2 link cùng một orderCode, nên gọi createPaymentLink lần nữa sẽ
        // bị từ chối và rơi vào màn "lỗi" — đây chính là bug khi khách back/mở lại
        // trang thanh toán (dedupe đăng ký dùng lại đúng đơn pending đó).
        if ($order->payos_link_id) {
            return redirect()->away($this->checkoutUrl($order));
        }

        try {
            $link = $this->payos->createPaymentLink(
                $order,
                description: 'Thanh toan Milaedu',      // ≤ 25 ký tự theo yêu cầu PayOS
                returnUrl: URL::signedRoute('payment.return', $order),
                cancelUrl: URL::signedRoute('payment.cancel', $order),
            );

            $order->update([
                'payos_link_id' => $link['paymentLinkId'],
                'meta'          => array_merge((array) $order->meta, ['checkout_url' => $link['checkoutUrl']]),
            ]);

            return redirect()->away($link['checkoutUrl']);
        } catch (\Throwable $e) {
            Log::error('PayOS create link failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            // 🛟 Lưới an toàn: nếu link ĐÃ tạo ở lần trước nhưng response bị mất giữa
            // chừng (đơn vẫn chưa lưu payos_link_id), PayOS sẽ báo "orderCode đã tồn
            // tại" ở mọi lần thử lại → kẹt vòng lỗi. Hỏi lại info để lấy paymentLinkId
            // rồi tái dựng checkout URL, thay vì bắt khách chịu lỗi vĩnh viễn.
            if ($recovered = $this->recoverExistingLink($order)) {
                return redirect()->away($recovered);
            }

            // Lỗi hạ tầng tạm thời (PayOS chậm/timeout) — báo đúng bản chất và cho
            // nút thử lại, KHÔNG hiện "đang hoàn thiện cổng" gây hiểu nhầm.
            return view('payment.pending', [
                'order'    => $order,
                'package'  => $package,
                'state'    => 'error',
                'retryUrl' => URL::signedRoute('payment.show', $order),
            ]);
        }
    }

    /**
     * URL checkout của đơn: ưu tiên URL đã lưu; nếu chưa có (đơn cũ trước bản vá)
     * thì tái dựng từ payos_link_id.
     */
    private function checkoutUrl(Order $order): string
    {
        return $order->meta['checkout_url']
            ?? $this->payos->checkoutUrlFor($order->payos_link_id);
    }

    /**
     * Cố gắng lấy lại link PayOS đã tồn tại cho đơn (khi tạo mới bị từ chối trùng
     * mã). Trả về checkout URL nếu phục hồi được, null nếu không.
     */
    private function recoverExistingLink(Order $order): ?string
    {
        try {
            $linkId = $this->payos->getPaymentInfo($order->order_code)['raw']['id'] ?? null;
            if (! $linkId) {
                return null;
            }

            $checkoutUrl = $this->payos->checkoutUrlFor($linkId);
            $order->update([
                'payos_link_id' => $linkId,
                'meta'          => array_merge((array) $order->meta, ['checkout_url' => $checkoutUrl]),
            ]);

            return $checkoutUrl;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 🧪 Giả lập "đã thanh toán" — CHỈ khi PAYOS_FAKE=true. Chạy đúng luồng
     * fulfillment như webhook thật (tạo tài khoản / bật cờ chấm + email).
     */
    public function devFulfill(Order $order)
    {
        abort_unless(config('payos.fake'), 404);

        if (! $order->isPaid()) {
            $this->fulfillment->fulfill($order);
        }

        return redirect()->to(URL::signedRoute('payment.return', $order));
    }

    public function return(Order $order)
    {
        // Không tin trạng thái từ query — trạng thái thật do webhook cập nhật.
        return view('payment.return', compact('order'));
    }

    public function cancel(Order $order)
    {
        if (! $order->isPaid()) {
            $order->update(['status' => Order::STATUS_CANCELED]);
        }

        return view('payment.cancel', compact('order'));
    }

    /**
     * Webhook PayOS — nguồn xác nhận thanh toán DUY NHẤT đáng tin.
     * Chỉ fulfill khi chữ ký hợp lệ, đúng đơn và đúng số tiền.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        Log::info('PayOS webhook nhận được', ['payload' => $payload]);

        // Luôn trả 200 để PayOS chấp nhận webhook (kể cả ping xác thực khi đăng
        // ký URL). Chỉ FULFILL khi chữ ký + số tiền hợp lệ — nên ack an toàn.
        if (! $this->payos->verifyWebhook($payload)) {
            Log::warning('PayOS webhook chữ ký không hợp lệ (ack, không xử lý)', ['payload' => $payload]);
            return response()->json(['success' => true]);
        }

        $data = $payload['data'] ?? [];

        // PayOS gửi webhook thử với orderCode giả khi xác nhận URL → ack êm.
        $order = Order::where('order_code', $data['orderCode'] ?? 0)->first();
        if (! $order) {
            return response()->json(['success' => true]);
        }

        // Chỉ fulfill khi báo thành công và đúng số tiền.
        $isSuccess = ($payload['code'] ?? null) === '00' || ($payload['success'] ?? false) === true;
        $amountOk  = (int) ($data['amount'] ?? -1) === (int) $order->amount;

        if ($isSuccess && $amountOk) {
            $this->fulfillment->fulfill($order); // idempotent
        }

        return response()->json(['success' => true]);
    }
}
