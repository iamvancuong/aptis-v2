<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trang chạy lệnh kiểm tra lớp online từ web.
 *
 * Chạy lệnh từ trình duyệt là con đường ngắn nhất tới một cửa hậu, nên phần lớn
 * các ca ở đây là ca TỪ CHỐI: học viên không vào được, khoá lệnh lạ không chạy,
 * và không lệnh nào ở trang này được ghi vào DB.
 */
class ClassToolsPageTest extends TestCase
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

    private function hocVien(): User
    {
        return User::create([
            'name' => 'Hoc Vien', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ]);
    }

    // ─────────── Ai được vào ───────────

    public function test_hoc_vien_khong_vao_duoc_trang_cong_cu(): void
    {
        $this->actingAs($this->hocVien())
            ->get(route('admin.class-tools.index'))
            ->assertForbidden();
    }

    public function test_hoc_vien_khong_chay_duoc_lenh(): void
    {
        $this->actingAs($this->hocVien())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'diagnose'])
            ->assertForbidden();
    }

    public function test_chua_dang_nhap_thi_bi_day_ve_login(): void
    {
        $this->get(route('admin.class-tools.index'))->assertRedirect();
    }

    // ─────────── Danh sách trắng ───────────

    public function test_khoa_lenh_khong_co_trong_danh_sach_trang_bi_tu_choi(): void
    {
        // Nếu ca này đỏ thì trang đã thành cửa chạy lệnh tuỳ ý.
        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'migrate:fresh'])
            ->assertSessionHasErrors('cong_cu');
    }

    public function test_khong_nhan_ten_lenh_tu_request(): void
    {
        // Gửi kèm `lenh` để thử ghi đè — controller phải bỏ qua hoàn toàn và vẫn
        // chạy đúng lệnh của khoá `diagnose`.
        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), [
                'cong_cu' => 'diagnose',
                'lenh'    => 'migrate:fresh',
            ])
            ->assertOk()
            ->assertSee('Chẩn đoán buổi học');

        // DB còn nguyên: `migrate:fresh` mà chạy thì bảng users đã trống.
        $this->assertGreaterThan(0, User::count());
    }

    // ─────────── Chạy thật ───────────

    public function test_admin_chay_duoc_lenh_chan_doan(): void
    {
        $lop = ClassGroup::create(['name' => 'Nhóm chính', 'is_active' => true]);
        ClassSession::create([
            'title' => 'Reading — Thứ Năm', 'class_group_id' => $lop->id,
            'meet_link' => 'https://meet.google.com/zch-cuko-zqm',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'diagnose'])
            ->assertOk()
            ->assertSee('Reading — Thứ Năm')
            ->assertSee('zch-cuko-zqm');
    }

    public function test_whois_tach_email_tu_van_ban_dan_vao(): void
    {
        User::create([
            'name' => 'Le Dung', 'email' => 'dung@gmail.com',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_IMPORT,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), [
                'cong_cu' => 'whois',
                'emails'  => "Name,Email,Duration\nLe Dung,dung@gmail.com,95 min\nAi Do,khach@gmail.com,12 min",
            ])
            ->assertOk()
            ->assertSee('Le Dung')
            ->assertSee('1 người lạ');
    }

    public function test_whois_khong_co_email_nao_thi_bao_loi(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'whois', 'emails' => 'không có gì'])
            ->assertSessionHasErrors('emails');
    }

    public function test_lenh_gui_email_that_khong_co_trong_trang_nay(): void
    {
        // `classes:remind` gửi email thật cho hàng trăm học viên. Một cú bấm nhầm
        // giữa buổi dạy là không rút lại được.
        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'remind'])
            ->assertSessionHasErrors('cong_cu');
    }

    public function test_lenh_sinh_buoi_chay_o_che_do_thu_nen_khong_tao_gi(): void
    {
        $lop = ClassGroup::create(['name' => 'Nhóm chính', 'is_active' => true]);
        ClassSession::create([
            'title' => 'Speaking — Thứ Hai', 'class_group_id' => $lop->id,
            'meet_link' => 'https://meet.google.com/qby-gpgt-eei',
            'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(3),
            'is_active' => true, 'repeat_weekly' => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'generate'])
            ->assertOk();

        // Cờ --dry-run nằm cứng trong controller, không phải tuỳ chọn của người bấm.
        $this->assertSame(1, ClassSession::count());
    }

    public function test_lenh_gom_nhom_thi_chay_o_che_do_thu(): void
    {
        $lop = ClassGroup::create([
            'name' => 'Nhóm thi tuần này', 'is_active' => true, 'auto_exam_days' => 7,
        ]);
        $this->hocVien()->update(['expires_at' => now()->addDays(3)]);

        $this->actingAs($this->admin())
            ->post(route('admin.class-tools.run'), ['cong_cu' => 'exam'])
            ->assertOk();

        $this->assertSame(0, $lop->members()->count());
    }
}
