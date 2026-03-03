<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class ImportListeningJson extends Command
{
    protected $signature = 'import:listening-json {file}';
    protected $description = 'Import Listening Questions from a JSON export';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['quizzes'])) {
            $this->error("Invalid JSON format");
            return 1;
        }

        DB::beginTransaction();
        try {
            foreach ($data['quizzes'] as $quizData) {
                $quiz = Quiz::updateOrCreate(
                    ['id' => $quizData['id']],
                    [
                        'title' => $quizData['title'],
                        'description' => $quizData['description'],
                        'skill' => $quizData['skill'],
                        'part' => $quizData['part'],
                        'is_published' => $quizData['is_published'],
                        'duration_minutes' => $quizData['duration_minutes'],
                        'show_explanation' => $quizData['show_explanation'],
                        'metadata' => $quizData['metadata'],
                    ]
                );

                if (isset($quizData['sets'])) {
                    foreach ($quizData['sets'] as $setData) {
                        $set = Set::updateOrCreate(
                            ['id' => $setData['id']],
                            [
                                'quiz_id'     => $quiz->id,
                                'title'       => $setData['title'] ?? '',
                                'skill'       => $setData['skill'] ?? 'listening',
                                'description' => $setData['description'] ?? null,
                                'is_public'   => $setData['is_public'] ?? false,
                                'order'       => $setData['order'] ?? 0,
                                'metadata'    => $setData['metadata'] ?? null,
                            ]
                        );

                        if (isset($setData['questions'])) {
                            foreach ($setData['questions'] as $qData) {
                                $mappedMetadata = $qData['metadata'] ?? [];
                                $part = $qData['part'];
                                $type = $qData['type'];
                                
                                if (($qData['skill'] ?? 'listening') === 'listening') {
                                    if ($part == 1) {
                                        // Map options → choices, correct_index → correct_answer
                                        $mappedMetadata['choices'] = $mappedMetadata['options'] ?? $mappedMetadata['choices'] ?? [];
                                        $mappedMetadata['correct_answer'] = isset($mappedMetadata['correct_index'])
                                            ? (int) $mappedMetadata['correct_index']
                                            : ($mappedMetadata['correct_answer'] ?? 0);
                                        // Clean up duplicate/redundant fields
                                        unset($mappedMetadata['options'], $mappedMetadata['correct_index'], $mappedMetadata['stem']);

                                    } elseif ($part == 2) {
                                        // Topic from stem
                                        $mappedMetadata['topic'] = $qData['stem'] ?? ($mappedMetadata['title'] ?? ($mappedMetadata['stem'] ?? ''));
                                        // Map options → choices (6 options)
                                        $mappedMetadata['choices'] = $mappedMetadata['options'] ?? $mappedMetadata['choices'] ?? [];
                                        // Map answers → correct_answers (int array, one per speaker)
                                        $mappedMetadata['correct_answers'] = isset($mappedMetadata['answers'])
                                            ? array_map('intval', $mappedMetadata['answers'])
                                            : ($mappedMetadata['correct_answers'] ?? []);
                                        // Map speakers → items (speaker names), audio_files, speaker_descriptions
                                        if (isset($mappedMetadata['speakers'])) {
                                            $items = [];
                                            $audioFiles = [];
                                            $speakerDescriptions = [];
                                            foreach ($mappedMetadata['speakers'] as $speaker) {
                                                $items[] = $speaker['label'] ?? '';
                                                $audioFiles[] = $speaker['audio'] ?? null;
                                                $speakerDescriptions[] = $speaker['description'] ?? '';
                                            }
                                            $mappedMetadata['items'] = $items;
                                            $mappedMetadata['audio_files'] = $audioFiles;
                                            $mappedMetadata['speaker_descriptions'] = $speakerDescriptions;
                                        }
                                        // Normalize type
                                        $type = 'listening_speakers_match';
                                        // Clean up stale fields from JSON export
                                        unset(
                                            $mappedMetadata['options'],
                                            $mappedMetadata['answers'],
                                            $mappedMetadata['speakers'],
                                            $mappedMetadata['description'],
                                            $mappedMetadata['title'],
                                            $mappedMetadata['stem']
                                        );

                                    } elseif ($part == 3) {
                                        // Map title/stem → topic
                                        $mappedMetadata['topic'] = $mappedMetadata['title'] ?? $mappedMetadata['stem'] ?? '';
                                        // Map options → shared_choices
                                        $mappedMetadata['shared_choices'] = $mappedMetadata['options'] ?? $mappedMetadata['shared_choices'] ?? [];
                                        // Map items → statements
                                        $mappedMetadata['statements'] = $mappedMetadata['items'] ?? $mappedMetadata['statements'] ?? [];
                                        // Map answers → correct_answers (int)
                                        $mappedMetadata['correct_answers'] = isset($mappedMetadata['answers'])
                                            ? array_map('intval', $mappedMetadata['answers'])
                                            : ($mappedMetadata['correct_answers'] ?? []);
                                        // Normalize type to single standard value
                                        $type = 'multi_matching';
                                        // Clean up
                                        unset($mappedMetadata['options'], $mappedMetadata['answers'], $mappedMetadata['items'], $mappedMetadata['title'], $mappedMetadata['stem']);

                                    } elseif ($part == 4) {
                                        // Map stem → topic
                                        $mappedMetadata['topic'] = $mappedMetadata['stem'] ?? $mappedMetadata['topic'] ?? '';
                                        // Map questions: stem → question, options → choices, save text & correct_index
                                        if (isset($mappedMetadata['questions'])) {
                                            $newQuestions = [];
                                            $correctAnswers = [];
                                            foreach ($mappedMetadata['questions'] as $q) {
                                                $newQuestions[] = [
                                                    'question' => $q['stem'] ?? '',
                                                    'choices'  => $q['options'] ?? [],
                                                    'text'     => $q['text'] ?? null, // keep transcript/note
                                                    'sub'      => $q['sub'] ?? null,
                                                ];
                                                $correctAnswers[] = isset($q['correct_index']) ? (int) $q['correct_index'] : 0;
                                            }
                                            $mappedMetadata['questions'] = $newQuestions;
                                            $mappedMetadata['correct_answers'] = $correctAnswers;
                                        }
                                        // Normalize empty-string audio → null
                                        if (isset($mappedMetadata['audio']) && $mappedMetadata['audio'] === '') {
                                            $mappedMetadata['audio'] = null;
                                        }
                                        // Normalize type
                                        $type = 'single_choice';
                                        // Clean up
                                        unset($mappedMetadata['stem']);
                                    }
                                }
                                
                                // Resolve audio path: prefer audio_path column, fallback to metadata.audio
                                $audioPath = $qData['audio_path'] ?? ($mappedMetadata['audio'] ?? null);
                                if ($audioPath === '') $audioPath = null;

                                // Resolve explanation/transcript
                                $explanation = $qData['explanation'] ?? null;
                                if (empty($explanation)) {
                                    // Part 1/3: use description as transcript
                                    $explanation = $mappedMetadata['description'] ?? null;
                                }
                                if (empty($explanation) && $part == 4) {
                                    // Part 4: use first question's text field
                                    $explanation = $mappedMetadata['questions'][0]['text'] ?? null;
                                }

                                $question = Question::updateOrCreate(
                                    ['id' => $qData['id']],
                                    [
                                        'quiz_id'        => $quiz->id,
                                        // title CANNOT be null in DB — fallback to stem, then empty string
                                        'title'          => $qData['title'] ?? ($qData['metadata']['title'] ?? ($qData['stem'] ?? '')),
                                        'reading_set_id' => $qData['reading_set_id'] ?? null,
                                        'stem'           => $qData['stem'] ?? null,
                                        'explanation'    => $explanation,
                                        'skill'          => $qData['skill'] ?? 'listening',
                                        'part'           => $part,
                                        'type'           => $type,
                                        'order'          => $qData['order'] ?? 0,
                                        'point'          => $qData['point'] ?? 1,
                                        'audio_path'     => $audioPath,
                                        'image_path'     => $qData['image_path'] ?? null,
                                        'metadata'       => $mappedMetadata,
                                    ]
                                );
                                
                                // Attach question to set
                                $set->questions()->syncWithoutDetaching([$question->id]);
                            }
                        }
                    }
                }
            }
            DB::commit();
            $this->info("Successfully imported {$filePath}");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing: " . $e->getMessage());
            return 1;
        }
    }
}
