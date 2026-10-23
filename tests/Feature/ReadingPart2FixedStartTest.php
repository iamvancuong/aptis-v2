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
use Tests\TestCase;

/**
 * Reading Part 2-3 (sắp xếp câu): câu mở đầu cố định trong bài thi.
 *
 * Bài học 23/10/2026: partial reading-part2 dùng chung với trang luyện tập gọi
 * `p2HasFixedStart()`, nhưng trang thi thử/Full Test không định nghĩa hàm này →
 * x-if lỗi, học viên làm bài KHÔNG thấy câu cố định ("0. Last week I went…").
 */
class ReadingPart2FixedStartTest extends TestCase
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

    private function mockTest(User $student, ?string $opener, string $status = 'in_progress'): array
    {
        $quiz = Quiz::create([
            'title' => 'R2', 'skill' => 'reading', 'part' => 2,
            'duration_minutes' => 35, 'is_published' => true,
        ]);
        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'S', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);
        $sentences = [$opener, 'Câu A.', 'Câu B.', 'Câu C.', 'Câu D.', 'Câu E.'];
        $question = Question::create([
            'quiz_id' => $quiz->id, 'skill' => 'reading', 'part' => 2, 'type' => 'reading',
            'title' => 'New Café in Town', 'stem' => 'Sắp xếp', 'point' => 1, 'order' => 1,
            'metadata' => ['sentences' => $sentences],
        ]);
        $set->questions()->attach($question->id);

        $mock = MockTest::create([
            'user_id' => $student->id, 'skill' => 'reading',
            'sections' => [['part' => 2, 'set_id' => $set->id]],
            'duration_minutes' => 35, 'started_at' => now()->subMinutes(10),
            'finished_at' => $status === 'completed' ? now() : null,
            'duration_seconds' => $status === 'completed' ? 600 : null,
            'status' => $status, 'score' => 0,
        ]);

        return [$mock, $set, $question, $sentences];
    }

    public function test_trang_thi_dinh_nghia_ham_ve_cau_co_dinh(): void
    {
        $student = $this->student();
        [$mock] = $this->mockTest($student, '0. Last week I went to a new cafe');

        $this->actingAs($student)->get(route('mock-test.show', $mock))
            ->assertOk()
            ->assertSee('p2HasFixedStart() {', false);
    }

    public function test_ket_qua_danh_so_cau_co_dinh_la_0(): void
    {
        $student = $this->student();
        [$mock, $set, $question, $sentences] = $this->mockTest($student, '0. Last week I went to a new cafe', 'completed');
        $this->answer($student, $mock, $set, $question, array_slice($sentences, 1));

        $html = $this->actingAs($student)->get(route('mock-test.result', $mock))->assertOk()->getContent();

        $this->assertStringContainsString('0. Last week I went to a new cafe', $html);
        $this->assertMatchesRegularExpression('/>\s*0\s*<\/span>\s*<span[^>]*>0\. Last week/', $html);
    }

    public function test_de_khong_co_cau_co_dinh_thi_ket_qua_khong_hien_dong_co_dinh_rong(): void
    {
        $student = $this->student();
        [$mock, $set, $question, $sentences] = $this->mockTest($student, null, 'completed');
        $this->answer($student, $mock, $set, $question, array_slice($sentences, 1));

        $this->actingAs($student)->get(route('mock-test.result', $mock))
            ->assertOk()
            ->assertDontSee('Cố định');
    }

    private function answer(User $student, MockTest $mock, Set $set, Question $question, array $order): void
    {
        $attempt = Attempt::create([
            'user_id' => $student->id, 'skill' => 'reading', 'mode' => 'mock',
            'set_id' => $set->id, 'mock_test_id' => $mock->id, 'score' => 1,
            'started_at' => now()->subMinutes(10), 'finished_at' => now(),
        ]);
        AttemptAnswer::create([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer' => array_map(fn ($t) => ['text' => $t], $order),
            'is_correct' => true, 'score' => 1,
        ]);
    }
}
