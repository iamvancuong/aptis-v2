<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end guard: the rendered practice page must not contain answer keys.
 *
 * Blocking DevTools or copy/paste cannot help here — the page source is handed
 * to the browser in full, so the only real defence is not sending the answers.
 */
class PracticeAnswerLeakTest extends TestCase
{
    use RefreshDatabase;

    private function learner(): User
    {
        return User::create([
            'name'            => 'Learner',
            'email'           => 'learner@example.test',
            'password'        => bcrypt('password'),
            'role'            => 'user',
            'status'          => 'active',
            'max_devices'     => 2,
            'violation_count' => 0,
        ]);
    }

    private function setWithQuestion(string $skill, int $part, array $metadata): Set
    {
        $quiz = Quiz::create([
            'title'            => 'Test quiz',
            'skill'            => $skill,
            'part'             => $part,
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        $set = Set::create([
            'quiz_id'      => $quiz->id,
            'title'        => 'Test set',
            'status'       => 'published',
            'order'        => 1,
            'is_public'    => true,
            'max_attempts' => 3,
        ]);

        $question = Question::create([
            'quiz_id'  => $quiz->id,
            'skill'    => $skill,
            'part'     => $part,
            'type'     => 'mcq',
            'title'    => 'Q',
            'stem'     => 'Stem',
            'point'    => 10,
            'order'    => 1,
            'metadata' => $metadata,
        ]);

        $set->questions()->attach($question->id);

        return $set;
    }

    /**
     * Blade's `@js()` encodes with JSON_HEX_QUOT, so a JSON key lands in the
     * page as `"correct_answers"`, not as `&quot;...&quot;`. Matching
     * that exact form is what separates the embedded *data* from the Alpine
     * *source* in the same page, which legitimately mentions
     * `metadata.correct_answers` as code.
     *
     * The positive assertion matters as much as the negative one: without it a
     * typo in the needle would make this test pass while answers leak.
     */
    /** How a double quote appears once `@js()` has encoded the payload. */
    private const Q = '\\u0022';

    private function assertPayloadKeyIsBlank(string $key, string $blankJson, string $realJson, string $html): void
    {
        $this->assertStringContainsString(
            self::Q . $key . self::Q . ':' . $blankJson,
            $html,
            "`{$key}` should still be present, but blank, so the templates keep working."
        );

        $this->assertStringNotContainsString(
            self::Q . $key . self::Q . ':' . $realJson,
            $html,
            "The real value of `{$key}` is still being shipped to the browser."
        );
    }

    public function test_reading_page_does_not_ship_the_answer_key(): void
    {
        $set = $this->setWithQuestion('reading', 1, [
            'paragraphs'      => ['The cat ___ on the mat.'],
            'choices'         => [['sat', 'sit', 'sitting']],
            'correct_answers' => [0],
            'explanation'     => 'past simple',
        ]);

        $html = $this->actingAs($this->learner())
            ->get(route('practice.show', $set))
            ->assertOk()
            ->getContent();

        $this->assertPayloadKeyIsBlank('correct_answers', '[]', '[0]', $html);
        $this->assertPayloadKeyIsBlank('explanation', 'null', self::Q . 'past simple' . self::Q, $html);
        $this->assertStringContainsString('The cat', $html, 'the passage itself must still render');
    }

    public function test_listening_page_does_not_ship_the_answer_key(): void
    {
        $set = $this->setWithQuestion('listening', 1, [
            'choices'        => ['Monday', 'Tuesday', 'Friday'],
            'description'    => 'A voicemail',
            'correct_answer' => 2,
        ]);

        $html = $this->actingAs($this->learner())
            ->get(route('practice.show', $set))
            ->assertOk()
            ->getContent();

        $this->assertPayloadKeyIsBlank('correct_answer', 'null', '2', $html);
        $this->assertStringContainsString('Monday', $html);
    }

    public function test_writing_page_does_not_ship_the_sample_answer(): void
    {
        $set = $this->setWithQuestion('writing', 2, [
            'scenario'      => 'Describe your home town.',
            'word_limit'    => ['min' => 20, 'max' => 30],
            'sample_answer' => 'SECRETMODELANSWER',
        ]);

        $html = $this->actingAs($this->learner())
            ->get(route('practice.show', $set))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('SECRETMODELANSWER', $html);
        $this->assertStringContainsString('Describe your home town', $html);
    }

    /** The check endpoint releases the key for one question, on request. */
    public function test_check_endpoint_releases_the_key_for_a_single_question(): void
    {
        $set      = $this->setWithQuestion('reading', 1, [
            'paragraphs'      => ['The cat ___ on the mat.'],
            'choices'         => [['sat', 'sit', 'sitting']],
            'correct_answers' => [0],
        ]);
        $question = $set->questions()->first();

        $this->actingAs($this->learner())
            ->postJson(route('practice.check', $set), ['question_id' => $question->id])
            ->assertOk()
            ->assertJsonPath('answer_key.correct_answers', [0]);
    }

    /**
     * The explanation/transcript lives in a top-level `explanation` COLUMN
     * (production shape), not in metadata. It must be hidden on initial load
     * (it spells out the answer) yet released by the check endpoint afterwards,
     * so the feedback panel can show it. This is the Reading Part 3 bug fix.
     */
    public function test_check_endpoint_releases_the_explanation_column(): void
    {
        $set = $this->setWithQuestion('reading', 3, [
            'options'         => ['A text', 'B text', 'C text', 'D text'],
            'questions'       => ['Who mentions the weather?'],
            'correct_answers' => [2],
        ]);
        $question = $set->questions()->first();
        $question->update(['explanation' => 'SECRET_EXPLANATION_TEXT']);

        $learner = $this->learner();

        // Not shipped on the initial page load…
        $html = $this->actingAs($learner)
            ->get(route('practice.show', $set))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('SECRET_EXPLANATION_TEXT', $html);

        // …but released by the check endpoint once the learner asks for it.
        $this->actingAs($learner)
            ->postJson(route('practice.check', $set), ['question_id' => $question->id])
            ->assertOk()
            ->assertJsonPath('answer_key.explanation', 'SECRET_EXPLANATION_TEXT');
    }

    /** A question outside this set cannot be used to fish for answers. */
    public function test_check_endpoint_rejects_a_question_from_another_set(): void
    {
        // `quizzes` is unique on (skill, part), so these must differ.
        $setA = $this->setWithQuestion('reading', 1, ['correct_answers' => [0]]);
        $setB = $this->setWithQuestion('reading', 3, ['correct_answers' => [1]]);

        $foreign = $setB->questions()->first();

        $this->actingAs($this->learner())
            ->postJson(route('practice.check', $setA), ['question_id' => $foreign->id])
            ->assertNotFound();
    }

    /** Signed-out visitors get nothing. */
    public function test_check_endpoint_requires_authentication(): void
    {
        $set      = $this->setWithQuestion('reading', 1, ['correct_answers' => [0]]);
        $question = $set->questions()->first();

        $this->postJson(route('practice.check', $set), ['question_id' => $question->id])
            ->assertUnauthorized();
    }
}
