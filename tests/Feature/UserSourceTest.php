<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Nguồn gốc tài khoản (`users.source`) — cơ sở để chia lớp.
 *
 * Lý do phải có cột này thay vì truy từ `orders`: luồng PayOS lên production
 * 28/07/2026, nên tại thời điểm thêm cột chỉ 2/848 tài khoản có đơn đã thanh
 * toán. "Không có đơn ⇒ admin tạo tay" là suy luận sai với dữ liệu cũ.
 */
class UserSourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `created_at` KHÔNG nằm trong `$fillable` nên `User::create()` bỏ qua nó và
     * đóng dấu now(). Phải ghi riêng sau đó, nếu không mọi tài khoản trong test
     * đều "tạo hôm nay" và nhánh `import` không bao giờ chạy.
     */
    private function user(array $overrides = []): User
    {
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $u = User::create(array_merge([
            'name' => 'HV', 'email' => 'hv' . random_int(1, 999999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'status' => 'active',
            'max_devices' => 3, 'violation_count' => 0,
        ], $overrides));

        if ($createdAt) {
            $u->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        // `source` có giá trị mặc định ở tầng DB, model vừa create() chưa biết.
        return $u->fresh();
    }

    public function test_backfill_phan_loai_dung_ba_nhom(): void
    {
        $moc = '2026-07-28';

        $daMua  = $this->user(['created_at' => '2026-07-30 10:00:00']);
        $cu     = $this->user(['created_at' => '2026-05-01 10:00:00']);
        $taoTay = $this->user(['created_at' => '2026-08-01 10:00:00']);

        Order::create([
            'order_code' => 123456, 'email' => $daMua->email, 'type' => Order::TYPE_REGISTRATION,
            'package' => 'month', 'quantity' => 1, 'amount' => 699000,
            'status' => Order::STATUS_PAID, 'user_id' => $daMua->id, 'paid_at' => now(),
        ]);

        $this->artisan("users:backfill-source --moc={$moc}")->assertSuccessful();

        $this->assertSame(User::SOURCE_PURCHASE, $daMua->fresh()->source);
        $this->assertSame(User::SOURCE_IMPORT,   $cu->fresh()->source);
        $this->assertSame(User::SOURCE_MANUAL,   $taoTay->fresh()->source);
    }

    public function test_dry_run_khong_ghi_gi(): void
    {
        $cu = $this->user(['created_at' => '2026-05-01 10:00:00']);
        $truoc = $cu->source;

        $this->artisan('users:backfill-source --dry-run --moc=2026-07-28')->assertSuccessful();

        $this->assertSame($truoc, $cu->fresh()->source);
    }

    public function test_don_chua_thanh_toan_khong_tinh_la_mua(): void
    {
        $u = $this->user(['created_at' => '2026-08-01 10:00:00']);

        Order::create([
            'order_code' => 999111, 'email' => $u->email, 'type' => Order::TYPE_REGISTRATION,
            'package' => 'week', 'quantity' => 1, 'amount' => 399000,
            'status' => Order::STATUS_PENDING, 'user_id' => $u->id,
        ]);

        $this->artisan('users:backfill-source --moc=2026-07-28')->assertSuccessful();

        $this->assertSame(User::SOURCE_MANUAL, $u->fresh()->source);
    }

    public function test_tai_khoan_sinh_ra_tu_don_thanh_toan_la_purchase(): void
    {
        Mail::fake();

        $order = Order::create([
            'order_code' => 222333, 'email' => 'nguoimoi@example.test',
            'type' => Order::TYPE_REGISTRATION, 'package' => 'month', 'quantity' => 1,
            'amount' => 699000, 'status' => Order::STATUS_PENDING,
        ]);

        app(OrderFulfillmentService::class)->fulfill($order);

        $this->assertSame(User::SOURCE_PURCHASE, User::firstWhere('email', 'nguoimoi@example.test')->source);
    }

    public function test_gia_han_khong_doi_nguon_cua_tai_khoan_tao_tay(): void
    {
        // Tài khoản admin tạo tay rồi tự gia hạn bằng chuyển khoản VẪN là tài
        // khoản tạo tay. "Đã từng trả tiền chưa" là câu hỏi khác, hỏi bảng orders.
        Mail::fake();

        $u = $this->user(['source' => User::SOURCE_MANUAL, 'expires_at' => now()->addDays(5)]);

        $order = Order::create([
            'order_code' => 444555, 'email' => $u->email, 'type' => Order::TYPE_REGISTRATION,
            'package' => 'month', 'quantity' => 1, 'amount' => 699000,
            'status' => Order::STATUS_PENDING,
        ]);

        app(OrderFulfillmentService::class)->fulfill($order);

        $this->assertSame(User::SOURCE_MANUAL, $u->fresh()->source);
        $this->assertTrue($u->fresh()->expires_at->gt(now()->addDays(30)));  // vẫn cộng hạn
    }

    public function test_admin_tao_tay_thi_nguon_la_manual(): void
    {
        $admin = $this->user(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Học viên mới', 'email' => 'moi@example.test',
            'role' => 'user', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::SOURCE_MANUAL, User::firstWhere('email', 'moi@example.test')->source);
    }
}
