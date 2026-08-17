<?php

namespace Tests\Feature;

use App\Console\Commands\SendClassReminders;
use App\Mail\ClassSessionReminderMail;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Công tắc `aptis.classes_enabled` — lớp học online đang HOÃN.
 *
 * Các test lớp học khác chạy với cờ BẬT (đặt trong `phpunit.xml`) vì code phải
 * còn đúng cho ngày mở lại. File này giữ chiều ngược lại: khi TẮT thì học viên
 * không còn đường nào chạm tới tính năng.
 *
 * Ẩn menu thôi là chưa đủ, nên mỗi lối vào được kiểm riêng: menu, thẻ dashboard,
 * URL gõ thẳng (bookmark cũ) và email nhắc giờ do cron gửi.
 */
class ClassesFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['aptis.classes_enabled' => false]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Học viên', 'email' => 'hv' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0, 'expires_at' => now()->addMonth(),
        ]);
    }

    private function makeSession(): ClassSession
    {
        return ClassSession::create([
            'title' => 'Buổi 1 — Chữa Writing',
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
    }

    public function test_menu_lop_hoc_khong_hien_tren_dashboard(): void
    {
        $this->actingAs($this->student())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Lớp học')
            ->assertDontSee('/lop-hoc');
    }

    public function test_the_lop_sap_toi_khong_hien_du_co_buoi_dang_dien_ra(): void
    {
        $this->makeSession();

        $this->actingAs($this->student())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Lớp đang diễn ra')
            ->assertDontSee('Lớp sắp tới');
    }

    public function test_go_thang_url_lop_hoc_tra_404(): void
    {
        $this->actingAs($this->student())
            ->get(route('classes.index'))
            ->assertNotFound();
    }

    /**
     * Quan trọng nhất: `join` là chỗ DUY NHẤT trả link Meet ra ngoài. Học viên cũ
     * còn giữ link này trong email nhắc giờ đã gửi trước khi hoãn.
     */
    public function test_go_thang_url_join_tra_404_va_khong_lo_link_meet(): void
    {
        $session = $this->makeSession();

        $this->actingAs($this->student())
            ->get(route('classes.join', $session))
            ->assertNotFound()
            ->assertDontSee('meet.google.com');
    }

    public function test_cron_khong_gui_email_nhac_gio(): void
    {
        Mail::fake();

        $this->student();

        ClassSession::create([
            'title' => 'Buổi sắp tới',
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'is_active' => true,
        ]);

        $this->artisan('classes:remind')->assertExitCode(SendClassReminders::SUCCESS);

        Mail::assertNothingSent();
    }

    public function test_bat_lai_bang_cau_hinh_thi_tinh_nang_song_lai(): void
    {
        // Xác nhận công tắc là thứ duy nhất chặn — không có gì bị xoá nhầm.
        config(['aptis.classes_enabled' => true]);

        $this->actingAs($this->student())
            ->get(route('classes.index'))
            ->assertOk();
    }
}
