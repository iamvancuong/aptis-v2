<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\VocabularyLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cô tự chỉnh lượt ở admin: Full Test mặc định cho tài khoản mới, tra từ/ngày
 * cho mọi học viên, và tra từ cấp thêm cho từng học viên.
 */
class AdminQuotaSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user', array $extra = []): User
    {
        return User::create(array_merge([
            'name' => 'U', 'email' => $role . '-' . uniqid() . '@example.test',
            'password' => bcrypt('password'), 'role' => $role, 'status' => 'active',
            'max_devices' => 99, 'violation_count' => 0,
        ], $extra));
    }

    private function settingsPayload(array $override = []): array
    {
        return array_merge([
            'zalo_contact_number' => '0886160515',
            'writing_grading_limit' => 2,
            'speaking_grading_limit' => 2,
            'default_max_devices' => 2,
            'default_ai_limit' => 10,
            'speaking_ai_limit' => 5,
            'default_full_test_quota' => 5,
            'vocab_daily_limit' => 100,
        ], $override);
    }

    public function test_admin_luu_duoc_hai_muc_moi_trong_cai_dat(): void
    {
        $this->actingAs($this->user('admin'))
            ->post(route('admin.settings.update'), $this->settingsPayload())
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame(5, Setting::int('default_full_test_quota', 0));
        $this->assertSame(100, Setting::int('vocab_daily_limit', 0));

        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('name="default_full_test_quota"', false)
            ->assertSee('name="vocab_daily_limit"', false);
    }

    public function test_tai_khoan_moi_nhan_luot_full_test_theo_cai_dat_tai_khoan_cu_giu_nguyen(): void
    {
        $old = $this->user();
        $this->assertSame(3, (int) $old->fresh()->full_test_quota);

        Setting::updateOrCreate(['key' => 'default_full_test_quota'], ['value' => 7]);

        $this->assertSame(7, (int) $this->user()->fresh()->full_test_quota);
        $this->assertSame(3, (int) $old->fresh()->full_test_quota);
    }

    public function test_luot_tra_tu_lay_tu_cai_dat_cong_phan_cap_them(): void
    {
        $service = app(VocabularyLookupService::class);
        $user = $this->user();

        // Chưa lưu cài đặt → dùng .env/config.
        config(['aptis.vocab.daily_limit' => 60]);
        $this->assertSame(60, $service->remainingToday($user));

        Setting::updateOrCreate(['key' => 'vocab_daily_limit'], ['value' => 20]);
        $this->assertSame(20, $service->remainingToday($user));

        $user->forceFill(['vocab_extra_daily' => 15])->save();
        $this->assertSame(35, $service->remainingToday($user->fresh()));
    }

    public function test_admin_cap_them_luot_tra_tu_cho_tung_hoc_vien(): void
    {
        $student = $this->user();

        $this->actingAs($this->user('admin'))
            ->put(route('admin.full-tests.quotas.vocab', $student), ['vocab_extra_daily' => 40])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(40, (int) $student->fresh()->vocab_extra_daily);

        $this->get(route('admin.full-tests.quotas'))->assertOk()
            ->assertSee('Tra từ thêm/ngày');
    }

    public function test_hoc_vien_khong_tu_cap_luot_tra_tu(): void
    {
        $student = $this->user();

        $this->actingAs($student)
            ->put(route('admin.full-tests.quotas.vocab', $student), ['vocab_extra_daily' => 999]);

        $this->assertSame(0, (int) $student->fresh()->vocab_extra_daily);
    }
}
