<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HighScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $scores = [
            [
                'name' => 'Phan Văn A',
                'avatar' => 'https://ui-avatars.com/api/?name=Phan+Van+A&background=random',
                'certificate' => 'Aptis C',
                'is_active' => true,
            ],
            [
                'name' => 'Nguyễn Thị B',
                'avatar' => 'https://ui-avatars.com/api/?name=Nguyen+Thi+B&background=random',
                'certificate' => 'Aptis B2',
                'is_active' => true,
            ],
            [
                'name' => 'Lê Hoàng C',
                'avatar' => 'https://ui-avatars.com/api/?name=Le+Hoang+C&background=random',
                'certificate' => 'Aptis C',
                'is_active' => true,
            ],
            [
                'name' => 'Trần Thu D',
                'avatar' => 'https://ui-avatars.com/api/?name=Tran+Thu+D&background=random',
                'certificate' => 'Aptis B2',
                'is_active' => true,
            ],
        ];

        foreach ($scores as $score) {
            \App\Models\HighScore::create($score);
        }
    }
}
