<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function paidOrder(string $type, int $amount, ?string $paidAt = null): Order
    {
        return Order::create([
            'order_code' => random_int(100000, 9999999),
            'email'      => 'buyer@example.test',
            'type'       => $type,
            'package'    => $type === Order::TYPE_REGISTRATION ? 'month' : null,
            'quantity'   => 1,
            'amount'     => $amount,
            'status'     => Order::STATUS_PAID,
            'paid_at'    => $paidAt ?? now(),
        ]);
    }

    public function test_chia_dang_ky_70_30_va_cham_bai_100_cho_co_dung(): void
    {
        // Đổi 01/09/2026: bỏ phần 30% của Cường, Cô Dung lên 70%.
        // Đăng ký 1.000.000 → Cô Dung 700k, Còn lại 300k.
        $this->paidOrder(Order::TYPE_REGISTRATION, 1000000);
        // Chấm bài: 100k + 100k = 200k → 100% Cô Dung.
        $this->paidOrder(Order::TYPE_GRADING, 100000);
        $this->paidOrder(Order::TYPE_GRADING, 100000);

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('1.200.000')   // tổng
            ->assertSee('900.000')     // Cô Dung = 700k + 200k
            ->assertSee('300.000')     // Còn lại
            ->assertDontSee('Cường');  // dòng này đã gỡ khỏi bảng phân chia
    }

    public function test_hai_phan_cong_lai_dung_100_phan_tram(): void
    {
        // Không có ràng buộc nào ở code canh việc này, mà chia sai thì bảng vẫn
        // hiện bình thường với tổng không khớp doanh thu — sai im lặng.
        $split = config('pricing.revenue_split');

        $this->assertSame(100, array_sum($split),
            'Tổng các phần chia phải đúng 100%.');
        $this->assertArrayNotHasKey('cuong', $split,
            'Phần của Cường đã gộp vào Cô Dung — không được để sót khoá cũ.');
    }

    public function test_pending_orders_are_excluded(): void
    {
        Order::create([
            'order_code' => 555, 'email' => 'x@example.test', 'type' => Order::TYPE_REGISTRATION,
            'package' => 'month', 'quantity' => 1, 'amount' => 500000, 'status' => Order::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSee('Chưa có giao dịch nào.');
    }

    public function test_date_filter_narrows_results(): void
    {
        $this->paidOrder(Order::TYPE_REGISTRATION, 500000, '2026-01-10 10:00:00');
        $this->paidOrder(Order::TYPE_REGISTRATION, 700000, '2026-03-10 10:00:00');

        // Chỉ tháng 3 → tổng 700k.
        $this->actingAs($this->admin())
            ->get(route('admin.revenue.index', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('700.000')
            ->assertDontSee('1.200.000');
    }

    public function test_non_admin_cannot_access(): void
    {
        $user = User::create([
            'name' => 'U', 'email' => 'u@example.test', 'password' => bcrypt('x'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);

        $this->actingAs($user)->get(route('admin.revenue.index'))->assertForbidden();
    }
}
