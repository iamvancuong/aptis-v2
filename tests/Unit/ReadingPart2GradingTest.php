<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\GradingService;
use PHPUnit\Framework\TestCase;

/**
 * Reading Part 2 is a sentence-ordering task. Scoring used to rely on an
 * `originalIndex` the browser sent back with each sentence, which meant the
 * client decided its own score. These tests pin the behaviour to the stored
 * sentence order instead.
 */
class ReadingPart2GradingTest extends TestCase
{
    private GradingService $grading;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grading = new GradingService();
    }

    private function question(): Question
    {
        $question = new Question();
        $question->skill = 'reading';
        $question->part  = 2;
        $question->point = 10;
        $question->metadata = [
            'sentences' => [
                'I moved to Hanoi last year.', // fixed opening sentence
                'At first I found the traffic overwhelming.',
                'After a few weeks I bought a motorbike.',
                'Now I ride to work every morning.',
                'I cannot imagine living anywhere else.',
            ],
        ];

        return $question;
    }

    /** The correct order, submitted as the UI sends it, scores full marks. */
    public function test_correct_order_scores_full_marks(): void
    {
        $answer = [
            ['text' => 'At first I found the traffic overwhelming.', 'originalIndex' => 1],
            ['text' => 'After a few weeks I bought a motorbike.', 'originalIndex' => 2],
            ['text' => 'Now I ride to work every morning.', 'originalIndex' => 3],
            ['text' => 'I cannot imagine living anywhere else.', 'originalIndex' => 4],
        ];

        $result = $this->grading->gradeQuestion($this->question(), $answer);

        $this->assertSame(10.0, (float) $result['score']);
        $this->assertTrue($result['is_correct']);
    }

    /**
     * The exploit: a sequential `originalIndex` with the sentences in the wrong
     * order. This used to score full marks without the learner reading anything.
     */
    public function test_forged_sequential_index_with_wrong_text_scores_zero(): void
    {
        $answer = [
            ['text' => 'I cannot imagine living anywhere else.', 'originalIndex' => 1],
            ['text' => 'Now I ride to work every morning.', 'originalIndex' => 2],
            ['text' => 'After a few weeks I bought a motorbike.', 'originalIndex' => 3],
            ['text' => 'At first I found the traffic overwhelming.', 'originalIndex' => 4],
        ];

        $result = $this->grading->gradeQuestion($this->question(), $answer);

        $this->assertSame(0.0, (float) $result['score']);
        $this->assertFalse($result['is_correct']);
    }

    /** Indices alone, with no sentences, cannot earn a score. */
    public function test_index_only_payload_scores_zero(): void
    {
        $answer = [
            ['originalIndex' => 1],
            ['originalIndex' => 2],
            ['originalIndex' => 3],
            ['originalIndex' => 4],
        ];

        $result = $this->grading->gradeQuestion($this->question(), $answer);

        $this->assertSame(0.0, (float) $result['score']);
    }

    /** A genuine wrong ordering still scores zero. */
    public function test_wrong_order_scores_zero(): void
    {
        $answer = [
            ['text' => 'At first I found the traffic overwhelming.', 'originalIndex' => 1],
            ['text' => 'Now I ride to work every morning.', 'originalIndex' => 2],
            ['text' => 'After a few weeks I bought a motorbike.', 'originalIndex' => 3],
            ['text' => 'I cannot imagine living anywhere else.', 'originalIndex' => 4],
        ];

        $result = $this->grading->gradeQuestion($this->question(), $answer);

        $this->assertSame(0.0, (float) $result['score']);
    }

    /** A short submission cannot pass by matching only its first entries. */
    public function test_incomplete_submission_scores_zero(): void
    {
        $answer = [
            ['text' => 'At first I found the traffic overwhelming.', 'originalIndex' => 1],
        ];

        $result = $this->grading->gradeQuestion($this->question(), $answer);

        $this->assertSame(0.0, (float) $result['score']);
    }
}
