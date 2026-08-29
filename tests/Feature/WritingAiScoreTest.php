<?php

namespace Tests\Feature;

use App\Jobs\ProcessWritingGrading;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Services\AiService;
use App\Support\AttemptScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Điểm bài Viết do AI chấm.
 *
 * Bản cũ chỉ lưu `grading_status` + `ai_metadata` và BỎ TRỐNG cột `score`, cũng
 * không cập nhật `attempts.score`. Hệ quả: AI chấm xong, nhận xét hiện đầy đủ,
 * nhưng trang kết quả hiện "0.0/10" cho từng phần và "0%" cho điểm tổng — học
 * viên đọc thành "làm rồi mà không được điểm". Bên Nói vốn đã ghi đúng cả hai
 * chỗ; đây đúng là chỗ hai kỹ năng lệch nhau.
 */
class WritingAiScoreTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function writingAnswer(User $student, mixed $answer = 'My essay.'): AttemptAnswer
    {
        $quiz = Quiz::create([
            'title' => 'WQ', 'skill' => 'writing', 'part' => 2,
            'duration_minutes' => 50, 'is_published' => true,
        ]);

        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'WSet', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id, 'skill' => 'writing', 'part' => 2, 'type' => 'writing',
            'title' => 'Q', 'stem' => 'Write about your hometown.', 'point' => 10,
            'order' => 1, 'metadata' => [],
        ]);

        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => 'writing', 'mode' => 'mock',
            'set_id' => $set->id, 'score' => 0,
            'started_at' => now()->subMinutes(30), 'finished_at' => now(),
        ]);

        return AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => $answer, 'grading_status' => 'pending',
        ]);
    }

    /** Bắt OpenAI trả về đúng bộ điểm ta muốn kiểm. */
    private function fakeAi(array $scores, mixed $overallScore = null): void
    {
        config(['services.openai.key' => 'test-key']);

        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'schema_version' => 3,
                    'part' => 2,
                    'scores' => $scores,
                    'overall_score' => $overallScore,
                    'feedback' => [
                        'grammar' => 'Vài lỗi thì.',
                        'vocabulary' => 'Từ vựng ổn.',
                        'coherence' => 'Ý mạch lạc.',
                        'task_fulfillment' => 'Đúng yêu cầu.',
                    ],
                ])]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150],
            ], 200),
        ]);
    }

    private function runJob(AttemptAnswer $answer): void
    {
        (new ProcessWritingGrading($answer->id, ['part' => 2, 'stem' => 'Write about your hometown.']))
            ->handle(app(AiService::class));
    }

    /* ───────────────────────── điểm được ghi ───────────────────────── */

    public function test_diem_tung_phan_duoc_ghi_vao_cot_score(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        // 4 tiêu chí thang 0–5, trung bình 3.5 → thang 10 là 7.0
        $this->fakeAi(['grammar' => 3, 'vocabulary' => 4, 'coherence' => 3, 'task_fulfillment' => 4]);

        $this->runJob($answer);

        $answer->refresh();
        $this->assertSame('ai_graded', $answer->grading_status);
        $this->assertEqualsWithDelta(7.0, (float) $answer->score, 0.01,
            'Điểm phần phải được ghi vào cột `score`, không chỉ nằm trong ai_metadata.');
    }

    public function test_diem_tong_cua_bai_duoc_cap_nhat(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        $this->fakeAi(['grammar' => 4, 'vocabulary' => 4, 'coherence' => 4, 'task_fulfillment' => 4]);

        $this->runJob($answer);

        // Trung bình phần = 8/10 → điểm bài lưu theo % = 80
        $this->assertEqualsWithDelta(80.0, (float) $answer->attempt->fresh()->score, 0.01,
            'attempts.score phải được tính lại, nếu không vòng tròn điểm tổng mãi là 0%.');
    }

    /* ─────────── không tin overall_score của AI ─────────── */

    public function test_overall_score_bay_cua_ai_khong_lam_hong_diem(): void
    {
        // Bản mock trong AiService trả `overall_score = 14` cho bộ 3/4/3/4 —
        // nhân đôi ra 28, chặn trần thành 10/10 cho một bài trung bình.
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        $this->fakeAi(
            ['grammar' => 3, 'vocabulary' => 4, 'coherence' => 3, 'task_fulfillment' => 4],
            overallScore: 14,
        );

        $this->runJob($answer);

        $this->assertEqualsWithDelta(7.0, (float) $answer->fresh()->score, 0.01,
            'Điểm phải tính từ 4 tiêu chí, không lấy overall_score của AI.');
    }

    public function test_tieu_chi_vuot_thang_bi_chan_hai_dau(): void
    {
        $this->assertSame(10.0, AttemptScore::writingScoreOutOfTen([
            'scores' => ['grammar' => 99, 'vocabulary' => 5, 'coherence' => 5, 'task_fulfillment' => 5],
        ]));

        $this->assertSame(0.0, AttemptScore::writingScoreOutOfTen([
            'scores' => ['grammar' => -3, 'vocabulary' => 0, 'coherence' => 0, 'task_fulfillment' => 0],
        ]));
    }

    public function test_khong_co_tieu_chi_nao_thi_tra_null_chu_khong_phai_0(): void
    {
        // 0 nghĩa là "chấm rồi, được 0 điểm"; null nghĩa là "chưa có điểm".
        // Lẫn hai thứ này là lý do trang kết quả từng hiện 0% cho bài chưa chấm.
        $this->assertNull(AttemptScore::writingScoreOutOfTen([]));
        $this->assertNull(AttemptScore::writingScoreOutOfTen(['scores' => ['grammar' => 'abc']]));
    }

    /* ─────────── lệnh tính bù cho bài chấm trước bản vá ─────────── */

    public function test_lenh_tinh_bu_diem_cho_bai_cu(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        // Mô phỏng bài chấm bằng bản CŨ: có nhận xét, không có điểm.
        $answer->update([
            'grading_status' => 'ai_graded',
            'score'          => null,
            'ai_metadata'    => ['feedback' => [
                'scores' => ['grammar' => 4, 'vocabulary' => 4, 'coherence' => 4, 'task_fulfillment' => 4],
            ]],
        ]);

        $this->artisan('writing:backfill-scores')->assertSuccessful();

        $this->assertEqualsWithDelta(8.0, (float) $answer->fresh()->score, 0.01);
        $this->assertEqualsWithDelta(80.0, (float) $answer->attempt->fresh()->score, 0.01);
    }

    public function test_lenh_tinh_bu_khong_ghi_gi_o_che_do_thu(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        $answer->update([
            'grading_status' => 'ai_graded',
            'score'          => null,
            'ai_metadata'    => ['feedback' => [
                'scores' => ['grammar' => 4, 'vocabulary' => 4, 'coherence' => 4, 'task_fulfillment' => 4],
            ]],
        ]);

        $this->artisan('writing:backfill-scores --dry-run')->assertSuccessful();

        $this->assertNull($answer->fresh()->score, '--dry-run không được ghi vào database.');
    }

    public function test_lenh_tinh_bu_khong_dung_vao_bai_giao_vien_cham(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student);

        $answer->update([
            'grading_status' => 'manually_graded',
            'score'          => 0,   // giáo viên cho 0 thật
            'ai_metadata'    => ['feedback' => [
                'scores' => ['grammar' => 5, 'vocabulary' => 5, 'coherence' => 5, 'task_fulfillment' => 5],
            ]],
        ]);

        $this->artisan('writing:backfill-scores')->assertSuccessful();

        $this->assertEqualsWithDelta(0.0, (float) $answer->fresh()->score, 0.01,
            'Điểm giáo viên chấm không bao giờ được ghi đè bằng điểm AI.');
    }

    /* ─────────── bài bỏ trống ─────────── */

    public function test_bai_bo_trong_duoc_0_diem_chu_khong_phai_null(): void
    {
        $student = $this->student();
        $answer  = $this->writingAnswer($student, '');

        // Bài rỗng không gọi API — job tự dựng khối điểm 0.
        $this->runJob($answer);

        $answer->refresh();
        $this->assertSame('ai_graded', $answer->grading_status);
        $this->assertEqualsWithDelta(0.0, (float) $answer->score, 0.01);
    }
}
