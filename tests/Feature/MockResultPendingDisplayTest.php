<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Trang kết quả thi thử khi bài TỰ LUẬN chưa chấm xong.
 *
 * Bài học 27/08/2026: với Nói/Viết thì `mock_tests.score` là 0 cho tới khi job
 * chấm chạy xong, mà trang vẫn vẽ vòng tròn "0%" đỏ kèm tiêu đề "Cần ôn tập
 * thêm". Dòng "AI đang chấm" có nằm ngay dưới nhưng chữ nhỏ — không ai đọc nó
 * khi phía trên là số 0 to đùng. Chính chủ dự án cũng đọc nhầm thành "AI hỏng,
 * học viên bị 0 điểm", trong khi job chỉ đang xếp hàng.
 *
 * Luật giữ ở đây: CHƯA CÓ ĐIỂM THÌ KHÔNG HIỆN CON SỐ NÀO.
 */
class MockResultPendingDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    /**
     * Dựng một bài thi thử đã nộp, với trạng thái chấm do ca test quyết định.
     *
     * @param  mixed  $answer  nội dung bài làm (mảng path ghi âm với Nói, chuỗi với Viết)
     */
    private function mockTest(
        User $student,
        string $skill,
        mixed $answer,
        string $gradingStatus = 'pending',
        ?array $aiMetadata = null,
        float $attemptScore = 0,
    ): MockTest {
        $quiz = Quiz::create([
            'title' => 'Q', 'skill' => $skill, 'part' => 1,
            'duration_minutes' => 30, 'is_published' => true,
        ]);

        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'S', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id, 'skill' => $skill, 'part' => 1, 'type' => $skill,
            'title' => 'Q', 'stem' => 'Đề bài', 'point' => 10, 'order' => 1, 'metadata' => [],
        ]);

        if (is_array($answer)) {
            foreach ($answer as $path) {
                Storage::disk('public')->put($path, 'FAKE-AUDIO');
            }
        }

        $mock = MockTest::create([
            'user_id' => $student->id, 'skill' => $skill,
            'sections' => [['part' => 1, 'set_id' => $set->id]],
            'duration_minutes' => 30,
            'started_at' => now()->subMinutes(20), 'finished_at' => now(),
            'duration_seconds' => 1200,
            'score' => 0,               // đúng như thực tế: bài tự luận nộp xong là 0
            'status' => 'completed',
        ]);

        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => $skill, 'mode' => 'mock',
            'set_id' => $set->id, 'mock_test_id' => $mock->id,
            'score' => $attemptScore,
            'started_at' => now()->subMinutes(20), 'finished_at' => now(),
        ]);

        AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => $answer, 'grading_status' => $gradingStatus,
            'ai_metadata' => $aiMetadata,
        ]);

        return $mock;
    }

    /* ─────────────── Nói: chưa chấm xong ─────────────── */

    public function test_bai_noi_dang_cho_cham_thi_khong_hien_diem_0(): void
    {
        $student = $this->student();
        $mock = $this->mockTest($student, 'speaking', ['speaking_attempts/a.webm']);

        $res = $this->actingAs($student)->get(route('mock-test.result', $mock))->assertOk();

        $res->assertSee('Đang chấm bài');
        $res->assertSee('AI đang chấm bài của bạn');
        $res->assertDontSee('0%');
        $res->assertDontSee('Cần ôn tập thêm');
    }

    public function test_khong_con_hua_cham_xong_trong_1_3_phut(): void
    {
        // Hàng đợi lúc cao điểm tồn hàng trăm job — hứa "1–3 phút" thì học viên
        // tải lại trang liên tục rồi nhắn giảng viên hỏi vì sao chưa có điểm.
        $student = $this->student();
        $mock = $this->mockTest($student, 'speaking', ['speaking_attempts/a.webm']);

        $this->actingAs($student)->get(route('mock-test.result', $mock))
            ->assertOk()
            ->assertDontSee('1–3 phút');
    }

    /* ─────────────── Viết: trước đây bị bỏ sót hoàn toàn ─────────────── */

    public function test_bai_viet_dang_cho_cham_cung_khong_hien_diem_0(): void
    {
        // Cờ "đang chấm" bản cũ chỉ tính cho Speaking, nên mock Writing chưa chấm
        // xong hiện 0% đỏ mà KHÔNG một dòng nào nói là đang chờ.
        $student = $this->student();
        $mock = $this->mockTest($student, 'writing', 'This is my essay answer.');

        $this->actingAs($student)->get(route('mock-test.result', $mock))
            ->assertOk()
            ->assertSee('Đang chấm bài')
            ->assertSee('AI đang chấm bài của bạn')
            ->assertDontSee('0%')
            ->assertDontSee('Cần ôn tập thêm');
    }

    /* ─────────────── Đã chấm xong thì điểm phải hiện lại bình thường ─────────────── */

    public function test_cham_xong_thi_hien_diem_that(): void
    {
        $student = $this->student();
        $mock = $this->mockTest(
            $student, 'speaking', ['speaking_attempts/a.webm'],
            gradingStatus: 'ai_graded',
            aiMetadata: ['feedback' => ['overall_score' => 7, 'cefr_level' => 'B1']],
            attemptScore: 70,
        );

        $this->actingAs($student)->get(route('mock-test.result', $mock))
            ->assertOk()
            ->assertSee('70%')
            ->assertSee('Điểm nháp do AI chấm')
            ->assertDontSee('Đang chấm bài');
    }

    /* ─────────────── Phần bỏ trống không phải là "đang chờ" ─────────────── */

    public function test_bai_noi_khong_ghi_am_thi_khong_bao_dang_cham(): void
    {
        // Ca #202450 (mất audio lúc hosting chết): không có gì để chấm thì đừng
        // quay vòng "AI đang chấm…" vô tận — học viên chờ một thứ không tới.
        $student = $this->student();
        $mock = $this->mockTest($student, 'speaking', []);

        $this->actingAs($student)->get(route('mock-test.result', $mock))
            ->assertOk()
            ->assertSee('Không ghi âm')
            ->assertDontSee('AI đang chấm bài của bạn');
    }
}
