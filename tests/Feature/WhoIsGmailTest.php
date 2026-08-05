<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `classes:whois` — cầu nối giữa danh sách Gmail trong phòng Meet và tài khoản
 * Milaedu.
 *
 * Ca đáng bảo vệ nhất là tra bằng `google_email`: người khai Gmail riêng để vào
 * Meet sẽ bị báo nhầm là "người lạ" nếu lệnh chỉ tra cột `email` — và họ chính
 * là nhóm mà cột đó sinh ra để phục vụ.
 */
class WhoIsGmailTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Học viên ' . random_int(1, 99999),
            'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => User::SOURCE_MANUAL,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => now()->addMonth(),
        ], $overrides));
    }

    public function test_tra_bang_email_tai_khoan_thi_xep_vao_hop_le(): void
    {
        $this->student(['email' => 'an@gmail.com', 'name' => 'Nguyễn An']);

        $this->artisan('classes:whois', ['email' => ['an@gmail.com']])
            ->expectsOutputToContain('Nguyễn An')
            ->expectsOutputToContain('0 người lạ')
            ->expectsOutputToContain('1 hợp lệ')
            ->assertSuccessful();
    }

    public function test_tra_bang_google_email_khai_rieng_van_tim_ra(): void
    {
        // Người này đăng ký Milaedu bằng email công ty nhưng vào Meet bằng Gmail
        // cá nhân. Tra một cột là báo nhầm họ thành người lạ.
        $this->student([
            'email' => 'binh@congty.vn',
            'google_email' => 'binh.personal@gmail.com',
            'name' => 'Trần Bình',
        ]);

        // Mỗi `expectsOutputToContain` khớp và "tiêu thụ" MỘT dòng, nên hai kỳ
        // vọng cùng trỏ vào một dòng thì cái sau luôn trượt. Tên và email tài
        // khoản nằm chung dòng ⇒ gộp vào một chuỗi.
        $this->artisan('classes:whois', ['email' => ['binh.personal@gmail.com']])
            ->expectsOutputToContain('Trần Bình · Admin tự thêm · tài khoản: binh@congty.vn')
            ->expectsOutputToContain('1 hợp lệ')
            ->assertSuccessful();
    }

    public function test_email_khong_co_trong_db_thi_bao_nguoi_la(): void
    {
        $this->artisan('classes:whois', ['email' => ['ai-do@gmail.com']])
            ->expectsOutputToContain('KHÔNG CÓ TÀI KHOẢN MILAEDU')
            ->expectsOutputToContain('1 người lạ')
            ->assertSuccessful();
    }

    public function test_tai_khoan_het_han_tach_rieng_khoi_nguoi_la(): void
    {
        // Hết hạn mà vẫn ngồi trong phòng nghĩa là còn giữ lời mời Calendar cũ —
        // việc phải làm là gỡ khỏi sự kiện, khác hẳn việc mời người lạ ra.
        $this->student(['email' => 'cu@gmail.com', 'expires_at' => now()->subDay()]);

        $this->artisan('classes:whois', ['email' => ['cu@gmail.com']])
            ->expectsOutputToContain('HẾT HẠN')
            ->expectsOutputToContain('0 người lạ')
            ->expectsOutputToContain('1 hết hạn/khoá')
            ->assertSuccessful();
    }

    public function test_hoc_vien_that_nhung_khong_thuoc_lop_cua_buoi(): void
    {
        $lop = ClassGroup::create(['name' => 'Lớp trừ nhóm web', 'is_active' => true]);
        $buoi = ClassSession::create([
            'title' => 'Speaking T2', 'class_group_id' => $lop->id,
            'meet_link' => 'https://meet.google.com/qby-gpgt-eei',
            'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $trongLop = $this->student(['email' => 'trong@gmail.com']);
        $trongLop->classGroups()->attach($lop->id);
        $this->student(['email' => 'ngoai@gmail.com', 'source' => User::SOURCE_PURCHASE]);

        $this->artisan('classes:whois', [
            'email' => ['trong@gmail.com', 'ngoai@gmail.com'],
            '--session' => $buoi->id,
        ])
            ->expectsOutputToContain('KHÔNG thuộc lớp của buổi này')
            ->expectsOutputToContain('1 sai lớp')
            ->expectsOutputToContain('1 hợp lệ')
            ->assertSuccessful();
    }

    public function test_tu_tach_email_khoi_van_ban_lon_xon_trong_file(): void
    {
        // Đây là dữ liệu thật: CSV điểm danh của Google có tên, giờ vào/ra, tổng
        // phút. Bắt admin dọn tay trước khi tra là cách chắc chắn nhất để lệnh
        // này không được dùng vào lúc cần nhất — giữa buổi dạy.
        $this->student(['email' => 'dung@gmail.com', 'name' => 'Lê Dung']);

        $duongDan = tempnam(sys_get_temp_dir(), 'whois');
        file_put_contents($duongDan, <<<'CSV'
            Name,Email,Duration,Time joined,Time exited
            Le Dung,dung@gmail.com,95 min,19:28,21:03
            Nguoi La,khach@gmail.com,12 min,20:41,20:53
            CSV);

        try {
            $this->artisan('classes:whois', ['--file' => $duongDan])
                ->expectsOutputToContain('Đã tra 2 địa chỉ')
                ->expectsOutputToContain('Lê Dung')
                ->expectsOutputToContain('1 người lạ')
                ->expectsOutputToContain('1 hợp lệ')
                ->assertSuccessful();
        } finally {
            @unlink($duongDan);
        }
    }

    public function test_khong_phan_biet_hoa_thuong_va_bo_dia_chi_trung(): void
    {
        // Google trả email viết hoa lẫn lộn tuỳ nguồn; cùng một người xuất hiện
        // hai dòng (rớt mạng vào lại) là chuyện thường.
        $this->student(['email' => 'hoa@gmail.com', 'name' => 'Phạm Hoa']);

        $this->artisan('classes:whois', ['email' => ['HOA@Gmail.com', 'hoa@gmail.com']])
            ->expectsOutputToContain('Đã tra 1 địa chỉ')
            ->expectsOutputToContain('Phạm Hoa')
            ->expectsOutputToContain('1 hợp lệ')
            ->assertSuccessful();
    }

    public function test_khong_co_dia_chi_nao_thi_bao_loi_va_huong_dan(): void
    {
        $this->artisan('classes:whois', ['email' => ['khong-phai-email']])
            ->expectsOutputToContain('Không tìm thấy địa chỉ email nào')
            ->assertFailed();
    }
}
