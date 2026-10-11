<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AI chấm thay giáo viên: tắt TEACHER_GRADING_ENABLED thì admin không còn thấy
 * mục "Writing/Speaking chờ chấm" (sidebar) và các thẻ chờ chấm ở Tổng quan.
 * Trang chấm vẫn mở được bằng URL để xem bài cũ.
 */
class ManualGradingHiddenTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
    }

    public function test_tat_cham_tay_thi_an_muc_cho_cham(): void
    {
        config(['aptis.teacher_grading_enabled' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Writing chờ chấm')
            ->assertDontSee('Speaking chờ chấm')
            ->assertDontSee('Bài Viết Chờ Chấm');

        $this->get(route('admin.writing-reviews.index'))->assertOk();
    }

    public function test_bat_cham_tay_thi_hien_lai(): void
    {
        config(['aptis.teacher_grading_enabled' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Writing chờ chấm')
            ->assertSee('Bài Viết Chờ Chấm');
    }
}
