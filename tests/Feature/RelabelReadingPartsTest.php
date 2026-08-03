<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Set;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lệnh đồng bộ số Part trong tiêu đề Reading.
 *
 * Là lệnh GHI DỮ LIỆU nên phải chắc hai điều: đổi đúng số, và chạy lại nhiều lần
 * không làm hỏng thêm (cron/người dùng lỡ tay gõ hai lần là chuyện thường).
 */
class RelabelReadingPartsTest extends TestCase
{
    use RefreshDatabase;

    private function quizWithSet(string $skill, int $part, string $title): Quiz
    {
        $quiz = Quiz::create([
            'title' => $title, 'skill' => $skill, 'part' => $part,
            'duration_minutes' => 30, 'is_published' => true,
        ]);

        Set::create([
            'quiz_id' => $quiz->id, 'title' => $title . ' - Bộ 1', 'status' => 'published',
            'order' => 1, 'is_public' => true, 'max_attempts' => 3,
        ]);

        return $quiz;
    }

    public function test_doi_so_part_trong_tieu_de_quiz_va_set(): void
    {
        $quiz = $this->quizWithSet('reading', 3, 'Reading Part 3: Reading Comprehension');

        $this->artisan('reading:relabel-parts')->assertSuccessful();

        $this->assertSame('Reading Part 4: Reading Comprehension', $quiz->refresh()->title);
        $this->assertSame('Reading Part 4: Reading Comprehension - Bộ 1', $quiz->sets()->first()->title);
    }

    public function test_part_2_thanh_2_3(): void
    {
        $quiz = $this->quizWithSet('reading', 2, 'Reading Part 2: Multiple Choice');

        $this->artisan('reading:relabel-parts')->assertSuccessful();

        $this->assertSame('Reading Part 2-3: Multiple Choice', $quiz->refresh()->title);
    }

    public function test_chay_lai_khong_nhan_doi_hau_to(): void
    {
        $quiz = $this->quizWithSet('reading', 2, 'Reading Part 2: Multiple Choice');

        $this->artisan('reading:relabel-parts')->assertSuccessful();
        $this->artisan('reading:relabel-parts')->assertSuccessful();
        $this->artisan('reading:relabel-parts')->assertSuccessful();

        // Sai ở đây sẽ ra "Part 2-3-3" — lý do phải bắt cả dạng đã đổi trong regex.
        $this->assertSame('Reading Part 2-3: Multiple Choice', $quiz->refresh()->title);
    }

    public function test_dry_run_khong_ghi_gi(): void
    {
        $quiz = $this->quizWithSet('reading', 4, 'Reading Part 4: Long Passage');

        $this->artisan('reading:relabel-parts --dry-run')->assertSuccessful();

        $this->assertSame('Reading Part 4: Long Passage', $quiz->refresh()->title);
    }

    public function test_khong_dung_toi_ky_nang_khac(): void
    {
        $listening = $this->quizWithSet('listening', 3, 'Listening Part 3: Monologue');

        $this->artisan('reading:relabel-parts')->assertSuccessful();

        $this->assertSame('Listening Part 3: Monologue', $listening->refresh()->title);
    }

    public function test_giu_nguyen_phan_chu_mo_ta(): void
    {
        // Chỉ đổi con số. Tên dạng bài là nội dung học thuật, không phải việc của lệnh.
        $quiz = $this->quizWithSet('reading', 4, 'Reading Part 4: Long Passage');

        $this->artisan('reading:relabel-parts')->assertSuccessful();

        $this->assertStringContainsString('Long Passage', $quiz->refresh()->title);
    }
}
