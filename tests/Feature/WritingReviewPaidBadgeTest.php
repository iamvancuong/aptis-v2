<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trang chấm Writing phân biệt bài chấm CÓ PHÍ (có đơn grading đã thanh toán trỏ
 * tới attempt) với bài MIỄN PHÍ (admin chấm / dữ liệu cũ, không có đơn paid).
 */
class WritingReviewPaidBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role . '@example.test', 'password' => bcrypt('x'),
            'role' => $role, 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    private function gradingAttempt(User $user): Attempt
    {
        return Attempt::create([
            'user_id' => $user->id, 'skill' => 'writing', 'mode' => 'mock',
            'is_grading_requested' => true, 'grading_requested_at' => now(),
            'started_at' => now()->subMinutes(30), 'finished_at' => now(),
        ]);
    }

    public function test_index_marks_paid_vs_free_gradings(): void
    {
        $admin   = $this->user('admin');
        $student = $this->user('user');

        $paidAttempt = $this->gradingAttempt($student);   // có đơn đã thanh toán
        $freeAttempt = $this->gradingAttempt($student);   // dữ liệu cũ, không đơn

        Order::create([
            'order_code' => 770001, 'email' => $student->email, 'type' => Order::TYPE_GRADING,
            'amount' => 99000, 'status' => Order::STATUS_PAID, 'user_id' => $student->id,
            'meta' => ['attempt_id' => $paidAttempt->id, 'skill' => 'writing'],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index', ['filter' => 'all']))
            ->assertOk()
            ->assertSee('💰 Có phí')     // đơn paid được nhận diện (whereIn meta->attempt_id chạy)
            ->assertSee('Miễn phí')
            ->getContent();

        // Đúng 1 bài có phí, 1 bài miễn phí.
        $this->assertSame(1, substr_count($html, '💰 Có phí'));
        $this->assertSame(1, substr_count($html, 'Miễn phí'));
    }

    public function test_pending_grading_order_does_not_count_as_paid(): void
    {
        $admin   = $this->user('admin');
        $student = $this->user('user');
        $attempt = $this->gradingAttempt($student);

        // Đơn CHỜ thanh toán → chưa tính là có phí.
        Order::create([
            'order_code' => 770002, 'email' => $student->email, 'type' => Order::TYPE_GRADING,
            'amount' => 99000, 'status' => Order::STATUS_PENDING, 'user_id' => $student->id,
            'meta' => ['attempt_id' => $attempt->id, 'skill' => 'writing'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.writing-reviews.index', ['filter' => 'all']))
            ->assertOk()
            ->assertSee('Miễn phí')
            ->assertDontSee('💰 Có phí');
    }
}
