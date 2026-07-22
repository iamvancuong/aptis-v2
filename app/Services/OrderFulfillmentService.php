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
        // Đã xử lý rồi thì thôi (chống webhook trùng).
        if ($order->isPaid() && $order->user_id) {
            return;
        }

        if ($order->type === Order::TYPE_REGISTRATION) {
            $this->fulfillRegistration($order);
            return;
        }

        $this->fulfillGrading($order);
    }

    /**
     * Đơn chấm bài đã trả → bật cờ để bài vào hàng đợi giáo viên chấm.
     */
    private function fulfillGrading(Order $order): void
    {
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
    }

    private function fulfillRegistration(Order $order): void
    {
        $days = $order->durationDays();

        [$user, $isNew, $password] = DB::transaction(function () use ($order, $days) {
            $user = User::where('email', $order->email)->first();

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

        Mail::to($user->email)->send(new AccountCredentialsMail(
            email: $user->email,
            password: $password,        // null nếu là gia hạn
            isNew: $isNew,
            expiresAt: $user->expires_at,
        ));
    }
}
