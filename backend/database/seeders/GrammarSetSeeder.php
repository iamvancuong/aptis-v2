<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class GrammarSetSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ── Quiz ────────────────────────────────────────────────────────
            $quiz = Quiz::firstOrCreate(
                ['skill' => 'grammar'],
                [
                    'title'            => 'Grammar and Vocabulary',
                    'part'             => 0,
                    'duration_minutes' => 25,
                    'is_published'     => true,
                ]
            );

            // ── Set ─────────────────────────────────────────────────────────
            $set = Set::create([
                'quiz_id'  => $quiz->id,
                'title'    => 'Grammar Test 01 – Sample Set',
                'status'   => 'published',
                'metadata' => [
                    'grammar_config' => [
                        'mcq_count'            => 25,
                        'vocab_count'          => 5,
                        'vocab_types_required' => [
                            'synonym_match',
                            'definition_match',
                            'sentence_completion',
                            'synonym_match',
                            'collocation_match',
                        ],
                    ],
                ],
            ]);

            // ── Part 1: 25 MCQ questions ────────────────────────────────────
            $mcqs = [
                1  => ['stem' => 'She ___ to school every day by bus.', 'A' => 'go', 'B' => 'goes', 'C' => 'going', 'correct' => 'B'],
                2  => ['stem' => 'They ___ playing football when it started to rain.', 'A' => 'was', 'B' => 'were', 'C' => 'are', 'correct' => 'B'],
                3  => ['stem' => 'I have never ___ to Japan before.', 'A' => 'been', 'B' => 'went', 'C' => 'go', 'correct' => 'A'],
                4  => ['stem' => 'He ___ finish his homework before going out.', 'A' => 'must', 'B' => 'musts', 'C' => 'is must', 'correct' => 'A'],
                5  => ['stem' => 'The book ___ on the table belongs to my sister.', 'A' => 'laying', 'B' => 'lying', 'C' => 'lain', 'correct' => 'B'],
                6  => ['stem' => 'If I ___ more time, I would travel the world.', 'A' => 'have', 'B' => 'had', 'C' => 'has', 'correct' => 'B'],
                7  => ['stem' => 'The report ___ submitted by Friday.', 'A' => 'must be', 'B' => 'must', 'C' => 'should', 'correct' => 'A'],
                8  => ['stem' => 'Neither the students nor the teacher ___ ready.', 'A' => 'were', 'B' => 'was', 'C' => 'are', 'correct' => 'B'],
                9  => ['stem' => 'She speaks English ___ than her brother.', 'A' => 'more fluent', 'B' => 'more fluently', 'C' => 'fluenter', 'correct' => 'B'],
                10 => ['stem' => 'By the time he arrived, the meeting ___.', 'A' => 'ended', 'B' => 'had ended', 'C' => 'has ended', 'correct' => 'B'],
                11 => ['stem' => 'I think it is right ___ children to play outside.', 'A' => 'of', 'B' => 'to', 'C' => 'for', 'correct' => 'C'],
                12 => ['stem' => 'The news ___ surprising to everyone.', 'A' => 'were', 'B' => 'was', 'C' => 'are', 'correct' => 'B'],
                13 => ['stem' => 'We need ___ more information before deciding.', 'A' => 'a few', 'B' => 'a little', 'C' => 'few', 'correct' => 'B'],
                14 => ['stem' => 'He suggested ___ to the museum on Saturday.', 'A' => 'go', 'B' => 'to go', 'C' => 'going', 'correct' => 'C'],
                15 => ['stem' => 'The children enjoyed ___ the animals at the zoo.', 'A' => 'watching', 'B' => 'to watch', 'C' => 'watch', 'correct' => 'A'],
                16 => ['stem' => 'She asked me ___ her with the project.', 'A' => 'helping', 'B' => 'to help', 'C' => 'help', 'correct' => 'B'],
                17 => ['stem' => 'I wish I ___ speak French fluently.', 'A' => 'can', 'B' => 'could', 'C' => 'will', 'correct' => 'B'],
                18 => ['stem' => '___ the weather was bad, we still went hiking.', 'A' => 'Although', 'B' => 'Despite', 'C' => 'However', 'correct' => 'A'],
                19 => ['stem' => 'He is used to ___ early every morning.', 'A' => 'wake', 'B' => 'waking', 'C' => 'woken', 'correct' => 'B'],
                20 => ['stem' => 'The exam results ___ announced next week.', 'A' => 'will be', 'B' => 'will', 'C' => 'are', 'correct' => 'A'],
                21 => ['stem' => 'She has been working here ___ five years.', 'A' => 'since', 'B' => 'for', 'C' => 'during', 'correct' => 'B'],
                22 => ['stem' => 'The more you practice, ___ you become.', 'A' => 'the better', 'B' => 'the best', 'C' => 'better', 'correct' => 'A'],
                23 => ['stem' => 'He ___ have left already; his coat is gone.', 'A' => 'should', 'B' => 'must', 'C' => 'would', 'correct' => 'B'],
                24 => ['stem' => 'We were told ___ the instructions carefully.', 'A' => 'reading', 'B' => 'read', 'C' => 'to read', 'correct' => 'C'],
                25 => ['stem' => 'It was the ___ film I have ever seen.', 'A' => 'most boring', 'B' => 'more boring', 'C' => 'boringer', 'correct' => 'A'],
            ];

            foreach ($mcqs as $order => $data) {
                $q = Question::create([
                    'quiz_id'  => $quiz->id,
                    'skill'    => 'grammar',
                    'part'     => 1,
                    'type'     => 'mcq3',
                    'stem'     => $data['stem'],
                    'point'    => 1,
                    'order'    => $order,
                    'metadata' => [
                        'options' => [
                            ['id' => 'A', 'text' => $data['A']],
                            ['id' => 'B', 'text' => $data['B']],
                            ['id' => 'C', 'text' => $data['C']],
                        ],
                        'correct_option' => $data['correct'],
                    ],
                ]);
                $set->questions()->attach($q->id);
            }

            // ── Part 2: 5 Vocab questions ────────────────────────────────────

            // Q26: synonym_match
            $q26 = Question::create([
                'quiz_id'  => $quiz->id,
                'skill'    => 'grammar',
                'part'     => 2,
                'type'     => 'synonym_match',
                'stem'     => 'Synonym – Select the word with the same meaning',
                'point'    => 5,
                'order'    => 26,
                'metadata' => [
                    'vocab_type'    => 'synonym_match',
                    'connector'     => '=',
                    'example'       => ['left' => 'big', 'right' => 'large'],
                    'pairs'         => [
                        ['id' => 1, 'prompt' => 'study'],
                        ['id' => 2, 'prompt' => 'receive'],
                        ['id' => 3, 'prompt' => 'start'],
                        ['id' => 4, 'prompt' => 'talk'],
                        ['id' => 5, 'prompt' => 'pick'],
                    ],
                    'dropdown_pool'   => ['learn', 'get', 'begin', 'speak', 'choose', 'donate', 'go', 'run', 'listen', 'end'],
                    'correct_answers' => ['1' => 'learn', '2' => 'get', '3' => 'begin', '4' => 'speak', '5' => 'choose'],
                ],
            ]);
            $set->questions()->attach($q26->id);

            // Q27: definition_match
            $q27 = Question::create([
                'quiz_id'  => $quiz->id,
                'skill'    => 'grammar',
                'part'     => 2,
                'type'     => 'definition_match',
                'stem'     => 'Definition – Match the word to its definition',
                'point'    => 5,
                'order'    => 27,
                'metadata' => [
                    'vocab_type'  => 'definition_match',
                    'connector'   => '→',
                    'pairs'       => [
                        ['id' => 1, 'prompt' => 'the place where you sleep'],
                        ['id' => 2, 'prompt' => 'a person who teaches'],
                        ['id' => 3, 'prompt' => 'the opposite of hot'],
                        ['id' => 4, 'prompt' => 'to move very quickly on foot'],
                        ['id' => 5, 'prompt' => 'a tool used to write'],
                    ],
                    'dropdown_pool'   => ['bedroom', 'teacher', 'cold', 'run', 'pen', 'kitchen', 'student', 'warm', 'walk', 'pencil'],
                    'correct_answers' => ['1' => 'bedroom', '2' => 'teacher', '3' => 'cold', '4' => 'run', '5' => 'pen'],
                ],
            ]);
            $set->questions()->attach($q27->id);

            // Q28: sentence_completion
            $q28 = Question::create([
                'quiz_id'  => $quiz->id,
                'skill'    => 'grammar',
                'part'     => 2,
                'type'     => 'sentence_completion',
                'stem'     => 'Sentence – Choose the word that completes the sentence',
                'point'    => 5,
                'order'    => 28,
                'metadata' => [
                    'vocab_type' => 'sentence_completion',
                    'pairs'      => [
                        ['id' => 1, 'prefix' => 'She always',                  'suffix' => 'her best in every exam.'],
                        ['id' => 2, 'prefix' => 'The doctor',                  'suffix' => 'his patients to exercise regularly.'],
                        ['id' => 3, 'prefix' => 'We need to',                  'suffix' => 'a decision before Friday.'],
                        ['id' => 4, 'prefix' => 'He forgot to',                'suffix' => 'the lights before leaving.'],
                        ['id' => 5, 'prefix' => 'They are planning to',        'suffix' => 'a new project next month.'],
                    ],
                    'dropdown_pool'   => ['does', 'advises', 'make', 'turn off', 'launch', 'begins', 'tells', 'take', 'switch', 'start'],
                    'correct_answers' => ['1' => 'does', '2' => 'advises', '3' => 'make', '4' => 'turn off', '5' => 'launch'],
                ],
            ]);
            $set->questions()->attach($q28->id);

            // Q29: synonym_match (second one)
            $q29 = Question::create([
                'quiz_id'  => $quiz->id,
                'skill'    => 'grammar',
                'part'     => 2,
                'type'     => 'synonym_match',
                'stem'     => 'Synonym – Select the word with the same meaning',
                'point'    => 5,
                'order'    => 29,
                'metadata' => [
                    'vocab_type'  => 'synonym_match',
                    'connector'   => '=',
                    'example'     => ['left' => 'happy', 'right' => 'glad'],
                    'pairs'       => [
                        ['id' => 1, 'prompt' => 'fast'],
                        ['id' => 2, 'prompt' => 'angry'],
                        ['id' => 3, 'prompt' => 'tired'],
                        ['id' => 4, 'prompt' => 'clever'],
                        ['id' => 5, 'prompt' => 'sad'],
                    ],
                    'dropdown_pool'   => ['quick', 'furious', 'exhausted', 'smart', 'unhappy', 'slow', 'calm', 'fresh', 'dull', 'joyful'],
                    'correct_answers' => ['1' => 'quick', '2' => 'furious', '3' => 'exhausted', '4' => 'smart', '5' => 'unhappy'],
                ],
            ]);
            $set->questions()->attach($q29->id);

            // Q30: collocation_match
            $q30 = Question::create([
                'quiz_id'  => $quiz->id,
                'skill'    => 'grammar',
                'part'     => 2,
                'type'     => 'collocation_match',
                'stem'     => 'Collocation – Choose the word that goes with the prompt',
                'point'    => 5,
                'order'    => 30,
                'metadata' => [
                    'vocab_type'  => 'collocation_match',
                    'connector'   => '+',
                    'example'     => ['left' => 'make', 'right' => 'a decision'],
                    'pairs'       => [
                        ['id' => 1, 'prompt' => 'do'],
                        ['id' => 2, 'prompt' => 'take'],
                        ['id' => 3, 'prompt' => 'give'],
                        ['id' => 4, 'prompt' => 'break'],
                        ['id' => 5, 'prompt' => 'keep'],
                    ],
                    'dropdown_pool'   => ['homework', 'a photo', 'a speech', 'a record', 'a promise', 'the dishes', 'a nap', 'advice', 'the rules', 'a note'],
                    'correct_answers' => ['1' => 'homework', '2' => 'a photo', '3' => 'a speech', '4' => 'a record', '5' => 'a promise'],
                ],
            ]);
            $set->questions()->attach($q30->id);

            $this->command->info("✅ Grammar Set seeded: \"{$set->title}\" (ID: {$set->id}) with 30 questions.");
        });
    }
}
