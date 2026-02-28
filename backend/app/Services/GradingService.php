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

        // Writing & Speaking are manually graded in mock_test mode
        if ($skill === 'writing' || $skill === 'speaking') {
            return [
                'score'          => 0,
                'is_correct'     => null,
                'grading_status' => ($mode === 'mock_test') ? 'pending' : 'graded',
            ];
        }

        // Grammar: MCQ (part=1) or Vocab dropdown (part=2)
        if ($skill === 'grammar') {
            return $this->gradeGrammarQuestion($question, $userAnswer);
        }

        // Auto-grade Reading & Listening
        $calculatedScore = $this->calculateScore($question, $userAnswer);
        $isCorrect = abs($calculatedScore - $question->point) < 0.01;

        return [
            'score'          => $calculatedScore,
            'is_correct'     => $isCorrect,
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
        $totalPossible  = $questions->sum('point');
        $totalEarned    = 0;

        foreach ($questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;

            if ($userAnswer !== null && $userAnswer !== '' && $userAnswer !== []) {
                $result = $this->gradeQuestion($question, $userAnswer, $mode);
            } else {
                $result = [
                    'score'          => 0,
                    'is_correct'     => in_array($question->skill, ['writing', 'speaking']) ? null : false,
                    'grading_status' => in_array($question->skill, ['writing', 'speaking']) ? 'pending' : null,
                ];
            }

            $totalEarned += $result['score'];

            $attemptAnswers[] = [
                'question_id'    => $question->id,
                'answer'         => $userAnswer ?? '',
                'is_correct'     => $result['is_correct'],
                'score'          => $result['score'],
                'feedback'       => null,
                'grading_status' => $result['grading_status'],
                'ai_metadata'    => null,
            ];
        }

        $percentage = ($totalPossible > 0) ? ($totalEarned / $totalPossible) * 100 : 0;

        return [
            'attempt_answers' => $attemptAnswers,
            'total_earned'    => $totalEarned,
            'total_possible'  => $totalPossible,
            'percentage'      => $percentage,
        ];
    }

    // ─── Grammar Grading ─────────────────────────────────────────────────────

    /**
     * Dispatch to correct grammar grader based on part.
     */
    private function gradeGrammarQuestion(Question $question, $userAnswer): array
    {
        return match ($question->part) {
            1       => $this->gradeGrammarMcq($question, $userAnswer),
            2       => $this->gradeVocabQuestion($question, $userAnswer),
            default => ['score' => 0, 'is_correct' => false, 'grading_status' => null],
        };
    }

    /**
     * Grade Grammar MCQ (Part 1): 3-option A/B/C question.
     */
    private function gradeGrammarMcq(Question $question, $userAnswer): array
    {
        $correctOption = $question->metadata['correct_option'] ?? null;
        $isCorrect     = ($correctOption !== null && (string) $userAnswer === (string) $correctOption);

        return [
            'score'          => $isCorrect ? (float) $question->point : 0.0,
            'is_correct'     => $isCorrect,
            'grading_status' => null,
        ];
    }

    /**
     * Grade Vocabulary dropdown (Part 2).
     * Types: synonym_match, definition_match, sentence_completion, collocation_match
     *
     * $userAnswer: {"1": "learn", "2": "get", ...}
     *
     * Rules:
     *  - Dynamic point-per-item (NOT hardcoded)
     *  - Backend duplicate validation: duplicate choices score 0
     */
    private function gradeVocabQuestion(Question $question, $userAnswer): array
    {
        $metadata       = $question->metadata ?? [];
        $correctAnswers = $metadata['correct_answers'] ?? [];
        $totalItems     = count($correctAnswers);

        if ($totalItems === 0 || !is_array($userAnswer)) {
            return ['score' => 0, 'is_correct' => false, 'grading_status' => null];
        }

        $pointPerItem = $question->point / $totalItems; // Dynamic — NOT hardcoded to 5

        // Backend duplicate validation
        $valueCounts     = array_count_values(array_values(array_filter($userAnswer, fn($v) => $v !== null && $v !== '')));
        $duplicateValues = array_keys(array_filter($valueCounts, fn($c) => $c > 1));

        $earned = 0;
        foreach ($correctAnswers as $pairId => $correctWord) {
            $chosen = $userAnswer[(string) $pairId] ?? null;

            if ($chosen === null || $chosen === '') {
                continue; // Not answered
            }

            // Duplicate answer → 0 for this sub-item
            if (in_array($chosen, $duplicateValues)) {
                continue;
            }

            if ((string) $chosen === (string) $correctWord) {
                $earned += $pointPerItem;
            }
        }

        $earned    = round($earned, 2);
        $isCorrect = abs($earned - (float) $question->point) < 0.01;

        return [
            'score'          => $earned,
            'is_correct'     => $isCorrect,
            'grading_status' => null,
        ];
    }

    // ─── Reading / Listening Grading ─────────────────────────────────────────

    /**
     * Calculate score for a reading/listening question.
     */
    private function calculateScore(Question $question, $userAnswer): float
    {
        $metadata  = $question->metadata;
        $skill     = $question->skill;
        $part      = $question->part;
        $maxPoints = $question->point;

        if ($skill === 'reading') {
            switch ($part) {
                case 1: // Gap Fill
                case 3: // Matching
                case 4: // Headings
                    $correctAnswers = $metadata['correct_answers'] ?? [];
                    $totalItems     = count($correctAnswers);

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
                    $totalItems     = count($correctAnswers);

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
