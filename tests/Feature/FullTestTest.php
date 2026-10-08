<?php

namespace Tests\Feature;

use App\Jobs\ProcessSpeakingGrading;
use App\Jobs\ProcessWritingGrading;
use App\Models\FullTest;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Services\FullTestService;
use App\Support\AptisScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Full Test: 5 phần liên tục, giới hạn lượt, server giữ giờ, chấm AI không trừ
 * lượt thường, bảng điểm chỉ mở khi chấm xong, admin cấp lượt / xem / chấm lại.
 */
class FullTestTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Nguyễn Văn A', 'email' => 'u' . uniqid() . '@example.test', 'password' => Hash::make('x'),
            // Request trong test không giữ cookie thiết bị → mỗi request là một "thiết bị"
            // mới; nâng giới hạn để SessionLimit không đăng xuất giữa chừng.
            'role' => 'user', 'status' => 'active', 'max_devices' => 999, 'violation_count' => 0,
        ], $attrs));
    }

    private function quiz(string $skill, int $part): Quiz
    {
        return Quiz::create([
            'title' => "{$skill} {$part}", 'skill' => $skill, 'part' => $part,
            'duration_minutes' => 30, 'is_published' => true,
        ]);
    }

    private function set(Quiz $quiz): Set
    {
        return Set::create([
            'quiz_id' => $quiz->id, 'title' => 'Bộ ' . $quiz->title, 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);
    }

    private function question(Set $set, string $skill, int $part, array $metadata = [], int $point = 10): Question
    {
        $q = Question::create([
            'quiz_id' => $set->quiz_id, 'skill' => $skill, 'part' => $part, 'type' => 'mcq',
            'title' => "{$skill} P{$part}", 'stem' => "Stem {$skill} {$part}", 'point' => $point,
            'order' => $part, 'metadata' => $metadata,
        ]);
        $set->questions()->attach($q->id);

        return $q;
    }

    /** Đủ đề cho cả 5 kỹ năng. */
    private function seedBank(): void
    {
        foreach (['speaking', 'writing'] as $skill) {
            $set = $this->set($this->quiz($skill, 1));
            foreach ([1, 2, 3, 4] as $p) {
                $this->question($set, $skill, $p, ['questions' => ['Q?']]);
            }
        }

        $g = $this->set($this->quiz('grammar', 0));
        $this->question($g, 'grammar', 1, ['options' => [['id' => 'A', 'text' => 'a'], ['id' => 'B', 'text' => 'b']], 'correct_option' => 'A']);
        $this->question($g, 'grammar', 2, ['pairs' => [['id' => '1', 'prompt' => 'x']], 'dropdown_pool' => ['learn', 'get'], 'correct_answers' => ['1' => 'learn']]);

        foreach ([1, 3, 4] as $p) {
            $this->question($this->set($this->quiz('reading', $p)), 'reading', $p, ['correct_answers' => [1]]);
        }
        $r2 = $this->quiz('reading', 2);
        foreach ([1, 2] as $i) {
            $this->question($this->set($r2), 'reading', 2, ['sentences' => ['Intro', 'One', 'Two']]);
        }

        $this->question($this->set($this->quiz('listening', 1)), 'listening', 1, ['correct_answer' => 1]);
        foreach ([2, 3, 4] as $p) {
            $this->question($this->set($this->quiz('listening', $p)), 'listening', $p, ['correct_answers' => [1]]);
        }
    }

    /** Vào phần hiện tại và nộp bài (trả lời rỗng). */
    private function doStage(User $u, FullTest $ft): MockTest
    {
        $this->actingAs($u)->post(route('full-test.next', $ft))->assertRedirect();
        $mock = MockTest::where('full_test_id', $ft->id)->latest('id')->first();

        $answers = [];
        foreach (array_keys($mock->sections) as $i) {
            $answers[$i] = [];
        }

        $this->actingAs($u)->postJson(route('mock-test.submit', $mock), ['answers' => $answers])
            ->assertOk()
            ->assertJsonPath('redirect', route('full-test.show', $ft));

        return $mock;
    }

    // ─── Luồng thi ──────────────────────────────────────────────────────────

    public function test_thi_du_5_phan_theo_dung_thu_tu(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();

        $this->actingAs($u)->post(route('full-test.start'))->assertRedirect();
        $ft = FullTest::firstOrFail();

        $order = [];
        for ($i = 0; $i < 5; $i++) {
            $order[] = $this->doStage($u, $ft->fresh())->skill;
        }

        $this->assertSame(['speaking', 'listening', 'grammar', 'reading', 'writing'], $order);
        $this->assertTrue($ft->fresh()->isCompleted());
        $this->actingAs($u)->get(route('full-test.show', $ft))->assertRedirect(route('full-test.result', $ft));
    }

    public function test_vao_lai_tiep_tuc_dung_phan_dang_do(): void
    {
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();

        $this->actingAs($u)->post(route('full-test.next', $ft));
        $this->actingAs($u)->post(route('full-test.next', $ft));

        // Bấm "tiếp tục" lần hai không tạo bài mới.
        $this->assertSame(1, MockTest::where('full_test_id', $ft->id)->count());
        // Bấm "Bắt đầu" lại trả về đúng lượt đang dở, không tạo lượt mới.
        $this->actingAs($u)->post(route('full-test.start'))->assertRedirect(route('full-test.show', $ft));
        $this->assertSame(1, FullTest::count());
    }

    public function test_het_luot_thi_khong_bat_dau_duoc(): void
    {
        $this->seedBank();
        $u = $this->user();
        foreach (range(1, 3) as $i) {
            FullTest::create(['user_id' => $u->id, 'status' => 'completed', 'current_stage' => 5, 'started_at' => now()]);
        }

        $this->actingAs($u)->post(route('full-test.start'))
            ->assertRedirect(route('full-test.index'))
            ->assertSessionHas('error');
        $this->assertSame(3, FullTest::count());

        // Admin cấp thêm 1 lượt → thi được.
        $u->forceFill(['full_test_quota' => 4])->save();
        $this->actingAs($u)->post(route('full-test.start'));
        $this->assertSame(4, FullTest::count());
    }

    public function test_server_giu_gio_thi(): void
    {
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        $this->actingAs($u)->post(route('full-test.next', $ft));

        $mock = MockTest::firstOrFail();
        $mock->update(['started_at' => now()->subMinutes(30)]); // Speaking chỉ có 12 phút

        $this->actingAs($u)->get(route('mock-test.show', $mock))
            ->assertOk()
            ->assertSee('timeRemaining: 0,', false)
            ->assertSee('Phần 1/5');
    }

    public function test_trang_thi_grammar_hien_dung_va_khong_lo_dap_an(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        $this->doStage($u, $ft->fresh()); // speaking
        $this->doStage($u, $ft->fresh()); // listening
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $g = MockTest::where('full_test_id', $ft->id)->where('skill', 'grammar')->firstOrFail();

        $html = $this->actingAs($u)->get(route('mock-test.show', $g))
            ->assertOk()
            ->assertSee('Phần 3/5')
            ->assertSee('Choose the correct option')           // partial Grammar Part 1
            ->assertSee('setVocabAnswer', false)                 // hàm cho partial Part 2
            ->getContent();

        // Đáp án bị xoá khỏi dữ liệu gửi xuống trình duyệt.
        $this->assertStringContainsString('"correct_option":null', $html);
        $this->assertStringNotContainsString('"correct_answers":{"1":"learn"}', $html);
    }

    public function test_boc_de_uu_tien_bo_du_cau(): void
    {
        // Listening Part 1 cần 13 câu: kho có bộ 13 câu và bộ chỉ 1 câu.
        $quiz = $this->quiz('listening', 1);
        $full = $this->set($quiz);
        foreach (range(1, 13) as $i) {
            $this->question($full, 'listening', 1, ['correct_answer' => 1]);
        }
        $tiny = $this->set($quiz);
        $this->question($tiny, 'listening', 1, ['correct_answer' => 1]);
        foreach ([2, 3, 4] as $p) {
            $this->question($this->set($this->quiz('listening', $p)), 'listening', $p, ['correct_answers' => [1]]);
        }

        $builder = app(\App\Services\MockTestBuilder::class);
        foreach (range(1, 15) as $i) {
            $sections = $builder->sectionsFor('listening');
            $this->assertSame($full->id, $sections[0]['set_id'], 'Không được bốc bộ chỉ có 1 câu.');
        }
    }

    public function test_nop_trung_khong_tao_hai_bai_lam(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $mock = MockTest::firstOrFail();
        $payload = ['answers' => [0 => [], 1 => [], 2 => [], 3 => []]];

        // Một request khác đang giữ khoá nộp → request này bị từ chối, không chấm.
        $lock = \Illuminate\Support\Facades\Cache::lock("mock-test-submit:{$mock->id}", 60);
        $lock->get();
        $this->actingAs($u)->postJson(route('mock-test.submit', $mock), $payload)->assertStatus(409);
        $this->assertSame(0, $mock->attempts()->count());
        $lock->release();

        // Bấm nộp 2 lần liên tiếp → chỉ 1 bài làm, chỉ tiến 1 phần.
        $this->actingAs($u)->postJson(route('mock-test.submit', $mock), $payload)->assertOk();
        $this->actingAs($u)->postJson(route('mock-test.submit', $mock), $payload)->assertOk()
            ->assertJsonPath('redirect', route('full-test.show', $ft));

        $this->assertSame(1, $mock->attempts()->count());
        $this->assertSame(1, $ft->fresh()->current_stage);
    }

    public function test_khong_xem_ket_qua_tung_phan_khi_chua_thi_xong(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        $mock = $this->doStage($u, $ft);

        $this->actingAs($u)->get(route('mock-test.result', $mock))->assertRedirect(route('full-test.show', $ft));
    }

    public function test_nguoi_khac_khong_xem_duoc(): void
    {
        $this->seedBank();
        $owner = $this->user();
        $this->actingAs($owner)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();

        $other = $this->user();
        $this->actingAs($other)->get(route('full-test.show', $ft))->assertForbidden();
        $this->actingAs($other)->post(route('full-test.next', $ft))->assertForbidden();
        $this->actingAs($other)->get(route('full-test.pdf', $ft))->assertForbidden();
    }

    // ─── Chấm điểm & bảng điểm ──────────────────────────────────────────────

    public function test_cham_ai_khong_tru_luot_thuong_va_grammar_duoc_cham(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();

        // speaking, listening rỗng
        $this->doStage($u, $ft->fresh());
        $this->doStage($u, $ft->fresh());

        // grammar: trả lời đúng cả 2 câu
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $g = MockTest::where('full_test_id', $ft->id)->where('skill', 'grammar')->firstOrFail();
        $q1 = Question::where('skill', 'grammar')->where('part', 1)->first();
        $q2 = Question::where('skill', 'grammar')->where('part', 2)->first();
        $this->actingAs($u)->postJson(route('mock-test.submit', $g), [
            'answers' => [0 => [$q1->id => 'A'], 1 => [$q2->id => ['1' => 'learn']]],
        ])->assertOk();
        $this->assertEquals(100, (float) $g->fresh()->score);

        $this->doStage($u, $ft->fresh()); // reading

        // writing có bài làm → đẩy job chấm, KHÔNG ghi lượt AI thường
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $w = MockTest::where('full_test_id', $ft->id)->where('skill', 'writing')->firstOrFail();
        $wq = Question::where('skill', 'writing')->where('part', 1)->first();
        $this->actingAs($u)->postJson(route('mock-test.submit', $w), [
            'answers' => [0 => [$wq->id => ['I like reading books']], 1 => [], 2 => [], 3 => []],
        ])->assertOk();

        Queue::assertPushed(ProcessWritingGrading::class);
        $this->assertSame(0, $u->writingAiUsages()->count());
        $this->assertTrue($ft->fresh()->isCompleted());
    }

    public function test_bang_diem_chi_mo_khi_cham_xong(): void
    {
        Queue::fake();
        $this->seedBank();
        $u = $this->user();
        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        for ($i = 0; $i < 4; $i++) {
            $this->doStage($u, $ft->fresh());
        }
        // writing có bài làm 1 phần
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $w = MockTest::where('full_test_id', $ft->id)->where('skill', 'writing')->firstOrFail();
        $wq = Question::where('skill', 'writing')->where('part', 1)->first();
        $this->actingAs($u)->postJson(route('mock-test.submit', $w), [
            'answers' => [0 => [$wq->id => ['My answer']], 1 => [], 2 => [], 3 => []],
        ]);

        // Writing còn chờ chấm → chưa có bảng điểm.
        $this->actingAs($u)->get(route('full-test.result', $ft))
            ->assertOk()->assertSee('Đang chấm')->assertDontSee('Overall CEFR level');
        $this->actingAs($u)->get(route('full-test.pdf', $ft))->assertRedirect();

        // AI chấm xong: 8/10.
        $answer = $w->attempts()->first()->attemptAnswers()->where('question_id', $wq->id)->first();
        $answer->update(['grading_status' => 'ai_graded', 'score' => 8]);

        $report = app(FullTestService::class)->report($ft->fresh());
        $this->assertTrue($report['fully_graded']);
        // 1 phần 8/10, 3 phần bỏ trống = 0 → 20% → 10/50 (KHÔNG phải 80%).
        $this->assertSame(10, $report['skills']['writing']['scale']);

        $this->actingAs($u)->get(route('full-test.result', $ft))
            ->assertOk()
            ->assertSee('Overall CEFR level')
            ->assertSee('Final scale score')
            ->assertSee('không phải')          // ghi chú không phải chứng chỉ thật
            ->assertDontSee('British Council –'); // không giả mạo đơn vị cấp

        $pdf = $this->actingAs($u)->get(route('full-test.pdf', $ft));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    /** Lượt thi đã chấm xong với điểm % cho sẵn từng kỹ năng. */
    private function gradedFullTest(User $u, array $pct): FullTest
    {
        $ft = FullTest::create(['user_id' => $u->id, 'status' => 'completed', 'current_stage' => 5,
            'started_at' => now()->subHours(3), 'finished_at' => now()]);
        foreach ($pct as $skill => $p) {
            $mock = MockTest::create(['user_id' => $u->id, 'full_test_id' => $ft->id, 'skill' => $skill,
                'sections' => [], 'duration_minutes' => 30, 'started_at' => now(), 'status' => 'completed', 'score' => $p]);
            $att = \App\Models\Attempt::create(['user_id' => $u->id, 'skill' => $skill, 'mode' => 'mock',
                'mock_test_id' => $mock->id, 'score' => $p, 'started_at' => now(), 'finished_at' => now()]);
            if (in_array($skill, ['writing', 'speaking'])) {
                $q = $this->question($this->set($this->quiz($skill, 1)), $skill, 1);
                $att->attemptAnswers()->create(['question_id' => $q->id,
                    'answer' => $skill === 'writing' ? ['text'] : ['speaking_attempts/a.webm'],
                    'score' => $p / 10, 'grading_status' => 'ai_graded']);
            }
        }

        return $ft;
    }

    public function test_dat_muc_tieu_thi_ban_phao_hoa(): void
    {
        $u = $this->user(['target_level' => 'B1']);
        $ft = $this->gradedFullTest($u, ['speaking' => 84, 'listening' => 72, 'grammar' => 88, 'reading' => 80, 'writing' => 82]);

        $this->actingAs($u)->get(route('full-test.result', $ft))
            ->assertOk()
            ->assertSee('vượt mục tiêu B1')          // đạt B2 > mục tiêu B1
            ->assertSee('fullTestFireworks', false);
    }

    public function test_chua_dat_muc_tieu_thi_dong_vien(): void
    {
        $u = $this->user(['target_level' => 'C1']); // C1 → bậc C của Aptis
        $ft = $this->gradedFullTest($u, ['speaking' => 84, 'listening' => 50, 'grammar' => 88, 'reading' => 40, 'writing' => 82]);

        $this->actingAs($u)->get(route('full-test.result', $ft))
            ->assertOk()
            ->assertSee('mục tiêu C không còn xa')
            ->assertSee('Nên ưu tiên luyện <strong>Reading</strong> — đang 20/50', false)
            ->assertDontSee('fullTestFireworks', false);
    }

    public function test_thang_diem_tong_va_tung_ky_nang(): void
    {
        $this->assertSame('B2', AptisScale::levelForScale('speaking', 41));
        $this->assertSame('B1', AptisScale::levelForScale('speaking', 40));
        $this->assertSame('B1', AptisScale::levelForScale('listening', 24));
        // Ngưỡng tổng B1 = 24 + 26 + 26 + 26 = 102
        $this->assertSame('B1', AptisScale::overallLevel(102));
        $this->assertSame('A2', AptisScale::overallLevel(101));
        $this->assertSame('C', AptisScale::overallLevel(196));
    }

    // ─── Admin ──────────────────────────────────────────────────────────────

    public function test_admin_cap_luot_xem_va_cham_lai(): void
    {
        Queue::fake();
        $this->seedBank();
        $admin = $this->user(['role' => 'admin']);
        $u = $this->user();

        $this->actingAs($admin)->put(route('admin.full-tests.quotas.update', $u), ['full_test_quota' => 7])
            ->assertSessionHasNoErrors();
        $this->assertSame(7, (int) $u->fresh()->full_test_quota);

        $this->actingAs($u)->post(route('full-test.start'));
        $ft = FullTest::firstOrFail();
        for ($i = 0; $i < 4; $i++) {
            $this->doStage($u, $ft->fresh());
        }
        $this->actingAs($u)->post(route('full-test.next', $ft));
        $w = MockTest::where('full_test_id', $ft->id)->where('skill', 'writing')->firstOrFail();
        $wq = Question::where('skill', 'writing')->where('part', 2)->first();
        $this->actingAs($u)->postJson(route('mock-test.submit', $w), [
            'answers' => [0 => [], 1 => [$wq->id => 'Some text'], 2 => [], 3 => []],
        ]);

        $this->actingAs($admin)->get(route('admin.full-tests.index'))->assertOk()->assertSee($ft->code());
        $this->actingAs($admin)->get(route('admin.full-tests.show', $ft))->assertOk()->assertSee('Chấm lại phần còn treo');
        $this->actingAs($admin)->get(route('admin.full-tests.quotas', ['q' => $u->email]))->assertOk()->assertSee($u->email);

        Queue::fake(); // xoá job từ lúc nộp bài
        $this->actingAs($admin)->post(route('admin.full-tests.regrade', $ft))->assertSessionHas('success');
        Queue::assertPushed(ProcessWritingGrading::class, 1);
    }

    public function test_hoc_vien_khong_vao_duoc_admin_full_test(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get(route('admin.full-tests.index'))->assertStatus(403);
        $this->actingAs($u)->put(route('admin.full-tests.quotas.update', $u), ['full_test_quota' => 99])->assertStatus(403);
        $this->assertSame(3, (int) $u->fresh()->full_test_quota);
    }
}
