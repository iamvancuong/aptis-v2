<?php

namespace Tests\Feature;

use App\Models\MockTest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use App\Services\MockTestBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cắt câu khi một bộ Reading/Listening có nhiều câu hơn số cần thi.
 *
 * Bài học 23/10/2026: production mỗi Part Reading chỉ có MỘT bộ nhiều câu, và
 * cách cắt cũ sắp theo crc32(mockId_questionId). crc32 tuyến tính nên đổi mockId
 * gần như không đổi thứ tự — Part 5 (3 câu) bốc trúng một câu ~50% số lượt, học
 * viên thấy "đề nào cũng như nhau".
 */
class MockTestQuestionPickTest extends TestCase
{
    use RefreshDatabase;

    private function readingSet(int $part, int $questionCount): Set
    {
        $quiz = Quiz::create([
            'title' => "R$part", 'skill' => 'reading', 'part' => $part,
            'duration_minutes' => 35, 'is_published' => true,
        ]);
        $set = Set::create([
            'quiz_id' => $quiz->id, 'title' => 'Bộ 1', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        for ($i = 1; $i <= $questionCount; $i++) {
            $q = Question::create([
                'quiz_id' => $quiz->id, 'skill' => 'reading', 'part' => $part, 'type' => 'reading',
                'title' => "Câu $i", 'stem' => 'x', 'point' => 1, 'order' => $i, 'metadata' => [],
            ]);
            $set->questions()->attach($q->id);
        }

        return $set;
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function pickedIds(MockTest $mock, int $part): array
    {
        return $mock->getSectionsWithSets()
            ->firstWhere('part', $part)['set']->questions->pluck('id')->sort()->values()->all();
    }

    public function test_bai_moi_danh_dau_cach_cat_md5(): void
    {
        foreach ([1, 2, 3, 4] as $part) {
            $this->readingSet($part, 3);
        }

        $sections = app(MockTestBuilder::class)->sectionsFor('reading');

        $this->assertIsArray($sections);
        foreach ($sections as $section) {
            $this->assertSame(MockTest::PICK_MD5, $section['pick']);
        }
    }

    public function test_cau_bai_cu_khong_doi_sau_khi_sua(): void
    {
        // Bài đã tạo trước bản sửa không có `pick` → vẫn cắt theo crc32 như lúc
        // học viên làm, để trang kết quả hiện đúng câu đã làm.
        $set     = $this->readingSet(4, 5);
        $student = $this->student();
        $mock    = MockTest::create([
            'user_id' => $student->id, 'skill' => 'reading',
            'sections' => [['part' => 4, 'set_id' => $set->id]],
            'duration_minutes' => 35, 'started_at' => now(), 'status' => 'completed', 'score' => 0,
        ]);

        $expected = $set->questions->sortBy(fn ($q) => crc32($mock->id . '_' . $q->id))->take(1)->pluck('id')->all();

        $this->assertSame($expected, $this->pickedIds($mock, 4));
    }

    public function test_bo_3_cau_moi_cau_deu_duoc_boc_kha_deu(): void
    {
        $set     = $this->readingSet(4, 3);
        $student = $this->student();
        $counts  = [];

        for ($i = 0; $i < 150; $i++) {
            $mock = MockTest::create([
                'user_id' => $student->id, 'skill' => 'reading',
                'sections' => [['part' => 4, 'set_id' => $set->id, 'pick' => MockTest::PICK_MD5]],
                'duration_minutes' => 35, 'started_at' => now(), 'status' => 'in_progress', 'score' => 0,
            ]);
            $id = $this->pickedIds($mock, 4)[0];
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        // Kỳ vọng 50 lượt/câu; crc32 cũ có câu tới ~75 lượt.
        $this->assertCount(3, $counts);
        $this->assertLessThan(70, max($counts));
    }
}
