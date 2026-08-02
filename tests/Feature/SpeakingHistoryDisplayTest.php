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
 * Trang chi tiết bài Nói phải LUÔN nói rõ tình trạng chấm tự động.
 *
 * Bản đầu chỉ hiện khối AI khi đã có kết quả — trong lúc chờ thì trang trống
 * trơn, học viên không phân biệt được "máy đang chạy" với "không có tính năng".
 * Đó là báo cáo thật từ người dùng, nên các trạng thái dưới đây có test riêng.
 */
class SpeakingHistoryDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv@example.test', 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function attemptWith(User $student, mixed $answer, string $status, ?array $aiMetadata = null): Attempt
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
            'title' => 'Q', 'stem' => 'Tell me about your hometown.', 'point' => 10, 'order' => 1,
            'metadata' => [],
        ]);

        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => 'speaking', 'mode' => 'mock',
            'set_id' => $set->id, 'started_at' => now()->subMinutes(10), 'finished_at' => now(),
        ]);

        AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => $answer, 'grading_status' => $status, 'ai_metadata' => $aiMetadata,
        ]);

        return $attempt;
    }

    public function test_dang_cho_cham_thi_bao_ro_ai_dang_cham(): void
    {
        $student = $this->student();
        $attempt = $this->attemptWith($student, ['speaking_attempts/a.webm'], 'pending');

        $this->actingAs($student)
            ->get(route('speakingHistory.show', $attempt->id))
            ->assertOk()
            ->assertSee('AI đang chấm', false)
            ->assertSee('Nhận xét tự động đang được tạo', false);
    }

    public function test_khong_ghi_am_thi_khong_hua_suong_la_dang_cham(): void
    {
        $student = $this->student();
        $attempt = $this->attemptWith($student, [], 'pending');

        $response = $this->actingAs($student)
            ->get(route('speakingHistory.show', $attempt->id))
            ->assertOk()
            ->assertSee('Không có bản ghi âm', false);

        $response->assertDontSee('AI đang chấm', false);
    }

    public function test_tat_cong_tac_thi_khong_noi_ai_dang_cham(): void
    {
        config(['services.openai.speaking_ai_enabled' => false]);

        $student = $this->student();
        $attempt = $this->attemptWith($student, ['speaking_attempts/a.webm'], 'pending');

        $this->actingAs($student)
            ->get(route('speakingHistory.show', $attempt->id))
            ->assertOk()
            ->assertDontSee('AI đang chấm', false)
            ->assertSee('Chờ giảng viên chấm', false);
    }

    public function test_cham_xong_thi_hien_diem_va_gioi_han_cua_may(): void
    {
        $student = $this->student();
        $attempt = $this->attemptWith($student, ['speaking_attempts/a.webm'], 'ai_graded', [
            'transcript' => 'I come from Hanoi.',
            'feedback' => [
                'scores' => ['task_fulfillment' => 4, 'vocabulary' => 3, 'grammar' => 3, 'coherence' => 4],
                'overall_score_10' => 7,
                'feedback' => ['task_fulfillment' => 'Đúng trọng tâm.', 'vocabulary' => '', 'grammar' => '', 'coherence' => ''],
                'improved_sample' => 'I am from Hanoi.',
                'key_mistakes' => [], 'suggestions' => [],
                'not_assessed' => ['pronunciation', 'fluency'],
            ],
        ]);
        $attempt->attemptAnswers()->update(['score' => 7]);

        $this->actingAs($student)
            ->get(route('speakingHistory.show', $attempt->id))
            ->assertOk()
            ->assertSee('AI chấm nháp', false)
            // Giới hạn của cách A phải luôn đi kèm điểm, không được lặng lẽ bỏ.
            ->assertSee('KHÔNG chấm phát âm và độ trôi chảy', false)
            ->assertSee('I come from Hanoi.', false);
    }

    public function test_ai_hong_thi_noi_ro_va_bao_khong_bi_tru_luot(): void
    {
        $student = $this->student();
        $attempt = $this->attemptWith($student, ['speaking_attempts/a.webm'], 'ai_failed', [
            'error' => ['reason' => 'no_speech', 'message' => 'Không nhận ra tiếng nói trong bản ghi.'],
        ]);

        $this->actingAs($student)
            ->get(route('speakingHistory.show', $attempt->id))
            ->assertOk()
            ->assertSee('Không nhận ra tiếng nói', false)
            ->assertSee('không bị trừ', false);
    }
}
