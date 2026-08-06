<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityFlag;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityFlagController extends Controller
{
    /**
     * Review queue for accounts the DevTools detector flagged.
     *
     * These signals are heuristic, so this is a list for a human to judge — the
     * admin decides whether to block, using the existing block action.
     */
    public function index(Request $request)
    {
        // Hai loại cảnh báo tách riêng, không trộn: DevTools hiếm và đáng xem
        // từng dòng; vi phạm thiết bị nhiều hơn hẳn nên gộp chung sẽ đẩy DevTools
        // trôi khỏi trang đầu và không ai còn thấy nó nữa.
        $flags = SecurityFlag::with('user')
            ->where('type', SecurityFlag::TYPE_DEVTOOLS)
            ->latest()
            ->paginate(30, ['*'], 'devtools');

        $summary = SecurityFlag::selectRaw('user_id, count(*) as flag_count, max(created_at) as last_flagged_at')
            ->where('type', SecurityFlag::TYPE_DEVTOOLS)
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('last_flagged_at')
            ->get();

        $flagsThietBi = SecurityFlag::with('user')
            ->where('type', SecurityFlag::TYPE_DEVICE)
            ->latest()
            ->paginate(30, ['*'], 'thiet-bi');

        // Tài khoản ĐANG mang vi phạm thiết bị.
        //
        // Nguồn khác với bảng trên có chủ đích: `security_flags` chỉ có từ lúc
        // tính năng ghi log này lên, còn `violation_count` đã tích luỹ từ trước —
        // nên đây là chỗ duy nhất thấy được tình trạng hiện tại của những tài
        // khoản vi phạm trước đó. Đổi lại, nó chỉ là con số: bao nhiêu lần, gần
        // nhất khi nào, chứ không nói từ máy nào.
        $dangViPham = User::where('role', '!=', 'admin')
            ->where('violation_count', '>', 0)
            ->withCount('loginSessions')
            ->orderByDesc('violation_count')
            ->orderByDesc('last_violation_at')
            ->limit(100)
            ->get(['id', 'name', 'email', 'status', 'violation_count', 'last_violation_at', 'max_devices']);

        return view('admin.security-flags.index', compact(
            'flags', 'summary', 'flagsThietBi', 'dangViPham'
        ));
    }
}
