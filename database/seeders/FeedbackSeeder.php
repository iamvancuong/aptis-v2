<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $feedbacks = [
            [
                'name' => 'Nguyễn Văn A',
                'avatar' => 'https://ui-avatars.com/api/?name=Nguyen+Van+A&background=random',
                'rating' => 5,
                'content' => 'Hệ thống thi thử sát với đề thi thật. Nhờ AI chấm điểm phần Writing và Speaking chi tiết, mình đã cải thiện điểm số rất nhanh và đạt B2 Aptis chỉ sau 1 tháng.',
                'is_active' => true,
            ],
            [
                'name' => 'Trần Thị B',
                'avatar' => 'https://ui-avatars.com/api/?name=Tran+Thi+B&background=random',
                'rating' => 5,
                'content' => 'Giao diện làm bài mượt mà, không bị lỗi. Mình thích nhất phần phân tích kết quả sau khi thi, giúp mình biết phần Grammar và Reading còn yếu để tập trung cải thiện.',
                'is_active' => true,
            ],
            [
                'name' => 'Lê Văn C',
                'avatar' => 'https://ui-avatars.com/api/?name=Le+Van+C&background=random',
                'rating' => 5,
                'content' => 'Giá cả cực kỳ hợp lý so với các trung tâm bên ngoài. Bộ đề đa dạng và có giải thích chi tiết. Recommended cho các bạn đang tự ôn thi Aptis!',
                'is_active' => true,
            ],
        ];

        foreach ($feedbacks as $feedback) {
            \App\Models\Feedback::create($feedback);
        }
    }
}
