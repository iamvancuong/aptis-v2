<?php

namespace App\Services;

use App\Models\Question;

class GradingService
{
    /**
     * Grade a single question based on skill/part logic.
     *
     * @return array{score: float, is_correct: bool|null, grading_status: string|null}
     */
    public function gradeQuestion(Question $question, $userAnswer, string $mode = 'practice'): array
    {
        $skill = $question->skill;

        // Writing is always manually graded in mock_test mode, self-check in practice
        if ($skill === 'writing') {
            return [
                'score' => 0,
                'is_correct' => null,
                'grading_status' => ($mode === 'mock_test') ? 'pending' : 'graded',
            ];
        }

        // Auto-grade Reading & Listening
        $calculatedScore = $this->calculateScore($question, $userAnswer);
        $isCorrect = abs($calculatedScore - $question->point) < 0.01;

        return [
            'score' => $calculatedScore,
            'is_correct' => $isCorrect,
            'grading_status' => null,
        ];
    }

    /**
     * Grade all questions in a set.
     *
     * @return array{attempt_answers: array, total_earned: float, total_possible: float, percentage: float}
     */
    public function gradeSet($questions, array $answers, string $mode = 'practice'): array
    {
        $attemptAnswers = [];
        $totalPossible = $questions->sum('point');
        $totalEarned = 0;
        
        $isWritingMock = ($questions->first()?->skill === 'writing' && $mode === 'mock_test');
        $aiService = $isWritingMock ? app(\App\Services\AiService::class) : null;

        foreach ($questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;

            if ($userAnswer) {
                $result = $this->gradeQuestion($question, $userAnswer, $mode);
            } else {
                $result = [
                    'score' => 0,
                    'is_correct' => false,
                    'grading_status' => ($question->skill === 'writing' && $mode === 'mock_test') ? 'pending' : null,
                ];
            }
            
            $aiMetadata = null;
            if ($aiService && $question->skill === 'writing') {
                try {
                    // Only call OpenAI if there's an actual answer to grade
                    if (!empty($userAnswer)) {
                        $aiMetadata = $aiService->gradeWriting([
                            'part' => $question->part,
                            'word_limit' => $question->metadata['word_limit'] ?? null,
                            'question_stem' => $question->stem,
                            'student_answer' => $userAnswer,
                        ]);
                        $result['grading_status'] = 'ai_graded';
                    } else {
                        // Empty answer -> instant 0
                        $aiMetadata = [
                            'feedback' => [
                                'schema_version' => 3,
                                'part' => $question->part,
                                'overall_score' => 0,
                                'scores' => ['grammar' => 0, 'vocabulary' => 0, 'coherence' => 0, 'task_fulfillment' => 0],
                                'feedback' => ['task_fulfillment' => 'No answer provided.']
                            ]
                        ];
                        $result['grading_status'] = 'ai_graded';
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Auto AI Grading failed: ' . $e->getMessage());
                    // Will remain 'pending'
                }
            }

            $totalEarned += $result['score'];

            $attemptAnswers[] = [
                'question_id' => $question->id,
                'answer' => $userAnswer ?? "",
                'is_correct' => $result['is_correct'],
                'score' => $result['score'],
                'feedback' => null,
                'grading_status' => $result['grading_status'],
                'ai_metadata' => $aiMetadata,
            ];
        }

        $percentage = ($totalPossible > 0) ? ($totalEarned / $totalPossible) * 100 : 0;

        return [
            'attempt_answers' => $attemptAnswers,
            'total_earned' => $totalEarned,
            'total_possible' => $totalPossible,
            'percentage' => $percentage,
        ];
    }

    /**
     * Calculate score for a reading/listening question.
     * Extracted from PracticeController::calculateQuestionScore()
     */
    private function calculateScore(Question $question, $userAnswer): float
    {
        $metadata = $question->metadata;
        $skill = $question->skill;
        $part = $question->part;
        $maxPoints = $question->point;

        if ($skill === 'reading') {
            switch ($part) {
                case 1: // Gap Fill
                case 3: // Matching
                case 4: // Headings
                    $correctAnswers = $metadata['correct_answers'] ?? [];
                    $totalItems = count($correctAnswers);

                    if ($totalItems === 0) return 0;
                    if (!is_array($userAnswer)) return 0;

                    $correctCount = 0;
                    foreach ($correctAnswers as $idx => $correct) {
                        if (isset($userAnswer[$idx]) && $userAnswer[$idx] == $correct) {
                            $correctCount++;
                        }
                    }

                    return ($correctCount / $totalItems) * $maxPoints;

                case 2: // Ordering
                    if (!is_array($userAnswer)) return 0;

                    foreach ($userAnswer as $idx => $item) {
                        if (!isset($item['originalIndex']) || $item['originalIndex'] !== ($idx + 1)) {
                            return 0;
                        }
                    }
                    return $maxPoints;

                default:
                    return 0;
            }
        }

        if ($skill === 'listening') {
            switch ($part) {
                case 1: // Single MCQ
                    $correctAnswer = $metadata['correct_answer'] ?? null;
                    if ($correctAnswer !== null && $userAnswer == $correctAnswer) {
                        return $maxPoints;
                    }
                    return 0;

                case 2: // Speaker Matching
                case 3: // Shared Dropdown
                case 4: // Complex Audio MCQ
                    $correctAnswers = $metadata['correct_answers'] ?? [];
                    $totalItems = count($correctAnswers);

                    if ($totalItems === 0) return 0;
                    if (!is_array($userAnswer)) return 0;

                    $correctCount = 0;
                    foreach ($correctAnswers as $idx => $correct) {
                        if (isset($userAnswer[$idx]) && $userAnswer[$idx] == $correct) {
                            $correctCount++;
                        }
                    }

                    return ($correctCount / $totalItems) * $maxPoints;

                default:
                    return 0;
            }
        }

        return 0;
    }
}
