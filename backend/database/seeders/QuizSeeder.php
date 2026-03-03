<?php

namespace Database\Seeders;

use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = [
            // Reading
            ['skill' => 'reading', 'part' => 1, 'title' => 'Reading Part 1: Matching', 'duration_minutes' => 10],
            ['skill' => 'reading', 'part' => 2, 'title' => 'Reading Part 2: Multiple Choice', 'duration_minutes' => 15],
            ['skill' => 'reading', 'part' => 3, 'title' => 'Reading Part 3: Reading Comprehension', 'duration_minutes' => 20],
            ['skill' => 'reading', 'part' => 4, 'title' => 'Reading Part 4: Long Passage', 'duration_minutes' => 25],

            // Listening
            ['skill' => 'listening', 'part' => 1, 'title' => 'Listening Part 1: Short Audio MCQ', 'duration_minutes' => 10],
            ['skill' => 'listening', 'part' => 2, 'title' => 'Listening Part 2: Conversation', 'duration_minutes' => 15],
            ['skill' => 'listening', 'part' => 3, 'title' => 'Listening Part 3: Monologue', 'duration_minutes' => 20],
            ['skill' => 'listening', 'part' => 4, 'title' => 'Listening Part 4: Complex Audio', 'duration_minutes' => 25],

            // Writing (Cohesive Set)
            ['skill' => 'writing', 'part' => 1, 'title' => 'Writing Part 1: Form Filling', 'duration_minutes' => 0],
            ['skill' => 'writing', 'part' => 2, 'title' => 'Writing Part 2: Short Email', 'duration_minutes' => 0],
            ['skill' => 'writing', 'part' => 3, 'title' => 'Writing Part 3: Social Media', 'duration_minutes' => 0],
            ['skill' => 'writing', 'part' => 4, 'title' => 'Writing Part 4: Dual Email', 'duration_minutes' => 50],

            // Speaking
            ['skill' => 'speaking', 'part' => 1, 'title' => 'Speaking Part 1: Personal Questions', 'duration_minutes' => 5],
            ['skill' => 'speaking', 'part' => 2, 'title' => 'Speaking Part 2: Describe Image', 'duration_minutes' => 5],
            ['skill' => 'speaking', 'part' => 3, 'title' => 'Speaking Part 3: Compare Images', 'duration_minutes' => 5],
            ['skill' => 'speaking', 'part' => 4, 'title' => 'Speaking Part 4: Long Turn', 'duration_minutes' => 5],

            // Grammar
            ['skill' => 'grammar', 'part' => 0, 'title' => 'Grammar and Vocabulary', 'duration_minutes' => 25],
        ];

        foreach ($quizzes as $quiz) {
            Quiz::firstOrCreate(
                ['skill' => $quiz['skill'], 'part' => $quiz['part']],
                [
                    'title' => $quiz['title'],
                    'duration_minutes' => $quiz['duration_minutes'],
                    'is_published' => true,
                ]
            );
        }
    }
}
