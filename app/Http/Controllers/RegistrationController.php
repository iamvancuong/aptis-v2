<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
        ]);
    }

    public function store(Request $request)
    {
        $packages = config('pricing.packages');

        $data = $request->validate([
            'email'    => 'required|email|max:255',
            'package'  => 'required|in:' . implode(',', array_keys($packages)),
            'quantity' => 'required|integer|min:1',
        ]);

        $package  = $packages[$data['package']];
        $quantity = min($data['quantity'], $package['max']);
        $amount   = $package['price'] * $quantity;

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
            ]);
        }

        return redirect()->to(URL::signedRoute('payment.show', $order));
    }
}
