<?php

namespace Tests\Feature;

use App\Models\SecurityFlag;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The DevTools flag endpoint records a review item and ends the session —
 * it must never ban by itself.
 */
class DevtoolsFlagTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::create([
            'name'            => ucfirst($role),
            'email'           => $role . '@example.test',
            'password'        => bcrypt('password'),
            'role'            => $role,
            'status'          => 'active',
            'max_devices'     => 2,
            'violation_count' => 0,
        ]);
    }

    public function test_it_logs_a_flag_and_bumps_the_violation_count(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->postJson(route('security.devtools-flag'))
            ->assertOk()
            ->assertJson(['status' => 'flagged']);

        $this->assertDatabaseHas('security_flags', [
            'user_id' => $user->id,
            'type'    => 'devtools',
        ]);

        $this->assertSame(1, $user->fresh()->violation_count);
    }

    public function test_it_never_blocks_the_account_automatically(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(route('security.devtools-flag'))->assertOk();

        // The whole point of the safe design: a heuristic must not ban.
        $this->assertSame('active', $user->fresh()->status);
        $this->assertFalse($user->fresh()->isBlocked());
    }

    public function test_it_ends_the_session(): void
    {
        $user = $this->user();

        $this->actingAs($user)->postJson(route('security.devtools-flag'))->assertOk();

        $this->assertGuest();
    }

    public function test_admins_are_never_flagged(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->postJson(route('security.devtools-flag'))->assertOk();

        $this->assertDatabaseCount('security_flags', 0);
        $this->assertSame(0, $admin->fresh()->violation_count);
    }

    public function test_guests_cannot_hit_the_endpoint(): void
    {
        $this->postJson(route('security.devtools-flag'))->assertUnauthorized();
    }

    public function test_admin_review_page_lists_flagged_users(): void
    {
        $admin   = $this->user('admin');
        $learner = $this->user();

        SecurityFlag::create([
            'user_id'    => $learner->id,
            'type'       => 'devtools',
            'ip_address' => '10.0.0.9',
            'url'        => 'http://localhost/practice/1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.security-flags.index'))
            ->assertOk()
            ->assertSee($learner->email);
    }

    public function test_guard_is_injected_for_a_learner(): void
    {
        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('devtools-guard-overlay')
            ->assertSee('Vui lòng đóng Developer Tools', false);
    }

    /**
     * Same layouts.app page as the learner test, so the only reason the guard
     * is absent is the admin exemption in the partial — not a different layout.
     */
    public function test_guard_is_not_injected_for_an_admin(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('devtools-guard-overlay');
    }

    public function test_master_switch_off_removes_the_guard_for_everyone(): void
    {
        Setting::updateOrCreate(['key' => 'devtools_guard_enabled'], ['value' => '0']);

        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('devtools-guard-overlay');
    }

    public function test_exempted_user_does_not_get_the_guard(): void
    {
        $user = $this->user();
        $user->update(['devtools_guard_disabled' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('devtools-guard-overlay');
    }

    public function test_admin_can_toggle_a_users_exemption(): void
    {
        $admin   = $this->user('admin');
        $learner = $this->user();

        $this->assertFalse($learner->fresh()->devtools_guard_disabled);

        // Turn the guard off for this learner.
        $this->actingAs($admin)
            ->post(route('admin.users.toggle-devtools-guard', $learner))
            ->assertRedirect();

        $this->assertTrue($learner->fresh()->devtools_guard_disabled);

        // Toggle back on.
        $this->actingAs($admin)
            ->post(route('admin.users.toggle-devtools-guard', $learner))
            ->assertRedirect();

        $this->assertFalse($learner->fresh()->devtools_guard_disabled);
    }

    public function test_users_list_shows_the_devtools_flag_count(): void
    {
        $admin   = $this->user('admin');
        $learner = $this->user();

        SecurityFlag::create(['user_id' => $learner->id, 'type' => 'devtools', 'ip_address' => '10.0.0.1']);
        SecurityFlag::create(['user_id' => $learner->id, 'type' => 'devtools', 'ip_address' => '10.0.0.2']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('DevTools'); // the new column header
    }
}
