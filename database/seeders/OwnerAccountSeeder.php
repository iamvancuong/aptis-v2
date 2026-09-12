<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo (hoặc cập nhật) tài khoản CHỦ SỞ HỮU hệ thống — role `owner`.
 *
 *  • Toàn quyền như admin, nhưng bị ẩn khỏi mọi truy vấn của người khác (global
 *    scope `hideOwner` trong App\Models\User) — kể cả admin hiện tại cũng không
 *    thấy trong danh sách người dùng/học viên.
 *  • Chỉ owner mới thấy phần "Doanh thu tổng thật" ở màn Doanh số.
 *
 * Email + mật khẩu đọc từ .env (KHÔNG hardcode vào file bị commit vào git):
 *
 *     OWNER_EMAIL=chu-so-huu@vidu.com
 *     OWNER_PASSWORD=mat-khau-that-dai-va-manh
 *
 * Chạy riêng lẻ trên production (không chạy cả DatabaseSeeder — nó tạo cả tài
 * khoản demo):
 *
 *     php artisan db:seed --class=OwnerAccountSeeder
 *
 * Thiếu biến .env thì seeder bỏ qua (không tạo tài khoản hỏng), in cảnh báo.
 */
class OwnerAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Đọc qua config() chứ KHÔNG env() trực tiếp: khi production đã
        // config:cache thì env() ngoài thư mục config/ trả về null. Xem
        // config/owner.php. Nhớ set .env TRƯỚC rồi mới config:cache.
        $email    = config('owner.email');
        $password = config('owner.password');

        if (empty($email) || empty($password)) {
            $this->command?->warn(
                'Bỏ qua OwnerAccountSeeder: chưa thấy OWNER_EMAIL/OWNER_PASSWORD. '
                . 'Đặt trong .env rồi chạy lại `php artisan config:clear` trước khi seed.'
            );
            return;
        }

        // `withoutGlobalScopes()` để tìm được owner đã tồn tại — nếu không global
        // scope `hideOwner` sẽ ẩn nó khỏi updateOrCreate và tạo ra bản trùng.
        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name'                 => 'Hệ thống',
                'password'             => Hash::make($password),
                'role'                 => User::ROLE_OWNER,
                'source'               => User::SOURCE_SYSTEM,
                'status'               => 'active',
                'must_change_password' => false, // tài khoản hệ thống, không ép đổi
                'expires_at'           => null,   // không bao giờ hết hạn
                'max_devices'          => 99,     // không vướng giới hạn thiết bị
                'violation_count'      => 0,
            ]
        );

        $this->command?->info("Tài khoản owner đã sẵn sàng: {$email}");
    }
}
