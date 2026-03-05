<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Set;

class TestListeningSeeder extends Seeder
{
    public function run()
    {
        $set = Set::whereHas('quiz', function($q) {
            $q->where('skill', 'listening');
        })->first();

        if (!$set) {
            $this->command->error("No Listening Set found.");
            return;
        }

        $targetTotal = 100;
        $startOrder = Question::whereHas('sets', function($q) use ($set) {
            $q->where('sets.id', $set->id);
        })->where('part', 1)->max('order') + 1;

        $questions = [];
        for ($i = 1; $i <= $targetTotal; $i++) {
            $order = $startOrder + $i - 1;
            
            // Create using Eloquent to handle automatic timestamps and model events
            $question = Question::create([
                'quiz_id' => $set->quiz_id,
                'skill' => 'listening',
                'part' => 1,
                'type' => 'mcq',
                'title' => 'Dummy Audio Question ' . $i,
                'stem' => 'This is test question number ' . $i . '. Could you search this stem text to verify the feature works?',
                'point' => 1.0,
                'order' => $order,
                'metadata' => [
                    'audio_url' => '',
                    'items' => ['Option A', 'Option B', 'Option C'],
                    'correct_answer' => '0',
                    'explanation' => 'Just a test.'
                ],
            ]);
            
            $questions[] = $question->id;
        }

        // Attach to the set via the pivot table
        $set->questions()->attach($questions);

        $this->command->info("Successfully seeded $targetTotal dummy listening questions to Set: {$set->name}.");
    }
}
