<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\QuestionSanitizer;
use Tests\TestCase;

/**
 * Every question shape in the bank, checked to make sure no answer key
 * survives into the payload handed to the browser.
 */
class QuestionSanitizerTest extends TestCase
{
    private QuestionSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new QuestionSanitizer();
    }

    private function make(string $skill, int $part, array $metadata): Question
    {
        $question = new Question();
        $question->skill    = $skill;
        $question->part     = $part;
        $question->point    = 10;
        $question->metadata = $metadata;

        return $question;
    }

    public function test_reading_part_1_drops_answers_but_keeps_the_passage(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('reading', 1, [
            'paragraphs'      => ['A ___ b', 'C ___ d'],
            'choices'         => [['x', 'y', 'z'], ['p', 'q', 'r']],
            'correct_answers' => [0, 2],
            'explanation'     => 'because ...',
        ]));

        // Blanked, not removed — the templates read these fields eagerly.
        $this->assertSame([], $clean['correct_answers']);
        $this->assertNull($clean['explanation']);
        $this->assertCount(2, $clean['paragraphs']);
        $this->assertCount(2, $clean['choices']);
    }

    public function test_listening_part_1_drops_the_singular_answer_key(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('listening', 1, [
            'choices'        => ['a', 'b', 'c'],
            'description'    => 'A short talk',
            'correct_answer' => 2,
        ]));

        $this->assertNull($clean['correct_answer']);
        $this->assertSame(['a', 'b', 'c'], $clean['choices']);
    }

    public function test_grammar_part_1_drops_correct_option(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('grammar', 1, [
            'options'        => [['id' => 'A', 'text' => 'go'], ['id' => 'B', 'text' => 'went']],
            'correct_option' => 'B',
        ]));

        $this->assertNull($clean['correct_option']);
        $this->assertCount(2, $clean['options']);
    }

    public function test_grammar_part_2_drops_the_answer_map_but_keeps_the_pool(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('grammar', 2, [
            'pairs'           => [['id' => 1, 'prompt' => 'study']],
            'dropdown_pool'   => ['learn', 'get', 'take'],
            'correct_answers' => ['1' => 'learn'],
        ]));

        $this->assertSame([], $clean['correct_answers']);
        $this->assertSame(['learn', 'get', 'take'], $clean['dropdown_pool']);
    }

    public function test_writing_part_4_drops_nested_sample_answers(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('writing', 4, [
            'context' => 'You are a member of a club.',
            'task1'   => ['instruction' => 'Write an email', 'sample_answer' => 'Dear Sir ...'],
            'task2'   => ['instruction' => 'Write a report', 'sample_answer' => 'To whom ...'],
        ]));

        $this->assertNull($clean['task1']['sample_answer']);
        $this->assertNull($clean['task2']['sample_answer']);
        $this->assertSame('Write an email', $clean['task1']['instruction']);
        $this->assertSame('Write a report', $clean['task2']['instruction']);
    }

    public function test_writing_part_2_drops_the_top_level_sample_answer(): void
    {
        $clean = $this->sanitizer->metadataForClient($this->make('writing', 2, [
            'scenario'      => 'Describe your town',
            'word_limit'    => ['min' => 20, 'max' => 30],
            'sample_answer' => 'My town is ...',
        ]));

        $this->assertNull($clean['sample_answer']);
        $this->assertSame('Describe your town', $clean['scenario']);
    }

    /**
     * Reading Part 2 has no answer-key field: the stored order of `sentences`
     * IS the answer, so it must reach the browser shuffled.
     */
    public function test_reading_part_2_sentences_are_shuffled_away_from_stored_order(): void
    {
        $sentences = [
            'Opening sentence.',
            'First step.',
            'Second step.',
            'Third step.',
            'Fourth step.',
            'Fifth step.',
        ];

        $question     = $this->make('reading', 2, ['sentences' => $sentences]);
        $question->id = 4242;

        $clean = $this->sanitizer->metadataForClient($question);

        $this->assertSame($sentences[0], $clean['sentences'][0], 'opening sentence stays pinned');
        $this->assertNotSame($sentences, $clean['sentences'], 'the answer order must not survive');
        $this->assertEqualsCanonicalizing($sentences, $clean['sentences'], 'no sentence may be lost');
    }

    /** The same learner must see a stable order across reloads. */
    public function test_reading_part_2_shuffle_is_stable_for_the_same_question(): void
    {
        $question     = $this->make('reading', 2, ['sentences' => ['O.', 'a', 'b', 'c', 'd', 'e']]);
        $question->id = 99;

        $first  = $this->sanitizer->metadataForClient($question);
        $second = $this->sanitizer->metadataForClient($question);

        $this->assertSame($first['sentences'], $second['sentences']);
    }

    /** The answer key released on check must carry the true stored order. */
    public function test_answer_key_returns_the_true_order_for_reading_part_2(): void
    {
        $sentences = ['Opening.', 'one', 'two', 'three', 'four', 'five'];
        $question  = $this->make('reading', 2, ['sentences' => $sentences]);

        $this->assertSame($sentences, $this->sanitizer->answerKeyFor($question)['sentences']);
    }

    public function test_question_for_client_carries_no_answer_key_anywhere(): void
    {
        $question = $this->make('reading', 3, [
            'options'         => ['A', 'B', 'C', 'D'],
            'questions'       => ['q1', 'q2'],
            'correct_answers' => [1, 3],
        ]);
        $question->id = 7;

        $clean = $this->sanitizer->questionForClient($question);

        $this->assertSame([], $clean['metadata']['correct_answers']);
        $this->assertStringContainsString('q1', json_encode($clean));
    }
}
