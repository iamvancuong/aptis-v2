<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Set;
use Illuminate\Database\Seeder;

class SetSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = Quiz::all();

        foreach ($quizzes as $quiz) {
            for ($i = 1; $i <= 3; $i++) {
                Set::create([
                    'quiz_id' => $quiz->id,
                    'title' => "{$quiz->title} - Bộ {$i}",
                    'order' => $i - 1,
                    'is_public' => true,
                ]);
            }
        }
    }
}
