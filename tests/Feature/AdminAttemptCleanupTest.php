<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Models\WritingReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cô minh hoạ đề Writing/Speaking trên lớp mỗi tuần nên bài của admin dồn lại.
 * Admin xoá được bài của CHÍNH MÌNH ở Lịch sử; học viên thì không.
 */
class AdminAttemptCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role . random_int(1, 99999) . '@example.test', 'password' => bcrypt('x'),
            'role' => $role, 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function attempt(User $user, string $skill, array $answer = ['text' => 'bài']): Attempt
    {
        $quiz = Quiz::create(['title' => 'Q', 'skill' => $skill, 'part' => 1, 'duration_minutes' => 30, 'is_published' => true]);
        $set = Set::create(['quiz_id' => $quiz->id, 'title' => 'S', 'status' => 'published', 'order' => 1, 'is_public' => true, 'max_attempts' => 3]);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'skill' => $skill, 'part' => 1, 'type' => 'essay',
            'title' => 'Q', 'stem' => 'Stem', 'point' => 10, 'order' => 1, 'metadata' => [],
        ]);
        $attempt = Attempt::create([
            'user_id' => $user->id, 'skill' => $skill, 'mode' => 'practice', 'set_id' => $set->id,
            'started_at' => now()->subMinutes(5), 'finished_at' => now(), 'score' => 50,
        ]);
        AttemptAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id, 'answer' => $answer, 'grading_status' => 'ai_graded']);

        return $attempt;
    }

    public function test_admin_xoa_mot_bai_writing_kem_nhan_xet(): void
    {
        $admin = $this->user('admin');
        $attempt = $this->attempt($admin, 'writing');
        $answer = $attempt->attemptAnswers()->first();
        WritingReview::create(['attempt_answer_id' => $answer->id, 'reviewer_id' => $admin->id, 'total_score' => 5, 'comment' => 'ok']);

        $this->actingAs($admin)
            ->get(route('writingHistory.index'))
            ->assertSee('Xoá tất cả');

        $this->actingAs($admin)
            ->delete(route('history.destroy', $attempt))
            ->assertRedirect(route('writingHistory.index'));

        $this->assertDatabaseMissing('attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('attempt_answers', ['id' => $answer->id]);
        $this->assertDatabaseCount('writing_reviews', 0);
    }

    public function test_xoa_tat_ca_speaking_xoa_file_ghi_am_va_vo_thi_thu_rong(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('speaking_attempts/a.webm', 'x');

        $admin = $this->user('admin');
        $mock = MockTest::create(['user_id' => $admin->id, 'skill' => 'speaking', 'status' => 'completed', 'started_at' => now(), 'sections' => [], 'duration_minutes' => 12]);
        $speaking = $this->attempt($admin, 'speaking', ['speaking_attempts/a.webm']);
        $speaking->update(['mode' => 'mock', 'mock_test_id' => $mock->id]);
        $writing = $this->attempt($admin, 'writing');

        $this->actingAs($admin)
            ->delete(route('history.destroy-mine', 'speaking'))
            ->assertRedirect(route('speakingHistory.index'));

        $this->assertDatabaseMissing('attempts', ['id' => $speaking->id]);
        $this->assertDatabaseMissing('mock_tests', ['id' => $mock->id]);
        Storage::disk('public')->assertMissing('speaking_attempts/a.webm');
        // Kỹ năng khác không bị đụng.
        $this->assertDatabaseHas('attempts', ['id' => $writing->id]);
    }

    public function test_hoc_vien_khong_xoa_duoc_bai(): void
    {
        $student = $this->user('user');
        $attempt = $this->attempt($student, 'writing');

        $this->actingAs($student)
            ->get(route('writingHistory.index'))
            ->assertDontSee('Xoá tất cả');

        $this->actingAs($student)->delete(route('history.destroy', $attempt))->assertForbidden();
        $this->actingAs($student)->delete(route('history.destroy-mine', 'writing'))->assertForbidden();
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id]);
    }

    public function test_admin_khong_xoa_duoc_bai_cua_nguoi_khac(): void
    {
        $admin = $this->user('admin');
        $attempt = $this->attempt($this->user('user'), 'writing');

        $this->actingAs($admin)->delete(route('history.destroy', $attempt))->assertForbidden();
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id]);
    }
}
