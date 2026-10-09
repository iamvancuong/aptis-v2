<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Màn "Xem đề" (/sets/{set}) đã bị gỡ hẳn: nó in toàn bộ câu hỏi ra một trang
 * tĩnh nên học viên copy/lộ đề. Học viên chỉ vào đề qua màn luyện tập.
 */
class SetPreviewRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_xem_de_khong_con_ton_tai_va_danh_sach_khong_link_toi(): void
    {
        $user = User::create([
            'name' => 'Learner', 'email' => 'learner@example.test', 'password' => bcrypt('password'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
        $quiz = Quiz::create(['title' => 'Q', 'skill' => 'listening', 'part' => 1, 'duration_minutes' => 30, 'is_published' => true]);
        $set = Set::create(['quiz_id' => $quiz->id, 'title' => 'Đề A', 'status' => 'published', 'order' => 1, 'is_public' => true, 'max_attempts' => 3]);

        $this->actingAs($user)->get('/sets/' . $set->id)->assertNotFound();

        $this->actingAs($user)->get(route('sets.index', ['listening', 1]))
            ->assertOk()
            ->assertSee(route('practice.show', $set->id), false)
            ->assertDontSee(url('/sets/' . $set->id), false)
            ->assertDontSee('Xem đề');
    }
}
