<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hàng đợi "Chờ chấm" Writing: mọi bài đã yêu cầu chấm mà CHƯA được giáo viên
 * chốt điểm ('graded') đều phải hiện — kể cả 'limit_reached' (học viên hết lượt
 * AI), vốn là nhóm hay trả phí nhờ giáo viên chấm. Trước đây bị lọt.
 */
class WritingReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role . '@example.test', 'password' => bcrypt('x'),
            'role' => $role, 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function writingQuestion(): Question
    {
        $quiz = Quiz::create([
            'title' => 'WQ', 'skill' => 'writing', 'part' => 1,
            'duration_minutes' => 30, 'is_published' => true,
        ]);
        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'WSet', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        return Question::create([
            'quiz_id' => $quiz->id, 'skill' => 'writing', 'part' => 1, 'type' => 'essay',
            'title' => 'Q', 'stem' => 'Stem', 'point' => 10, 'order' => 1, 'metadata' => [],
        ]);
    }

    private function requestedAttempt(User $student, string $status): Attempt
    {
        $question = $this->writingQuestion();
        $attempt  = Attempt::create([
            'user_id' => $student->id, 'skill' => 'writing', 'mode' => 'mock',
            'set_id' => $question->quiz->sets()->first()->id,
            'is_grading_requested' => true, 'grading_requested_at' => now(),
            'started_at' => now()->subMinutes(30), 'finished_at' => now(),
        ]);
        AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => ['text' => 'bài làm'], 'grading_status' => $status,
        ]);

        return $attempt;
    }

    public function test_limit_reached_attempt_appears_in_pending_queue(): void
    {
        $admin   = $this->user('admin');
        $student = $this->user('user');
        $this->requestedAttempt($student, 'limit_reached');

        // Tab mặc định "Chờ chấm" phải liệt kê bài này.
        $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index'))
            ->assertOk()
            ->assertSee($student->email);

        // Tab "Đã chấm" KHÔNG được liệt kê (chưa giáo viên chốt).
        $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index', ['filter' => 'graded']))
            ->assertOk()
            ->assertDontSee($student->email);
    }

    public function test_graded_attempt_is_in_graded_tab_not_pending(): void
    {
        $admin   = $this->user('admin');
        $student = $this->user('user');
        $this->requestedAttempt($student, 'graded');

        $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index'))
            ->assertOk()
            ->assertDontSee($student->email);

        $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index', ['filter' => 'graded']))
            ->assertOk()
            ->assertSee($student->email);
    }
}
