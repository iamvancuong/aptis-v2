<?php

namespace App\Services;

use App\Mail\AccountCredentialsMail;
use App\Models\Attempt;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Biến một đơn đã thanh toán thành kết quả thật: tạo/gia hạn tài khoản và gửi
 * email. Idempotent — gọi lại trên đơn đã fulfill sẽ không làm gì thêm (webhook
 * có thể bắn trùng).
 */
class OrderFulfillmentService
{
    /** Mật khẩu mặc định cho tài khoản mới; buộc đổi ở lần đăng nhập đầu. */
    public const DEFAULT_PASSWORD = '12345678';

    public function fulfill(Order $order): void
    {
        if ($order->type === Order::TYPE_REGISTRATION) {
            $this->fulfillRegistration($order);
            return;
        }

        $this->fulfillGrading($order);
    }

    /**
     * Đơn chấm bài đã trả → bật cờ để bài vào hàng đợi giáo viên chấm.
     *
     * Khoá đơn và re-check trong transaction: nếu webhook + reconcile chạy song
     * song trên cùng đơn, chỉ tiến trình đầu tiên bật cờ, tiến trình sau bỏ qua.
     */
    private function fulfillGrading(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->first();

            // Đã xử lý rồi (tiến trình song song đã fulfill) → bỏ qua.
            if (! $order || $order->isPaid()) {
                return;
            }

            $attemptId = $order->meta['attempt_id'] ?? null;

            if ($attemptId) {
                Attempt::where('id', $attemptId)->update([
                    'is_grading_requested' => true,
                    'grading_requested_at' => now(),
                ]);
            }

            $order->update([
                'status'  => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);
        });
    }

    private function fulfillRegistration(Order $order): void
    {
        $days = $order->durationDays();

        // Toàn bộ phần đổi trạng thái + cấp/gia hạn tài khoản nằm trong 1
        // transaction có khoá row đơn. Webhook và reconcile (hoặc 2 webhook)
        // chạy song song trên cùng đơn sẽ bị tuần tự hoá: tiến trình đầu fulfill,
        // các tiến trình sau đọc lại thấy đơn đã `paid` → trả về null → không cộng
        // hạn/gửi mail lần hai. Email gửi SAU commit để không giữ khoá khi gọi SMTP.
        $mailData = DB::transaction(function () use ($order, $days) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if (! $order || $order->isPaid()) {
                return null;
            }

            // Khoá luôn user (nếu có) để 2 đơn cùng email không cộng dồn đè nhau.
            $user = User::where('email', $order->email)->lockForUpdate()->first();

            if ($user) {
                // Gia hạn: cộng dồn từ mốc còn hạn (nếu còn) hoặc từ hiện tại.
                $base = ($user->expires_at && $user->expires_at->isFuture())
                    ? $user->expires_at
                    : now();

                $user->update([
                    'expires_at' => $base->copy()->addDays($days),
                    'status'     => 'active',
                ]);

                $result = [$user, false, null];
            } else {
                $user = User::create([
                    'name'                 => strtok($order->email, '@'),
                    'email'                => $order->email,
                    'password'             => Hash::make(self::DEFAULT_PASSWORD),
                    'role'                 => 'user',
                    'status'               => 'active',
                    'max_devices'          => 2,
                    'violation_count'      => 0,
                    'must_change_password' => true,
                    'expires_at'           => now()->addDays($days),
                ]);

                $result = [$user, true, self::DEFAULT_PASSWORD];
            }

            $order->update([
                'status'  => Order::STATUS_PAID,
                'paid_at' => now(),
                'user_id' => $user->id,
            ]);

            return $result;
        });

        // Đơn đã được tiến trình khác fulfill → không gửi mail lần hai.
        if ($mailData === null) {
            return;
        }

        [$user, $isNew, $password] = $mailData;

        Mail::to($user->email)->send(new AccountCredentialsMail(
            email: $user->email,
            password: $password,        // null nếu là gia hạn
            isNew: $isNew,
            expiresAt: $user->expires_at,
        ));
    }
}
