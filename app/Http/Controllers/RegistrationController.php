<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Flow đăng ký-trả tiền.
 *
 * ⚠️ P1 (hiện tại): chỉ dựng trang chọn gói + nhập email. Bước tạo đơn PayOS,
 * QR và webhook fulfill sẽ làm ở P2 — chỗ đó đã đánh dấu TODO bên dưới.
 */
class RegistrationController extends Controller
{
    /**
     * Link giới thiệu của sale: /dk/{sale}/{goi?}. Ghi nhớ mã sale vào session
     * (bền qua vài lần bấm loanh quanh) rồi chuyển tới trang đăng ký với gói
     * chọn sẵn. Mã sai/không active → bỏ qua, đăng ký bình thường (không vỡ).
     */
    public function referral(Request $request, string $sale, ?string $goi = null)
    {
        if ($code = Sales::resolve($sale)) {
            $request->session()->put('sale_code', $code);
        }

        // Slug tiếng Việt trong link → key gói. Thiếu/không khớp thì để trang tự mặc định.
        $map  = ['thang' => 'month', 'tuan' => 'week'];
        $goiKey = $map[strtolower((string) $goi)] ?? null;

        return redirect()->route('register', $goiKey ? ['goi' => $goiKey] : []);
    }

    public function create(Request $request)
    {
        $packages = config('pricing.packages');

        // Gói chọn sẵn từ nút trên trang chủ (?goi=week|month), mặc định 'month'.
        $selected = $request->query('goi');
        if (! is_string($selected) || ! array_key_exists($selected, $packages)) {
            $selected = 'month';
        }

        return view('auth.register', [
            'packages' => $packages,
            'selected' => $selected,
            // Mã sale (nếu đến từ link giới thiệu) — render vào input ẩn để gắn khi submit.
            'sale'     => Sales::resolve($request->session()->get('sale_code')),
        ]);
    }

    public function store(Request $request)
    {
        $packages = config('pricing.packages');

        $data = $request->validate([
            'email'    => 'required|email|max:255',
            'package'  => 'required|in:' . implode(',', array_keys($packages)),
            'quantity' => 'required|integer|min:1',
            'sale'     => 'nullable|string|max:16',
        ]);

        $package  = $packages[$data['package']];
        $quantity = min($data['quantity'], $package['max']);
        $amount   = $package['price'] * $quantity;

        // Mã sale: ưu tiên input ẩn của form, fallback session (link giới thiệu).
        // Chỉ nhận mã hợp lệ + đang active; ngược lại đơn không gắn sale.
        $saleCode = Sales::resolve($data['sale'] ?? null)
            ?? Sales::resolve($request->session()->get('sale_code'));

        // Chống double-submit / bấm lại: nếu đã có đơn pending y hệt (email + gói
        // + số lượng + số tiền) tạo trong thời gian gần, dùng lại thay vì đẻ đơn
        // rác mới. Cùng tinh thần với dedupe ở luồng xin chấm bài.
        $order = Order::where('email', $data['email'])
            ->where('type', Order::TYPE_REGISTRATION)
            ->where('package', $data['package'])
            ->where('quantity', $quantity)
            ->where('amount', $amount)
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();

        if (! $order) {
            $order = Order::create([
                'order_code' => Order::generateCode(),
                'email'      => $data['email'],
                'type'       => Order::TYPE_REGISTRATION,
                'package'    => $data['package'],
                'quantity'   => $quantity,
                'amount'     => $amount,
                'status'     => Order::STATUS_PENDING,
                'sale_code'  => $saleCode,
            ]);
        }

        return redirect()->to(URL::signedRoute('payment.show', $order));
    }
}
