<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpeakingSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quiz = \App\Models\Quiz::firstOrCreate(
            ['skill' => 'speaking', 'part' => 1],
            [
                'title' => 'Speaking',
                'duration_minutes' => 12,
                'metadata' => []
            ]
        );

        $set = \App\Models\Set::firstOrCreate(
            ['quiz_id' => $quiz->id, 'title' => 'Speaking Test 01 - Sample for Testing'],
            [
                'is_public' => true,
                'status' => 'published'
            ]
        );

        // We need 4 questions.
        // Part 1
        $q1 = \App\Models\Question::firstOrCreate(
            ['quiz_id' => $quiz->id, 'part' => 1, 'type' => 'speaking-part-1'],
            [
                'skill' => 'speaking',
                'order' => 1,
                'stem' => 'In this part, I am going to ask you three short questions...',
                'metadata' => [
                    'questions' => [
                        'Please tell me about your family.',
                        'What do you like to do in your free time?',
                        'Tell me about your typical day.'
                    ],
                    'prep_time' => 0,
                    'answer_time_per_question' => 30
                ]
            ]
        );
        $set->questions()->syncWithoutDetaching([$q1->id]);

        // Part 2
        $q2 = \App\Models\Question::firstOrCreate(
            ['quiz_id' => $quiz->id, 'part' => 2, 'type' => 'speaking-part-2'],
            [
                'skill' => 'speaking',
                'order' => 2,
                'stem' => 'Describe this picture and answer the questions.',
                'metadata' => [
                    'image_path' => '', // Empty for now, user can upload in Admin
                    'questions' => [
                        'Describe this picture.',
                        'Why do you think the people in the picture are happy?',
                        'Tell me about a time you experienced something similar.'
                    ],
                    'prep_time' => 21,
                    'answer_time_per_question' => 45
                ]
            ]
        );
        $set->questions()->syncWithoutDetaching([$q2->id]);

        // Part 3
        $q3 = \App\Models\Question::firstOrCreate(
            ['quiz_id' => $quiz->id, 'part' => 3, 'type' => 'speaking-part-3'],
            [
                'skill' => 'speaking',
                'order' => 3,
                'stem' => 'Compare the two pictures and answer the following questions.',
                'metadata' => [
                    'image_paths' => ['', ''],
                    'questions' => [
                        'Compare the two pictures.',
                        'Which of these two places would you prefer to visit and why?',
                        'How do you think tourism affects these kinds of places?'
                    ],
                    'prep_time' => 0,
                    'answer_time_per_question' => 45
                ]
            ]
        );
        $set->questions()->syncWithoutDetaching([$q3->id]);

        // Part 4
        $q4 = \App\Models\Question::firstOrCreate(
            ['quiz_id' => $quiz->id, 'part' => 4, 'type' => 'speaking-part-4'],
            [
                'skill' => 'speaking',
                'order' => 4,
                'stem' => 'Look at the picture and answer the questions below.',
                'metadata' => [
                    'image_path' => '',
                    'questions' => [
                        'Tell me about a time when you were on your own.',
                        'How did you feel about it?',
                        'What are some of the ways of passing the time on your own?'
                    ],
                    'prep_time' => 60,
                    'total_answer_time' => 120
                ]
            ]
        );
        $set->questions()->syncWithoutDetaching([$q4->id]);
    }
}
