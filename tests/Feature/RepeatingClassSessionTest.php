<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buổi học lặp hằng tuần — khớp với sự kiện lặp lại trên Google Calendar.
 *
 * Hai thứ phải đúng tuyệt đối, vì sai thì không ai phát hiện ngay:
 * ① chạy lại KHÔNG tạo buổi trùng (cron chạy hằng ngày, chồng nhau là chuyện thường),
 * ② buổi con KHÔNG tự lặp tiếp — nếu không, mỗi buổi sinh ra lại thành một gốc
 *    mới và số buổi tăng theo cấp số nhân cho tới khi ai đó mở bảng ra xem.
 */
class RepeatingClassSessionTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = 'https://meet.google.com/qby-gpgt-eei';

    private function goc(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'title' => 'Speaking (trừ nhóm web)',
            'meet_link' => self::LINK,
            // Tuần trước, 19:30 — giống lịch thật của giảng viên.
            'starts_at' => now()->subWeek()->setTime(19, 30),
            'ends_at' => now()->subWeek()->setTime(22, 30),
            'is_active' => true,
            'repeat_weekly' => true,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    public function test_sinh_du_buoi_cho_so_tuan_yeu_cau(): void
    {
        $goc = $this->goc();

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();

        // Hôm nay + 7 ngày + 14 ngày = 3 buổi mới, cùng thứ cùng giờ với gốc.
        $con = ClassSession::where('repeat_source_id', $goc->id)->orderBy('starts_at')->get();

        $this->assertCount(3, $con);
        $this->assertSame('19:30', $con->first()->starts_at->format('H:i'));
        $this->assertSame(
            $goc->starts_at->dayOfWeek,
            $con->last()->starts_at->dayOfWeek,
            'Buổi sinh ra phải rơi đúng thứ của buổi gốc.',
        );
    }

    public function test_chay_lai_khong_tao_buoi_trung(): void
    {
        $this->goc();

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();
        $lanDau = ClassSession::count();

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();

        $this->assertSame($lanDau, ClassSession::count(), 'Chạy lần hai không được tạo thêm buổi nào.');
    }

    public function test_buoi_con_khong_tu_lap_tiep(): void
    {
        $goc = $this->goc();

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();

        $this->assertSame(
            0,
            ClassSession::where('repeat_source_id', $goc->id)->where('repeat_weekly', true)->count(),
            'Buổi con mà tự lặp thì số buổi tăng theo cấp số nhân.',
        );
    }

    public function test_buoi_con_ke_thua_lop_link_va_do_dai_buoi(): void
    {
        $lop = ClassGroup::create(['name' => 'Lớp trừ nhóm web', 'is_active' => true]);
        $goc = $this->goc(['class_group_id' => $lop->id]);

        $this->artisan('classes:generate-sessions', ['--weeks' => 1])->assertSuccessful();

        $con = ClassSession::where('repeat_source_id', $goc->id)->firstOrFail();

        $this->assertSame($lop->id, $con->class_group_id);
        $this->assertSame(self::LINK, $con->meet_link);
        $this->assertSame($goc->title, $con->title);
        $this->assertSame(180, (int) $con->starts_at->diffInMinutes($con->ends_at), 'Buổi 3 tiếng phải giữ nguyên 3 tiếng.');
    }

    public function test_khong_sinh_khi_buoi_goc_da_tat(): void
    {
        // Tắt buổi gốc là cách dừng một lịch lặp mà không phải xoá nó.
        $goc = $this->goc(['is_active' => false]);

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();

        $this->assertSame(0, ClassSession::where('repeat_source_id', $goc->id)->count());
    }

    public function test_khong_sinh_khi_buoi_goc_khong_co_gio_bat_dau(): void
    {
        // Không có giờ bắt đầu thì không suy ra được thứ mấy để lặp.
        $goc = $this->goc(['starts_at' => null, 'ends_at' => null]);

        $this->artisan('classes:generate-sessions', ['--weeks' => 2])->assertSuccessful();

        $this->assertSame(0, ClassSession::where('repeat_source_id', $goc->id)->count());
        $this->assertFalse($goc->isRepeatTemplate());
    }

    public function test_khach_moi_rieng_khong_duoc_sao_chep_sang_buoi_moi(): void
    {
        // Khách mời riêng là ngoại lệ MỘT LẦN (học bù, học thử). Copy tự động sẽ
        // âm thầm cấp quyền vĩnh viễn — mở quyền im lặng là kiểu lỗi không ai thấy.
        $lop = ClassGroup::create(['name' => 'Lớp A', 'is_active' => true]);
        $goc = $this->goc(['class_group_id' => $lop->id]);

        $khach = User::create([
            'name' => 'Khách học thử', 'email' => 'khach@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_MANUAL,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ]);
        $goc->extraMembers()->attach($khach->id);

        $this->artisan('classes:generate-sessions', ['--weeks' => 1])
            ->expectsOutputToContain('KHÔNG sao chép sang buổi mới')
            ->assertSuccessful();

        $con = ClassSession::where('repeat_source_id', $goc->id)->firstOrFail();

        $this->assertSame(0, $con->extraMembers()->count());
        $this->assertFalse($khach->canJoinClassSession($con));
    }

    public function test_dry_run_khong_ghi_gi(): void
    {
        $goc = $this->goc();

        $this->artisan('classes:generate-sessions', ['--weeks' => 2, '--dry-run' => true])
            ->expectsOutputToContain('CHẠY THỬ')
            ->assertSuccessful();

        $this->assertSame(0, ClassSession::where('repeat_source_id', $goc->id)->count());
    }

    public function test_xoa_buoi_goc_thi_buoi_da_sinh_van_con(): void
    {
        // Buổi con có nhật ký vào lớp của học viên thật. Xoá gốc mà kéo theo cả
        // lịch sử là mất dữ liệu thật vì một thao tác dọn lịch.
        $goc = $this->goc();
        $this->artisan('classes:generate-sessions', ['--weeks' => 1])->assertSuccessful();
        $soCon = ClassSession::where('repeat_source_id', $goc->id)->count();

        $goc->delete();

        $this->assertGreaterThan(0, $soCon);
        $this->assertSame($soCon, ClassSession::whereNull('repeat_source_id')->count());
    }

    public function test_tick_lap_ma_khong_dien_gio_bat_dau_thi_bao_loi(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-sessions.store'), [
                'title' => 'Buổi không giờ',
                'meet_link' => self::LINK,
                'is_active' => '1',
                'repeat_weekly' => '1',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(0, ClassSession::count());
    }

    public function test_admin_tao_duoc_buoi_lap_qua_form(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-sessions.store'), [
                'title' => 'Speaking T2',
                'meet_link' => self::LINK,
                'starts_at' => now()->addDay()->setTime(19, 30)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->setTime(22, 30)->format('Y-m-d\TH:i'),
                'is_active' => '1',
                'repeat_weekly' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(ClassSession::firstOrFail()->isRepeatTemplate());
    }
}
