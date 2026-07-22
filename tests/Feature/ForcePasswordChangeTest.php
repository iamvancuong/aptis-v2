<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $mustChange): User
    {
        return User::create([
            'name' => 'U', 'email' => 'u@example.test', 'password' => Hash::make('12345678'),
            'role' => 'user', 'status' => 'active', 'max_devices' => 2, 'violation_count' => 0,
            'must_change_password' => $mustChange,
        ]);
    }

    public function test_flagged_user_is_forced_to_change_password(): void
    {
        $user = $this->user(true);

        // Truy cập trang khác → bị đẩy tới màn đổi mật khẩu.
        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_change_password_screen_itself_is_reachable(): void
    {
        $this->actingAs($this->user(true))
            ->get(route('password.change'))
            ->assertOk();
    }

    public function test_updating_password_clears_the_flag(): void
    {
        $user = $this->user(true);

        $this->actingAs($user)
            ->post(route('password.update'), [
                'password'              => 'new-strong-pass',
                'password_confirmation' => 'new-strong-pass',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('new-strong-pass', $user->password));

        // Sau khi đổi, vào được trang khác bình thường.
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_password_must_be_confirmed_and_long_enough(): void
    {
        $this->actingAs($this->user(true))
            ->post(route('password.update'), ['password' => 'short', 'password_confirmation' => 'nope'])
            ->assertSessionHasErrors('password');
    }

    public function test_normal_user_is_not_forced(): void
    {
        $this->actingAs($this->user(false))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_refund_policy_page_is_public(): void
    {
        $this->get(route('policy.refund'))
            ->assertOk()
            ->assertSee('Không hoàn tiền');
    }
}
