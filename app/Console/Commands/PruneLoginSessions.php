<?php

namespace App\Console\Commands;

use App\Models\LoginSession;
use Illuminate\Console\Command;

/**
 * Dọn phiên đăng nhập đã chết từ lâu.
 *
 * Thuần dọn rác: phép đếm thiết bị đã lọc theo `activity_window_hours` nên những
 * dòng này không còn ảnh hưởng ai được vào. Nhưng bảng chỉ có thêm, không bao giờ
 * bớt — mỗi lần học viên xoá cookie là một dòng ở lại vĩnh viễn.
 */
class PruneLoginSessions extends Command
{
    protected $signature = 'sessions:prune {--days= : Ghi đè số ngày trong config/devices.php}';

    protected $description = 'Xoá các phiên đăng nhập không hoạt động đã lâu';

    public function handle(): int
    {
        $ngay = (int) ($this->option('days') ?: config('devices.prune_after_days'));
        $moc  = now()->subDays($ngay);

        $so = LoginSession::where('last_active_at', '<', $moc)->delete();

        $this->info("Đã xoá {$so} phiên không hoạt động từ trước {$moc->format('d/m/Y H:i')}.");

        return self::SUCCESS;
    }
}
