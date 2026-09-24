<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu tối thiểu để thử tính năng TRA TỪ trên máy dev.
 *
 * Chỉ dùng khi chạy với DB rỗng (SQLite cục bộ). DB test từ xa đã có đề thật
 * rồi, không cần seeder này.
 *
 * Chạy: php artisan db:seed --class=VocabDemoSeeder
 *
 * Chạy lại nhiều lần được — mọi thứ đều updateOrCreate.
 */
class VocabDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder này tạo tài khoản mật khẩu yếu. Chặn cứng ở production thay vì
        // tin vào việc "chắc không ai chạy nhầm".
        if (app()->environment('production')) {
            $this->command->error('VocabDemoSeeder không chạy ở môi trường production.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'hocvien@example.test'],
            [
                'name' => 'Học viên Demo',
                'password' => bcrypt('12345678'),
                'role' => 'user',
                'status' => 'active',
                'max_devices' => 5,
                'violation_count' => 0,
                'must_change_password' => false,
                // Bộ canh DevTools tưởng nhầm cửa sổ trình duyệt bị thu nhỏ là
                // DevTools đang mở rồi đá ra ngoài giữa lúc thử. Tài khoản demo
                // được miễn để khỏi mất thì giờ.
                'devtools_guard_disabled' => true,
            ]
        );

        // Tài khoản admin để vào /admin và thử phía quản trị. Admin KHÔNG bị trừ
        // lượt tra từ (xem User::isAdmin), nên muốn thử phần hết hạn mức thì
        // phải đăng nhập bằng tài khoản học viên ở trên.
        User::updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin Demo',
                'password' => bcrypt('12345678'),
                'role' => 'admin',
                'status' => 'active',
                'max_devices' => 99,
                'violation_count' => 0,
                'must_change_password' => false,
                'devtools_guard_disabled' => true,
            ]
        );

        $quiz = Quiz::updateOrCreate(
            ['title' => 'Reading Part 4 - Demo tra từ'],
            ['skill' => 'reading', 'part' => 4, 'duration_minutes' => 20, 'is_published' => true]
        );

        $set = Set::updateOrCreate(
            ['quiz_id' => $quiz->id, 'title' => 'Bộ đề demo tra từ'],
            ['status' => 'published', 'order' => 1, 'is_public' => true, 'max_attempts' => 99]
        );

        // Đoạn văn cố ý nhét từ khó (indispensable, cumbersome, bewildering) và
        // câu dài để thử cả hai chế độ: tra từ đơn và dịch cả câu.
        $question = Question::updateOrCreate(
            ['quiz_id' => $quiz->id, 'order' => 1],
            [
                'skill' => 'reading',
                'part' => 4,
                'type' => 'heading_matching',
                'title' => 'Urban beekeeping',
                'stem' => 'Read the passage and choose the best heading for each paragraph.',
                'point' => 1,
                'metadata' => [
                    'headings' => [
                        'A surprising comeback',
                        'Rules that had to change',
                        'What the honey tastes like',
                    ],
                    'paragraphs' => [
                        'Urban beekeeping has undergone a remarkable revival over the past decade. Councils that once discouraged hives on rooftops now actively implement schemes to support them, arguing that pollinators are indispensable to city parks and allotments.',
                        'The legislation governing hives in built-up areas was notoriously cumbersome. Keepers had to notify several authorities before installing a single colony, and the paperwork often deterred newcomers altogether.',
                        'Connoisseurs claim that city honey is more complex than its rural counterpart, because bees forage across a bewildering variety of garden flowers rather than a single crop.',
                    ],
                    'correct_answers' => [0, 1, 2],
                ],
            ]
        );

        $set->questions()->syncWithoutDetaching([$question->id]);

        $this->command->info('Xong.');
        $this->command->info('  Học viên: hocvien@example.test / 12345678');
        $this->command->info('  Admin:    admin@example.test / 12345678  →  /admin');
        $this->command->info('  Trang luyện tập: /practice/' . $set->id);
    }
}
