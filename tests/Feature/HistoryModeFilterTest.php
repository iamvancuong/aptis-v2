<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bài thi thử lưu mode = 'mock'. Bản cũ lọc tab "Thi thử" theo 'mock_test'
 * nên tab đó luôn rỗng và mọi bài đều bị gắn nhãn "Luyện tập".
 */
class HistoryModeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_tab_thi_thu_hien_bai_mode_mock(): void
    {
        $user = User::create([
            'name' => 'Learner', 'email' => 'learner@example.test', 'password' => bcrypt('password'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
        ]);
        foreach (['mock' => 77, 'practice' => 33] as $mode => $score) {
            Attempt::create([
                'user_id' => $user->id, 'skill' => 'reading', 'mode' => $mode,
                'started_at' => now()->subHour(), 'finished_at' => now(), 'score' => $score,
            ]);
        }

        $this->actingAs($user)->get(route('history.index', ['mode' => 'mock_test']))
            ->assertOk()->assertSee('77%')->assertDontSee('33%');

        $this->actingAs($user)->get(route('history.index', ['mode' => 'practice']))
            ->assertOk()->assertSee('33%')->assertDontSee('77%');
    }
}
