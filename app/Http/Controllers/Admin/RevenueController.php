<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Sales;
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
            'from'  => 'nullable|date',
            'to'    => 'nullable|date',
            'range' => 'nullable|in:thang,tat_ca',
        ]);

        // MẶC ĐỊNH LÀ THÁNG NÀY, không phải toàn bộ.
        //
        // Bản cũ không lọc gì khi vào trang, nên con số cộng dồn từ ngày mở bán:
        // sang tháng mới vẫn thấy tổng của mọi tháng trước và không đọc ra được
        // tháng này bán được bao nhiêu. Nay mỗi đầu tháng bảng tự về 0, còn số
        // luỹ kế xem bằng nút "Tổng".
        //
        // Mốc tháng tính theo giờ Việt Nam (`config/app.php` đặt
        // Asia/Ho_Chi_Minh), nên "ngày 1" đúng là ngày 1 ở đây chứ không lệch
        // 7 tiếng như khi để UTC.
        $coLocNgay = ! empty($filters['from']) || ! empty($filters['to']);
        $phamVi    = $coLocNgay ? 'tuy_chon' : ($filters['range'] ?? 'thang');

        $paid = Order::where('status', Order::STATUS_PAID);

        // So sánh datetime (không bọc DATE()) để tận dụng index paid_at.
        if ($phamVi === 'thang') {
            $paid->where('paid_at', '>=', now()->startOfMonth())
                 ->where('paid_at', '<=', now()->endOfMonth());
        } elseif ($phamVi === 'tuy_chon') {
            if (! empty($filters['from'])) {
                $paid->where('paid_at', '>=', Carbon::parse($filters['from'])->startOfDay());
            }
            if (! empty($filters['to'])) {
                $paid->where('paid_at', '<=', Carbon::parse($filters['to'])->endOfDay());
            }
        }
        // 'tat_ca' → không thêm điều kiện nào: đây là nút "Tổng".

        $period = [
            'mode'  => $phamVi,
            'label' => match ($phamVi) {
                'thang'   => 'Tháng ' . now()->format('n/Y'),
                'tat_ca'  => 'Toàn bộ từ trước tới nay',
                default   => trim(
                    (! empty($filters['from']) ? 'từ ' . Carbon::parse($filters['from'])->format('d/m/Y') . ' ' : '')
                    . (! empty($filters['to']) ? 'đến ' . Carbon::parse($filters['to'])->format('d/m/Y') : '')
                ),
            },
        ];

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

        // Thống kê theo sale (chỉ doanh thu ĐĂNG KÝ đã thanh toán). "Số người" =
        // số đơn đã thanh toán. Đơn không có mã sale gom vào nhóm null.
        $bySaleRaw = (clone $paid)->where('type', Order::TYPE_REGISTRATION)
            ->selectRaw('sale_code, COUNT(*) as orders_count, SUM(amount) as revenue')
            ->groupBy('sale_code')
            ->get()
            ->keyBy('sale_code');

        // Dựng bảng: mọi sale đang active luôn hiện (kể cả 0 đơn) + các mã lạ đã
        // phát sinh trong dữ liệu + nhóm "không qua sale".
        $saleRows = [];
        foreach (array_keys(Sales::active()) as $code) {
            $row = $bySaleRaw->get($code);
            $saleRows[] = [
                'code'    => $code,
                'name'    => Sales::name($code),
                'count'   => (int) ($row->orders_count ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
            ];
        }
        foreach ($bySaleRaw as $code => $row) {
            if ($code === null || $code === '' || array_key_exists($code, Sales::active())) {
                continue; // null xử lý riêng; active đã thêm ở trên.
            }
            $saleRows[] = [
                'code'    => $code,
                'name'    => Sales::name($code) . ' (đã ẩn)',
                'count'   => (int) $row->orders_count,
                'revenue' => (int) $row->revenue,
            ];
        }

        $noSale = $bySaleRaw->first(fn ($r, $k) => $k === null || $k === '');

        // Link giới thiệu sinh sẵn cho từng sale active — admin copy gửi cho sale.
        $links = [];
        foreach (Sales::active() as $code => $rep) {
            $links[] = [
                'code'  => $code,
                'name'  => $rep['name'] ?? $code,
                'thang' => url("/dk/{$code}/thang"),
                'tuan'  => url("/dk/{$code}/tuan"),
            ];
        }

        $sales = [
            'rows'    => $saleRows,
            'no_sale' => [
                'count'   => (int) ($noSale->orders_count ?? 0),
                'revenue' => (int) ($noSale->revenue ?? 0),
            ],
            'links'   => $links,
        ];

        $orders = (clone $paid)->latest('paid_at')->paginate(30)->withQueryString();

        return view('admin.revenue.index', compact('summary', 'orders', 'filters', 'sales', 'period'));
    }
}
