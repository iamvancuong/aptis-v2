<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tên giảng viên trên trang công khai do MỘT công tắc quyết định
 * (config seo.instructor.show ← env SEO_SHOW_INSTRUCTOR). Mặc định tắt.
 */
class InstructorNameSwitchTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/gioi-thieu', '/luyen-thi-aptis'];

    private function bat(bool $hien): void
    {
        config([
            'seo.instructor.show'    => $hien,
            'seo.instructor.name'    => 'Cô Dung',
            'seo.instructor.display' => $hien ? 'Cô Dung' : 'đội ngũ giảng viên Milaedu',
        ]);
    }

    public function test_mac_dinh_tat_khong_lo_ten_o_dau_ca(): void
    {
        $this->bat(false);
        foreach (self::PAGES as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsStringIgnoringCase('cô dung', $html, "Lộ tên ở $url");
        }
    }

    public function test_bat_cong_tac_thi_hien_lai_ten(): void
    {
        $this->bat(true);
        $this->get('/')->assertOk()->assertSee('Luyện thi Aptis cùng Cô Dung');
        $this->get('/gioi-thieu')->assertOk()->assertSee('"@type":"Person"', false);
    }
}
