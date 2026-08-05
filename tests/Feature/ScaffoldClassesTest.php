<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `classes:scaffold` — dựng lớp + buổi lặp từ file lịch.
 *
 * Lệnh này chạy trên PRODUCTION có dữ liệu thật, nên hai tính chất phải đúng
 * tuyệt đối: chạy lại **không tạo trùng**, và **không gỡ ai** khỏi lớp đã có.
 */
class ScaffoldClassesTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = tempnam(sys_get_temp_dir(), 'lich') ?: '';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    private function lich(array $lich): string
    {
        file_put_contents($this->file, json_encode($lich));

        return $this->file;
    }

    private function hocVien(string $ten, string $source): User
    {
        return User::create([
            'name' => $ten, 'email' => strtolower(str_replace(' ', '', $ten)) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => $source,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ]);
    }

    private function lichMau(): array
    {
        return [
            'groups' => [[
                'name' => 'Nhóm chính (trừ nhóm web)',
                'members_from_sources' => ['import', 'manual'],
            ]],
            'sessions' => [[
                'title' => 'Speaking — Thứ Hai',
                'group' => 'Nhóm chính (trừ nhóm web)',
                'weekday' => 1, 'start' => '19:30', 'end' => '22:30',
                'meet_link' => 'qby-gpgt-eei',
            ]],
        ];
    }

    public function test_dung_lop_va_buoi_lap_tu_file_lich(): void
    {
        $this->hocVien('Hoc Vien Cu', User::SOURCE_IMPORT);
        $this->hocVien('Admin Them', User::SOURCE_MANUAL);
        $this->hocVien('Mua Web', User::SOURCE_PURCHASE);

        $this->artisan('classes:scaffold', ['--file' => $this->lich($this->lichMau())])
            ->assertSuccessful();

        $lop = ClassGroup::firstWhere('name', 'Nhóm chính (trừ nhóm web)');
        $this->assertNotNull($lop);

        // Chỉ 2 nguồn được khai; người mua web không được thêm.
        $this->assertSame(2, $lop->members()->count());
        $this->assertNotContains('Mua Web', $lop->members()->pluck('name')->all());

        $buoi = ClassSession::firstWhere('title', 'Speaking — Thứ Hai');
        $this->assertNotNull($buoi);
        $this->assertSame($lop->id, $buoi->class_group_id);
        $this->assertTrue($buoi->repeat_weekly);
        $this->assertSame('19:30', $buoi->starts_at->format('H:i'));
        $this->assertSame(1, $buoi->starts_at->dayOfWeekIso);
        $this->assertSame('https://meet.google.com/qby-gpgt-eei', $buoi->meet_link);
    }

    public function test_chay_lai_khong_tao_trung(): void
    {
        $duongDan = $this->lich($this->lichMau());

        $this->artisan('classes:scaffold', ['--file' => $duongDan])->assertSuccessful();
        $this->artisan('classes:scaffold', ['--file' => $duongDan])->assertSuccessful();

        $this->assertSame(1, ClassGroup::count());
        $this->assertSame(1, ClassSession::count());
    }

    public function test_chay_lai_khong_go_ai_khoi_lop(): void
    {
        // Admin có thể đã thêm tay người ngoài bộ lọc. Chạy lại lệnh dựng mà gỡ
        // họ ra là phá công admin, âm thầm.
        $duongDan = $this->lich($this->lichMau());
        $this->artisan('classes:scaffold', ['--file' => $duongDan])->assertSuccessful();

        $lop = ClassGroup::firstOrFail();
        $khach = $this->hocVien('Khach Them Tay', User::SOURCE_PURCHASE);
        $lop->members()->attach($khach->id, ['added_at' => now()]);

        $this->artisan('classes:scaffold', ['--file' => $duongDan])->assertSuccessful();

        $this->assertContains('Khach Them Tay', $lop->members()->pluck('name')->all());
    }

    public function test_dry_run_khong_ghi_gi(): void
    {
        $this->artisan('classes:scaffold', [
            '--file' => $this->lich($this->lichMau()), '--dry-run' => true,
        ])->expectsOutputToContain('CHẠY THỬ')->assertSuccessful();

        $this->assertSame(0, ClassGroup::count());
        $this->assertSame(0, ClassSession::count());
    }

    public function test_buoi_tro_toi_lop_khong_ton_tai_thi_bo_qua_chu_khong_tao(): void
    {
        // Tạo mà bỏ trống lớp = buổi mở cho MỌI học viên còn hạn, tức là lộ quyền
        // im lặng. Thà không tạo và báo lỗi.
        $lich = $this->lichMau();
        $lich['sessions'][0]['group'] = 'Lớp không tồn tại';

        $this->artisan('classes:scaffold', ['--file' => $this->lich($lich)])
            ->expectsOutputToContain('không tìm thấy lớp')
            ->assertSuccessful();

        $this->assertSame(0, ClassSession::count());
    }

    public function test_lop_tu_gom_theo_ngay_thi_khong_bi_them_thanh_vien_tay(): void
    {
        $this->hocVien('Hoc Vien Cu', User::SOURCE_IMPORT);

        $this->artisan('classes:scaffold', ['--file' => $this->lich([
            'groups' => [[
                'name' => 'Nhóm thi tuần này',
                'auto_exam_days' => 7,
                'members_from_sources' => [],
            ]],
            'sessions' => [],
        ])])->assertSuccessful();

        $lop = ClassGroup::firstWhere('name', 'Nhóm thi tuần này');

        $this->assertSame(7, $lop->auto_exam_days);
        $this->assertTrue($lop->isAutoExamGroup());
        // Danh sách do `classes:sync-exam-groups` quản, lệnh dựng không đụng vào.
        $this->assertSame(0, $lop->members()->count());
    }

    public function test_bao_loi_khi_file_lich_khong_doc_duoc(): void
    {
        $this->artisan('classes:scaffold', ['--file' => '/khong/co/file.json'])
            ->expectsOutputToContain('Không đọc được file lịch')
            ->assertFailed();
    }
}
