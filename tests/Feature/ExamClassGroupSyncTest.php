<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lớp tự gom theo ngày thi ("Nhóm thi tuần này").
 *
 * Ô "Ngày thi (Exam Date)" ở form tạo user ghi thẳng vào `users.expires_at`.
 * Ca đáng bảo vệ nhất là ca tài khoản MUA QUA WEB: với họ `expires_at` là
 * "ngày mua + 14/30 ngày", không phải ngày thi — gom vào là biến người sắp hết
 * hạn gói thành người sắp thi, và không ai nhận ra vì cả hai đều là một ngày
 * trong tương lai gần.
 */
class ExamClassGroupSyncTest extends TestCase
{
    use RefreshDatabase;

    private function hocVien(string $ten, ?string $ngayThi, string $source = User::SOURCE_MANUAL): User
    {
        return User::create([
            'name' => $ten,
            'email' => strtolower(str_replace(' ', '', $ten)) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => $source,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => $ngayThi,
        ]);
    }

    private function lopTuGom(int $soNgay = 7): ClassGroup
    {
        return ClassGroup::create([
            'name' => 'Nhóm thi tuần này',
            'is_active' => true,
            'auto_exam_days' => $soNgay,
        ]);
    }

    public function test_gom_dung_nguoi_thi_trong_cua_so(): void
    {
        $lop = $this->lopTuGom(7);

        $this->hocVien('Thi Mai', now()->addDays(3));   // trong cửa sổ
        $this->hocVien('Thi Xa', now()->addDays(20));   // ngoài cửa sổ
        $this->hocVien('Khong Han', null);              // không có ngày thi

        $this->artisan('classes:sync-exam-groups')->assertSuccessful();

        $ten = $lop->members()->pluck('name');

        $this->assertContains('Thi Mai', $ten->all());
        $this->assertNotContains('Thi Xa', $ten->all());
        $this->assertNotContains('Khong Han', $ten->all());
    }

    public function test_khong_gom_tai_khoan_mua_qua_web(): void
    {
        // Với họ `expires_at` = ngày mua + 14/30 ngày, KHÔNG phải ngày thi.
        $lop = $this->lopTuGom(7);

        $this->hocVien('Mua Web', now()->addDays(3), User::SOURCE_PURCHASE);
        $this->hocVien('Admin Tao', now()->addDays(3), User::SOURCE_MANUAL);
        $this->hocVien('Du Lieu Cu', now()->addDays(3), User::SOURCE_IMPORT);

        $this->artisan('classes:sync-exam-groups')->assertSuccessful();

        $ten = $lop->members()->pluck('name');

        $this->assertNotContains('Mua Web', $ten->all());
        $this->assertContains('Admin Tao', $ten->all());
        $this->assertContains('Du Lieu Cu', $ten->all());
    }

    public function test_nguoi_da_qua_ngay_thi_tu_roi_khoi_lop(): void
    {
        // Đây là điểm khiến tính năng này đáng làm: không ai phải nhớ gỡ.
        $lop = $this->lopTuGom(7);
        $daThi = $this->hocVien('Da Thi Xong', now()->subDays(2));
        $lop->members()->attach($daThi->id, ['added_at' => now()->subWeek()]);

        $this->artisan('classes:sync-exam-groups')->assertSuccessful();

        $this->assertSame(0, $lop->members()->count());
    }

    public function test_lop_thuong_khong_bi_dong_bo_dung_vao(): void
    {
        // `auto_exam_days` để trống = thành viên do admin chọn tay. Lệnh đụng vào
        // lớp thường sẽ xoá sạch danh sách admin đã dựng công phu.
        $lop = ClassGroup::create(['name' => 'Nhóm chính', 'is_active' => true]);
        $hv = $this->hocVien('Chon Tay', now()->addYear());
        $lop->members()->attach($hv->id, ['added_at' => now()]);

        $this->artisan('classes:sync-exam-groups')
            ->expectsOutputToContain('Chưa có lớp nào bật')
            ->assertSuccessful();

        $this->assertSame(1, $lop->members()->count());
    }

    public function test_dry_run_khong_doi_thanh_vien(): void
    {
        $lop = $this->lopTuGom(7);
        $this->hocVien('Thi Mai', now()->addDays(3));

        $this->artisan('classes:sync-exam-groups', ['--dry-run' => true])
            ->expectsOutputToContain('CHẠY THỬ')
            ->assertSuccessful();

        $this->assertSame(0, $lop->members()->count());
    }

    public function test_nhac_dan_lai_danh_sach_moi_calendar(): void
    {
        // Đồng bộ phía web xong mà quên Calendar thì người mới vẫn phải xin duyệt
        // và người cũ vẫn vào thẳng — lệnh phải tự nói ra, đừng để admin nhớ.
        $this->lopTuGom(7);

        $this->artisan('classes:sync-exam-groups')
            ->expectsOutputToContain('Google KHÔNG tự cập nhật theo')
            ->assertSuccessful();
    }

    public function test_khong_gom_nguoi_bi_khoa(): void
    {
        $lop = $this->lopTuGom(7);
        $hv = $this->hocVien('Bi Khoa', now()->addDays(3));
        $hv->update(['status' => 'blocked']);

        $this->artisan('classes:sync-exam-groups')->assertSuccessful();

        $this->assertSame(0, $lop->members()->count());
    }
}
