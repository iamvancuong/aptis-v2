<?php

namespace Tests\Feature;

use App\Models\MockTest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Services\GradingService;
use App\Support\SpeakingAudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Đợt vá bảo mật 10/2026 — mỗi test là một cách tấn công cụ thể bằng F12 /
 * sửa request, chứng minh nó không còn hiệu quả.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'U', 'email' => 'u' . uniqid() . '@example.test', 'password' => Hash::make('dung-mat-khau'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 5, 'violation_count' => 0,
        ], $attrs));
    }

    private function setWithQuestion(string $skill, int $part, array $metadata): array
    {
        $quiz = Quiz::create([
            'title' => 'Q', 'skill' => $skill, 'part' => $part, 'duration_minutes' => 30, 'is_published' => true,
        ]);
        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'S', 'status' => 'published', 'order' => 1,
            'is_public' => true, 'max_attempts' => 3,
        ]);
        $q = Question::create([
            'quiz_id' => $quiz->id, 'skill' => $skill, 'part' => $part, 'type' => 'mcq',
            'title' => 'T', 'stem' => 'S', 'point' => 10, 'order' => 1, 'metadata' => $metadata,
        ]);
        $set->questions()->attach($q->id);

        return [$set, $q];
    }

    // ─── 1. Upload ghi âm giả mạo ────────────────────────────────────────────

    public function test_nhan_dien_audio_bang_chu_ky_file(): void
    {
        $this->assertSame('webm', SpeakingAudio::sniffAudioExtension("\x1A\x45\xDF\xA3rest"));
        $this->assertSame('ogg', SpeakingAudio::sniffAudioExtension('OggS....'));
        $this->assertSame('mp4', SpeakingAudio::sniffAudioExtension("\0\0\0\x20ftypM4A "));
        $this->assertSame('wav', SpeakingAudio::sniffAudioExtension('RIFF1234WAVEfmt '));
        $this->assertSame('mp3', SpeakingAudio::sniffAudioExtension('ID3' . "\x03\x00"));

        $this->assertNull(SpeakingAudio::sniffAudioExtension('<?php echo 1; ?>'));
        $this->assertNull(SpeakingAudio::sniffAudioExtension('<!DOCTYPE html><script>alert(1)</script>'));
        $this->assertNull(SpeakingAudio::sniffAudioExtension('<svg xmlns="http://www.w3.org/2000/svg">'));
    }

    public function test_file_html_doi_ten_webm_khong_duoc_luu(): void
    {
        Storage::fake('public');
        Queue::fake();

        [$set, $q] = $this->setWithQuestion('speaking', 1, []);
        $evil = UploadedFile::fake()->createWithContent('rec.webm', '<!DOCTYPE html><script>alert(1)</script>');
        $real = UploadedFile::fake()->createWithContent('rec2.webm', "\x1A\x45\xDF\xA3" . str_repeat("\0", 100));

        $this->actingAs($this->user())->post(route('practice.store', $set), [
            'answers' => json_encode([]),
            'speaking_audio' => [$q->id => [$evil, $real]],
        ]);

        $files = Storage::disk('public')->files('speaking_attempts');
        $this->assertCount(1, $files, 'Chỉ file audio thật được lưu.');
        $this->assertStringEndsWith('.webm', $files[0]);
        $this->assertStringNotContainsString('<script>', Storage::disk('public')->get($files[0]));
    }

    // ─── 2. XSS qua link lọc trang admin ─────────────────────────────────────

    public function test_link_loc_admin_khong_chay_duoc_javascript(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $payload = "');alert(document.cookie)//";

        foreach (['admin.users.index' => 'expiration', 'admin.writing-reviews.index' => 'expiration'] as $route => $param) {
            $html = $this->actingAs($admin)->get(route($route, [$param => $payload]))->getContent();

            // Kiểu cũ: '{{ }}' → &#039; — trình duyệt giải mã lại thành ' trước khi Alpine chạy.
            $this->assertStringNotContainsString("&#039;);alert(document.cookie)//", $html, $route);
            $this->assertStringNotContainsString("');alert(document.cookie)//", $html, $route);
        }
    }

    // ─── 3. Dò mật khẩu ─────────────────────────────────────────────────────

    public function test_dang_nhap_sai_5_lan_bi_khoa_tam(): void
    {
        $u = $this->user(['email' => 'nannhan@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'nannhan@example.test', 'password' => 'sai-' . $i]);
        }

        // Lần thứ 6 dù ĐÚNG mật khẩu vẫn bị chặn.
        $this->post('/login', ['email' => 'nannhan@example.test', 'password' => 'dung-mat-khau'])
            ->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }

    public function test_dang_nhap_dung_van_vao_binh_thuong(): void
    {
        $this->user(['email' => 'ok@example.test']);

        $this->post('/login', ['email' => 'ok@example.test', 'password' => 'sai']);
        $this->post('/login', ['email' => 'ok@example.test', 'password' => 'dung-mat-khau'])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    // ─── 4. Gian lận điểm bằng payload `true` ───────────────────────────────

    public function test_gui_true_thay_dap_an_khong_duoc_diem(): void
    {
        $grading = app(GradingService::class);

        [, $listening] = $this->setWithQuestion('listening', 1, ['correct_answer' => 2]);
        $this->assertEquals(0, $grading->gradeQuestion($listening, true)['score']);
        $this->assertEquals(10, $grading->gradeQuestion($listening, 2)['score']);
        $this->assertEquals(10, $grading->gradeQuestion($listening, '2')['score']);

        [, $reading] = $this->setWithQuestion('reading', 1, ['correct_answers' => [1, 2, 0]]);
        $this->assertEquals(0, $grading->gradeQuestion($reading, [true, true, true])['score']);
        $this->assertEquals(10, $grading->gradeQuestion($reading, [1, '2', 0])['score']);
    }

    // ─── 5. Lấy đáp án qua API check trong giờ thi thử ──────────────────────

    public function test_khong_lay_duoc_dap_an_khi_dang_thi_thu(): void
    {
        $u = $this->user();
        [$set, $q] = $this->setWithQuestion('reading', 1, ['correct_answers' => [1]]);

        $mock = MockTest::create([
            'user_id' => $u->id, 'skill' => 'reading', 'duration_minutes' => 35,
            'sections' => [['part' => 1, 'set_id' => $set->id]],
            'started_at' => now(), 'status' => 'in_progress',
        ]);

        $this->actingAs($u)->postJson(route('practice.check', $set), ['question_id' => $q->id])
            ->assertStatus(423)
            ->assertJsonMissingPath('answer_key');

        // Nộp bài xong → luyện tập lại bình thường.
        $mock->update(['status' => 'completed']);
        $this->actingAs($u)->postJson(route('practice.check', $set), ['question_id' => $q->id])
            ->assertOk()
            ->assertJsonPath('answer_key.correct_answers', [1]);
    }

    public function test_bai_thi_bo_do_qua_gio_khong_khoa_luyen_tap(): void
    {
        $u = $this->user();
        [$set, $q] = $this->setWithQuestion('reading', 1, ['correct_answers' => [1]]);

        MockTest::create([
            'user_id' => $u->id, 'skill' => 'reading', 'duration_minutes' => 35,
            'sections' => [['part' => 1, 'set_id' => $set->id]],
            'started_at' => now()->subHours(2), 'status' => 'in_progress',
        ]);

        $this->actingAs($u)->postJson(route('practice.check', $set), ['question_id' => $q->id])->assertOk();
    }
}
