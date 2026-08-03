<?php

namespace Tests\Feature;

use App\Jobs\ProcessSpeakingGrading;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Services\AiService;
use App\Services\SpeakingAiDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Chấm Nói bằng AI (cách A: phiên âm → chấm transcript).
 *
 * Trọng tâm không phải "đường thành công" mà là các nhánh HỎNG: bài đẩy lên
 * production khi chưa ai xác nhận shared host gọi được ra api.openai.com, nên
 * điều phải chắc là hỏng thì hiện ra và không lấy mất lượt của học viên.
 */
class SpeakingAiGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openai.key' => 'test-key']);
        Storage::fake('public');
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function student(string $email = 'hv@example.test'): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => $email, 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function speakingQuestion(int $part = 1): Question
    {
        $quiz = Quiz::create([
            'title' => 'SQ', 'skill' => 'speaking', 'part' => $part,
            'duration_minutes' => 12, 'is_published' => true,
        ]);
        Set::create([
            'quiz_id' => $quiz->id, 'title' => 'SSet', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        return Question::create([
            'quiz_id' => $quiz->id, 'skill' => 'speaking', 'part' => $part, 'type' => 'speaking',
            'title' => 'Q', 'stem' => 'Tell me about your hometown.', 'point' => 10, 'order' => 1,
            'metadata' => ['questions' => ['Where are you from?']],
        ]);
    }

    /** Tạo bài nộp kèm file ghi âm giả trên disk. */
    private function submittedAnswer(User $student, array $paths = ['speaking_attempts/a.webm'], string $status = 'pending'): AttemptAnswer
    {
        $question = $this->speakingQuestion();

        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'FAKE-AUDIO-BYTES');
        }

        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => 'speaking', 'mode' => 'mock',
            'set_id' => $question->quiz->sets()->first()->id,
            'started_at' => now()->subMinutes(10), 'finished_at' => now(),
        ]);

        return AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => $paths, 'grading_status' => $status,
        ]);
    }

    private function runJob(AttemptAnswer $answer, int $part = 1): void
    {
        (new ProcessSpeakingGrading($answer->id, [
            'part' => $part, 'stem' => 'Tell me about your hometown.', 'metadata' => [],
        ]))->handle(app(AiService::class));
    }

    private function fakeGradeBody(): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode([
                'scores' => ['task_fulfillment' => 4, 'vocabulary' => 3, 'grammar' => 3, 'coherence' => 4],
                'overall_score_10' => 7,
                'feedback' => [
                    'task_fulfillment' => 'Trả lời đúng câu hỏi.',
                    'vocabulary' => 'Từ vựng ổn.',
                    'grammar' => 'Vài lỗi thì.',
                    'coherence' => 'Ý mạch lạc.',
                ],
                'improved_sample' => 'I come from Hanoi, a busy city in the north.',
                'key_mistakes' => ['Sai thì quá khứ'],
                'suggestions' => ['Luyện thêm thì quá khứ'],
            ])]]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150],
        ];
    }

    /* ───────────────────────── đường thành công ───────────────────────── */

    public function test_cham_thanh_cong_thi_luu_diem_transcript_va_nhan_xet(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'I come from Hanoi.']),
            '*/chat/completions' => Http::response($this->fakeGradeBody()),
        ]);

        $answer = $this->submittedAnswer($this->student());
        $this->runJob($answer);

        $answer->refresh();

        $this->assertSame('ai_graded', $answer->grading_status);
        $this->assertEquals(7.0, (float) $answer->score);
        $this->assertSame('I come from Hanoi.', $answer->ai_metadata['transcript']);
        $this->assertSame(4, $answer->ai_metadata['feedback']['scores']['task_fulfillment']);

        // Giới hạn của cách A phải nằm trong chính bản ghi, không chỉ ở template.
        $this->assertSame(['pronunciation', 'fluency'], $answer->ai_metadata['feedback']['not_assessed']);
    }

    public function test_diem_tong_cua_bai_duoc_tinh_lai_theo_thang_phan_tram(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'Hello.']),
            '*/chat/completions' => Http::response($this->fakeGradeBody()),
        ]);

        $answer = $this->submittedAnswer($this->student());
        $this->runJob($answer);

        // 7/10 ở phần duy nhất → 70% cho cả bài.
        $this->assertEquals(70.0, (float) $answer->attempt->refresh()->score);
    }

    public function test_nhieu_ban_ghi_trong_mot_phan_duoc_ghep_lai_thanh_mot_transcript(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::sequence()
                ->push(['text' => 'Câu một.'])
                ->push(['text' => 'Câu hai.']),
            '*/chat/completions' => Http::response($this->fakeGradeBody()),
        ]);

        $answer = $this->submittedAnswer($this->student(), [
            'speaking_attempts/a.webm', 'speaking_attempts/b.webm',
        ]);
        $this->runJob($answer);

        $this->assertSame("Câu một.\nCâu hai.", $answer->refresh()->ai_metadata['transcript']);
    }

    /* ───────────────────────── band CEFR ───────────────────────── */

    public function test_band_cefr_duoc_luu_cung_nhan_xet(): void
    {
        $body = $this->fakeGradeBody();
        $content = json_decode($body['choices'][0]['message']['content'], true);
        $content['cefr_level'] = 'B2';
        $content['cefr_reason'] = 'Nói được ý rõ ràng.';
        $body['choices'][0]['message']['content'] = json_encode($content);

        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'I come from Hanoi.']),
            '*/chat/completions' => Http::response($body),
        ]);

        $answer = $this->submittedAnswer($this->student());
        $this->runJob($answer);

        $this->assertSame('B2', $answer->refresh()->ai_metadata['feedback']['cefr_level']);
        $this->assertSame('Nói được ý rõ ràng.', $answer->ai_metadata['feedback']['cefr_reason']);
    }

    /**
     * Model hay trả biến thể: "B2+", "level b2", "B2/C1". Học viên đọc bậc này để
     * biết mình đang ở đâu nên không được để lọt chuỗi lạ ra giao diện.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cefrBienTheProvider')]
    public function test_bien_the_cua_band_duoc_ep_ve_dung_bac(string $raw, string $mongDoi): void
    {
        $body = $this->fakeGradeBody();
        $content = json_decode($body['choices'][0]['message']['content'], true);
        $content['cefr_level'] = $raw;
        $body['choices'][0]['message']['content'] = json_encode($content);

        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'Hello.']),
            '*/chat/completions' => Http::response($body),
        ]);

        $answer = $this->submittedAnswer($this->student());
        $this->runJob($answer);

        $this->assertSame($mongDoi, $answer->refresh()->ai_metadata['feedback']['cefr_level']);
    }

    public static function cefrBienTheProvider(): array
    {
        return [
            'có dấu cộng'   => ['B2+', 'B2'],
            'viết thường'   => ['b1', 'B1'],
            'có tiền tố'    => ['Level C1', 'C1'],
            // Không đọc được → suy từ điểm 7/10 theo đúng mốc trong system prompt.
            'rác'           => ['không rõ', 'B2'],
            'rỗng'          => ['', 'B2'],
        ];
    }

    /* ───────────────────────── nhánh hỏng ───────────────────────── */

    public function test_khong_co_file_ghi_am_thi_bao_hong_va_hoan_lai_luot(): void
    {
        Http::fake();

        $student = $this->student();
        $student->recordSpeakingAiUsage(1); // lượt đã trừ lúc nộp bài

        $answer = $this->submittedAnswer($student);
        Storage::disk('public')->delete('speaking_attempts/a.webm');

        $this->runJob($answer);

        $answer->refresh();
        $this->assertSame('ai_failed', $answer->grading_status);
        $this->assertSame('file_missing', $answer->ai_metadata['error']['reason']);

        // Không nhận được gì thì không được giữ lượt đã trừ.
        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));

        Http::assertNothingSent(); // phát hiện sớm, không tốn một lượt gọi API nào
    }

    public function test_khong_nghe_ra_tieng_noi_thi_bao_ro_cho_hoc_vien(): void
    {
        Http::fake(['*/audio/transcriptions' => Http::response(['text' => '   '])]);

        $student = $this->student();
        $student->recordSpeakingAiUsage(1);
        $answer = $this->submittedAnswer($student);

        $this->runJob($answer);

        $answer->refresh();
        $this->assertSame('ai_failed', $answer->grading_status);
        $this->assertSame('no_speech', $answer->ai_metadata['error']['reason']);
        $this->assertStringContainsString('micro', $answer->ai_metadata['error']['message']);
        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));
    }

    public function test_loi_tam_thoi_thi_nem_ra_de_queue_thu_lai_chu_khong_danh_dau_hong(): void
    {
        // 500 = OpenAI trục trặc; lượt sau thường qua.
        Http::fake(['*/audio/transcriptions' => Http::response('server error', 500)]);

        $answer = $this->submittedAnswer($this->student());

        $this->expectException(\App\Exceptions\AiGradingException::class);

        try {
            $this->runJob($answer);
        } finally {
            // Vẫn 'pending' → bài còn nằm trong hàng chờ, chưa kết luận hỏng.
            $this->assertSame('pending', $answer->refresh()->grading_status);
        }
    }

    public function test_key_bi_tu_choi_la_loi_vinh_vien_khong_retry(): void
    {
        Http::fake(['*/audio/transcriptions' => Http::response('unauthorized', 401)]);

        $answer = $this->submittedAnswer($this->student());
        $this->runJob($answer); // không được ném lỗi

        $this->assertSame('ai_failed', $answer->refresh()->grading_status);
        $this->assertSame('config', $answer->ai_metadata['error']['reason']);
    }

    public function test_file_qua_25mb_bi_chan_truoc_khi_goi_api(): void
    {
        Http::fake();

        $answer = $this->submittedAnswer($this->student());
        Storage::disk('public')->put('speaking_attempts/a.webm', str_repeat('x', AiService::MAX_AUDIO_BYTES + 1));

        $this->runJob($answer);

        $this->assertSame('too_large', $answer->refresh()->ai_metadata['error']['reason']);
        Http::assertNothingSent();
    }

    public function test_json_hong_thi_coi_la_loi_tam_de_thu_lai(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'Hello.']),
            '*/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'không phải JSON']]]]),
        ]);

        $answer = $this->submittedAnswer($this->student());

        $this->expectException(\App\Exceptions\AiGradingException::class);
        $this->runJob($answer);
    }

    /* ───────────────────────── tiết kiệm & an toàn ───────────────────────── */

    public function test_khong_phien_am_lai_khi_da_co_transcript_tu_luot_truoc(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'KHÔNG ĐƯỢC GỌI']),
            '*/chat/completions' => Http::response($this->fakeGradeBody()),
        ]);

        $answer = $this->submittedAnswer($this->student());
        $answer->update(['ai_metadata' => ['transcript' => 'Bản phiên âm lượt trước.']]);

        $this->runJob($answer);

        $this->assertSame('Bản phiên âm lượt trước.', $answer->refresh()->ai_metadata['transcript']);

        // Trả tiền phiên âm hai lần cho cùng một file là lỗi im lặng, phải chặn.
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'audio/transcriptions'));
    }

    public function test_khong_ghi_de_diem_giao_vien_da_cham(): void
    {
        Http::fake();

        $answer = $this->submittedAnswer($this->student());
        $answer->update(['grading_status' => 'graded', 'score' => 9.5, 'feedback' => 'Cô chấm']);

        $this->runJob($answer);

        $answer->refresh();
        $this->assertSame('graded', $answer->grading_status);
        $this->assertEquals(9.5, (float) $answer->score);
        Http::assertNothingSent();
    }

    /* ───────────────────────── dispatcher & credit ───────────────────────── */

    public function test_het_luot_thi_danh_dau_limit_reached_va_khong_day_job(): void
    {
        \App\Models\Setting::create(['key' => 'default_ai_limit', 'value' => '1']);

        $student = $this->student();
        $student->recordSpeakingAiUsage(1); // dùng hết 1 lượt

        $answer = $this->submittedAnswer($student);

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        $this->assertSame('limit_reached', $answer->refresh()->grading_status);
    }

    public function test_phan_khong_co_ban_ghi_thi_khong_bi_tru_luot(): void
    {
        $student = $this->student();
        $answer = $this->submittedAnswer($student);
        $answer->update(['answer' => []]);

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        $this->assertSame('pending', $answer->refresh()->grading_status);
        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));
    }

    /**
     * Luồng practice: `GradingService` gán 'graded' cho bài Nói khi mode khác
     * 'mock_test' (nghĩa là "không cần máy chấm"), trùng tên với 'graded' của
     * giáo viên. Dispatcher từng chỉ nhận đúng 'pending' nên practice KHÔNG BAO
     * GIỜ được chấm — không ai phát hiện vì mock test vẫn chạy đúng.
     */
    public function test_bai_practice_bi_gan_graded_van_duoc_ai_cham(): void
    {
        Http::fake([
            '*/audio/transcriptions' => Http::response(['text' => 'I come from Hanoi.']),
            '*/chat/completions' => Http::response($this->fakeGradeBody()),
        ]);

        $student = $this->student();
        $answer = $this->submittedAnswer($student, ['speaking_attempts/a.webm'], 'graded');

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        // Queue chạy sync trong test nên job đã chạy xong ngay tại đây.
        $this->assertSame('ai_graded', $answer->refresh()->grading_status);
        $this->assertEquals(7.0, (float) $answer->score);
    }

    public function test_bai_giao_vien_da_cham_thi_khong_day_job_va_khong_tru_luot(): void
    {
        Http::fake();

        $student = $this->student();
        // Dấu hiệu của người chấm: 'graded' KÈM nhận xét.
        $answer = $this->submittedAnswer($student, ['speaking_attempts/a.webm'], 'graded');
        $answer->update(['feedback' => 'Em nói tốt, chú ý phát âm đuôi s.', 'score' => 8]);

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        $answer->refresh();
        $this->assertSame('graded', $answer->grading_status);
        $this->assertEquals(8.0, (float) $answer->score);
        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));
        Http::assertNothingSent();
    }

    public function test_khong_cham_lai_phan_da_co_ket_qua_ai(): void
    {
        Http::fake();

        $student = $this->student();
        $answer = $this->submittedAnswer($student);
        $answer->update(['ai_metadata' => ['feedback' => ['scores' => []]]]);

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));
        Http::assertNothingSent();
    }

    public function test_cong_tac_tat_thi_khong_cham_ai(): void
    {
        config(['services.openai.speaking_ai_enabled' => false]);

        $student = $this->student();
        $answer = $this->submittedAnswer($student);

        app(SpeakingAiDispatcher::class)->dispatchFor($answer->attempt, $student);

        $this->assertSame('pending', $answer->refresh()->grading_status);
        $this->assertSame(0, (int) $student->speakingAiUsages()->sum('usage_count'));
    }
}
