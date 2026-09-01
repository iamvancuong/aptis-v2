<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Trang Doanh số — phạm vi thời gian.
 *
 * Bản cũ không lọc gì khi vào trang, nên con số cộng dồn từ ngày mở bán: sang
 * tháng mới vẫn thấy tổng của mọi tháng trước và không đọc ra được tháng này
 * bán được bao nhiêu. Nay mặc định là THÁNG NÀY (tự về 0 mỗi đầu tháng), còn
 * số luỹ kế xem bằng nút "Tổng".
 *
 * Mốc tháng theo giờ Việt Nam — `config/app.php` đặt Asia/Ho_Chi_Minh.
 */
class RevenueMonthlyResetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function paidOrder(int $amount, Carbon $paidAt): Order
    {
        return Order::create([
            'order_code' => random_int(100000, 999999),
            'email'      => 'kh' . random_int(1, 99999) . '@example.test',
            'type'       => Order::TYPE_REGISTRATION,
            'package'    => 'thang',
            'quantity'   => 1,
            'amount'     => $amount,
            'status'     => Order::STATUS_PAID,
            'paid_at'    => $paidAt,
        ]);
    }

    /** Dựng sẵn: 1 triệu tháng này + 5 triệu tháng trước. */
    private function seedTwoMonths(): void
    {
        $this->paidOrder(1_000_000, now()->startOfMonth()->addDays(2));
        $this->paidOrder(5_000_000, now()->subMonthNoOverflow()->startOfMonth()->addDays(10));
    }

    public function test_mac_dinh_chi_tinh_thang_nay(): void
    {
        $this->seedTwoMonths();

        $res = $this->actingAs($this->admin())->get(route('admin.revenue.index'))->assertOk();

        $res->assertSee('1.000.000đ');           // tháng này
        $res->assertDontSee('6.000.000đ');       // không cộng dồn cả hai tháng
        $res->assertSee('Tháng ' . now()->format('n/Y'));
    }

    public function test_nut_tong_xem_toan_bo(): void
    {
        $this->seedTwoMonths();

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index', ['range' => 'tat_ca']))
            ->assertOk()
            ->assertSee('6.000.000đ')            // 1tr + 5tr
            ->assertSee('Toàn bộ từ trước tới nay');
    }

    public function test_sang_thang_moi_thi_bang_ve_0(): void
    {
        // Đây chính là điều chủ dự án yêu cầu: "sang tháng mới phải reset lại".
        $this->paidOrder(3_000_000, now()->startOfMonth()->addDays(3));

        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth()->addDay());

        $res = $this->actingAs($this->admin())->get(route('admin.revenue.index'))->assertOk();

        $res->assertDontSee('3.000.000đ');
        $res->assertSee('Tháng ' . now()->format('n/Y'));

        // Nhưng tiền cũ KHÔNG mất — vẫn xem được bằng nút Tổng.
        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index', ['range' => 'tat_ca']))
            ->assertOk()
            ->assertSee('3.000.000đ');
    }

    public function test_loc_ngay_tu_chon_van_chay(): void
    {
        $this->seedTwoMonths();

        $thangTruoc = now()->subMonthNoOverflow();

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index', [
                'from' => $thangTruoc->copy()->startOfMonth()->toDateString(),
                'to'   => $thangTruoc->copy()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('5.000.000đ')    // chỉ tháng trước
            ->assertDontSee('6.000.000đ');
    }

    public function test_loc_ngay_thang_quyen_uu_tien_hon_range(): void
    {
        // Có from/to thì đó là ý người dùng, đừng để `range` mặc định đè lên.
        $this->seedTwoMonths();

        $thangTruoc = now()->subMonthNoOverflow();

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index', [
                'range' => 'thang',
                'from'  => $thangTruoc->copy()->startOfMonth()->toDateString(),
                'to'    => $thangTruoc->copy()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('5.000.000đ');
    }

    public function test_hoc_vien_thuong_khong_vao_duoc(): void
    {
        $student = User::create([
            'name' => 'Học viên', 'email' => 'hv@example.test', 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);

        $this->actingAs($student)->get(route('admin.revenue.index'))->assertForbidden();
    }
}
