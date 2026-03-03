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
                                'quiz_id' => $quiz->id,
                                'title' => $setData['title'],
                                'skill' => $setData['skill'],
                                'description' => $setData['description'],
                                'is_public' => $setData['is_public'],
                                'order' => $setData['order'],
                                'metadata' => $setData['metadata'],
                            ]
                        );

                        if (isset($setData['questions'])) {
                            foreach ($setData['questions'] as $qData) {
                                $mappedMetadata = $qData['metadata'] ?? [];
                                $part = $qData['part'];
                                
                                if (($qData['skill'] ?? 'listening') === 'listening') {
                                    if ($part == 1) {
                                        if (isset($mappedMetadata['options'])) {
                                            $mappedMetadata['choices'] = $mappedMetadata['options'];
                                        }
                                        if (isset($mappedMetadata['correct_index'])) {
                                            $mappedMetadata['correct_answer'] = (int) $mappedMetadata['correct_index'];
                                        }
                                    } elseif ($part == 2) {
                                        if (isset($mappedMetadata['options'])) {
                                            $mappedMetadata['choices'] = $mappedMetadata['options'];
                                        }
                                        if (isset($mappedMetadata['answers'])) {
                                            $mappedMetadata['correct_answers'] = array_map('intval', $mappedMetadata['answers']);
                                        }
                                        if (isset($mappedMetadata['speakers'])) {
                                            $items = [];
                                            $audioFiles = [];
                                            foreach ($mappedMetadata['speakers'] as $speaker) {
                                                $items[] = $speaker['label'] ?? '';
                                                $audioFiles[] = $speaker['audio'] ?? null;
                                            }
                                            $mappedMetadata['items'] = $items;
                                            $mappedMetadata['audio_files'] = $audioFiles;
                                        }
                                    } elseif ($part == 3) {
                                        if (isset($mappedMetadata['title']) || isset($mappedMetadata['stem'])) {
                                            $mappedMetadata['topic'] = $mappedMetadata['title'] ?? $mappedMetadata['stem'];
                                        }
                                        if (isset($mappedMetadata['options'])) {
                                            $mappedMetadata['shared_choices'] = $mappedMetadata['options'];
                                        }
                                        if (isset($mappedMetadata['items'])) {
                                            $mappedMetadata['statements'] = $mappedMetadata['items'];
                                        }
                                        if (isset($mappedMetadata['answers'])) {
                                            $mappedMetadata['correct_answers'] = array_map('intval', $mappedMetadata['answers']);
                                        }
                                    } elseif ($part == 4) {
                                        if (isset($mappedMetadata['stem'])) {
                                            $mappedMetadata['topic'] = $mappedMetadata['stem'];
                                        }
                                        if (isset($mappedMetadata['questions'])) {
                                            $newQuestions = [];
                                            $correctAnswers = [];
                                            foreach ($mappedMetadata['questions'] as $q) {
                                                $newQuestions[] = [
                                                    'question' => $q['stem'] ?? '',
                                                    'choices' => $q['options'] ?? []
                                                ];
                                                $correctAnswers[] = isset($q['correct_index']) ? (int) $q['correct_index'] : 0;
                                            }
                                            $mappedMetadata['questions'] = $newQuestions;
                                            $mappedMetadata['correct_answers'] = $correctAnswers;
                                        }
                                    }
                                }
                                
                                $audioPath = $qData['audio_path'] ?? ($mappedMetadata['audio'] ?? null);
                                // Fallback transcript mapping
                                $explanation = $qData['explanation'] ?? ($mappedMetadata['description'] ?? null);
                                if (empty($explanation) && $part == 4 && !empty($mappedMetadata['questions'][0]['text'] ?? '')) {
                                    $explanation = $mappedMetadata['questions'][0]['text'];
                                }

                                $question = Question::updateOrCreate(
                                    ['id' => $qData['id']],
                                    [
                                        'quiz_id' => $quiz->id,
                                        'title' => $qData['title'] ?? ($qData['metadata']['title'] ?? ($qData['stem'] ?? null)),
                                        'reading_set_id' => $qData['reading_set_id'],
                                        'stem' => $qData['stem'],
                                        'explanation' => $explanation,
                                        'skill' => $qData['skill'],
                                        'part' => $part,
                                        'type' => $qData['type'],
                                        'order' => $qData['order'] ?? 0,
                                        'point' => $qData['point'] ?? 1,
                                        'audio_path' => $audioPath,
                                        'image_path' => $qData['image_path'] ?? null,
                                        'metadata' => $mappedMetadata,
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
