<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Doanh số — tính hoàn toàn từ bảng `orders` đã thanh toán.
 *
 *  • Doanh thu ĐĂNG KÝ: chia Cô Dung 40% / Cường 30% / Còn lại 30%.
 *  • Doanh thu CHẤM BÀI: để riêng, 100% cho Cô Dung.
 *  • Không tính thuế (theo yêu cầu — giáo dục).
 */
class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);

        $paid = Order::where('status', Order::STATUS_PAID);

        // So sánh datetime (không bọc DATE()) để tận dụng index paid_at.
        if (! empty($filters['from'])) {
            $paid->where('paid_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $paid->where('paid_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        // Tổng theo loại (clone để không đụng nhau).
        $registration = (int) (clone $paid)->where('type', Order::TYPE_REGISTRATION)->sum('amount');
        $grading      = (int) (clone $paid)->where('type', Order::TYPE_GRADING)->sum('amount');

        $split = config('pricing.revenue_split');

        $coDungFromReg = (int) round($registration * $split['co_dung'] / 100);
        $cuong         = (int) round($registration * $split['cuong'] / 100);
        $conLai        = (int) round($registration * $split['con_lai'] / 100);

        $summary = [
            'total'           => $registration + $grading,
            'registration'    => $registration,
            'grading'         => $grading,
            'split'           => $split,
            // Cô Dung = 40% đăng ký + 100% chấm bài.
            'co_dung_total'   => $coDungFromReg + $grading,
            'co_dung_reg'     => $coDungFromReg,
            'cuong'           => $cuong,
            'con_lai'         => $conLai,
        ];

        $orders = (clone $paid)->latest('paid_at')->paginate(30)->withQueryString();

        return view('admin.revenue.index', compact('summary', 'orders', 'filters'));
    }
}
