<?php

namespace Tests\Unit;

use App\Support\PartLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Nhãn Part hiển thị phải khớp đề APTIS thật.
 *
 * Reading của đề thật có 5 phần, hệ thống lưu 4 (phần 2 gộp phần 2+3 của đề).
 * Đây thuần là ánh xạ HIỂN THỊ — số nội bộ và URL không đổi.
 */
class PartLabelTest extends TestCase
{
    #[DataProvider('readingProvider')]
    public function test_reading_doi_sang_so_cua_de_that(int $noiBo, string $mongDoi): void
    {
        $this->assertSame($mongDoi, PartLabel::number('reading', $noiBo));
        $this->assertSame("Part {$mongDoi}", PartLabel::text('reading', $noiBo));
    }

    public static function readingProvider(): array
    {
        return [
            'part 1 giữ nguyên' => [1, '1'],
            'part 2 gộp 2 và 3' => [2, '2-3'],
            'part 3 thành 4'    => [3, '4'],
            'part 4 thành 5'    => [4, '5'],
        ];
    }

    #[DataProvider('kyNangKhacProvider')]
    public function test_ky_nang_khac_giu_nguyen_so(string $skill): void
    {
        foreach ([1, 2, 3, 4] as $part) {
            $this->assertSame((string) $part, PartLabel::number($skill, $part), "{$skill} part {$part} không được đổi");
        }
    }

    public static function kyNangKhacProvider(): array
    {
        return [['listening'], ['writing'], ['speaking'], ['grammar']];
    }

    public function test_part_ngoai_bang_anh_xa_thi_giu_nguyen(): void
    {
        // Nếu sau này Reading có thêm part 5 nội bộ, đừng nuốt mất nó.
        $this->assertSame('5', PartLabel::number('reading', 5));
    }

    public function test_du_lieu_thieu_khong_lam_vo_giao_dien(): void
    {
        $this->assertSame('?', PartLabel::number('reading', null));
        $this->assertSame('Part ?', PartLabel::text('reading', null));
        $this->assertSame('?', PartLabel::number(null, ''));
        // Chuỗi có sẵn (ví dụ '?' từ groupBy khi thiếu question) thì giữ nguyên.
        $this->assertSame('?', PartLabel::number('reading', '?'));
    }

    public function test_so_dang_chuoi_van_doi_dung(): void
    {
        // groupBy trong Blade trả về khoá dạng chuỗi.
        $this->assertSame('2-3', PartLabel::number('reading', '2'));
        $this->assertSame('4', PartLabel::number('reading', '3'));
    }
}
