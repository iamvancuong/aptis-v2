<?php

namespace App\Console\Commands;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bật chính sách thiết bị mới trên dữ liệu đang chạy. Chạy MỘT LẦN khi deploy.
 *
 * Vì sao phải reset `violation_count`: luật cũ đếm cả lịch sử đăng nhập nên phạt
 * nhầm người xoá cookie / dùng ẩn danh. Giữ lại số vi phạm tích luỹ từ luật cũ mà
 * hạ ngưỡng khoá xuống 2 thì hàng chục tài khoản bị khoá ngay ngày đầu vì những
 * "vi phạm" mà chính hệ thống đã tính sai. Bật luật mới thì đếm lại từ đầu.
 */
class ApplyDevicePolicy extends Command
{
    protected $signature = 'devices:apply-policy {--dry-run : Chỉ in ra sẽ đổi gì, không ghi DB}';

    protected $description = 'Áp chính sách giới hạn thiết bị mới cho toàn bộ tài khoản';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tran   = (int) config('devices.max_devices');
        $ngayDon = (int) config('devices.prune_after_days');

        $this->info($dryRun ? '🔎 CHẠY THỬ — không ghi gì vào DB' : '✍️  GHI THẬT vào DB');
        $this->newLine();

        $hocVien = User::where('role', '!=', 'admin');

        $doiTran   = (clone $hocVien)->where('max_devices', '!=', $tran)->count();
        $coViPham  = (clone $hocVien)->where('violation_count', '>', 0)->count();
        $seKhoaOan = (clone $hocVien)
            ->where('violation_count', '>=', (int) config('devices.block_after_violations'))
            ->count();
        $phienCu   = LoginSession::where('last_active_at', '<', now()->subDays($ngayDon))->count();

        $this->table(['Việc', 'Số lượng'], [
            ["Đặt max_devices = {$tran}", $doiTran],
            ['Reset violation_count về 0', $coViPham],
            ["  → trong đó sẽ bị KHOÁ OAN nếu không reset", $seKhoaOan],
            ["Xoá phiên không hoạt động > {$ngayDon} ngày", $phienCu],
        ]);

        if ($seKhoaOan > 0) {
            $this->warn("⚠️  {$seKhoaOan} tài khoản đang mang đủ vi phạm để bị khoá ngay theo ngưỡng mới.");
            $this->warn('    Chúng tích từ luật CŨ (luật cũ đếm sai) nên phải reset, nếu không họ bị khoá oan.');
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Chưa ghi gì. Bỏ --dry-run để ghi thật.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($tran, $ngayDon) {
            User::where('role', '!=', 'admin')->update([
                'max_devices'       => $tran,
                'violation_count'   => 0,
                'last_violation_at' => null,
            ]);

            LoginSession::where('last_active_at', '<', now()->subDays($ngayDon))->delete();
        });

        $this->newLine();
        $this->info('✅ Đã áp chính sách mới.');
        $this->line("   Trần {$tran} thiết bị cùng lúc · cửa sổ "
            . config('devices.activity_window_hours') . ' giờ · khoá sau '
            . config('devices.block_after_violations') . ' vi phạm · vi phạm hết hạn sau '
            . config('devices.violation_reset_days') . ' ngày.');

        // KHÔNG khoá/mở khoá ai ở đây. Tài khoản đang `blocked` có thể bị khoá vì
        // lý do khác (admin chặn tay), tự động mở hết là vượt quyền.
        $dangKhoa = User::where('role', '!=', 'admin')->where('status', 'blocked')->count();

        if ($dangKhoa > 0) {
            $this->newLine();
            $this->warn("ℹ️  {$dangKhoa} tài khoản đang bị KHOÁ — lệnh này cố ý không mở khoá ai.");
            $this->warn('    Nếu họ bị khoá oan theo luật cũ, mở tay ở /admin/users (nút Unblock giờ đã reset vi phạm).');
        }

        return self::SUCCESS;
    }
}
