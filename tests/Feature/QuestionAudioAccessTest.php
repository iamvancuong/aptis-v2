<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuestionSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Listening audio must not be fetchable by anyone holding a guessed path.
 */
class QuestionAudioAccessTest extends TestCase
{
    use RefreshDatabase;

    private function learner(): User
    {
        return User::create([
            'name'            => 'Learner',
            'email'           => 'audio-learner@example.test',
            'password'        => bcrypt('password'),
            'role'            => 'user',
            'status'          => 'active',
            'max_devices'     => 2,
            'violation_count' => 0,
        ]);
    }

    private function question(): Question
    {
        $quiz = Quiz::create([
            'title'            => 'Listening quiz',
            'skill'            => 'listening',
            'part'             => 1,
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        return Question::create([
            'quiz_id'    => $quiz->id,
            'skill'      => 'listening',
            'part'       => 1,
            'type'       => 'mcq',
            'title'      => 'Q',
            'stem'       => 'Listen',
            'point'      => 10,
            'order'      => 1,
            'audio_path' => 'audio/clip.mp3',
            'metadata'   => ['choices' => ['a', 'b', 'c']],
        ]);
    }

    public function test_signed_url_lets_a_signed_in_learner_play_the_clip(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/clip.mp3', 'fake-audio-bytes');

        $question = $this->question();
        $url      = app(QuestionSanitizer::class)->audioUrl($question);

        $this->assertNotNull($url);
        $this->assertStringContainsString('signature=', $url);

        $this->actingAs($this->learner())->get($url)->assertOk();
    }

    public function test_unsigned_url_is_rejected(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/clip.mp3', 'fake-audio-bytes');

        $question = $this->question();

        $this->actingAs($this->learner())
            ->get(route('media.question-audio', ['question' => $question->id]))
            ->assertForbidden();
    }

    public function test_guests_cannot_play_the_clip_even_with_a_valid_signature(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/clip.mp3', 'fake-audio-bytes');

        $question = $this->question();
        $url      = app(QuestionSanitizer::class)->audioUrl($question);

        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_missing_file_returns_not_found(): void
    {
        Storage::fake('public');

        $question = $this->question();
        $url      = app(QuestionSanitizer::class)->audioUrl($question);

        $this->actingAs($this->learner())->get($url)->assertNotFound();
    }

    /** The raw storage path must no longer be what the player points at. */
    public function test_client_payload_uses_the_signed_route_not_a_public_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio/clip.mp3', 'fake-audio-bytes');

        $payload = app(QuestionSanitizer::class)->questionForClient($this->question());

        $this->assertStringContainsString('/media/questions/', $payload['audio_url']);
        $this->assertStringNotContainsString('/storage/', $payload['audio_url']);
    }
}
