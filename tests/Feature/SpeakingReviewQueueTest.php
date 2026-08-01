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
 * Hàng đợi "Chờ chấm" Speaking.
 *
 * Từ khi có chấm AI, một bài ĐÃ TRẢ PHÍ nhờ giáo viên chấm sẽ chuyển sang
 * 'ai_graded' trước khi cô Dung kịp mở ra. Nếu bộ lọc vẫn hiểu "chờ chấm" là
 * "còn phần ở trạng thái pending" thì bài đó biến mất khỏi danh sách và khách
 * mất tiền không được chấm — đúng bằng lỗi đã gặp ở trang Writing (TIEN_DO §21).
 */
class SpeakingReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role . '@example.test', 'password' => bcrypt('x'),
            'role' => $role, 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function requestedAttempt(User $student, string $status): Attempt
    {
        $quiz = Quiz::create([
            'title' => 'SQ', 'skill' => 'speaking', 'part' => 1,
            'duration_minutes' => 12, 'is_published' => true,
        ]);
        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'SSet', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'skill' => 'speaking', 'part' => 1, 'type' => 'speaking',
            'title' => 'Q', 'stem' => 'Stem', 'point' => 10, 'order' => 1, 'metadata' => [],
        ]);

        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => 'speaking', 'mode' => 'mock',
            'set_id' => $set->id,
            'is_grading_requested' => true, 'grading_requested_at' => now(),
            'started_at' => now()->subMinutes(12), 'finished_at' => now(),
        ]);

        AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => ['speaking_attempts/a.webm'], 'grading_status' => $status,
        ]);

        return $attempt;
    }

    /** @return array<string> */
    public static function chuaChamProvider(): array
    {
        return [
            'AI đã chấm nháp' => ['ai_graded'],
            'AI chấm hỏng'    => ['ai_failed'],
            'hết lượt AI'     => ['limit_reached'],
            'chưa chấm gì'    => ['pending'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('chuaChamProvider')]
    public function test_bai_chua_duoc_giao_vien_cham_van_nam_trong_cho_cham(string $status): void
    {
        $admin = $this->user('admin');
        $student = $this->user('user');
        $this->requestedAttempt($student, $status);

        $this->actingAs($admin)
            ->get(route('admin.speaking-reviews.index'))
            ->assertOk()
            ->assertSee($student->email);

        $this->actingAs($admin)
            ->get(route('admin.speaking-reviews.index', ['status' => 'graded']))
            ->assertOk()
            ->assertDontSee($student->email);
    }

    public function test_bai_giao_vien_da_cham_chuyen_sang_tab_da_cham(): void
    {
        $admin = $this->user('admin');
        $student = $this->user('user');
        $this->requestedAttempt($student, 'graded');

        $this->actingAs($admin)
            ->get(route('admin.speaking-reviews.index'))
            ->assertOk()
            ->assertDontSee($student->email);

        $this->actingAs($admin)
            ->get(route('admin.speaking-reviews.index', ['status' => 'graded']))
            ->assertOk()
            ->assertSee($student->email);
    }
}
