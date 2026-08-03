<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Điền `users.source` cho dữ liệu có TRƯỚC khi cột này tồn tại.
 *
 * Vì sao không suy thẳng từ bảng `orders` mỗi lần cần: luồng PayOS lên production
 * 28/07/2026, nên tuyệt đại đa số học viên hiện tại không có đơn nào — không phải
 * vì họ không trả tiền, mà vì lúc họ vào thì hệ thống thu tiền chưa tồn tại.
 * Suy "không có đơn ⇒ tạo tay" là đúng về mặt kỹ thuật nhưng sai về mặt nghiệp vụ,
 * nên phải phân biệt tài khoản 'manual' (admin thật sự tạo tay, sau mốc) với
 * 'import' (dữ liệu cũ, không biết nguồn).
 *
 * Chạy lại nhiều lần vô hại: luôn tính lại từ đầu theo cùng bộ luật.
 */
class BackfillUserSource extends Command
{
    protected $signature = 'users:backfill-source
                            {--dry-run : Chỉ in ra sẽ đổi gì, không ghi DB}
                            {--moc=2026-07-28 : Ngày luồng PayOS lên production}';

    protected $description = 'Điền users.source cho tài khoản có trước khi có cột này';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moc    = Carbon::parse($this->option('moc'))->startOfDay();

        // Tài khoản đã từng có đơn ĐĂNG KÝ thanh toán thành công.
        $daMua = Order::where('type', Order::TYPE_REGISTRATION)
            ->where('status', Order::STATUS_PAID)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->all();

        $this->info($dryRun ? '🔎 CHẠY THỬ — không ghi gì vào DB' : '✍️  GHI THẬT vào DB');
        $this->line("Mốc PayOS lên production: {$moc->format('d/m/Y')}");
        $this->newLine();

        $ke = ['purchase' => 0, 'import' => 0, 'manual' => 0];

        User::where('role', '!=', 'admin')
            ->select(['id', 'source', 'created_at'])
            ->chunkById(500, function ($users) use ($daMua, $moc, $dryRun, &$ke) {
                foreach ($users as $u) {
                    $moi = match (true) {
                        in_array($u->id, $daMua, true)  => User::SOURCE_PURCHASE,
                        $u->created_at?->lt($moc)       => User::SOURCE_IMPORT,
                        default                         => User::SOURCE_MANUAL,
                    };

                    $ke[$moi]++;

                    if (! $dryRun && $u->source !== $moi) {
                        // `update()` thẳng trên query để không kích hoạt event/timestamps
                        // — đây là sửa dữ liệu lịch sử, không phải hành động của người dùng.
                        DB::table('users')->where('id', $u->id)->update(['source' => $moi]);
                    }
                }
            });

        $this->table(
            ['Nguồn', 'Số tài khoản', 'Nghĩa'],
            [
                [User::SOURCE_PURCHASE, $ke['purchase'], 'Có đơn đăng ký đã thanh toán'],
                [User::SOURCE_IMPORT,   $ke['import'],   'Tạo trước mốc PayOS — không rõ nguồn'],
                [User::SOURCE_MANUAL,   $ke['manual'],   'Tạo sau mốc mà không có đơn ⇒ admin tạo tay'],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('Chưa ghi gì. Bỏ --dry-run để ghi thật.');
        }

        // Admin không nằm trong phạm vi backfill — giữ mặc định 'manual' của cột.
        $this->line('(Tài khoản admin không được tính — không liên quan tới lớp học.)');

        return self::SUCCESS;
    }
}
