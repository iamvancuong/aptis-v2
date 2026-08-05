<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hộp "danh sách mời" trên trang buổi học gom TOÀN TRƯỜNG.
 *
 * Từ khi có lớp, dán danh sách đó vào sự kiện Calendar của một lớp là mời luôn
 * người ngoài lớp vào thẳng phòng — phá đúng thứ việc chia lớp dựng lên, mà
 * Google không báo lỗi gì. Trang phải cảnh báo TRƯỚC khi admin kịp bấm Copy.
 */
class ClassSessionInviteGuidanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    public function test_co_lop_thi_canh_bao_va_tro_sang_danh_sach_cua_tung_lop(): void
    {
        ClassGroup::create(['name' => 'Nhóm chính (trừ nhóm web)', 'is_active' => true]);
        ClassGroup::create(['name' => 'Nhóm thi tuần này', 'is_active' => true, 'auto_exam_days' => 7]);

        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertSee('hãy dùng danh sách mời CỦA TỪNG LỚP')
            ->assertSee('Danh sách mời: Nhóm chính (trừ nhóm web)')
            ->assertSee('Danh sách mời: Nhóm thi tuần này')
            // Danh sách toàn trường vẫn còn nhưng phải tự khai giới hạn của nó.
            ->assertSee('chỉ cho buổi không gắn lớp');
    }

    public function test_chua_co_lop_nao_thi_khong_canh_bao_thua(): void
    {
        // Chưa chia lớp thì danh sách toàn trường đúng là thứ cần dùng.
        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertDontSee('hãy dùng danh sách mời CỦA TỪNG LỚP');
    }

    public function test_huong_dan_mo_ta_dung_quy_trinh_lich_co_dinh(): void
    {
        // Bản cũ ghi "làm 1 lần cho mỗi buổi" — viết khi chưa có sự kiện lặp và
        // chưa có buổi tự sinh. Hướng dẫn sai còn tệ hơn không có hướng dẫn.
        $this->actingAs($this->admin())
            ->get(route('admin.class-sessions.index'))
            ->assertOk()
            ->assertSee('lịch đã cố định, không phải dựng lại mỗi tuần')
            ->assertDontSee('Cách dùng — làm 1 lần cho mỗi buổi');
    }
}
